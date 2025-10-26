# 🚀 PSAU Admission System - Deployment Guide

## 📋 Pre-Deployment Checklist

### ✅ **Files Ready for Git Upload**

All files are now ready for Git upload and Render deployment:

#### **Core System Files:**
- ✅ `includes/encryption.php` - AES-256-GCM encryption
- ✅ `includes/backup_system.php` - Backup and recovery system
- ✅ `includes/database_migration.php` - Auto-creates security tables
- ✅ `includes/encrypted_data_access.php` - Encrypted database access
- ✅ `includes/encrypted_file_storage.php` - Encrypted file storage
- ✅ `deploy.php` - Automatic deployment script

#### **Admin Management:**
- ✅ `admin/backup_management.php` - Backup management interface
- ✅ `admin/emergency_recovery.php` - Emergency recovery tools
- ✅ `admin/security_monitor.php` - Security monitoring dashboard
- ✅ `admin/auto_backup.php` - Automated backup script

#### **Updated Application Files:**
- ✅ `public/register.php` - Registration with encryption
- ✅ `public/login.php` - Login with encrypted lookup
- ✅ `public/profile.php` - Profile with application fields
- ✅ `public/application_form.php` - Enhanced application form
- ✅ `public/html/register.html` - Updated registration form
- ✅ `public/html/application_form.html` - Enhanced application form
- ✅ `public/html/profile.html` - Enhanced profile form

#### **Configuration Files:**
- ✅ `render.yaml` - Render deployment configuration
- ✅ `.gitignore` - Git ignore rules
- ✅ `env.example` - Environment variables template
- ✅ `encryption_config.example` - Encryption configuration

## 🚀 **Deployment Steps**

### **1. Git Upload**
```bash
# Initialize git repository
git init

# Add all files
git add .

# Commit changes
git commit -m "PSAU Admission System with Encryption and Security"

# Add remote repository
git remote add origin https://github.com/yourusername/psau-admission-system.git

# Push to GitHub
git push -u origin main
```

### **2. Render Deployment**

#### **A. Create New Web Service:**
1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click "New +" → "Web Service"
3. Connect your GitHub repository
4. Use these settings:

**Basic Settings:**
- **Name**: `psau-admission-system`
- **Environment**: `PHP`
- **Region**: `Oregon (US West)`
- **Branch**: `main`
- **Root Directory**: Leave empty

**Build & Deploy:**
- **Build Command**: `php deploy.php`
- **Start Command**: `php -S 0.0.0.0:$PORT`

#### **B. Create Database:**
1. Go to "New +" → "PostgreSQL"
2. Name: `psau-database`
3. Database: `psau_admission`
4. User: `psau_admin`
5. Plan: `Starter`

#### **C. Environment Variables:**
Add these in Render dashboard:

**Required Variables:**
```
ENCRYPTION_KEY=your_32_byte_base64_encoded_key_here
DB_HOST=from_database_connection
DB_NAME=from_database_connection
DB_USER=from_database_connection
DB_PASS=from_database_connection
DB_PORT=from_database_connection
```

**Firebase Configuration:**
```
FIREBASE_API_KEY=AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8
FIREBASE_AUTH_DOMAIN=psau-admission-system.firebaseapp.com
FIREBASE_PROJECT_ID=psau-admission-system
FIREBASE_STORAGE_BUCKET=psau-admission-system.appspot.com
FIREBASE_MESSAGING_SENDER_ID=522448258958
FIREBASE_APP_ID=1:522448258958:web:994b133a4f7b7f4c1b06df
FIREBASE_EMAIL_FUNCTION_URL=https://sendemail-alsstt22ha-uc.a.run.app
```

**Security Configuration:**
```
RECAPTCHA_SITE_KEY=6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA
RECAPTCHA_SECRET_KEY=6LezOyYrAAAAAFBdA-STTB2MsNfK6CyDC_2qFR8N
ENVIRONMENT=production
DOMAIN=psau-admission-system.onrender.com
BACKUP_RETENTION_DAYS=30
SECURITY_MONITORING=true
FAILED_LOGIN_LIMIT=5
IP_BLOCK_DURATION=3600
```

### **3. Generate Encryption Key**
```bash
# Generate a secure encryption key
php -r "echo base64_encode(random_bytes(32));"
```

Copy the output and set it as `ENCRYPTION_KEY` in Render.

## 🔧 **Post-Deployment Setup**

### **1. Verify Deployment**
After deployment, check these URLs:

- **Main Application**: `https://psau-admission-system.onrender.com/public/`
- **Admin Login**: `https://psau-admission-system.onrender.com/admin/`
- **Backup Management**: `https://psau-admission-system.onrender.com/admin/backup_management.php`
- **Security Monitor**: `https://psau-admission-system.onrender.com/admin/security_monitor.php`
- **Emergency Recovery**: `https://psau-admission-system.onrender.com/admin/emergency_recovery.php`

### **2. Test Security Tables**
The `database_migration.php` script will automatically create these tables:
- ✅ `blocked_ips` - IP blocking system
- ✅ `user_sessions` - Session management
- ✅ `activity_logs` - Activity logging
- ✅ `otp_attempts` - OTP security
- ✅ `system_health` - System monitoring
- ✅ `backup_history` - Backup tracking
- ✅ `security_incidents` - Security incidents

### **3. Set Up Automated Backups**
The system includes automated backups:
- **Daily Full Backup**: 2:00 AM UTC
- **Hourly Incremental**: Every 6 hours
- **Security Cleanup**: 3:00 AM UTC

## 🛡️ **Security Features Deployed**

### **Encryption System:**
- ✅ AES-256-GCM encryption for all sensitive data
- ✅ Encrypted database fields
- ✅ Encrypted file storage
- ✅ Encrypted session data

### **Backup System:**
- ✅ Automated daily backups
- ✅ Encrypted backup storage
- ✅ One-click restore
- ✅ Emergency backup creation

### **Security Monitoring:**
- ✅ Real-time threat detection
- ✅ Automatic IP blocking
- ✅ Failed login protection
- ✅ Security incident tracking

### **Form Enhancements:**
- ✅ Mobile number, gender, birth date in registration
- ✅ Application form with user table fields
- ✅ Profile editing for application fields
- ✅ All data encrypted at rest

## 📊 **Monitoring & Maintenance**

### **Daily Tasks:**
1. Check security dashboard for threats
2. Verify backup completion
3. Monitor system health

### **Weekly Tasks:**
1. Review security logs
2. Test backup restore
3. Check disk space

### **Monthly Tasks:**
1. Update encryption keys
2. Security audit
3. Performance review

## 🚨 **Emergency Procedures**

### **If System is Down:**
1. Check Render dashboard
2. Use Emergency Recovery tools
3. Restore from latest backup
4. Contact support if needed

### **If Security Breach:**
1. Go to Security Monitor
2. Block suspicious IPs
3. Create emergency backup
4. Review activity logs
5. Restore from clean backup if needed

## 📞 **Support Information**

- **System Admin**: [Your Contact]
- **Database Admin**: [DB Admin Contact]
- **Render Support**: [Render Support]
- **Emergency Contact**: [Emergency Contact]

## ✅ **Deployment Complete**

Your PSAU Admission System is now deployed with:
- ✅ Full encryption system
- ✅ Automated backups
- ✅ Security monitoring
- ✅ Enhanced forms
- ✅ Disaster recovery
- ✅ High availability

The system can handle thousands of applicants while maintaining security and reliability!
