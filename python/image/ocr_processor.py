"""
OCR Processor module for image processing and text extraction.
This module handles all OCR operations including image preprocessing and text extraction.
"""

import cv2
import numpy as np
from paddleocr import PaddleOCR
from typing import List, Dict, Any, Optional
import logging
import os

# PDF support (via PyMuPDF) - no external binaries needed
try:
    import fitz  # PyMuPDF
    PYMUPDF_AVAILABLE = True
except Exception:
    PYMUPDF_AVAILABLE = False

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class OCRProcessor:
    """
    OCR Processor for image processing and text extraction.
    
    This class handles:
    - PaddleOCR initialization and configuration
    - Image preprocessing and enhancement
    - PDF to image conversion
    - Text extraction and post-processing
    """
    
    def __init__(self, lang: str = 'en', use_gpu: bool = False):
        """
        Initialize the OCR processor with PaddleOCR.
        
        Args:
            lang: Language for OCR (default: 'en')
            use_gpu: Whether to use GPU acceleration (default: False)
        """
        self.ocr = None
        self.lang = lang
        self.use_gpu = use_gpu
        self._initialize_ocr()
    
    def _initialize_ocr(self) -> None:
        """
        Initialize PaddleOCR with proper configuration and fallbacks.
        """
        try:
            self.ocr = PaddleOCR(
                lang=self.lang, 
                text_detection_model_dir=None,
                text_recognition_model_dir=None,
                textline_orientation_model_dir=None
            )
            logger.info("PaddleOCR initialized successfully!")
        except Exception as e:
            logger.warning(f"Error initializing PaddleOCR: {e}")
            # Try with minimal configuration
            try:
                self.ocr = PaddleOCR(lang=self.lang)
                logger.info("PaddleOCR initialized with minimal config!")
            except Exception as e2:
                logger.warning(f"Error with minimal config: {e2}")
                # Try with offline mode
                try:
                    self.ocr = PaddleOCR(
                        lang=self.lang, 
                        use_angle_cls=False
                    )
                    logger.info("PaddleOCR initialized in offline mode!")
                except Exception as e3:
                    logger.error(f"Error with offline mode: {e3}")
                    self.ocr = None
    
    def is_ocr_available(self) -> bool:
        """
        Check if OCR is available and initialized.
        
        Returns:
            bool: True if OCR is available, False otherwise
        """
        return self.ocr is not None
    
    def is_pdf(self, filename: str) -> bool:
        """
        Check if a file is a PDF.
        
        Args:
            filename: Name of the file to check
            
        Returns:
            bool: True if file is PDF, False otherwise
        """
        return filename.lower().endswith('.pdf')
    
    def load_cv2_images_from_path(self, file_path: str, max_pages: int = 5) -> List[np.ndarray]:
        """
        Load one or more cv2 images from a path. If PDF, convert pages to images.
        
        Args:
            file_path: Path to the image or PDF file
            max_pages: Maximum number of pages to process from PDF
            
        Returns:
            List[np.ndarray]: List of BGR cv2 images
            
        Raises:
            RuntimeError: If file cannot be read or processed
        """
        if self.is_pdf(file_path):
            # Use PyMuPDF exclusively to render PDF pages (no external binaries needed)
            if PYMUPDF_AVAILABLE:
                doc = fitz.open(file_path)
                images_cv2: List[np.ndarray] = []
                try:
                    page_count = min(len(doc), max_pages)
                    for page_index in range(page_count):
                        page = doc.load_page(page_index)
                        # Render at higher zoom for better OCR
                        zoom = 2.0
                        mat = fitz.Matrix(zoom, zoom)
                        pix = page.get_pixmap(matrix=mat, alpha=False)
                        # Convert Pixmap to numpy array accounting for channel count
                        samples = pix.samples  # bytes-like object
                        channels = pix.n  # number of components (1=gray, 3=RGB, 4=RGBA)
                        height, width = pix.height, pix.width
                        img_np = np.frombuffer(samples, dtype=np.uint8)
                        if channels == 4:
                            img_np = img_np.reshape((height, width, 4))
                            img_bgr = cv2.cvtColor(img_np, cv2.COLOR_RGBA2BGR)
                        elif channels == 3:
                            img_np = img_np.reshape((height, width, 3))
                            img_bgr = cv2.cvtColor(img_np, cv2.COLOR_RGB2BGR)
                        elif channels == 1:
                            img_np = img_np.reshape((height, width))
                            img_bgr = cv2.cvtColor(img_np, cv2.COLOR_GRAY2BGR)
                        else:
                            # Fallback: try to infer channels if unexpected
                            try:
                                img_np = img_np.reshape((height, width, channels))
                                img_bgr = cv2.cvtColor(img_np, cv2.COLOR_RGB2BGR)
                            except Exception:
                                raise RuntimeError(f"Unsupported PDF image format with {channels} channels")
                        images_cv2.append(img_bgr)
                finally:
                    doc.close()
                if not images_cv2:
                    raise RuntimeError('Failed to render PDF pages using PyMuPDF')
                return images_cv2
            
            # If PyMuPDF is not available
            raise RuntimeError('PDF support not available: install pymupdf')
        
        # Regular image
        image = cv2.imread(file_path)
        if image is None:
            raise RuntimeError('Could not read image file')
        return [image]
    
    def add_padding(self, image: np.ndarray, top: int = 40, bottom: int = 150, 
                   left: int = 40, right: int = 40, color: tuple = (255, 255, 255)) -> np.ndarray:
        """
        Add padding around the image (default adds more at the bottom).
        
        Args:
            image: Input image
            top: Top padding
            bottom: Bottom padding
            left: Left padding
            right: Right padding
            color: Padding color (default: white)
            
        Returns:
            np.ndarray: Padded image
        """
        padded = cv2.copyMakeBorder(
            image,
            top, bottom, left, right,
            cv2.BORDER_CONSTANT,
            value=color
        )
        return padded
    
    def preprocess_image(self, image: np.ndarray) -> np.ndarray:
        """
        Comprehensive preprocessing pipeline for OCR.
        
        Args:
            image: Input image to preprocess
            
        Returns:
            np.ndarray: Preprocessed image
        """
        try:
            # Step 1: Convert to grayscale
            if len(image.shape) == 3:
                gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
            else:
                gray = image.copy()
            logger.info("Step 1: Converted to grayscale")
            
            # Step 2: Apply CLAHE (Contrast Limited Adaptive Histogram Equalization)
            clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8, 8))
            enhanced = clahe.apply(gray)
            logger.info("Step 2: Applied CLAHE enhancement")
            
            # Step 3: Apply thresholding
            binary = cv2.adaptiveThreshold(
                enhanced, 255,
                cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                cv2.THRESH_BINARY,
                11, 2
            )
            logger.info("Step 3: Applied adaptive thresholding")
            
            # Step 4: Morphological operations
            kernel = np.ones((1, 1), np.uint8)
            cleaned = cv2.morphologyEx(binary, cv2.MORPH_CLOSE, kernel)
            logger.info("Step 4: Applied morphological cleaning")
            
            # Step 5: Dilation for small text
            small_kernel = np.ones((2, 2), np.uint8)
            dilated = cv2.dilate(cleaned, small_kernel, iterations=1)
            logger.info("Step 5: Applied dilation for small text enhancement")
            
            # Step 6: Gaussian blur
            blurred = cv2.GaussianBlur(dilated, (1, 1), 0)
            logger.info("Step 6: Applied Gaussian blur for text smoothing")
            
            # Convert back to BGR
            processed_image = cv2.cvtColor(blurred, cv2.COLOR_GRAY2BGR)
            logger.info("Enhanced preprocessing pipeline completed successfully")
            
            return processed_image
        except Exception as e:
            logger.error(f"Error in preprocessing pipeline: {str(e)}")
            return image
    
    def post_process_ocr_results(self, extracted_texts: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Post-process OCR results to merge split text and improve accuracy.
        
        Args:
            extracted_texts: List of extracted text dictionaries
            
        Returns:
            List[Dict[str, Any]]: Post-processed text results
        """
        if not extracted_texts:
            return extracted_texts
        
        merged_texts = []
        i = 0
        while i < len(extracted_texts):
            current_text = extracted_texts[i]['text'].strip()
            if i < len(extracted_texts) - 1:
                next_text = extracted_texts[i + 1]['text'].strip()
                split_patterns = [
                    (current_text.lower() == "general average" and next_text.lower() == "for the semester"),
                    (current_text.lower() == "1st semester" and next_text.lower() == "final grade"),
                    (current_text.lower() == "school year" and next_text.lower() == "2024 - 2025"),
                ]
                if any(split_patterns):
                    merged_text = f"{current_text} {next_text}"
                    merged_confidence = max(extracted_texts[i]['confidence'],
                                            extracted_texts[i + 1]['confidence'])
                    merged_texts.append({
                        'text': merged_text,
                        'confidence': merged_confidence
                    })
                    i += 2
                    continue
            merged_texts.append(extracted_texts[i])
            i += 1
        
        return merged_texts
    
    def extract_text_from_image(self, image_path: str, max_pages: int = 5) -> Dict[str, Any]:
        """
        Extract text from image or PDF using PaddleOCR.
        
        Args:
            image_path: Path to the image or PDF file
            max_pages: Maximum number of pages to process from PDF
            
        Returns:
            Dict[str, Any]: Dictionary containing extracted text and metadata
        """
        try:
            if not self.is_ocr_available():
                return {"error": "OCR model not initialized. Please check your network connection and try again."}
            
            cv2_images = self.load_cv2_images_from_path(image_path, max_pages)
            aggregated_texts = []
            
            for page_index, image in enumerate(cv2_images, start=1):
                logger.info(f"Processing page {page_index} from: {image_path}")
                # Add padding to avoid cutting off text at the edges
                padded_image = self.add_padding(image)
                
                # Apply preprocessing after padding
                processed_image = self.preprocess_image(padded_image)
                
                # OCR on padded image
                logger.info("Trying OCR on padded image...")
                result = self.ocr.ocr(padded_image)
                
                # If no text found, try with processed image
                if not result or not result[0] or len(result[0]) == 0:
                    logger.info("No text found in padded image, trying preprocessed image...")
                    result = self.ocr.ocr(processed_image)
                
                if result and len(result) > 0:
                    if isinstance(result[0], dict):  # New format
                        if 'rec_texts' in result[0] and 'rec_scores' in result[0]:
                            texts = result[0]['rec_texts']
                            scores = result[0]['rec_scores']
                            for i, (text, score) in enumerate(zip(texts, scores)):
                                aggregated_texts.append({
                                    'text': text,
                                    'confidence': round(score * 100, 2)
                                })
                    elif isinstance(result[0], list):  # Old format
                        for line in result[0]:
                            if line and len(line) >= 2 and len(line[1]) >= 2:
                                text = line[1][0]
                                confidence = line[1][1]
                                aggregated_texts.append({
                                    'text': text,
                                    'confidence': round(confidence * 100, 2)
                                })
            
            processed_texts = self.post_process_ocr_results(aggregated_texts)
            
            return {
                "success": True,
                "texts": processed_texts,
                "total_texts": len(processed_texts)
            }
        except Exception as e:
            logger.error(f"Error in extract_text_from_image: {str(e)}")
            return {"error": f"Error processing image: {str(e)}"}
    
    def get_ocr_info(self) -> Dict[str, Any]:
        """
        Get information about the OCR processor.
        
        Returns:
            Dict[str, Any]: OCR processor information
        """
        return {
            "ocr_available": self.is_ocr_available(),
            "language": self.lang,
            "use_gpu": self.use_gpu,
            "pymupdf_available": PYMUPDF_AVAILABLE
        }
