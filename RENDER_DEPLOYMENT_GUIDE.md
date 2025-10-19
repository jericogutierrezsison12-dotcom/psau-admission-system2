# PSAU Admission System - Complete Render Deployment Guide

## 🚀 Step-by-Step Deployment Instructions

### Step 1: Access Render Platform
1. Go to [render.com](https://render.com)
2. Sign up for a free account (if you don't have one)
3. Log in to your Render dashboard

### Step 2: Create New Blueprint
1. Click "New +" in the top right corner
2. Select "Blueprint" from the dropdown
3. Connect your GitHub account if not already connected
4. Select your repository: `jericogutierrezsison12-dotcom/psau-admission-system`

### Step 3: Review Blueprint Configuration
Render will automatically detect your `render.yaml` file and show:
- **Web Service**: `psau-admission-system` (PHP)
- **Database Service**: `psau-admission-db` (PostgreSQL)

### Step 4: Deploy Services
1. Review the configuration (everything should be pre-configured)
2. Click "Apply" to create both services
3. Wait for deployment to complete (5-10 minutes)

### Step 5: Initialize Database
After deployment completes:

1. Go to your web service dashboard
2. Click on the "Shell" tab
3. Run the database initialization script:
   ```bash
   ./scripts/init_db.sh
   ```

### Step 6: Test Your Application
1. Visit your Render URL (e.g., `https://psau-admission-system.onrender.com`)
2. Test the following:
   - Homepage loads
   - User registration
   - Admin login (admin@psau.edu.ph / password)
   - File upload functionality

## 🔧 Configuration Details

### Environment Variables (Auto-configured)
- `DB_TYPE`: postgresql
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_PORT`: From database service
- `FIREBASE_API_KEY`: AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8
- `FIREBASE_AUTH_DOMAIN`: psau-admission-system.firebaseapp.com
- `FIREBASE_PROJECT_ID`: psau-admission-system
- `FIREBASE_STORAGE_BUCKET`: psau-admission-system.appspot.com
- `FIREBASE_MESSAGING_SENDER_ID`: 522448258958
- `FIREBASE_APP_ID`: 1:522448258958:web:994b133a4f7b7f4c1b06df
- `FIREBASE_EMAIL_FUNCTION_URL`: https://sendemail-alsstt22ha-uc.a.run.app
- `APP_ENV`: production
- `PHP_VERSION`: 8.2

### Database Schema
- PostgreSQL database with all required tables
- Sample data for testing
- Proper indexes for performance
- Triggers for automatic timestamp updates

### File Structure
- `public/` - Web root directory
- `uploads/` - File upload directory
- `images/` - Image storage directory
- `admin/` - Admin panel
- `includes/` - PHP includes and functions
- `firebase/` - Firebase configuration

## 🛠️ Troubleshooting

### Common Issues and Solutions

1. **Build Fails**
   - Check PHP version compatibility
   - Verify composer.json dependencies
   - Check build logs for specific errors

2. **Database Connection Error**
   - Verify environment variables are set
   - Check database service is running
   - Ensure database initialization script ran

3. **File Upload Issues**
   - Check uploads directory permissions
   - Verify file size limits
   - Check PHP configuration

4. **Firebase Errors**
   - Verify Firebase configuration
   - Check API keys and project settings
   - Review browser console for JavaScript errors

### Getting Help
- Check Render logs in the dashboard
- Review application logs for errors
- Check Firebase console for issues
- Contact support if needed

## 📊 Monitoring and Maintenance

### Regular Checks
- Monitor application performance
- Check database usage
- Review Firebase quotas
- Monitor error logs

### Updates
- Push changes to GitHub
- Render will auto-deploy
- Test after updates
- Monitor for issues

## 🔐 Security Notes

- All sensitive data in environment variables
- Database credentials managed by Render
- Firebase configuration secured
- File uploads validated
- Admin access protected

## 📞 Support Resources

- **Render Documentation**: https://render.com/docs
- **Firebase Documentation**: https://firebase.google.com/docs
- **Project Repository**: https://github.com/jericogutierrezsison12-dotcom/psau-admission-system
- **Deployment Guide**: This file

---

**Ready to Deploy!** Follow the steps above to get your PSAU Admission System live on Render.
