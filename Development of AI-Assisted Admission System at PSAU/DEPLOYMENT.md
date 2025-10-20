# PSAU Admission System - InfinityFree Deployment Guide

This guide will help you deploy the PSAU Admission System to InfinityFree hosting.

## Prerequisites

1. **InfinityFree Account**: Sign up at [infinityfree.net](https://infinityfree.net)
2. **Domain**: You can use the free subdomain provided by InfinityFree
3. **Database**: Create a MySQL database in your InfinityFree control panel

## Step 1: Database Setup

### Create Database in InfinityFree Control Panel

1. Log into your InfinityFree control panel
2. Go to "MySQL Databases"
3. Create a new database (note down the database name, username, and password)
4. Import the database schema:
   - Go to phpMyAdmin in your control panel
   - Select your database
   - Import the file: `database/psau_admission.sql`

### Update Database Configuration

1. Copy `includes/db_connect_infinity.php` to `includes/db_connect.php`
2. Update the database credentials in `includes/db_connect.php`:
   ```php
   $host = 'sqlXXX.infinityfree.com'; // Your server number
   $dbname = 'if0_XXXXXXXX'; // Your database name
   $username = 'if0_XXXXXXXX'; // Your database username
   $password = 'your_password_here'; // Your database password
   ```

## Step 2: File Upload

### Upload Files via File Manager

1. Log into your InfinityFree control panel
2. Go to "File Manager"
3. Navigate to the `htdocs` directory
4. Upload all project files (except vendor directory initially)
5. Extract/upload files maintaining the directory structure

### Alternative: FTP Upload

1. Use an FTP client like FileZilla
2. Connect using your FTP credentials from the control panel
3. Upload files to the `htdocs` directory

## Step 3: Composer Dependencies

Since InfinityFree doesn't support Composer directly, you need to upload the vendor directory:

1. On your local machine, run: `composer install --no-dev`
2. Upload the entire `vendor` directory to your InfinityFree hosting
3. Ensure the `vendor/autoload.php` file is accessible

## Step 4: File Permissions

Set the following permissions:
- `uploads/` directory: 755 (readable and writable)
- All PHP files: 644
- All directories: 755

## Step 5: Firebase Configuration

### Update Firebase Config

1. Update `firebase/config.php` with your production Firebase project details
2. Ensure your Firebase project is configured for production
3. Update the email function URL if needed

### Firebase Cloud Functions

1. Deploy your Firebase functions to production
2. Update the function URLs in your configuration
3. Test email functionality

## Step 6: Environment Configuration

### Create Production Config

Create a `config_production.php` file:

```php
<?php
// Production environment settings
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('BASE_URL', 'https://yourdomain.infinityfreeapp.com');

// Disable error display in production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
?>
```

## Step 7: Testing

### Test Basic Functionality

1. Visit your domain to test the homepage
2. Test user registration
3. Test admin login
4. Test file uploads
5. Test database operations

### Known Limitations on InfinityFree

1. **Python/OCR**: The OCR functionality for PDF processing may not work due to Python limitations
2. **Cron Jobs**: Limited cron job support
3. **File Size Limits**: Check InfinityFree's file upload limits
4. **Execution Time**: PHP execution time limits may affect large operations

## Step 8: Security Considerations

### Production Security

1. Remove or secure any test/development files
2. Ensure sensitive configuration files are not accessible via web
3. Set proper file permissions
4. Enable HTTPS if using a custom domain

### Database Security

1. Use strong database passwords
2. Regularly backup your database
3. Monitor database usage

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Verify database credentials
   - Check if database exists
   - Ensure MySQL service is running

2. **File Upload Issues**:
   - Check file permissions
   - Verify upload directory exists
   - Check file size limits

3. **Firebase Integration Issues**:
   - Verify Firebase configuration
   - Check network connectivity
   - Test Firebase functions

4. **Composer Dependencies**:
   - Ensure vendor directory is uploaded
   - Check autoload.php path
   - Verify all required packages are included

### Support

- InfinityFree Documentation: [infinityfree.net/support](https://infinityfree.net/support)
- Firebase Documentation: [firebase.google.com/docs](https://firebase.google.com/docs)

## Maintenance

### Regular Tasks

1. **Database Backups**: Regular database backups
2. **File Cleanup**: Clean up old uploaded files
3. **Log Monitoring**: Monitor error logs
4. **Security Updates**: Keep dependencies updated

### Monitoring

1. Monitor disk usage
2. Check database usage
3. Monitor email delivery
4. Track user registrations and activity

## Performance Optimization

### For InfinityFree

1. **Optimize Images**: Compress images before upload
2. **Database Queries**: Optimize database queries
3. **Caching**: Implement simple file-based caching
4. **File Management**: Regular cleanup of temporary files

## Backup Strategy

1. **Database**: Regular MySQL dumps
2. **Files**: Regular file backups
3. **Configuration**: Backup configuration files
4. **Firebase**: Export Firebase data if needed

---

**Note**: This deployment guide is specifically tailored for InfinityFree hosting. Some features may have limitations due to the shared hosting environment.
