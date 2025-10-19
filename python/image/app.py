"""
PSAU Combined Service - Main Application Entry Point
This file combines both the PSAU Admission System (AI Chatbot & Recommender) and OCR Service functionality.
"""

from flask import Flask, render_template, request, redirect, url_for, flash, jsonify
from flask_cors import CORS
import pandas as pd
import os
import mysql.connector
from mysql.connector import Error
import logging
from werkzeug.utils import secure_filename
import base64
import io
from PIL import Image
import cv2
import numpy as np
from typing import List, Dict, Any

# Import AI components
from database_recommender import DatabaseCourseRecommender
from ai_chatbot import AIChatbot

# Import OCR components
from ml_classifier import MLClassifier
from ocr_processor import OCRProcessor

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
# CORS configuration for distributed deployment
allowed_origins = [
    "http://localhost",
    "http://127.0.0.1",
    "https://your-infinityfree-domain.infinityfreeapp.com",  # Replace with your actual domain
    "https://your-render-app.onrender.com"  # Replace with your actual Render domain
]

# Add environment-based origins
if os.getenv('ALLOWED_ORIGINS'):
    allowed_origins.extend(os.getenv('ALLOWED_ORIGINS').split(','))

CORS(app, resources={r"/*": {"origins": allowed_origins, "supports_credentials": True}})
app.secret_key = 'your-secret-key'  # Required for flash messages
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size

# Database configuration - Environment-based for Replit deployment
db_config = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASS', ''),
    'database': os.getenv('DB_NAME', 'psau_admission')
}

# For Replit deployment, use environment variables
if os.getenv('REPLIT_DB_URL'):
    # Replit provides database URL in format: mysql://user:pass@host:port/db
    import re
    db_url = os.getenv('REPLIT_DB_URL')
    match = re.match(r'mysql://([^:]+):([^@]+)@([^:]+):(\d+)/(.+)', db_url)
    if match:
        db_config = {
            'host': match.group(3),
            'user': match.group(1),
            'password': match.group(2),
            'database': match.group(5),
            'port': int(match.group(4))
        }

# Initialize AI components
try:
    recommender = DatabaseCourseRecommender()
    print("✅ Recommender initialized successfully")
except Exception as e:
    print(f"⚠️  Warning: Could not initialize recommender: {e}")
    recommender = None

try:
    chatbot = AIChatbot(db_config)
    print("✅ Chatbot initialized successfully")
except Exception as e:
    print(f"⚠️  Warning: Could not initialize chatbot: {e}")
    chatbot = None

# Initialize OCR components
try:
    ml_classifier = MLClassifier()
    ocr_processor = OCRProcessor()
    print("✅ OCR components initialized successfully")
except Exception as e:
    print(f"⚠️  Warning: Could not initialize OCR components: {e}")
    ml_classifier = None
    ocr_processor = None

# Create uploads directory for OCR
upload_folder = 'uploads'
if not os.path.exists(upload_folder):
    os.makedirs(upload_folder)
app.config['UPLOAD_FOLDER'] = upload_folder

allowed_extensions = {'png', 'jpg', 'jpeg', 'gif', 'bmp', 'tiff', 'pdf'}

def get_db_connection():
    try:
        connection = mysql.connector.connect(**db_config)
        return connection
    except Error as e:
        print(f"❌ Error connecting to database: {e}")
        return None

def get_faqs_from_db():
    """Fetch FAQs from database with proper error handling"""
    connection = get_db_connection()
    faqs = []
    
    if connection:
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute("SELECT id, question, answer FROM faqs WHERE is_active = 1 ORDER BY sort_order, id")
            faqs = cursor.fetchall()
            cursor.close()
            print(f"✅ Successfully fetched {len(faqs)} FAQs from database")
        except Error as e:
            print(f"❌ Error fetching FAQs: {e}")
        finally:
            connection.close()
    else:
        print("❌ Could not connect to database")
    
    return faqs

def allowed_file(filename):
    """Check if file extension is allowed"""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in allowed_extensions

def process_document_with_classification(filepath):
    """Process document with OCR and classification"""
    try:
        if not ocr_processor or not ml_classifier:
            return {"error": "OCR service not available"}
        
        # Extract text using OCR processor
        result = ocr_processor.extract_text_from_image(filepath)
        
        if 'error' in result:
            return result
        
        # Classify the extracted text
        prediction = ml_classifier.classify_text(result.get('texts', []))
        
        # If it's a report card, verify pass/fail status
        status_info = None
        if prediction == "Report Card":
            status_info = ml_classifier.verify_report_card_status(result.get('texts', []))
        
        # Add classification results to the OCR result
        result.update({
            "prediction": prediction,
            "status_info": status_info
        })
        
        return result
        
    except Exception as e:
        logger.error(f"Error in document processing: {str(e)}")
        return {"error": f"Error processing document: {str(e)}"}

