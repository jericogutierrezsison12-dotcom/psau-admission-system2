#!/bin/bash
# Replit startup script for PSAU Admission System

echo "🚀 Starting PSAU Admission System Python Services..."

# Install dependencies if requirements.txt exists
if [ -f "requirements.txt" ]; then
    echo "📦 Installing Python dependencies..."
    pip install -r requirements.txt
fi

# Create necessary directories
mkdir -p uploads
mkdir -p logs

# Set permissions
chmod 755 uploads
chmod 755 logs

# Check if environment variables are set
if [ -z "$DB_HOST" ]; then
    echo "⚠️  Warning: DB_HOST environment variable not set"
fi

if [ -z "$DB_USER" ]; then
    echo "⚠️  Warning: DB_USER environment variable not set"
fi

if [ -z "$DB_PASS" ]; then
    echo "⚠️  Warning: DB_PASS environment variable not set"
fi

if [ -z "$DB_NAME" ]; then
    echo "⚠️  Warning: DB_NAME environment variable not set"
fi

# Start the Flask application
echo "🌐 Starting Flask application..."
python app.py
