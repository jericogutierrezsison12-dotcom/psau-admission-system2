const functions = require('firebase-functions');
const admin = require('firebase-admin');
const axios = require('axios');

// Initialize Firebase Admin
admin.initializeApp();

/**
 * Cloud Function to trigger email sending via the external Firebase email service
 * This function logs email requests to Firestore and sends them using an external service
 */
exports.sendEmail = functions.https.onRequest(async (req, res) => {
  // Enable CORS
  res.set('Access-Control-Allow-Origin', '*');
  res.set('Access-Control-Allow-Methods', 'POST');
  res.set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  
  // Handle preflight requests
  if (req.method === 'OPTIONS') {
    res.status(204).send('');
    return;
  }
  
  // Only accept POST requests
  if (req.method !== 'POST') {
    res.status(405).send('Method Not Allowed');
    return;
  }
  
  try {
    // Validate request body
    const { to, subject, html, from, cc, replyTo } = req.body;
    
    if (!to || !subject || !html) {
      res.status(400).send({ error: 'Missing required fields (to, subject, html)' });
      return;
    }
    
    // Log the email request in Firestore
    await admin.firestore().collection('mail_requests').add({
      to: to,
      subject: subject,
      html: html,
      from: from || 'PSAU Admissions <jericogutierrezsison12@gmail.com>',
      cc: cc || null,
      replyTo: replyTo || null,
      timestamp: admin.firestore.FieldValue.serverTimestamp(),
      status: 'pending'
    });
    
    // Forward to the external email service
    // This is now handled by the Firebase Cloud Function in firebase_email.php
    
    // Log success
    console.log(`Email request logged successfully for: ${to}`);
    
    // Send success response
    res.status(200).send({ success: true, message: 'Email request processed successfully' });
  } catch (error) {
    // Log error
    console.error('Error processing email request:', error);
    
    // Send error response
    res.status(500).send({ 
      error: 'Failed to process email request', 
      message: error.message,
      stack: error.stack
    });
  }
});

/**
 * Function to log welcome emails when new user registers
 * This is triggered by Firestore document creation
 */
exports.logWelcomeEmail = functions.firestore
  .document('users/{userId}')
  .onCreate(async (snap, context) => {
    try {
      const userData = snap.data();
      
      if (!userData.email) {
        console.log('No email address provided for new user');
        return null;
      }
      
      // Log the welcome email request in Firestore
      await admin.firestore().collection('mail_requests').add({
        to: userData.email,
        subject: 'Welcome to PSAU Admission System',
        html: `
          <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #2E7D32; color: white; padding: 20px; text-align: center;">
              <h2>Pampanga State Agricultural University</h2>
            </div>
            <div style="padding: 20px; border: 1px solid #ddd;">
              <p>Dear ${userData.first_name || 'Student'},</p>
              <p>Welcome to the PSAU Admission System!</p>
              <p>Your account has been created successfully. You can now log in and start your application process.</p>
              <p>Thank you for choosing Pampanga State Agricultural University.</p>
              <p>Best regards,<br>PSAU Admissions Team</p>
            </div>
            <div style="background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;">
              <p>&copy; ${new Date().getFullYear()} PSAU Admission System. All rights reserved.</p>
            </div>
          </div>
        `,
        timestamp: admin.firestore.FieldValue.serverTimestamp(),
        status: 'pending',
        userId: context.params.userId
      });
      
      console.log(`Welcome email request logged for ${userData.email}`);
      return null;
    } catch (error) {
      console.error('Error logging welcome email request:', error);
      return null;
    }
  });

/**
 * Log application submission email request
 */
