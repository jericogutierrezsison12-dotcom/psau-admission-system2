const functions = require('firebase-functions');
const nodemailer = require('nodemailer');

// Create a transporter using Gmail
const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: 'jericogutierrezsison12@gmail.com',
        pass: 'crsh iejc lhwz gasu'
    }
});

exports.sendEmail = functions.https.onRequest(async (req, res) => {
    // Enable CORS
    res.set('Access-Control-Allow-Origin', '*');
    
    if (req.method === 'OPTIONS') {
        // Send response to OPTIONS requests
        res.set('Access-Control-Allow-Methods', 'POST');
        res.set('Access-Control-Allow-Headers', 'Content-Type');
        res.set('Access-Control-Max-Age', '3600');
        res.status(204).send('');
        return;
    }

    try {
        // Check if request is POST
        if (req.method !== 'POST') {
            throw new Error('Only POST requests are accepted');
        }

        // Get email data from request
        const { to, subject, message, apiKey } = req.body;

        // Validate required fields
        if (!to || !subject || !message) {
            throw new Error('Missing required email fields');
        }

        // Validate API key
        if (apiKey !== 'crsh iejc lhwz gasu') {
            throw new Error('Invalid API key');
        }

        // Configure email options
        const mailOptions = {
            from: 'PSAU Admissions <jericogutierrezsison12@gmail.com>',
            to: to,
            subject: subject,
            text: message
        };

        // Send email
        await transporter.sendMail(mailOptions);

        // Return success response
        res.json({
            success: true,
            message: 'Email sent successfully'
        });

    } catch (error) {
        console.error('Error sending email:', error);
        res.status(500).json({
            success: false,
            message: error.message || 'Error sending email'
        });
    }
}); 