# =============================================================================
# HEALTH AND STATUS ENDPOINTS
# =============================================================================

@app.route('/health', methods=['GET'])
def health():
    """Basic health endpoint to verify the Flask app is running"""
    return jsonify({
        'status': 'ok',
        'service': 'psau-combined',
        'endpoints': {
            'ai': ['/faqs', '/ask_question', '/api/recommend', '/api/save_ratings'],
            'ocr': ['/ocr_service', '/submit_application', '/upload', '/process_base64']
        },
        'components': {
            'recommender': recommender is not None,
            'chatbot': chatbot is not None,
            'ml_classifier': ml_classifier is not None and ml_classifier.is_model_available(),
            'ocr_processor': ocr_processor is not None and ocr_processor.is_ocr_available()
        }
    })

@app.route('/db_check', methods=['GET'])
def db_check():
    """Check database connectivity and basic table counts"""
    try:
        connection = get_db_connection()
        if not connection:
            return jsonify({'ok': False, 'error': 'cannot connect to database'}), 500
        cursor = connection.cursor()
        cursor.execute('SELECT COUNT(*) FROM faqs')
        faqs_count = cursor.fetchone()[0]
        cursor.execute('SELECT COUNT(*) FROM courses')
        courses_count = cursor.fetchone()[0]
        cursor.close()
        connection.close()
        return jsonify({'ok': True, 'faqs': faqs_count, 'courses': courses_count})
    except Exception as e:
        return jsonify({'ok': False, 'error': str(e)}), 500

# =============================================================================
# AI ADMISSION SYSTEM ROUTES
# =============================================================================

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/recommender')
def recommender_page():
    return render_template('recommender.html')

@app.route('/chatbot')
def chatbot_page():
    # Fetch FAQs from database
    faqs = get_faqs_from_db()
    return render_template('chatbot.html', faqs=faqs)

@app.route('/faqs', methods=['GET'])
def faqs_api():
    faqs = get_faqs_from_db()
    return jsonify({
        'faqs': faqs
    })

@app.route('/ask_question', methods=['POST'])
def ask_question():
    if chatbot is None:
        return jsonify({
            'answer': 'Sorry, the AI chatbot is not available at the moment. Please try again later.',
            'confidence': 0.0,
            'suggested_questions': []
        })
    
    data = request.get_json()
    question = data.get('question', '')
    
    if not question.strip():
        return jsonify({
            'answer': 'Please enter a question.',
            'confidence': 0.0,
            'suggested_questions': []
        })
    
    # Get answer using AI chatbot
    answer, confidence = chatbot.find_best_match(question)
    
    # Get suggested questions
    suggested_questions = chatbot.get_suggested_questions(question)
    
    return jsonify({
        'answer': answer,
        'confidence': float(confidence),
        'suggested_questions': suggested_questions
    })

@app.route('/recommend', methods=['POST'])
def recommend():
    if recommender is None:
        flash('The recommendation system is not available at the moment. Please try again later.', 'error')
        return redirect(url_for('recommender_page'))
    
    try:
        # Get form data
        stanine = int(request.form['stanine'])
        gwa = float(request.form['gwa'])
        strand = request.form['strand']
        hobbies = request.form.get('hobbies', '').strip()
        
        # Validate inputs
        if not (1 <= stanine <= 9):
            flash('Stanine score must be between 1 and 9', 'error')
            return redirect(url_for('recommender_page'))
        
        if not (75 <= gwa <= 100):
            flash('GWA must be between 75 and 100', 'error')
            return redirect(url_for('recommender_page'))
        
        if not strand:
            flash('Please select a strand', 'error')
            return redirect(url_for('recommender_page'))
        
        if not hobbies:
            flash('Please enter your hobbies/interests. This field is required for better recommendations.', 'error')
            return redirect(url_for('recommender_page'))
        
        # Get recommendations
        recommendations = recommender.recommend_courses(
            stanine=stanine,
            gwa=gwa,
            strand=strand,
            hobbies=hobbies
        )
        
        return render_template('recommendations.html',
                             recommendations=recommendations,
                             stanine=stanine,
                             gwa=gwa,
                             strand=strand,
                             hobbies=hobbies)
    
    except Exception as e:
        flash(f'An error occurred: {str(e)}', 'error')
        return redirect(url_for('recommender_page'))

