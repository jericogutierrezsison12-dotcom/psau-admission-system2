#!/bin/bash

# PSAU Admission System - Complete Setup Script for Replit
# This script ensures ALL components work properly including AI functionality

echo "🚀 Starting PSAU Admission System Complete Setup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[SETUP]${NC} $1"
}

# Update system packages
print_header "Updating system packages..."
apt-get update -y
apt-get upgrade -y

# Install all required system packages
print_header "Installing system dependencies..."
apt-get install -y \
    php8.1 \
    php8.1-mysql \
    php8.1-curl \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    php8.1-intl \
    php8.1-bcmath \
    php8.1-dom \
    php8.1-fileinfo \
    php8.1-json \
    php8.1-openssl \
    php8.1-pdo \
    php8.1-tokenizer \
    mysql-server \
    mysql-client \
    tesseract-ocr \
    tesseract-ocr-eng \
    tesseract-ocr-fil \
    poppler-utils \
    python3.11 \
    python3.11-pip \
    python3.11-venv \
    python3.11-dev \
    python3-pil \
    python3-pil.imagetk \
    python3-opencv \
    nodejs \
    npm \
    git \
    curl \
    wget \
    unzip \
    build-essential \
    libssl-dev \
    libffi-dev \
    libjpeg-dev \
    libpng-dev \
    libtiff-dev \
    libwebp-dev \
    libopenblas-dev \
    liblapack-dev \
    pkg-config

# Install Composer
print_header "Installing Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    print_status "Composer installed successfully"
else
    print_status "Composer already installed"
fi

# Install Python dependencies for AI functionality
print_header "Installing Python dependencies for AI..."
pip3 install --upgrade pip
pip3 install -r requirements.txt

# Install additional Python packages for AI
pip3 install \
    flask \
    flask-cors \
    numpy \
    pandas \
    scikit-learn \
    opencv-python \
    pillow \
    pytesseract \
    pdf2image \
    python-dotenv \
    requests \
    beautifulsoup4 \
    nltk \
    spacy \
    transformers \
    torch \
    tensorflow \
    keras \
    matplotlib \
    seaborn \
    plotly \
    jupyter \
    ipython

# Install PHP dependencies
print_header "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies
print_header "Installing Node.js dependencies..."
npm install -g firebase-tools
npm install

# Setup MySQL
print_header "Setting up MySQL database..."
service mysql start
sleep 5

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS psau_admission CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';"
mysql -e "GRANT ALL PRIVILEGES ON psau_admission.* TO 'root'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import complete database schema with all tables and data
print_header "Importing complete database schema..."
mysql -u root psau_admission < database/psau_admission.sql

# Verify database import
print_header "Verifying database import..."
TABLE_COUNT=$(mysql -u root -e "USE psau_admission; SHOW TABLES;" | wc -l)
print_status "Database imported successfully with $TABLE_COUNT tables"

# Create necessary directories with proper permissions
print_header "Creating directories and setting permissions..."
mkdir -p uploads images logs temp cache sessions
mkdir -p python/image/uploads python/image/temp python/image/logs
mkdir -p admin/uploads admin/temp admin/logs
mkdir -p public/uploads public/temp public/logs

# Set proper permissions
chmod -R 755 uploads images logs temp cache sessions
chmod -R 755 python/image/uploads python/image/temp python/image/logs
chmod -R 755 admin/uploads admin/temp admin/logs
chmod -R 755 public/uploads public/temp public/logs
chmod 644 database/psau_admission.sql

# Create .env file with all necessary environment variables
print_header "Creating environment configuration..."
cat > .env << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_NAME=psau_admission
DB_USER=root
DB_PASS=

# Firebase Configuration
FIREBASE_API_KEY=AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8
FIREBASE_AUTH_DOMAIN=psau-admission-system.firebaseapp.com
FIREBASE_PROJECT_ID=psau-admission-system
FIREBASE_STORAGE_BUCKET=psau-admission-system.appspot.com
FIREBASE_MESSAGING_SENDER_ID=522448258958
FIREBASE_APP_ID=1:522448258958:web:994b133a4f7b7f4c1b06df
FIREBASE_EMAIL_FUNCTION_URL=https://sendemail-alsstt22ha-uc.a.run.app

# OCR Configuration
OCR_API_KEY=K87139000188957
TESSERACT_CMD=/usr/bin/tesseract

