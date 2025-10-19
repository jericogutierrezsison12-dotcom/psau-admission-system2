# PSAU AI-Assisted Admission System

A comprehensive admission system for Pampanga State Agricultural University featuring AI-powered document processing, course recommendations, and chatbot assistance.

## 🏗️ Architecture

This system uses a distributed architecture across multiple platforms:

- **InfinityFree**: PHP application hosting and MySQL database
- **Replit**: Python AI/ML services (OCR, Chatbot, Course Recommendations)
- **Render**: PHP vendor dependencies and API services (optional)

## 🚀 Quick Start

### Prerequisites

- PHP 7.4+ with MySQL support
- Python 3.11+
- Composer (for PHP dependencies)
- Git

### Local Development Setup

1. **Clone the repository**:
   ```bash
   git clone <your-repo-url>
   cd psau-admission-system
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Setup database**:
   - Import `database/psau_admission.sql` to your MySQL database
   - Update database credentials in `includes/db_connect.php`

4. **Setup Python services**:
   ```bash
   cd python/image
   pip install -r requirements.txt
   python app.py
   ```

5. **Configure environment**:
   - Copy `env.example` to `.env`
   - Update configuration values

## 📁 Project Structure

```
psau-admission-system/
├── admin/                 # Admin panel PHP files
├── public/               # Public-facing PHP files
│   ├── ai/               # AI integration files
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── templates/        # HTML templates
├── includes/             # PHP includes and utilities
├── python/image/        # Python AI/ML services
│   ├── app.py           # Main Flask application
│   ├── requirements.txt # Python dependencies
│   ├── .replit         # Replit configuration
│   └── replit.nix      # Replit Nix configuration
├── database/            # Database schema and data
├── firebase/            # Firebase configuration
├── images/              # Uploaded images
├── uploads/             # Uploaded documents
├── vendor/              # Composer dependencies
├── composer.json        # PHP dependencies
├── render.yaml          # Render deployment config
└── DEPLOYMENT.md        # Deployment guide
```

## 🔧 Features

### Core Features
- **User Registration & Authentication**: Secure user accounts with email verification
- **Document Upload & Processing**: AI-powered OCR for document validation
- **Course Recommendations**: ML-based course suggestions based on student profile
- **AI Chatbot**: Intelligent FAQ system with natural language processing
- **Admin Panel**: Comprehensive administration interface
- **Application Management**: Track and manage student applications

### AI/ML Features
- **Document Classification**: Automatically classify uploaded documents
- **OCR Processing**: Extract text from images and PDFs using PaddleOCR
- **Grade Validation**: Verify academic performance from report cards
- **Course Matching**: Recommend courses based on academic profile and interests

## 🌐 Deployment

### Production Deployment

1. **InfinityFree Setup**:
   - Upload PHP files to InfinityFree hosting
   - Import database schema
   - Configure database credentials

2. **Replit Setup**:
   - Create new Python Repl
   - Upload `python/image/` directory
   - Set environment variables
   - Install dependencies

3. **Configuration**:
   - Update API URLs in PHP files
   - Configure CORS settings
   - Set up environment variables

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

## 🔌 API Endpoints

### Python Services (Replit)
- `POST /ocr_service` - Document OCR processing
- `POST /ask_question` - Chatbot question handling
- `POST /api/recommend` - Course recommendations
- `POST /api/save_ratings` - Save user ratings
- `GET /health` - Service health check

### PHP Services (InfinityFree)
- `POST /public/ai/chatbot_handler.php` - Chatbot integration
- `POST /public/ai/recommendation_handler.php` - Recommendation integration
- `POST /admin/review_application.php` - Application review
- `GET /public/dashboard.php` - User dashboard

## 🛠️ Configuration

### Environment Variables

#### Python Services (Replit)
```
DB_HOST=your-database-host
DB_USER=your-database-user
DB_PASS=your-database-password
DB_NAME=psau_admission
ALLOWED_ORIGINS=https://your-domain.infinityfreeapp.com
```

#### PHP Services (InfinityFree)
Update `includes/db_connect.php` with your database credentials:
```php
$host = 'your-infinityfree-db-host';
$dbname = 'your-database-name';
$username = 'your-username';
$password = 'your-password';
```

## 🔒 Security Features

- **Input Validation**: Comprehensive validation for all user inputs
- **SQL Injection Protection**: Prepared statements and parameterized queries
- **XSS Protection**: Output escaping and content security policies
- **CSRF Protection**: Token-based request validation
- **File Upload Security**: Type validation and secure file handling
- **Authentication**: Secure session management and password hashing

## 📊 Database Schema

The system uses MySQL with the following main tables:
- `users` - User accounts and profiles
- `applications` - Student applications
- `courses` - Available courses
- `faqs` - Frequently asked questions
- `activity_logs` - System activity tracking
- `ai_document_analysis` - AI processing results

## 🤖 AI/ML Components

### Document Processing
- **PaddleOCR**: Text extraction from images and PDFs
- **ML Classification**: Document type classification
- **Grade Validation**: Academic performance verification

### Course Recommendations
- **Collaborative Filtering**: User-based recommendations
- **Content-Based Filtering**: Course attribute matching
- **Hybrid Approach**: Combined recommendation strategies

### Chatbot System
- **FAQ Matching**: Intelligent question-answer pairing
- **Natural Language Processing**: Understanding user queries
- **Context Awareness**: Maintaining conversation context

## 🧪 Testing

### Manual Testing
1. Test user registration and login
2. Test document upload and processing
3. Test course recommendations
4. Test chatbot functionality
5. Test admin panel features

### Automated Testing
```bash
# Run PHP tests (if available)
composer test

# Run Python tests
cd python/image
python -m pytest
```

## 📝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Check the [DEPLOYMENT.md](DEPLOYMENT.md) for deployment issues
- Review logs in each platform's dashboard
- Use browser developer tools for debugging API calls

## 🔄 Updates

### Recent Updates
- ✅ Distributed architecture implementation
- ✅ Replit Python service integration
- ✅ InfinityFree PHP hosting configuration
- ✅ CORS configuration for cross-platform communication
- ✅ Environment-based configuration
- ✅ Comprehensive deployment documentation

### Planned Features
- 🔄 Real-time notifications
- 🔄 Advanced analytics dashboard
- 🔄 Mobile app integration
- 🔄 Multi-language support