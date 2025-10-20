# 🚀 Complete InfinityFree Deployment Guide

## Step-by-Step Instructions to Deploy PSAU Admission System on InfinityFree

### 📋 **Prerequisites**
- InfinityFree account (free at [infinityfree.net](https://infinityfree.net))
- Your project files from GitHub
- Basic understanding of file uploads

---

## **Step 1: Create InfinityFree Account**

1. **Visit InfinityFree**: Go to [infinityfree.net](https://infinityfree.net)
2. **Sign Up**: Click "Sign Up" and create your free account
3. **Verify Email**: Check your email and verify your account
4. **Login**: Access your control panel

---

## **Step 2: Create MySQL Database**

1. **Access Control Panel**: Login to your InfinityFree account
2. **Go to MySQL Databases**: Click on "MySQL Databases" in the control panel
3. **Create Database**: Click "Create Database"
4. **Set Database Details**:
   - Database Name: Choose a name (e.g., `psau_admission`)
   - Username: Choose a username
   - Password: Set a strong password
5. **Note Down These Details** (you'll need them later):
   ```
   Host: sqlXXX.infinityfree.com (your server number)
   Database Name: if0_XXXXXXXX (your database name)
   Username: if0_XXXXXXXX (your username)
   Password: your_password_here
   ```

---

## **Step 3: Download Your Project**

### **Option A: Download from GitHub**
1. Go to: https://github.com/jericogutierrezsison12-dotcom/psau-admission-system.git
2. Click the green **"Code"** button
3. Click **"Download ZIP"**
4. Extract the ZIP file to your computer

### **Option B: Clone with Git**
```bash
git clone https://github.com/jericogutierrezsison12-dotcom/psau-admission-system.git
```

---

## **Step 4: Configure Database Connection**

1. **Open the project folder** on your computer
2. **Navigate to**: `includes/db_connect_infinity.php`
3. **Update the database credentials**:

```php
// Replace these with your actual InfinityFree database details
$host = 'sqlXXX.infinityfree.com'; // Replace XXX with your server number
$dbname = 'if0_XXXXXXXX'; // Replace with your database name
$username = 'if0_XXXXXXXX'; // Replace with your database username
$password = 'your_password_here'; // Replace with your database password
```

4. **Rename the file**: Rename `db_connect_infinity.php` to `db_connect.php` (replace the existing one)

---

## **Step 5: Upload Files to InfinityFree**

### **Method 1: Using File Manager (Recommended)**
1. **Access File Manager**: In your InfinityFree control panel, click "File Manager"
2. **Navigate to htdocs**: Go to the `htdocs` directory
3. **Upload Files**: 
   - Select all files from your project folder
   - Upload them to the `htdocs` directory
   - Maintain the folder structure

### **Method 2: Using FTP Client**
1. **Get FTP Credentials**: In your control panel, go to "FTP Accounts"
2. **Use FTP Client**: Use FileZilla or similar FTP client
3. **Connect**: Use your FTP credentials to connect
4. **Upload**: Upload all files to the `htdocs` directory

---

## **Step 6: Import Database Schema**

1. **Access phpMyAdmin**: In your control panel, click "phpMyAdmin"
2. **Select Database**: Choose your database from the left sidebar
3. **Import SQL File**: 
   - Click the "Import" tab
   - Click "Choose File"
   - Select the file: `database/psau_admission.sql`
   - Click "Go" to import

---

## **Step 7: Set File Permissions**

1. **Access File Manager**: Go to File Manager in your control panel
2. **Set Permissions**: 
   - Right-click on the `uploads` folder
   - Set permissions to `755` (readable and writable)
   - Ensure all PHP files have `644` permissions

---

## **Step 8: Configure Firebase (Optional)**

1. **Update Firebase Config**: Edit `firebase/config.php`
2. **Update Email Function URL**: If you have Firebase Cloud Functions
3. **Test Firebase Integration**: Ensure Firebase services are working

---

## **Step 9: Test Your Application**

1. **Visit Your Domain**: Go to your InfinityFree domain (e.g., `yourdomain.infinityfreeapp.com`)
2. **Test Homepage**: Check if the main page loads
3. **Test Registration**: Try registering a new user
4. **Test Admin Login**: Access the admin panel
5. **Test File Uploads**: Try uploading a document

---

## **Step 10: Final Configuration**

### **Update Production Config**
1. **Edit**: `config_production.php`
2. **Update BASE_URL**: Set your actual domain
3. **Update Email Settings**: Configure SMTP if needed

### **Security Settings**
1. **Remove Test Files**: Delete any test or development files
2. **Check File Permissions**: Ensure sensitive files are not web-accessible
3. **Enable HTTPS**: If using custom domain, enable SSL

---

## **🔧 Troubleshooting Common Issues**

### **Database Connection Error**
- Double-check your database credentials
- Ensure the database exists in phpMyAdmin
- Verify the host name is correct

### **File Upload Issues**
- Check uploads directory permissions (should be 755)
- Verify file size limits in PHP settings
- Ensure the uploads directory exists

### **Firebase Integration Issues**
- Check Firebase configuration
- Verify API keys are correct
- Test Firebase functions separately

### **Page Not Loading**
- Check file permissions
- Verify all files are uploaded correctly
- Check for PHP errors in the error log

---

## **📞 Support Resources**

- **InfinityFree Documentation**: [infinityfree.net/support](https://infinityfree.net/support)
- **Firebase Documentation**: [firebase.google.com/docs](https://firebase.google.com/docs)
- **PHP Documentation**: [php.net/docs](https://php.net/docs)

---

## **✅ Deployment Checklist**

- [ ] InfinityFree account created
- [ ] MySQL database created
- [ ] Project files downloaded
- [ ] Database credentials updated
- [ ] Files uploaded to htdocs
- [ ] Database schema imported
- [ ] File permissions set correctly
- [ ] Application tested
- [ ] Firebase configured (if needed)
- [ ] Security settings applied

---

## **🎉 Congratulations!**

Your PSAU Admission System should now be live on InfinityFree! 

**Your website URL**: `https://yourdomain.infinityfreeapp.com`

Remember to:
- Regularly backup your database
- Monitor your application for errors
- Keep your Firebase configuration updated
- Test all features after deployment

---

**Need Help?** Check the troubleshooting section above or refer to the InfinityFree support documentation.