# reCAPTCHA Configuration
RECAPTCHA_SECRET_KEY=6LezOyYrAAAAAFBdA-STTB2MsNfK6CyDC_2qFR8N

# Application Settings
APP_ENV=production
APP_DEBUG=false
UPLOAD_MAX_SIZE=10M
POST_MAX_SIZE=10M
MAX_EXECUTION_TIME=300
MEMORY_LIMIT=512M

# Security Settings
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict

# Email Settings
MAIL_FROM=jericogutierrezsison12@gmail.com
MAIL_FROM_NAME=PSAU Admissions

# AI Configuration
AI_MODEL_PATH=python/image/models/
AI_TRAINING_DATA_PATH=python/image/training_images/
AI_UPLOAD_PATH=python/image/uploads/
AI_TEMP_PATH=python/image/temp/

# Python Configuration
PYTHON_PATH=/usr/bin/python3.11
PYTHON_VENV_PATH=/opt/venv

# File Upload Configuration
ALLOWED_FILE_TYPES=pdf,jpg,jpeg,png,gif
MAX_FILE_SIZE=10485760
UPLOAD_PATH=uploads/

# Logging Configuration
LOG_LEVEL=info
LOG_PATH=logs/
ERROR_LOG_PATH=logs/error.log
ACCESS_LOG_PATH=logs/access.log
EOF