@app.route('/api/recommend', methods=['POST'])
def api_recommend():
    if recommender is None:
        return jsonify({'error': 'Recommender unavailable'}), 503
    try:
        data = request.get_json(force=True) or {}
        stanine = int(data.get('stanine'))
        gwa = float(data.get('gwa'))
        strand = str(data.get('strand') or '').strip()
        hobbies = str(data.get('hobbies') or '').strip()
        if not (1 <= stanine <= 9):
            return jsonify({'error': 'stanine must be between 1 and 9'}), 400
        if not (75 <= gwa <= 100):
            return jsonify({'error': 'gwa must be between 75 and 100'}), 400
        if not strand:
            return jsonify({'error': 'strand is required'}), 400
        if not hobbies:
            return jsonify({'error': 'hobbies is required'}), 400
        recs = recommender.recommend_courses(stanine=stanine, gwa=gwa, strand=strand, hobbies=hobbies)
        return jsonify({'recommendations': recs})
    except Exception as e:
        return jsonify({'error': str(e)}), 400

@app.route('/api/save_ratings', methods=['POST'])
def api_save_ratings():
    if recommender is None:
        return jsonify({'error': 'Recommender unavailable'}), 503
    try:
        data = request.get_json(force=True) or {}
        stanine = int(data.get('stanine'))
        gwa = float(data.get('gwa'))
        strand = str(data.get('strand') or '').strip()
        hobbies = str(data.get('hobbies') or '').strip()
        ratings = data.get('ratings') or {}
        if not isinstance(ratings, dict):
            return jsonify({'error': 'ratings must be an object'}), 400
        saved = 0
        for course, rating in ratings.items():
            ok = recommender.save_student_data(stanine=stanine, gwa=gwa, strand=strand, course=course, rating=rating, hobbies=hobbies)
            if ok:
                saved += 1
        # retrain after feedback
        try:
            recommender.train_model()
        except Exception:
            pass
        return jsonify({'saved': saved})
    except Exception as e:
        return jsonify({'error': str(e)}), 400

@app.route('/save_ratings', methods=['POST'])
def save_ratings():
    if recommender is None:
        flash('The recommendation system is not available at the moment. Please try again later.', 'error')
        return redirect(url_for('recommender_page'))
    
    try:
        # Get form data
        stanine = int(request.form['stanine'])
        gwa = float(request.form['gwa'])
        strand = request.form['strand']
        hobbies = request.form.get('hobbies', '').strip()
        
        # Get ratings
        ratings = {}
        for key, value in request.form.items():
            if key.startswith('rating_'):
                course = key.replace('rating_', '')
                if value != 'skip':  # Only save non-skip ratings
                    ratings[course] = value
        
        if ratings:  # Only save if there are actual ratings
            # Save each rating to database
            for course, rating in ratings.items():
                success = recommender.save_student_data(
                    stanine=stanine,
                    gwa=gwa,
                    strand=strand,
                    course=course,
                    rating=rating,
                    hobbies=hobbies if hobbies else None
                )
                if not success:
                    flash(f'Error saving rating for {course}', 'error')
                    continue
            
            # Retrain the model with new data
            recommender.train_model()
            
            flash('Thank you for your feedback! Your ratings have been saved.', 'success')
        else:
            flash('No feedback was provided. You can try again later.', 'info')
            
        return redirect(url_for('recommender_page'))
    
    except Exception as e:
        flash(f'An error occurred while saving ratings: {str(e)}', 'error')
        return redirect(url_for('recommender_page'))

# =============================================================================
# OCR SERVICE ROUTES
# =============================================================================

@app.route('/ocr_service', methods=['POST'])
def ocr_service():
    """OCR service endpoint for PHP integration"""
    try:
        if not ocr_processor or not ml_classifier:
            return jsonify({"success": False, "error": "OCR service not available"})
        
        # Check if file was uploaded
        if 'file' not in request.files:
            return jsonify({"success": False, "error": "No file uploaded"})
        
        file = request.files['file']
        if file.filename == '':
            return jsonify({"success": False, "error": "No file selected"})
        
        if not allowed_file(file.filename):
            return jsonify({"success": False, "error": "Invalid file type"})
        
        # Save uploaded file temporarily
        filename = secure_filename(file.filename)
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        file.save(filepath)
        
        # Process the document with OCR
        result = process_document_with_classification(filepath)
        
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

