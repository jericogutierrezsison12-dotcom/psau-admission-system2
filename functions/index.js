// Minimal email sender via Gmail for Firebase Functions v2
const { onRequest } = require('firebase-functions/v2/https');
const nodemailer = require('nodemailer');

exports.sendEmail = onRequest({ region: 'us-central1' }, async (req, res) => {
  try {
    if (req.method !== 'POST') {
      return res.status(405).json({ error: 'Method not allowed' });
    }

    const { to, subject, html, from } = req.body || {};
    if (!to || !subject || !html) {
      return res.status(400).json({ error: 'Missing fields' });
    }

    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS },
    });

    await transporter.sendMail({
      to,
      subject,
      html,
      from: from || process.env.SMTP_USER,
    });

    return res.status(200).json({ success: true });
  } catch (e) {
    return res.status(500).json({ success: false, error: e.message });
  }
});


