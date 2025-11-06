// Minimal email sender via Gmail for Firebase Functions v2
const { onRequest } = require('firebase-functions/v2/https');
const nodemailer = require('nodemailer');

exports.sendEmail = onRequest({ region: 'us-central1', secrets: ['SMTP_USER', 'SMTP_PASS'] }, async (req, res) => {
  try {
    if (req.method !== 'POST') {
      return res.status(405).json({ error: 'Method not allowed' });
    }

    const { to, subject, html, from } = req.body || {};
    if (!to || !subject || !html) {
      return res.status(400).json({ error: 'Missing fields' });
    }

    // Ensure secrets are present
    if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
      return res.status(500).json({ success: false, error: 'Missing SMTP credentials' });
    }

    // Trim whitespace and verify credentials
    const smtpUser = process.env.SMTP_USER.trim();
    const smtpPass = process.env.SMTP_PASS.trim();
    
    // Log for debugging (first 4 chars only for security)
    console.log('SMTP_USER:', smtpUser.substring(0, 4) + '...');
    console.log('SMTP_PASS length:', smtpPass.length);

    // Verify credentials format
    if (!smtpUser.includes('@') || smtpUser.length < 5) {
      console.error('Invalid SMTP_USER format:', smtpUser.substring(0, 10) + '...');
      return res.status(500).json({ success: false, error: 'Invalid SMTP_USER format' });
    }
    
    if (smtpPass.length !== 16) {
      console.error('SMTP_PASS length is not 16:', smtpPass.length);
      return res.status(500).json({ success: false, error: 'SMTP_PASS must be 16 characters (App Password)' });
    }

    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: { 
        user: smtpUser, 
        pass: smtpPass 
      },
      // Explicit SMTP settings for Gmail
      host: 'smtp.gmail.com',
      port: 587,
      secure: false, // true for 465, false for other ports
      requireTLS: true,
    });

    await transporter.sendMail({
      to,
      subject,
      html,
      from: from || `PSAU Admissions <${smtpUser}>`,
    });

    return res.status(200).json({ success: true });
  } catch (e) {
    return res.status(500).json({ success: false, error: e.message });
  }
});


