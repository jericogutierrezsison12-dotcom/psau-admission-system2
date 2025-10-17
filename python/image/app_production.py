"""
PSAU Production Flask App for Render Deployment
Optimized for cloud deployment with environment variables
"""

import os
from flask import Flask, request, jsonify
from flask_cors import CORS
import logging
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Configure CORS for production
CORS(app, resources={
    r"/*": {
        "origins": [
            "https://psau-admission-system.web.app",
            "https://psau-admission-system.firebaseapp.com",
            "http://localhost:3000",
            "http://127.0.0.1:3000"
        ],
        "supports_credentials": True
    }
})

app.secret_key = os.getenv('SECRET_KEY', 'your-secret-key')
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size

# Database configuration from environment variables
db_config = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'psau_admission'),
    'port': int(os.getenv('DB_PORT', '3306'))
}

# Initialize AI components (with error handling)
try:
    from database_recommender import DatabaseCourseRecommender
    from ai_chatbot import AIChatbot
    recommender = DatabaseCourseRecommender()
    chatbot = AIChatbot()
    logger.info("✅ AI components initialized successfully")
except Exception as e:
    logger.error(f"❌ Error initializing AI components: {e}")
    recommender = None
    chatbot = None

# Initialize OCR components (with error handling)
try:
    from ml_classifier import MLClassifier
    from ocr_processor import OCRProcessor
    ml_classifier = MLClassifier()
    ocr_processor = OCRProcessor()
    logger.info("✅ OCR components initialized successfully")
except Exception as e:
    logger.error(f"❌ Error initializing OCR components: {e}")
    ml_classifier = None
    ocr_processor = None

@app.route('/')
def home():
    """Health check endpoint"""
    return jsonify({
        'status': 'success',
        'message': 'PSAU AI-Assisted Admission System API',
        'version': '1.0.0',
        'services': {
            'chatbot': chatbot is not None,
            'recommender': recommender is not None,
            'ocr': ml_classifier is not None and ocr_processor is not None
        }
    })

@app.route('/health')
def health():
    """Health check endpoint for monitoring"""
    return jsonify({
        'status': 'healthy',
        'timestamp': str(pd.Timestamp.now()),
        'services': {
            'database': test_database_connection(),
            'ai_chatbot': chatbot is not None,
            'recommender': recommender is not None,
            'ocr_classifier': ml_classifier is not None,
            'ocr_processor': ocr_processor is not None
        }
    })

def test_database_connection():
    """Test database connection"""
    try:
        import mysql.connector
        conn = mysql.connector.connect(**db_config)
        conn.close()
        return True
    except Exception as e:
        logger.error(f"Database connection failed: {e}")
        return False

@app.route('/api/chatbot', methods=['POST'])
def chatbot_endpoint():
    """AI Chatbot endpoint"""
    if not chatbot:
        return jsonify({'error': 'Chatbot service not available'}), 503
    
    try:
        data = request.get_json()
        user_input = data.get('message', '')
        
        if not user_input:
            return jsonify({'error': 'No message provided'}), 400
        
        response = chatbot.get_response(user_input)
        return jsonify({'response': response})
    
    except Exception as e:
        logger.error(f"Chatbot error: {e}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/recommend', methods=['POST'])
def recommend_endpoint():
    """Course recommendation endpoint"""
    if not recommender:
        return jsonify({'error': 'Recommendation service not available'}), 503
    
    try:
        data = request.get_json()
        student_data = data.get('student_data', {})
        
        if not student_data:
            return jsonify({'error': 'No student data provided'}), 400
        
        recommendations = recommender.recommend_courses(student_data)
        return jsonify({'recommendations': recommendations})
    
    except Exception as e:
        logger.error(f"Recommendation error: {e}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/ocr/classify', methods=['POST'])
def ocr_classify_endpoint():
    """OCR document classification endpoint"""
    if not ml_classifier:
        return jsonify({'error': 'OCR classification service not available'}), 503
    
    try:
        if 'file' not in request.files:
            return jsonify({'error': 'No file provided'}), 400
        
        file = request.files['file']
        if file.filename == '':
            return jsonify({'error': 'No file selected'}), 400
        
        # Process the file
        result = ml_classifier.classify_document(file)
        return jsonify({'classification': result})
    
    except Exception as e:
        logger.error(f"OCR classification error: {e}")
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/ocr/extract', methods=['POST'])
def ocr_extract_endpoint():
    """OCR text extraction endpoint"""
    if not ocr_processor:
        return jsonify({'error': 'OCR extraction service not available'}), 503
    
    try:
        if 'file' not in request.files:
            return jsonify({'error': 'No file provided'}), 400
        
        file = request.files['file']
        if file.filename == '':
            return jsonify({'error': 'No file selected'}), 400
        
        # Process the file
        result = ocr_processor.extract_text(file)
        return jsonify({'extracted_text': result})
    
    except Exception as e:
        logger.error(f"OCR extraction error: {e}")
        return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    debug = os.getenv('FLASK_DEBUG', 'false').lower() == 'true'
    
    logger.info(f"Starting PSAU API server on port {port}")
    app.run(host='0.0.0.0', port=port, debug=debug)