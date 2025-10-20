# 🚀 PSAU Admission System - Complete Replit Deployment Guide

## 📋 **Complete Step-by-Step Deployment Instructions**

This guide ensures your PSAU Admission System with AI functionality works perfectly in Replit.

---

## **Step 1: Create Replit Account and Import Project**

### 1.1 Create Replit Account
1. Go to [https://replit.com](https://replit.com)
2. Click **"Sign up"** 
3. Choose **"Continue with GitHub"** (recommended)
4. Authorize Replit access to your GitHub account

### 1.2 Import Your Project
1. Click **"+ Create Repl"** (big blue button)
2. Select **"Import from GitHub"**
3. Enter repository URL: `https://github.com/jericogutierrezsison12-dotcom/psau-admission-system`
4. **Repl name**: `psau-admission-system`
5. Click **"Import from GitHub"**

---

## **Step 2: Automatic Setup (Run Complete Setup Script)**

### 2.1 Run the Complete Setup Script
Once your project is imported, open the **Shell** tab and run:

```bash
chmod +x setup_complete.sh
./setup_complete.sh
```

### 2.2 What the Setup Script Does:
- ✅ Installs PHP 8.1 with all extensions
- ✅ Installs MySQL 8.0 and sets up database
- ✅ Installs Python 3.11 with all AI libraries
- ✅ Installs Node.js and Firebase CLI
- ✅ Imports complete database schema with all tables
- ✅ Creates all necessary directories
- ✅ Sets up AI models and training data
- ✅ Configures environment variables
- ✅ Sets proper file permissions
- ✅ Tests all components

---

## **Step 3: Start All Services**

### 3.1 Start the Complete System
```bash
chmod +x start_all_services.sh
./start_all_services.sh
```

### 3.2 What Gets Started:
- 🗄️ **MySQL Database** - All tables and data
- 🤖 **AI Services** - OCR, document analysis, chatbot
- 🌐 **PHP Web Server** - Main application
- 📧 **Firebase Integration** - Email notifications
- 🔐 **Authentication** - User and admin login

---

## **Step 4: Access Your Application**

### 4.1 Main URLs
- **Main Site**: `https://YOUR_REPL_NAME.replit.app`
- **Admin Panel**: `https://YOUR_REPL_NAME.replit.app/admin/login.php`
- **User Registration**: `https://YOUR_REPL_NAME.replit.app/public/register.php`
- **Application Form**: `https://YOUR_REPL_NAME.replit.app/public/application_form.php`

### 4.2 Admin Login Credentials
- **Username**: `jerico`
- **Email**: `jericogutierrezsison12@gmail.com`
- **Password**: (hashed password from database)

---

## **Step 5: Verify AI Functionality**

### 5.1 Test AI Components
```bash
./health_check.sh
```

### 5.2 AI Features Available:
- ✅ **OCR Processing** - PDF text extraction
- ✅ **Document Analysis** - Report card validation
- ✅ **AI Chatbot** - Student assistance
- ✅ **Course Recommendation** - AI-powered suggestions
- ✅ **Image Processing** - 2x2 photo validation

---

## **🔧 Complete Command Reference**

### Setup Commands
```bash
# Run complete setup
chmod +x setup_complete.sh
./setup_complete.sh

# Start all services
chmod +x start_all_services.sh
./start_all_services.sh

# Check system health
chmod +x health_check.sh
./health_check.sh
```

### Manual Service Commands
```bash
# Start MySQL
service mysql start

# Start AI services
./start_ai_services.sh

# Start PHP server
php -S 0.0.0.0:8000 -t .

# Test database connection
mysql -u root -e "USE psau_admission; SHOW TABLES;"
```

### AI Testing Commands
```bash
# Test Python AI libraries
python3.11 -c "import cv2, numpy, PIL, pytesseract; print('AI libraries working')"

# Test OCR functionality
python3.11 -c "import pytesseract; print(pytesseract.get_tesseract_version())"

# Test Flask AI app
cd python/image && python3.11 app.py
```

---

## **🗄️ Database Information**

### All Tables Included:
- `activity_logs` - System activity tracking
- `admins` - Administrator accounts
- `admin_login_attempts` - Admin security
- `announcements` - System announcements
- `applications` - Student applications
- `application_attempts` - Application tracking
- `courses` - Available courses
- `course_assignments` - Student course assignments
- `course_selections` - Student preferences
- `enrollment_assignments` - Enrollment scheduling
- `enrollment_instructions` - Enrollment guidelines
- `enrollment_schedules` - Enrollment dates
- `entrance_exam_scores` - Exam results
- `exams` - Exam scheduling
- `exam_instructions` - Exam guidelines
- `exam_required_documents` - Exam requirements
- `exam_schedules` - Exam dates
- `faqs` - Frequently asked questions
- `login_attempts` - User security
- `remember_tokens` - User sessions
- `reminder_logs` - Email reminders
- `required_documents` - Document requirements
- `status_history` - Application status tracking
- `student_feedback_counts` - Student feedback
- `unanswered_questions` - User questions
- `users` - Student accounts
- `venues` - Exam and enrollment venues

---

## **🤖 AI Components Included**

### 1. OCR Processing
- **File**: `python/image/ocr_processor.py`
- **Function**: Extract text from PDFs and images
- **Libraries**: Tesseract, OpenCV, PIL

### 2. Document Analysis
- **File**: `python/image/ml_classifier.py`
- **Function**: Validate report cards and documents
- **Models**: Pre-trained models for document classification

### 3. AI Chatbot
- **File**: `python/image/ai_chatbot.py`
- **Function**: Answer student questions
- **Features**: Natural language processing

### 4. Course Recommendation
- **File**: `python/image/database_recommender.py`
- **Function**: Suggest courses based on student profile
- **Algorithm**: Machine learning recommendations

### 5. Flask Web Service
- **File**: `python/image/app.py`
- **Function**: REST API for AI services
- **Endpoints**: OCR, analysis, chatbot, recommendations

---

## **📁 File Structure Maintained**

```
psau-admission-system/
├── admin/                    # Admin interface
├── public/                   # User interface
├── includes/                 # PHP backend functions
├── database/                 # Database schema
├── firebase/                 # Firebase configuration
├── python/image/            # AI services
│   ├── models/              # AI models
│   ├── training_images/     # Training data
│   ├── uploads/             # File uploads
│   └── *.py                 # AI scripts
├── uploads/                 # User uploads
├── images/                  # User images
├── logs/                    # System logs
├── vendor/                  # Composer packages
├── .replit                  # Replit configuration
├── setup_complete.sh        # Complete setup script
├── start_all_services.sh    # Service startup
├── health_check.sh          # System health check
└── requirements.txt         # Python dependencies
```

---

## **🔐 Security Features**

### Authentication
- ✅ Firebase Authentication
- ✅ reCAPTCHA v3 protection
- ✅ Session management
- ✅ Password hashing
- ✅ Login attempt tracking

### File Security
- ✅ File type validation
- ✅ File size limits
- ✅ Upload directory protection
- ✅ Malware scanning

### Database Security
- ✅ SQL injection protection
- ✅ Input validation
- ✅ Access control
- ✅ Audit logging

---

## **📊 Monitoring and Logs**

### System Monitoring
```bash
# Check system status
./health_check.sh

# View logs
tail -f logs/error.log
tail -f logs/access.log

# Monitor processes
ps aux | grep -E "(mysql|php|python)"
```

### Performance Monitoring
- CPU and memory usage
- Database performance
- AI processing times
- File upload speeds

---

## **🚨 Troubleshooting**

### Common Issues and Solutions

#### 1. Setup Script Fails
```bash
# Check permissions
chmod +x setup_complete.sh

# Run with verbose output
bash -x setup_complete.sh

# Check system resources
df -h
free -m
```

#### 2. Database Connection Error
```bash
# Start MySQL
service mysql start

# Check MySQL status
service mysql status

# Test connection
mysql -u root -e "SELECT 1;"
```

#### 3. AI Services Not Working
```bash
# Check Python installation
python3.11 --version

# Test AI libraries
python3.11 -c "import cv2, numpy, PIL, pytesseract"

# Check AI models
ls -la python/image/models/
```

#### 4. File Upload Issues
```bash
# Check directory permissions
ls -la uploads/
chmod 755 uploads/

# Check PHP settings
php -i | grep upload
```

#### 5. Firebase Connection Issues
```bash
# Check environment variables
cat .env | grep FIREBASE

# Test Firebase connection
curl -I https://psau-admission-system.firebaseapp.com
```

---

## **✅ Deployment Checklist**

### Pre-Deployment
- [x] Project uploaded to GitHub
- [x] Replit configuration created
- [x] Database schema ready
- [x] AI models included
- [x] All dependencies documented

### Deployment Steps
- [ ] Create Replit account
- [ ] Import from GitHub
- [ ] Run setup_complete.sh
- [ ] Start all services
- [ ] Test all functionality
- [ ] Verify AI components

### Post-Deployment
- [ ] Test user registration
- [ ] Test admin login
- [ ] Test application submission
- [ ] Test AI document analysis
- [ ] Test chatbot functionality
- [ ] Test email notifications
- [ ] Test mobile responsiveness

---

## **🎉 Success Indicators**

Your deployment is successful when:
- ✅ All services start without errors
- ✅ Database connects successfully
- ✅ AI components respond to requests
- ✅ File uploads work properly
- ✅ Email notifications are sent
- ✅ Admin panel is accessible
- ✅ User registration works
- ✅ Mobile interface is responsive

---

## **📞 Support and Maintenance**

### Regular Maintenance
- Monitor system logs
- Check database performance
- Update dependencies
- Backup data regularly
- Monitor AI model performance

### Getting Help
- Check system logs for errors
- Run health check script
- Review troubleshooting section
- Contact development team

---

**🎯 Your PSAU Admission System with full AI functionality is now ready for deployment!**

Follow these steps exactly, and you'll have a fully functional system running in Replit with all AI capabilities working perfectly.
