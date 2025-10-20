#!/bin/bash

# PSAU Admission System Setup Script for Replit
echo "🚀 Setting up PSAU Admission System..."

# Update system packages
echo "📦 Updating system packages..."
apt-get update -y

# Install required system packages
echo "🔧 Installing system dependencies..."
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
    mysql-server \
    tesseract-ocr \
    tesseract-ocr-eng \
    poppler-utils \
    python3.11 \
    python3.11-pip \
    python3.11-venv \
    nodejs \
    npm \
    git \
    curl \
    wget

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Python dependencies
echo "🐍 Installing Python dependencies..."
pip3 install -r requirements.txt

# Install PHP dependencies
echo "📚 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Setup MySQL
echo "🗄️ Setting up MySQL database..."
service mysql start
mysql -e "CREATE DATABASE IF NOT EXISTS psau_admission;"
mysql -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';"
mysql -e "GRANT ALL PRIVILEGES ON psau_admission.* TO 'root'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import database schema
echo "📊 Importing database schema..."
mysql -u root psau_admission < database/psau_admission.sql

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p uploads
mkdir -p images
mkdir -p logs
mkdir -p temp

# Set permissions
echo "🔐 Setting permissions..."
chmod 755 uploads
chmod 755 images
chmod 755 logs
chmod 755 temp
chmod 644 database/psau_admission.sql

# Install Firebase CLI
echo "🔥 Installing Firebase CLI..."
npm install -g firebase-tools

# Create environment file
echo "⚙️ Creating environment configuration..."
cat > .env << EOF
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

# reCAPTCHA Configuration
RECAPTCHA_SECRET_KEY=6LezOyYrAAAAAFBdA-STTB2MsNfK6CyDC_2qFR8N

# Application Settings
APP_ENV=production
APP_DEBUG=false
UPLOAD_MAX_SIZE=10M
POST_MAX_SIZE=10M

# Security Settings
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict

# Email Settings
MAIL_FROM=jericogutierrezsison12@gmail.com
MAIL_FROM_NAME=PSAU Admissions
EOF

# Update database configuration for Replit
echo "🔧 Updating database configuration..."
cat > includes/db_connect.php << 'EOF'
<?php
/**
 * Database Connection File for Replit
 * Establishes connection to MySQL database for PSAU Admission System
 */

// Get environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'psau_admission';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

// Create connection
$conn = null;
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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

# Create startup script
echo "🚀 Creating startup script..."
cat > start.sh << 'EOF'
#!/bin/bash

# Start MySQL service
service mysql start

# Wait for MySQL to be ready
sleep 5

# Start PHP development server
echo "Starting PSAU Admission System..."
php -S 0.0.0.0:8000 -t .
EOF

chmod +x start.sh

echo "✅ Setup completed successfully!"
echo ""
echo "🎉 PSAU Admission System is ready!"
echo ""
echo "To start the application:"
echo "1. Run: ./start.sh"
echo "2. Open: http://localhost:8000"
echo ""
echo "Admin credentials:"
echo "Username: jerico"
echo "Email: jericogutierrezsison12@gmail.com"
echo "Password: (check database for hashed password)"
echo ""
echo "📝 Note: Make sure to update Firebase configuration and API keys for production use."
