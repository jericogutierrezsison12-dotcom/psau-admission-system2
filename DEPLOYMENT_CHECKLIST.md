# PSAU Admission System - Render Deployment Checklist

## Pre-Deployment Checklist ✅

### Code Preparation
- [x] Updated database connection to support PostgreSQL
- [x] Created PostgreSQL-compatible database schema
- [x] Updated Firebase configuration to use environment variables
- [x] Created render.yaml configuration file
- [x] Updated composer.json with required dependencies
- [x] Created proper .gitignore file
- [x] Added database initialization script
- [x] Created deployment documentation
- [x] Committed all changes to Git
- [x] Pushed changes to remote repository

### Files Created/Modified
- [x] `render.yaml` - Render deployment configuration
- [x] `database/psau_admission_postgresql.sql` - PostgreSQL schema
- [x] `scripts/init_db.sh` - Database initialization script
- [x] `includes/db_connect.php` - Updated for environment variables
- [x] `firebase/config.php` - Updated for environment variables
- [x] `composer.json` - Added PostgreSQL extensions
- [x] `.gitignore` - Proper ignore patterns
- [x] `DEPLOYMENT.md` - Deployment guide
- [x] `uploads/.gitkeep` - Directory placeholder
- [x] `images/.gitkeep` - Directory placeholder

## Render Deployment Steps

### 1. Create Render Account
1. Go to [render.com](https://render.com)
2. Sign up for a free account
3. Verify your email address

### 2. Connect Git Repository
1. In Render dashboard, click "New +"
2. Select "Blueprint" (recommended) or "Web Service"
3. Connect your GitHub repository: `jericogutierrezsison12-dotcom/psau-admission-system`
4. Select the main branch

### 3. Deploy Using Blueprint (Recommended)
1. Render will detect the `render.yaml` file
2. Review the configuration:
   - **Web Service**: `psau-admission-system`
   - **Database Service**: `psau-admission-db` (PostgreSQL)
3. Click "Apply" to create all services

### 4. Manual Service Creation (Alternative)
If not using Blueprint:

#### Web Service
- **Name**: `psau-admission-system`
- **Environment**: `PHP`
- **Plan**: `Starter` (free tier)
- **Build Command**: `composer install --no-dev --optimize-autoloader`
- **Start Command**: `php -S 0.0.0.0:$PORT -t public`

#### Database Service
- **Name**: `psau-admission-db`
- **Environment**: `PostgreSQL`
- **Plan**: `Starter` (free tier)
- **Database Name**: `psau_admission`

### 5. Environment Variables
The following variables are automatically configured by render.yaml:
- `DB_TYPE`: `postgresql`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_PORT` (from database service)
- `FIREBASE_API_KEY`: `AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8`
- `FIREBASE_AUTH_DOMAIN`: `psau-admission-system.firebaseapp.com`
- `FIREBASE_PROJECT_ID`: `psau-admission-system`
- `FIREBASE_STORAGE_BUCKET`: `psau-admission-system.appspot.com`
- `FIREBASE_MESSAGING_SENDER_ID`: `522448258958`
- `FIREBASE_APP_ID`: `1:522448258958:web:994b133a4f7b7f4c1b06df`
- `FIREBASE_EMAIL_FUNCTION_URL`: `https://sendemail-alsstt22ha-uc.a.run.app`
- `APP_ENV`: `production`
- `PHP_VERSION`: `8.2`

### 6. Database Initialization
After deployment:
1. Go to your web service dashboard
2. Click on "Shell" tab
3. Run: `./scripts/init_db.sh`
4. Or manually run the SQL file in your PostgreSQL service

### 7. Test Deployment
1. Visit your Render URL (e.g., `https://psau-admission-system.onrender.com`)
2. Test user registration
3. Test admin login (admin@psau.edu.ph / password)
4. Test file upload functionality
5. Check Firebase integration

## Post-Deployment Checklist

### Functionality Tests
- [ ] Homepage loads correctly
- [ ] User registration works
- [ ] Firebase authentication works
- [ ] Admin login works
- [ ] File upload works
- [ ] Database operations work
- [ ] Email notifications work
- [ ] Application form submission works

### Performance Checks
- [ ] Page load times are acceptable
- [ ] Database queries are optimized
- [ ] File uploads work within limits
- [ ] Firebase quota usage is reasonable

### Security Verification
- [ ] Environment variables are properly set
- [ ] Database credentials are secure
- [ ] Firebase rules are configured
- [ ] File upload validation works
- [ ] Admin access is restricted

## Troubleshooting

### Common Issues
1. **Build Failures**: Check composer.json dependencies
2. **Database Connection**: Verify environment variables
3. **File Uploads**: Check directory permissions
4. **Firebase Errors**: Verify configuration and quotas
5. **Performance Issues**: Monitor resource usage

### Support Resources
- Render Documentation: https://render.com/docs
- Firebase Documentation: https://firebase.google.com/docs
- Project Repository: https://github.com/jericogutierrezsison12-dotcom/psau-admission-system

## Maintenance

### Regular Tasks
- Monitor application logs
- Check database performance
- Review Firebase usage
- Update dependencies as needed
- Backup database regularly

### Updates
- Push changes to Git repository
- Render will automatically redeploy
- Test functionality after updates
- Monitor for any issues

---

**Deployment URL**: Will be provided by Render after successful deployment
**Admin Credentials**: admin@psau.edu.ph / password (change after first login)
**Database**: PostgreSQL on Render (managed service)
**Firebase**: Existing project configuration maintained
