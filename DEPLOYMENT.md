# PSAU Admission System - Deployment Guide

## Overview
This project is designed to run across multiple platforms:
- **InfinityFree**: PHP application and database hosting
- **Replit**: Python AI/ML services (OCR, Chatbot, Recommendations)
- **Render**: PHP vendor dependencies and API services

## Deployment Steps

### 1. InfinityFree Setup (Main Application)

1. **Upload PHP Files**:
   - Upload all PHP files to your InfinityFree hosting
   - Ensure `includes/db_connect.php` has correct database credentials
   - Update database credentials in the file for your InfinityFree database

2. **Database Setup**:
   - Import `database/psau_admission.sql` to your InfinityFree MySQL database
   - Update database connection details in `includes/db_connect.php`

3. **File Permissions**:
   - Set uploads/ directory to writable (755 or 777)
   - Set images/ directory to writable (755 or 777)

### 2. Replit Setup (Python Services)

1. **Create New Repl**:
   - Go to Replit.com and create a new Python Repl
   - Upload the `python/image/` directory contents

2. **Environment Variables**:
   Set these environment variables in Replit:
   ```
   DB_HOST=your-infinityfree-db-host
   DB_USER=your-infinityfree-db-user
   DB_PASS=your-infinityfree-db-password
   DB_NAME=your-infinityfree-db-name
   ALLOWED_ORIGINS=https://your-infinityfree-domain.infinityfreeapp.com
   ```

3. **Install Dependencies**:
   - Replit will automatically install from `requirements.txt`
   - Wait for all packages to install (may take 5-10 minutes)

4. **Run the Application**:
   - Click "Run" button in Replit
   - Note the URL provided (e.g., `https://your-app.replit.dev`)

### 3. Render Setup (Optional - for Vendor Dependencies)

1. **Create New Web Service**:
   - Go to Render.com and create a new Web Service
   - Connect your GitHub repository

2. **Configuration**:
   - Build Command: `composer install --no-dev --optimize-autoloader`
   - Start Command: `php -S 0.0.0.0:$PORT -t vendor`
   - Environment: PHP

3. **Environment Variables**:
   ```
   DB_HOST=your-database-host
   DB_USER=your-database-user
   DB_PASS=your-database-password
   DB_NAME=psau_admission
   PYTHON_API_URL=https://your-replit-app.replit.dev
   ```

### 4. Update Configuration Files

1. **Update Python API URL**:
   - In `includes/python_api.php`, update the Replit URL
   - Replace `https://your-replit-app.replit.dev` with your actual Replit URL

2. **Update CORS Origins**:
   - In `python/image/app.py`, update the allowed origins
   - Replace placeholder URLs with your actual domains

3. **Update Database Credentials**:
   - Update `includes/db_connect.php` with your InfinityFree database details
   - Update `python/image/app.py` with the same database details

### 5. Testing the Deployment

1. **Test PHP Application**:
   - Visit your InfinityFree domain
   - Test user registration and login
   - Test file uploads

2. **Test Python Services**:
   - Test chatbot functionality
   - Test course recommendations
   - Test OCR document processing

3. **Test Integration**:
   - Ensure PHP can communicate with Python services
   - Check CORS settings
   - Verify database connectivity

## Troubleshooting

### Common Issues:

1. **CORS Errors**:
   - Check allowed origins in `python/image/app.py`
   - Ensure your domain is included in the CORS configuration

2. **Database Connection Issues**:
   - Verify database credentials
   - Check if database server allows external connections
   - Ensure firewall settings allow connections

3. **Python Service Not Responding**:
   - Check Replit logs for errors
   - Verify all dependencies are installed
   - Check environment variables

4. **File Upload Issues**:
   - Check directory permissions
   - Verify upload limits in PHP configuration
   - Check available disk space

### Support:
- Check logs in each platform's dashboard
- Use browser developer tools to debug API calls
- Test each service individually before integration

## Security Notes:
- Never commit database credentials to version control
- Use environment variables for sensitive data
- Regularly update dependencies
- Monitor logs for suspicious activity