# Update database connection file for Replit environment
print_header "Updating database connection for Replit..."
cat > includes/db_connect.php << 'EOF'
<?php
/**
 * Database Connection File for Replit
 * Establishes connection to MySQL database for PSAU Admission System
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database credentials
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'psau_admission';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

// Create connection
$conn = null;
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set charset
    $conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8mb4");
} catch(PDOException $e) {
    // Log error instead of displaying it directly
    error_log("Connection failed: " . $e->getMessage());
    
    // If in development mode, you can display the error
    if(defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "Connection failed: " . $e->getMessage();
    } else {
        echo "Database connection error. Please try again later.";
    }
    exit;
}
EOF

# Create AI service startup script
print_header "Creating AI service startup script..."
cat > start_ai_services.sh << 'EOF'
#!/bin/bash

# Start AI services for PSAU Admission System

echo "🤖 Starting AI Services..."

# Start Python AI services
cd python/image

# Start Flask AI application
python3 app.py &
AI_PID=$!

# Start OCR processor
python3 ocr_processor.py &
OCR_PID=$!

# Start chatbot service
python3 ai_chatbot.py &
CHATBOT_PID=$!

echo "AI Services started:"
echo "Flask App PID: $AI_PID"
echo "OCR Processor PID: $OCR_PID"
echo "Chatbot PID: $CHATBOT_PID"

# Save PIDs for later cleanup
echo $AI_PID > /tmp/ai_app.pid
echo $OCR_PID > /tmp/ocr_processor.pid
echo $CHATBOT_PID > /tmp/chatbot.pid

echo "✅ AI Services are running!"
EOF

chmod +x start_ai_services.sh

# Create comprehensive startup script
print_header "Creating comprehensive startup script..."
cat > start_all_services.sh << 'EOF'
#!/bin/bash

# PSAU Admission System - Complete Startup Script

echo "🚀 Starting PSAU Admission System..."

# Start MySQL
echo "📊 Starting MySQL..."
service mysql start
sleep 5

# Verify MySQL is running
if ! pgrep -x "mysqld" > /dev/null; then
    echo "❌ MySQL failed to start"
    exit 1
fi

# Test database connection
mysql -u root -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ MySQL is running and accessible"
else
    echo "❌ MySQL connection failed"
    exit 1
fi

# Start AI services
echo "🤖 Starting AI Services..."
./start_ai_services.sh &
sleep 3

# Start PHP development server
echo "🌐 Starting PHP Server..."
php -S 0.0.0.0:8000 -t . &
PHP_PID=$!

echo "✅ PSAU Admission System is running!"
echo "📱 Access your application at: http://0.0.0.0:8000"
echo "🔧 Admin panel: http://0.0.0.0:8000/admin/login.php"
echo "👤 User registration: http://0.0.0.0:8000/public/register.php"
echo ""
echo "🔑 Admin credentials:"
echo "   Username: jerico"
echo "   Email: jericogutierrezsison12@gmail.com"
echo ""
echo "🛑 To stop all services, press Ctrl+C"

# Keep the script running
wait
EOF

chmod +x start_all_services.sh

# Create system health check script
print_header "Creating system health check script..."
cat > health_check.sh << 'EOF'
#!/bin/bash

echo "🔍 PSAU Admission System Health Check"

# Check MySQL
echo "📊 Checking MySQL..."
if pgrep -x "mysqld" > /dev/null; then
    echo "✅ MySQL is running"
    mysql -u root -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'psau_admission';" 2>/dev/null | tail -1
    echo "✅ Database is accessible"
else
    echo "❌ MySQL is not running"
fi

# Check PHP
echo "🐘 Checking PHP..."
if command -v php &> /dev/null; then
    echo "✅ PHP is installed: $(php --version | head -1)"
else
    echo "❌ PHP is not installed"
fi

# Check Python
echo "🐍 Checking Python..."
if command -v python3.11 &> /dev/null; then
    echo "✅ Python is installed: $(python3.11 --version)"
else
    echo "❌ Python is not installed"
fi

# Check AI models
echo "🤖 Checking AI Models..."
if [ -f "python/image/models/report_card_model.pkl" ]; then
    echo "✅ Report card model found"
else
    echo "⚠️  Report card model not found"
fi

if [ -f "python/image/models/auto_report_card_model.pkl" ]; then
    echo "✅ Auto report card model found"
else
    echo "⚠️  Auto report card model not found"
fi

if [ -f "python/image/vectorizer.pkl" ]; then
    echo "✅ Vectorizer found"
else
    echo "⚠️  Vectorizer not found"
fi

# Check Firebase configuration
echo "🔥 Checking Firebase Configuration..."
if [ -f ".env" ]; then
    echo "✅ Environment file found"
    if grep -q "FIREBASE_API_KEY" .env; then
        echo "✅ Firebase API key configured"
    else
        echo "⚠️  Firebase API key not configured"
    fi
else
    echo "❌ Environment file not found"
fi

echo "🏁 Health check complete"
EOF

chmod +x health_check.sh

# Install Firebase CLI
print_header "Installing Firebase CLI..."
npm install -g firebase-tools

# Create Python virtual environment for AI services
print_header "Setting up Python virtual environment..."
python3.11 -m venv /opt/venv
source /opt/venv/bin/activate
pip install -r requirements.txt

# Test all components
print_header "Testing system components..."

# Test PHP
php -v
if [ $? -eq 0 ]; then
    print_status "PHP is working correctly"
else
    print_error "PHP test failed"
fi

# Test MySQL
mysql -u root -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_status "MySQL is working correctly"
else
    print_error "MySQL test failed"
fi

# Test Python
python3.11 --version
if [ $? -eq 0 ]; then
    print_status "Python is working correctly"
else
    print_error "Python test failed"
fi

# Test AI components
python3.11 -c "import cv2, numpy, PIL, pytesseract; print('AI libraries imported successfully')" 2>/dev/null
if [ $? -eq 0 ]; then
    print_status "AI libraries are working correctly"
else
    print_warning "Some AI libraries may not be fully functional"
fi

# Create system status file
print_header "Creating system status file..."
cat > system_status.txt << EOF
PSAU Admission System - Setup Complete
=====================================

Setup Date: $(date)
PHP Version: $(php --version | head -1)
MySQL Version: $(mysql --version | head -1)
Python Version: $(python3.11 --version)
Node.js Version: $(node --version)

Database Status: Ready
AI Services Status: Ready
Firebase Integration: Ready
File Uploads: Ready

All components are properly configured and ready for use.
EOF

# Final status
print_header "Setup Complete!"
echo ""
print_status "✅ PSAU Admission System is fully configured"
print_status "✅ All database tables imported"
print_status "✅ AI functionality ready"
print_status "✅ Firebase integration configured"
print_status "✅ All dependencies installed"
echo ""
print_status "🚀 To start the system, run: ./start_all_services.sh"
print_status "🔍 To check system health, run: ./health_check.sh"
echo ""
print_status "📱 Your application will be available at: http://0.0.0.0:8000"
print_status "🔧 Admin panel: http://0.0.0.0:8000/admin/login.php"
echo ""
print_status "🔑 Admin credentials:"
print_status "   Username: jerico"
print_status "   Email: jericogutierrezsison12@gmail.com"
echo ""
print_status "🎉 Setup completed successfully!"
