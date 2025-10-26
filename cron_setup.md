# 🕐 Automated Backup & Security Setup Guide

## Overview
This guide explains how to set up automated backups and security monitoring for the PSAU Admission System to handle many applicants and protect against system failures or security breaches.

## 🚀 Quick Setup

### 1. **Automated Backups**

#### **Option A: Using Render Cron Jobs (Recommended)**
```bash
# Add to your render.yaml or use Render's cron job feature
# Daily full backup at 2 AM
0 2 * * * curl -X POST https://your-app.onrender.com/admin/auto_backup.php

# Hourly incremental backup
0 * * * * curl -X POST https://your-app.onrender.com/admin/auto_backup.php
```

#### **Option B: Using External Cron Service**
```bash
# Set up with a service like cron-job.org or EasyCron
# URL: https://your-app.onrender.com/admin/auto_backup.php
# Schedule: Every 6 hours
```

#### **Option C: Server Cron (if you have server access)**
```bash
# Add to crontab (crontab -e)
# Daily full backup at 2 AM
0 2 * * * /usr/bin/php /path/to/your/app/admin/auto_backup.php

# Every 6 hours incremental backup
0 */6 * * * /usr/bin/php /path/to/your/app/admin/auto_backup.php
```

### 2. **Database Setup for Security Monitoring**

Run this SQL to create security monitoring tables:

```sql
-- Blocked IPs table
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User sessions table (if not exists)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at)
);

-- Enhanced activity logs (if not exists)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    user_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
);
```

### 3. **Environment Configuration**

Add these to your `.env` file:

```env
# Backup Configuration
BACKUP_RETENTION_DAYS=30
BACKUP_PATH=../backups/
ENCRYPTION_BACKUP=true

# Security Configuration
SECURITY_MONITORING=true
FAILED_LOGIN_LIMIT=5
IP_BLOCK_DURATION=3600
ADMIN_EMAIL=your-admin@email.com

# Monitoring
LOG_LEVEL=INFO
LOG_FILE=../logs/security.log
```

## 🛡️ Security Features

### **1. Automated Threat Detection**
- **Failed Login Monitoring**: Automatically detects and blocks IPs with excessive failed attempts
- **Suspicious Activity Detection**: Monitors for unusual access patterns
- **Real-time Alerts**: Sends notifications for security incidents

### **2. Backup Protection**
- **Encrypted Backups**: All backups are encrypted using AES-256-GCM
- **Multiple Backup Types**: Full, incremental, and emergency backups
- **Automated Cleanup**: Old backups are automatically removed based on retention policy
- **Integrity Verification**: Backup integrity is checked before storage

### **3. Disaster Recovery**
- **Quick Restore**: One-click restore from any backup
- **Emergency Procedures**: Special tools for critical situations
- **System Health Monitoring**: Continuous monitoring of system status

## 📊 Monitoring Dashboard

### **Access the Security Dashboard**
1. Go to `https://your-app.onrender.com/admin/security_monitor.php`
2. Monitor real-time security statistics
3. Manage blocked IPs
4. View threat reports

### **Access the Backup Management**
1. Go to `https://your-app.onrender.com/admin/backup_management.php`
2. Create manual backups
3. Restore from backups
4. Manage backup retention

### **Access Emergency Recovery**
1. Go to `https://your-app.onrender.com/admin/emergency_recovery.php`
2. Use emergency tools when system is down
3. Quick system health checks
4. Emergency backup creation

## 🔧 Configuration for High Traffic

### **1. Database Optimization**
```sql
-- Add indexes for better performance
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_users_verified ON users(is_verified);
CREATE INDEX idx_activity_logs_recent ON activity_logs(created_at, action);
```

### **2. PHP Configuration**
```ini
; Increase limits for high traffic
max_execution_time = 300
memory_limit = 512M
upload_max_filesize = 10M
post_max_size = 10M
max_input_vars = 3000
```

### **3. Render Configuration**
```yaml
# render.yaml
services:
  - type: web
    name: psau-admission
    env: php
    plan: starter
    buildCommand: "composer install"
    startCommand: "php -S 0.0.0.0:$PORT"
    envVars:
      - key: ENCRYPTION_KEY
        sync: false
      - key: DB_HOST
        fromDatabase:
          name: psau-db
          property: host
      - key: DB_NAME
        fromDatabase:
          name: psau-db
          property: name
      - key: DB_USER
        fromDatabase:
          name: psau-db
          property: user
      - key: DB_PASS
        fromDatabase:
          name: psau-db
          property: password
      - key: DB_PORT
        fromDatabase:
          name: psau-db
          property: port
```

## 🚨 Emergency Procedures

### **If System is Hacked:**
1. **Immediate Actions:**
   - Go to Emergency Recovery dashboard
   - Create emergency backup
   - Block suspicious IPs
   - Force logout all users
   - Check security logs

2. **Investigation:**
   - Review activity logs
   - Check for data breaches
   - Identify attack vectors
   - Document findings

3. **Recovery:**
   - Restore from clean backup
   - Update security measures
   - Change all passwords
   - Notify users if necessary

### **If System Shuts Down:**
1. **Immediate Actions:**
   - Check Render dashboard for service status
   - Review error logs
   - Create emergency backup if possible
   - Check database connectivity

2. **Recovery Steps:**
   - Restart services
   - Restore from latest backup if needed
   - Verify data integrity
   - Test all functionality

3. **Prevention:**
   - Set up monitoring alerts
   - Implement health checks
   - Regular backup testing
   - Load balancing for high traffic

## 📈 Performance Monitoring

### **Key Metrics to Monitor:**
- **Response Time**: Should be under 2 seconds
- **Database Connections**: Monitor connection pool
- **Memory Usage**: Keep under 80% of limit
- **Disk Space**: Maintain at least 1GB free
- **Failed Requests**: Should be under 1%

### **Alert Thresholds:**
- **High CPU Usage**: > 80% for 5 minutes
- **High Memory Usage**: > 90% for 5 minutes
- **Failed Logins**: > 10 in 1 hour
- **Error Rate**: > 5% in 1 hour
- **Response Time**: > 5 seconds average

## 🔒 Security Best Practices

### **1. Regular Maintenance:**
- **Daily**: Check security dashboard
- **Weekly**: Review backup integrity
- **Monthly**: Update encryption keys
- **Quarterly**: Security audit

### **2. Access Control:**
- Use strong admin passwords
- Enable two-factor authentication
- Limit admin access to necessary personnel
- Regular access reviews

### **3. Data Protection:**
- All sensitive data encrypted
- Regular backup testing
- Secure backup storage
- Data retention policies

## 📞 Support & Maintenance

### **Regular Tasks:**
1. **Daily**: Check system health and security alerts
2. **Weekly**: Verify backup integrity and test restore
3. **Monthly**: Review security logs and update keys
4. **Quarterly**: Full security audit and penetration testing

### **Emergency Contacts:**
- **System Admin**: [Your Contact]
- **Database Admin**: [DB Admin Contact]
- **Hosting Provider**: Render.com Support
- **Security Team**: [Security Contact]

## 🎯 Success Metrics

### **System Reliability:**
- **Uptime**: 99.9% or higher
- **Backup Success Rate**: 100%
- **Recovery Time**: Under 1 hour
- **Data Loss**: Zero tolerance

### **Security Performance:**
- **Threat Detection**: Real-time
- **Response Time**: Under 5 minutes
- **False Positives**: Under 5%
- **Security Incidents**: Zero tolerance

This comprehensive setup ensures your PSAU Admission System can handle many applicants while maintaining security and reliability. The automated systems will protect against both technical failures and security threats.
