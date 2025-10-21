# PSAU Admission System - Render Deployment Guide

This guide will help you deploy the PSAU Admission System to Render.com.

## Prerequisites

1. A Render.com account
2. A GitHub account with your project repository
3. Firebase project setup (already configured)

## Step 1: Prepare Your Repository

1. Push your code to GitHub:
   ```bash
   git add .
   git commit -m "Prepare for Render deployment"
   git push origin main
   ```

## Step 2: Deploy to Render

### Option A: Using Render Dashboard (Recommended)

1. Go to [Render Dashboard](https://dashboard.render.com/)
2. Click "New +" and select "Web Service"
3. Connect your GitHub repository
4. Configure the service:
   - **Name**: `psau-admission-system`
   - **Environment**: `PHP`
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `vendor/bin/heroku-php-apache2 public/`
   - **Root Directory**: Leave empty (uses root)

### Option B: Using render.yaml (Infrastructure as Code)

1. Use the provided `render.yaml` file
2. In Render Dashboard, click "New +" and select "Blueprint"
3. Connect your repository
4. Render will automatically detect and use the `render.yaml` configuration

## Step 3: Database Setup

1. In Render Dashboard, create a new PostgreSQL database:
   - **Name**: `psau-db`
   - **Plan**: Free tier (or higher for production)
   - **Database Name**: `psau_admission`

2. Import your database schema:
   - Go to your database dashboard
   - Use the SQL editor or connect via external tool
   - Run the SQL from `database/psau_admission.sql`

## Step 4: Environment Variables

Set the following environment variables in your Render service:

### Database Variables (Auto-configured if using render.yaml)
- `DB_HOST` - From database service
- `DB_NAME` - From database service  
- `DB_USER` - From database service
- `DB_PASS` - From database service

### Firebase Variables
- `FIREBASE_API_KEY`: `AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8`
- `FIREBASE_AUTH_DOMAIN`: `psau-admission-system.firebaseapp.com`
- `FIREBASE_PROJECT_ID`: `psau-admission-system`
- `FIREBASE_STORAGE_BUCKET`: `psau-admission-system.appspot.com`
- `FIREBASE_MESSAGING_SENDER_ID`: `522448258958`
- `FIREBASE_APP_ID`: `1:522448258958:web:994b133a4f7b7f4c1b06df`
- `FIREBASE_EMAIL_FUNCTION_URL`: `https://sendemail-alsstt22ha-uc.a.run.app`

### Other Variables
- `ENVIRONMENT`: `production`

## Step 5: File Permissions

Make sure the following directories are writable:
- `uploads/` - For file uploads
- `images/` - For image storage

Render handles this automatically, but ensure your code doesn't have hardcoded paths.

## Step 6: Firebase Configuration

1. Update your Firebase project settings if needed
2. Ensure your Firebase Cloud Functions are deployed
3. Verify the email function URL is correct

## Step 7: Testing

1. Visit your deployed URL
2. Test user registration
3. Test admin login
4. Verify file uploads work
5. Check email notifications

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify environment variables are set correctly
   - Check database service is running
   - Ensure database schema is imported

2. **File Upload Issues**:
   - Check directory permissions
   - Verify upload limits in PHP settings

3. **Firebase Errors**:
   - Verify Firebase configuration
   - Check API keys are correct
   - Ensure Cloud Functions are deployed

4. **Build Failures**:
   - Check composer.json dependencies
   - Verify PHP version compatibility
   - Review build logs for specific errors

### Logs

- View application logs in Render Dashboard
- Check build logs for deployment issues
- Monitor database logs for connection problems

## Production Considerations

1. **Security**:
   - Use strong passwords for database
   - Enable HTTPS (automatic with Render)
   - Regular security updates

2. **Performance**:
   - Consider upgrading to paid plans for better performance
   - Implement caching strategies
   - Optimize database queries

3. **Monitoring**:
   - Set up monitoring and alerts
   - Regular backups of database
   - Monitor resource usage

## Support

For issues specific to this deployment:
1. Check Render documentation
2. Review application logs
3. Verify all environment variables are set correctly

## Cost Estimation

- **Free Tier**: $0/month (with limitations)
- **Starter Plan**: $7/month (recommended for production)
- **Database**: Free tier available

Note: Free tier has limitations on build time and sleep after inactivity.