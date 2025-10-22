# Render Deployment Configuration for PSAU Admission System

## Environment Variables for Render

Set these environment variables in your Render dashboard:

### Firebase Configuration (Optional - defaults will be used if not set)
```
FIREBASE_API_KEY=AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8
FIREBASE_AUTH_DOMAIN=psau-admission-system.firebaseapp.com
FIREBASE_PROJECT_ID=psau-admission-system
FIREBASE_STORAGE_BUCKET=psau-admission-system.appspot.com
FIREBASE_MESSAGING_SENDER_ID=522448258958
FIREBASE_APP_ID=1:522448258958:web:994b133a4f7b7f4c1b06df
FIREBASE_EMAIL_FUNCTION_URL=https://sendemail-alsstt22ha-uc.a.run.app
```

### Database Configuration
```
DB_HOST=your-database-host
DB_NAME=your-database-name
DB_USER=your-database-user
DB_PASS=your-database-password
```

### reCAPTCHA Configuration
```
RECAPTCHA_SITE_KEY=your-recaptcha-site-key
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key
```

## Render Service Configuration

### Build Command
```bash
composer install --no-dev --optimize-autoloader
```

### Start Command
```bash
php -S 0.0.0.0:$PORT -t public
```

### Environment
- **Runtime**: PHP 8.2+
- **Build Command**: `composer install --no-dev --optimize-autoloader`
- **Start Command**: `php -S 0.0.0.0:$PORT -t public`

## Features Compatible with Render

✅ **Forgot Password with Email OTP**
- Environment-aware configuration
- Production-ready error handling
- Clean JSON responses
- Firebase email integration
- reCAPTCHA protection

✅ **All Existing Features**
- User registration with email OTP
- Login system
- Admin dashboard
- Application management
- Course management
- Exam scheduling

## Testing on Render

1. Deploy your application to Render
2. Set up the environment variables
3. Test the forgot password flow:
   - Go to `/public/forgot_password.php`
   - Enter a valid email address
   - Complete reCAPTCHA verification
   - Check email for OTP
   - Enter OTP and reset password

## Troubleshooting

### Common Issues:
1. **JSON parsing errors**: Fixed with proper error handling
2. **Firebase email failures**: OTP is logged for debugging
3. **Environment detection**: Automatically detects Render environment
4. **Error reporting**: Different settings for production vs development

### Logs:
- Check Render logs for any errors
- Firebase email errors are logged
- OTP codes are logged for debugging if email fails