@app.route('/submit_application', methods=['POST'])
def submit_application():
    """Handle application form submission with OCR processing"""
    try:
        if not ocr_processor or not ml_classifier:
            return jsonify({"error": "OCR service not available"})
        
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
        if not allowed_file(pdf_file.filename):
            return jsonify({"error": "Document file must be an image or PDF format"})
        
        if not allowed_file(image_2x2.filename):
            return jsonify({"error": "ID picture must be an image format"})
        
        # Save uploaded files temporarily
        pdf_filename = secure_filename(pdf_file.filename)
        image_filename = secure_filename(image_2x2.filename)
        
        pdf_path = os.path.join(app.config['UPLOAD_FOLDER'], pdf_filename)
        image_path = os.path.join(app.config['UPLOAD_FOLDER'], image_filename)
        
        pdf_file.save(pdf_path)
        image_2x2.save(image_path)
        
        # Process the document with OCR
        result = process_document_with_classification(pdf_path)
        
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

@app.route('/upload', methods=['POST'])
def upload_file():
    """Handle multiple file uploads with merged classification"""
    if not ocr_processor or not ml_classifier:
        return jsonify({"error": "OCR service not available"})
    
    if 'files' not in request.files:
        return jsonify({"error": "No files uploaded"})
    
    files = request.files.getlist('files')
    if not files or all(file.filename == '' for file in files):
        return jsonify({"error": "No files selected"})
    
    results = []
    processed_files = []
    all_extracted_texts = []  # Store all texts from all images
    
    for file in files:
        if file and allowed_file(file.filename):
            filename = secure_filename(file.filename)
            filepath = os.path.join(app.config['UPLOAD_FOLDER'], filename)
            file.save(filepath)
            processed_files.append(filepath)
            
            # Extract text from individual image (without classification)
            result = ocr_processor.extract_text_from_image(filepath)
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
    merged_prediction = ml_classifier.classify_text(all_extracted_texts)
    
    # If merged prediction is a report card, verify pass/fail status
    merged_status_info = None
    if merged_prediction == "Report Card":
        merged_status_info = ml_classifier.verify_report_card_status(all_extracted_texts)
    
    return jsonify({
        "success": True,
        "results": results,
        "total_files": len(results),
        "merged_prediction": merged_prediction,
        "merged_status_info": merged_status_info,
        "total_merged_texts": len(all_extracted_texts)
    })

@app.route('/process_base64', methods=['POST'])
def process_base64():
    """Process base64 encoded image data"""
    try:
        if not ocr_processor or not ml_classifier:
            return jsonify({"error": "OCR service not available"})
        
        if not ocr_processor.is_ocr_available():
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
        padded_image = ocr_processor.add_padding(image_cv)
        
        # Apply preprocessing after padding
        processed_image = ocr_processor.preprocess_image(padded_image)
        
        logger.info("Trying OCR on padded image...")
        result = ocr_processor.ocr.ocr(padded_image)
        
        if not result or not result[0] or len(result[0]) == 0:
            logger.info("No text found in padded image, trying preprocessed image...")
            result = ocr_processor.ocr.ocr(processed_image)
        
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
        
        processed_texts = ocr_processor.post_process_ocr_results(extracted_texts)
        
        # Classify the extracted text
        prediction = ml_classifier.classify_text(processed_texts)
        
        # If it's a report card, verify pass/fail status
        status_info = None
        if prediction == "Report Card":
            status_info = ml_classifier.verify_report_card_status(processed_texts)
        
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

@app.route('/ocr-demo')
def ocr_demo():
    """Original OCR demo page"""
    return render_template('index.html')

@app.route('/index.html')
def index_html():
    """Direct access to index.html"""
    return render_template('index.html')

# =============================================================================
# MAIN APPLICATION ENTRY POINT
# =============================================================================

if __name__ == '__main__':
    print("🚀 Starting PSAU Combined Service...")
    print("📊 Database status:")
    
    # Test database connection
    faqs = get_faqs_from_db()
    if faqs:
        print(f"   ✅ FAQs loaded: {len(faqs)} questions available")
    else:
        print("   ⚠️  No FAQs loaded - chatbot will have limited functionality")
    
    print("🤖 AI Components:")
    print(f"   • Recommender: {'✅ Available' if recommender else '❌ Unavailable'}")
    print(f"   • Chatbot: {'✅ Available' if chatbot else '❌ Unavailable'}")
    
    print("🔍 OCR Components:")
    print(f"   • ML Classifier: {'✅ Available' if ml_classifier and ml_classifier.is_model_available() else '❌ Unavailable'}")
    print(f"   • OCR Processor: {'✅ Available' if ocr_processor and ocr_processor.is_ocr_available() else '❌ Unavailable'}")
    
    # Get port from environment (Replit uses dynamic ports)
    port = int(os.getenv('PORT', 5000))
    
    print("🌐 Access the application at: http://localhost:" + str(port))
    print("📋 Available services:")
    print("   • AI Admission System (recommendations, chatbot)")
    print("   • OCR Service (document processing, classification)")
    
    app.run(debug=False, host='0.0.0.0', port=port)