exports.logApplicationSubmissionEmail = functions.https.onCall(async (data, context) => {
  try {
    // Security check
    if (!context.auth) {
      throw new functions.https.HttpsError(
        'unauthenticated',
        'The function must be called while authenticated.'
      );
    }

    const { email, firstName, lastName, controlNumber } = data;
    
    if (!email || !firstName || !lastName || !controlNumber) {
      throw new functions.https.HttpsError(
        'invalid-argument',
        'Missing required parameters'
      );
    }

    // Log the email request in Firestore
    await admin.firestore().collection('mail_requests').add({
      to: email,
      subject: 'Application Submitted - PSAU Admission',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
          <h2 style="color: #34495e; text-align: center;">Pampanga State Agricultural University</h2>
          <h3 style="color: #3498db; text-align: center;">Admission System</h3>
          <p>Dear ${firstName} ${lastName},</p>
          <p>Your application with control number <strong>${controlNumber}</strong> has been successfully submitted to our system.</p>
          <p>Our team will review your application and documents. You will be notified once your application has been verified.</p>
          <p>You can check the status of your application anytime by logging into your account.</p>
          <p style="margin-top: 20px;">Thank you for applying to Pampanga State Agricultural University!</p>
          <p>Best regards,<br/>PSAU Admission Team</p>
        </div>
      `,
      timestamp: admin.firestore.FieldValue.serverTimestamp(),
      status: 'pending',
      userId: context.auth.uid
    });
    
    return { success: true, message: 'Application submission email request logged successfully' };
  } catch (error) {
    console.error('Error logging application submission email request:', error);
    throw new functions.https.HttpsError('internal', 'Error processing application email request', error);
  }
});

/**
 * Log application verification/rejection email request
 */
exports.logApplicationStatusEmail = functions.https.onCall(async (data, context) => {
  try {
    // Security check - admin only
    if (!context.auth || !context.auth.token.admin) {
      throw new functions.https.HttpsError(
        'permission-denied',
        'Only admins can call this function.'
      );
    }

    const { email, firstName, lastName, controlNumber, isApproved, remarks } = data;
    
    const status = isApproved ? 'Verified' : 'Rejected';
    const subject = isApproved ? 
      'Application Verified - PSAU Admission' : 
      'Application Status Update - PSAU Admission';
    
    // Prepare email content
    const emailContent = isApproved ? 
      `
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #34495e; text-align: center;">Pampanga State Agricultural University</h2>
        <h3 style="color: #27ae60; text-align: center;">Application Verified</h3>
        <p>Dear ${firstName} ${lastName},</p>
        <p>Your application with control number <strong>${controlNumber}</strong> has been verified and approved.</p>
        <p>You are now eligible to proceed to the next steps in the admission process.</p>
        <p>Please log into your account to check your application status and further instructions.</p>
        <p style="margin-top: 20px;">Thank you for choosing Pampanga State Agricultural University!</p>
        <p>Best regards,<br/>PSAU Admission Team</p>
      </div>
      ` : 
      `
      <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #34495e; text-align: center;">Pampanga State Agricultural University</h2>
        <h3 style="color: #e74c3c; text-align: center;">Application Update</h3>
        <p>Dear ${firstName} ${lastName},</p>
        <p>Your application with control number <strong>${controlNumber}</strong> requires your attention.</p>
        <p><strong>Remarks from Admissions:</strong> ${remarks || 'Please review and update your application'}</p>
        <p>Please log into your account to make the necessary updates to your application.</p>
        <p style="margin-top: 20px;">Thank you for your interest in Pampanga State Agricultural University!</p>
        <p>Best regards,<br/>PSAU Admission Team</p>
      </div>
      `;

    // Log email request to Firestore
    await admin.firestore().collection('mail_requests').add({
      to: email,
      subject: subject,
      html: emailContent,
      timestamp: admin.firestore.FieldValue.serverTimestamp(),
      status: 'pending',
      adminId: context.auth.uid,
      appStatus: status
    });
    
    return { success: true, message: `Application ${status.toLowerCase()} email request logged successfully` };
  } catch (error) {
    console.error('Error logging application status email request:', error);
    throw new functions.https.HttpsError('internal', 'Error processing application status email request', error);
  }
});

/**
 * API Proxy to forward requests to Render backend
 * This function acts as a proxy between Firebase Hosting and Render backend
 */
exports.apiProxy = functions.https.onRequest(async (req, res) => {
  // Enable CORS
  res.set('Access-Control-Allow-Origin', '*');
  res.set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  
  // Handle preflight requests
  if (req.method === 'OPTIONS') {
    res.status(204).send('');
    return;
  }
  
  try {
    // Get Render API URL from environment or use default
    const RENDER_API_URL = functions.config().render?.api_url || 'https://psau-backend-api.onrender.com';
    
    // Forward the request to Render backend
    const response = await axios({
      method: req.method,
      url: `${RENDER_API_URL}${req.path}`,
      data: req.body,
      headers: {
        ...req.headers,
        'host': undefined // Remove host header to avoid conflicts
      },
      timeout: 30000 // 30 second timeout
    });
    
    // Forward the response back to the client
    res.status(response.status).send(response.data);
    
  } catch (error) {
    console.error('API Proxy Error:', error.message);
    
    if (error.code === 'ECONNREFUSED' || error.code === 'ETIMEDOUT') {
      res.status(503).send({ 
        error: 'Backend service unavailable',
        message: 'The AI services are temporarily unavailable. Please try again later.'
      });
    } else if (error.response) {
      // Forward error response from backend
      res.status(error.response.status).send(error.response.data);
    } else {
      res.status(500).send({ 
        error: 'Internal server error',
        message: 'An unexpected error occurred'
      });
    }
  }
});

/**
 * System Health Check
 * Checks the health of both Firebase and Render services
 */
exports.systemHealth = functions.https.onRequest(async (req, res) => {
  res.set('Access-Control-Allow-Origin', '*');
  
  try {
    const RENDER_API_URL = functions.config().render?.api_url || 'https://psau-backend-api.onrender.com';
    
    // Check Firebase services
    const firebaseHealth = {
      functions: 'healthy',
      firestore: 'healthy',
      hosting: 'healthy'
    };
    
    // Check Render backend
    let renderHealth = 'unknown';
    try {
      const response = await axios.get(`${RENDER_API_URL}/health`, { timeout: 5000 });
      renderHealth = response.data.status || 'healthy';
    } catch (error) {
      renderHealth = 'unhealthy';
    }
    
    res.status(200).send({
      status: 'operational',
      timestamp: new Date().toISOString(),
      services: {
        firebase: firebaseHealth,
        render: renderHealth
      }
    });
    
  } catch (error) {
    console.error('Health check error:', error);
    res.status(500).send({
      status: 'error',
      message: 'Health check failed'
    });
  }
}); 