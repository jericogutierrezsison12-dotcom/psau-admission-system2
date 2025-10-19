"""
Flask Application module for PSAU OCR Service.
This module contains the main Flask application with OOP structure and dependency injection.
"""

from flask import Flask, request, render_template, jsonify
from flask_cors import CORS
import os
from werkzeug.utils import secure_filename
import base64
import io
from PIL import Image
import cv2
import numpy as np
from typing import List, Dict, Any
import logging

from ml_classifier import MLClassifier
from ocr_processor import OCRProcessor

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class PSAUOCRService:
    """
    Main PSAU OCR Service class that handles all Flask routes and business logic.
    
    This class uses dependency injection for MLClassifier and OCRProcessor
    to maintain separation of concerns and testability.
    """
    
    def __init__(self, ml_classifier: MLClassifier = None, ocr_processor: OCRProcessor = None):
        """
        Initialize the PSAU OCR Service.
        
        Args:
            ml_classifier: MLClassifier instance (optional, will create default if None)
            ocr_processor: OCRProcessor instance (optional, will create default if None)
        """
        self.app = Flask(__name__)
        self.app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size
        
        # Enable CORS for PHP integration
        CORS(self.app)
        
        # Initialize components with dependency injection
        self.ml_classifier = ml_classifier or MLClassifier()
        self.ocr_processor = ocr_processor or OCRProcessor()
        
        # Create uploads directory if it doesn't exist
        self.upload_folder = 'uploads'
        if not os.path.exists(self.upload_folder):
            os.makedirs(self.upload_folder)
        self.app.config['UPLOAD_FOLDER'] = self.upload_folder
        
        self.allowed_extensions = {'png', 'jpg', 'jpeg', 'gif', 'bmp', 'tiff', 'pdf'}
        
        # Register routes
        self._register_routes()
    
    def _register_routes(self) -> None:
        """Register all Flask routes."""
        self.app.route('/')(self.index)
        self.app.route('/ocr_service', methods=['POST'])(self.ocr_service)
        self.app.route('/submit_application', methods=['POST'])(self.submit_application)
        self.app.route('/ocr-demo')(self.ocr_demo)
        self.app.route('/index.html')(self.index_html)
        self.app.route('/upload', methods=['POST'])(self.upload_file)
        self.app.route('/process_base64', methods=['POST'])(self.process_base64)
    
    def allowed_file(self, filename: str) -> bool:
        """
        Check if file extension is allowed.
        
        Args:
            filename: Name of the file to check
            
        Returns:
            bool: True if file extension is allowed, False otherwise
        """
        return '.' in filename and filename.rsplit('.', 1)[1].lower() in self.allowed_extensions
    
    def index(self) -> Dict[str, Any]:
        """Root endpoint that returns service information."""
        return jsonify({
            "message": "PSAU OCR Service is running",
            "status": "active",
            "endpoints": {
                "ocr_service": "/ocr_service",
                "submit_application": "/submit_application",
                "upload": "/upload",
                "process_base64": "/process_base64"
            },
            "ml_model_available": self.ml_classifier.is_model_available(),
            "ocr_available": self.ocr_processor.is_ocr_available()
        })
    
    def ocr_service(self) -> Dict[str, Any]:
        """OCR service endpoint for PHP integration."""
        try:
            # Check if file was uploaded
            if 'file' not in request.files:
                return jsonify({"success": False, "error": "No file uploaded"})
            
            file = request.files['file']
            if file.filename == '':
                return jsonify({"success": False, "error": "No file selected"})
            
            if not self.allowed_file(file.filename):
                return jsonify({"success": False, "error": "Invalid file type"})
            
            # Save uploaded file temporarily
            filename = secure_filename(file.filename)
            filepath = os.path.join(self.app.config['UPLOAD_FOLDER'], filename)
            file.save(filepath)
            
            # Process the document with OCR
            result = self._process_document_with_classification(filepath)
            
            # Clean up uploaded file
            if os.path.exists(filepath):
                os.remove(filepath)
            
            if 'error' in result:
                return jsonify({"success": False, "error": result['error']})
            
            # Return result in format expected by PHP
            response_data = {
                "success": True,
                "prediction": result.get('prediction', 'Unknown'),
                "status_info": result.get('status_info'),
                "texts": result.get('texts', []),
                "total_texts": result.get('total_texts', 0),
                "is_report_card": result.get('prediction') == 'Report Card',
                "has_failed_remarks": result.get('status_info', {}).get('status') == 'failed' if result.get('status_info') else False
            }
            
            return jsonify(response_data)
            
        except Exception as e:
            logger.error(f"Error in OCR service: {str(e)}")
            return jsonify({"success": False, "error": f"Error processing document: {str(e)}"})
    
    def submit_application(self) -> Dict[str, Any]:
        """Handle application form submission with OCR processing."""
        try:
            # Get form data
            previous_school = request.form.get('previous_school', '')
            school_year = request.form.get('school_year', '')
            strand = request.form.get('strand', '')
            gpa = request.form.get('gpa', '')
            address = request.form.get('address', '')
            age = request.form.get('age', '')
            
            # Get uploaded files
            image_2x2 = request.files.get('image_2x2')
            pdf_file = request.files.get('pdf_file')
            
            # Validate required fields
            if not all([previous_school, school_year, strand, gpa, address, age]):
                return jsonify({"error": "All required fields must be filled"})
            
            if not pdf_file or pdf_file.filename == '':
                return jsonify({"error": "Please upload a document file"})
            
            if not image_2x2 or image_2x2.filename == '':
                return jsonify({"error": "Please upload a 2x2 ID picture"})
            
            # Validate file types
            if not self.allowed_file(pdf_file.filename):
                return jsonify({"error": "Document file must be an image or PDF format"})
            
            if not self.allowed_file(image_2x2.filename):
                return jsonify({"error": "ID picture must be an image format"})
            
            # Save uploaded files temporarily
            pdf_filename = secure_filename(pdf_file.filename)
            image_filename = secure_filename(image_2x2.filename)
            
            pdf_path = os.path.join(self.app.config['UPLOAD_FOLDER'], pdf_filename)
            image_path = os.path.join(self.app.config['UPLOAD_FOLDER'], image_filename)
            
            pdf_file.save(pdf_path)
            image_2x2.save(image_path)
            
            # Process the document with OCR
            result = self._process_document_with_classification(pdf_path)
            
            # Clean up uploaded files
            if os.path.exists(pdf_path):
                os.remove(pdf_path)
            if os.path.exists(image_path):
                os.remove(image_path)
            
            if 'error' in result:
                return jsonify({"error": result['error']})
            
            # Prepare response with form data and OCR results
            response_data = {
                "success": True,
                "form_data": {
                    "previous_school": previous_school,
                    "school_year": school_year,
                    "strand": strand,
                    "gpa": gpa,
                    "address": address,
                    "age": age,
                    "document_filename": pdf_filename,
                    "image_filename": image_filename
                },
                "prediction": result.get('prediction', 'Unknown'),
                "status_info": result.get('status_info'),
                "texts": result.get('texts', []),
                "total_texts": result.get('total_texts', 0)
            }
            
            return jsonify(response_data)
            
        except Exception as e:
            logger.error(f"Error in submit_application: {str(e)}")
            return jsonify({"error": f"Error processing application: {str(e)}"})
    
    def ocr_demo(self) -> str:
        """Original OCR demo page."""
        return render_template('index.html')
    
    def index_html(self) -> str:
        """Direct access to index.html."""
        return render_template('index.html')
    
    def upload_file(self) -> Dict[str, Any]:
        """Handle multiple file uploads with merged classification."""
        if 'files' not in request.files:
            return jsonify({"error": "No files uploaded"})
        
        files = request.files.getlist('files')
        if not files or all(file.filename == '' for file in files):
            return jsonify({"error": "No files selected"})
        
        results = []
        processed_files = []
        all_extracted_texts = []  # Store all texts from all images
        
        for file in files:
            if file and self.allowed_file(file.filename):
                filename = secure_filename(file.filename)
                filepath = os.path.join(self.app.config['UPLOAD_FOLDER'], filename)
                file.save(filepath)
                processed_files.append(filepath)
                
                # Extract text from individual image (without classification)
                result = self.ocr_processor.extract_text_from_image(filepath)
                result['filename'] = filename
                results.append(result)
                
                # Collect texts for merged classification
                if result.get('success') and result.get('texts'):
                    all_extracted_texts.extend(result['texts'])
            else:
                results.append({
                    "error": f"Invalid file type for {file.filename}. Please upload image or PDF files only.",
                    "filename": file.filename
                })
        
        # Clean up uploaded files
        for filepath in processed_files:
            if os.path.exists(filepath):
                os.remove(filepath)
        
        # Classify merged text from all images
        merged_prediction = self.ml_classifier.classify_text(all_extracted_texts)
        
        # If merged prediction is a report card, verify pass/fail status
        merged_status_info = None
        if merged_prediction == "Report Card":
            merged_status_info = self.ml_classifier.verify_report_card_status(all_extracted_texts)
        
        return jsonify({
            "success": True,
            "results": results,
            "total_files": len(results),
            "merged_prediction": merged_prediction,
            "merged_status_info": merged_status_info,
            "total_merged_texts": len(all_extracted_texts)
        })
    
    def process_base64(self) -> Dict[str, Any]:
        """Process base64 encoded image data."""
        try:
            if not self.ocr_processor.is_ocr_available():
                return jsonify({"error": "OCR model not initialized. Please check your network connection and try again."})
            
            data = request.get_json()
            if 'image' not in data:
                return jsonify({"error": "No image data provided"})
            
            image_data = data['image'].split(',')[1]
            image_bytes = base64.b64decode(image_data)
            
            image = Image.open(io.BytesIO(image_bytes))
            image_cv = cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)
            logger.info(f"Processing base64 image")
            logger.info(f"Image shape: {image_cv.shape}")
            
            # Add padding to avoid cutting off text
            padded_image = self.ocr_processor.add_padding(image_cv)
            
            # Apply preprocessing after padding
            processed_image = self.ocr_processor.preprocess_image(padded_image)
            
            logger.info("Trying OCR on padded image...")
            result = self.ocr_processor.ocr.ocr(padded_image)
            
            if not result or not result[0] or len(result[0]) == 0:
                logger.info("No text found in padded image, trying preprocessed image...")
                result = self.ocr_processor.ocr.ocr(processed_image)
            
            extracted_texts = []
            if result and len(result) > 0:
                if isinstance(result[0], dict):  # New format
                    if 'rec_texts' in result[0] and 'rec_scores' in result[0]:
                        texts = result[0]['rec_texts']
                        scores = result[0]['rec_scores']
                        for i, (text, score) in enumerate(zip(texts, scores)):
                            extracted_texts.append({
                                'text': text,
                                'confidence': round(score * 100, 2)
                            })
                elif isinstance(result[0], list):  # Old format
                    for line in result[0]:
                        if line and len(line) >= 2 and len(line[1]) >= 2:
                            text = line[1][0]
                            confidence = line[1][1]
                            extracted_texts.append({
                                'text': text,
                                'confidence': round(confidence * 100, 2)
                            })
            
            processed_texts = self.ocr_processor.post_process_ocr_results(extracted_texts)
            
            # Classify the extracted text
            prediction = self.ml_classifier.classify_text(processed_texts)
            
            # If it's a report card, verify pass/fail status
            status_info = None
            if prediction == "Report Card":
                status_info = self.ml_classifier.verify_report_card_status(processed_texts)
            
            return jsonify({
                "success": True,
                "texts": processed_texts,
                "total_texts": len(processed_texts),
                "prediction": prediction,
                "status_info": status_info
            })
        except Exception as e:
            logger.error(f"Error processing base64 image: {str(e)}")
            return jsonify({"error": f"Error processing image: {str(e)}"})
    
    def _process_document_with_classification(self, filepath: str) -> Dict[str, Any]:
        """
        Process document with OCR and classification.
        
        Args:
            filepath: Path to the document file
            
        Returns:
            Dict[str, Any]: Processing results with classification
        """
        try:
            # Extract text using OCR processor
            result = self.ocr_processor.extract_text_from_image(filepath)
            
            if 'error' in result:
                return result
            
            # Classify the extracted text
            prediction = self.ml_classifier.classify_text(result.get('texts', []))
            
            # If it's a report card, verify pass/fail status
            status_info = None
            if prediction == "Report Card":
                status_info = self.ml_classifier.verify_report_card_status(result.get('texts', []))
            
            # Add classification results to the OCR result
            result.update({
                "prediction": prediction,
                "status_info": status_info
            })
            
            return result
            
        except Exception as e:
            logger.error(f"Error in document processing: {str(e)}")
            return {"error": f"Error processing document: {str(e)}"}
    
    def get_service_info(self) -> Dict[str, Any]:
        """
        Get comprehensive service information.
        
        Returns:
            Dict[str, Any]: Service information including component status
        """
        return {
            "service_name": "PSAU OCR Service",
            "ml_classifier": self.ml_classifier.get_model_info(),
            "ocr_processor": self.ocr_processor.get_ocr_info(),
            "upload_folder": self.upload_folder,
            "allowed_extensions": list(self.allowed_extensions),
            "max_file_size": self.app.config['MAX_CONTENT_LENGTH']
        }
    
    def run(self, debug: bool = True, host: str = '0.0.0.0', port: int = 5000) -> None:
        """
        Run the Flask application.
        
        Args:
            debug: Enable debug mode
            host: Host to bind to
            port: Port to bind to
        """
        logger.info("Starting PSAU OCR Service...")
        logger.info(f"ML Model Available: {self.ml_classifier.is_model_available()}")
        logger.info(f"OCR Available: {self.ocr_processor.is_ocr_available()}")
        self.app.run(debug=debug, host=host, port=port)


def create_app(ml_classifier: MLClassifier = None, ocr_processor: OCRProcessor = None) -> PSAUOCRService:
    """
    Factory function to create PSAU OCR Service instance.
    
    Args:
        ml_classifier: Optional MLClassifier instance
        ocr_processor: Optional OCRProcessor instance
        
    Returns:
        PSAUOCRService: Configured service instance
    """
    return PSAUOCRService(ml_classifier, ocr_processor)


if __name__ == '__main__':
    # Create and run the service
    service = create_app()
    service.run()
