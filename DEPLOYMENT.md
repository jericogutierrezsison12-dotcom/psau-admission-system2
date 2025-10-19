# PSAU Admission System - Render Deployment Guide

This guide will help you deploy the PSAU Admission System to Render platform.

## Prerequisites

1. **Git Repository**: Your code should be in a Git repository (GitHub, GitLab, or Bitbucket)
2. **Render Account**: Sign up at [render.com](https://render.com)
3. **Firebase Project**: Ensure your Firebase project is properly configured

## Deployment Steps

### 1. Prepare Your Repository

Make sure your repository contains:
- `render.yaml` - Render configuration file
- `composer.json` - PHP dependencies
- `database/psau_admission_postgresql.sql` - PostgreSQL schema
- `scripts/init_db.sh` - Database initialization script
- `.gitignore` - Git ignore file

### 2. Create Render Services

#### Option A: Using render.yaml (Recommended)

1. Go to your Render dashboard
2. Click "New +" → "Blueprint"
3. Connect your Git repository
4. Render will automatically detect the `render.yaml` file
5. Click "Apply" to create all services

#### Option B: Manual Setup

##### Web Service
1. Go to your Render dashboard
2. Click "New +" → "Web Service"
3. Connect your Git repository
4. Configure:
   - **Name**: `psau-admission-system`
   - **Environment**: `PHP`
   - **Plan**: `Starter` (or higher)
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`

##### Database Service
1. Click "New +" → "PostgreSQL"
2. Configure:
   - **Name**: `psau-admission-db`
   - **Plan**: `Starter` (or higher)
   - **Database Name**: `psau_admission`

### 3. Environment Variables

Set the following environment variables in your web service:

#### Database Variables (Auto-configured if using render.yaml)
- `DB_TYPE`: `postgresql`
- `DB_HOST`: (Auto-configured from database service)
- `DB_NAME`: (Auto-configured from database service)
- `DB_USER`: (Auto-configured from database service)
- `DB_PASSWORD`: (Auto-configured from database service)
- `DB_PORT`: (Auto-configured from database service)

#### Firebase Variables
- `FIREBASE_API_KEY`: `AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8`
- `FIREBASE_AUTH_DOMAIN`: `psau-admission-system.firebaseapp.com`
- `FIREBASE_PROJECT_ID`: `psau-admission-system`
- `FIREBASE_STORAGE_BUCKET`: `psau-admission-system.appspot.com`
- `FIREBASE_MESSAGING_SENDER_ID`: `522448258958`
- `FIREBASE_APP_ID`: `1:522448258958:web:994b133a4f7b7f4c1b06df`
- `FIREBASE_EMAIL_FUNCTION_URL`: `https://sendemail-alsstt22ha-uc.a.run.app`

#### Application Variables
- `APP_ENV`: `production`
- `PHP_VERSION`: `8.2`

### 4. Database Initialization

After deployment, you need to initialize the database:

1. Go to your web service dashboard
2. Click on "Shell" tab
3. Run the database initialization script:
   ```bash
   ./scripts/init_db.sh
   ```

Alternatively, you can manually run the SQL file:
1. Go to your PostgreSQL service dashboard
2. Click on "Connect" → "External Connection"
3. Use a PostgreSQL client to connect and run `database/psau_admission_postgresql.sql`

### 5. File Permissions

Ensure the following directories are writable:
- `uploads/` - For file uploads
- `images/` - For image storage

You may need to create these directories if they don't exist:
```bash
mkdir -p uploads images
chmod 755 uploads images
```

### 6. Firebase Configuration

Make sure your Firebase project is properly configured:

1. **Authentication**: Enable Email/Password and Phone authentication
2. **Realtime Database**: Set up rules for your application
3. **Cloud Functions**: Deploy your email functions
4. **Storage**: Configure file upload rules

### 7. Testing Your Deployment

After deployment, test the following:

1. **Homepage**: Visit your Render URL
2. **User Registration**: Test user registration with Firebase
3. **Admin Login**: Test admin login functionality
4. **File Upload**: Test document upload functionality
5. **Database**: Verify data is being stored correctly

### 8. Monitoring and Maintenance

- **Logs**: Monitor application logs in Render dashboard
- **Database**: Monitor database performance and storage
- **Firebase**: Monitor Firebase usage and quotas
- **Updates**: Deploy updates by pushing to your Git repository

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check environment variables are set correctly
   - Verify database service is running
   - Check database initialization script ran successfully

2. **File Upload Issues**
   - Ensure uploads directory exists and is writable
   - Check file size limits in PHP configuration
   - Verify Firebase Storage rules

3. **Firebase Authentication Issues**
   - Check Firebase configuration in `firebase/config.php`
   - Verify Firebase project settings
   - Check browser console for JavaScript errors

4. **Build Failures**
   - Check composer.json dependencies
   - Verify PHP version compatibility
   - Check build logs for specific errors

### Support

For issues specific to:
- **Render Platform**: Check Render documentation or support
- **Firebase**: Check Firebase documentation or support
- **Application Code**: Review application logs and error messages

## Security Considerations

1. **Environment Variables**: Never commit sensitive data to Git
2. **Database Access**: Use strong passwords and limit access
3. **File Uploads**: Validate file types and sizes
4. **Firebase Rules**: Configure proper security rules
5. **HTTPS**: Render provides HTTPS by default

## Performance Optimization

1. **Database Indexes**: Ensure proper indexes are created
2. **Caching**: Implement caching where appropriate
3. **File Storage**: Consider using CDN for static files
4. **Database Queries**: Optimize database queries
5. **Monitoring**: Set up monitoring and alerting

## Backup Strategy

1. **Database**: Regular database backups
2. **Files**: Backup uploaded files
3. **Code**: Version control with Git
4. **Configuration**: Document all configuration changes

---

**Note**: This deployment guide assumes you're using the provided `render.yaml` configuration. Adjust the steps accordingly if you're setting up services manually.
