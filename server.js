const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const nodemailer = require('nodemailer');
const twilio = require('twilio');

const app = express();
app.use(bodyParser.json());
app.use(cors());

// MongoDB connection
mongoose.connect('mongodb://localhost:27017/auth_demo', {
    useNewUrlParser: true,
    useUnifiedTopology: true,
});

// User schema
const userSchema = new mongoose.Schema({
    email: { type: String, unique: true },
    phone: { type: String, unique: true },
    password: String,
});

const User = mongoose.model('User', userSchema);

// Email OTP storage (in-memory for demo purposes)
const emailOTPs = new Map();

// Twilio setup (replace with your credentials)
const twilioClient = twilio('TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN');

// Nodemailer setup (replace with your email credentials)
const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: 'your-email@gmail.com',
        pass: 'your-email-password',
    },
});

// Login with email
app.post('/api/login/email', async (req, res) => {
    const { email, password } = req.body;
    const user = await User.findOne({ email });

    if (!user || !bcrypt.compareSync(password, user.password)) {
        return res.status(401).json({ success: false, message: 'Invalid credentials' });
    }

    const token = jwt.sign({ userId: user._id }, 'your-secret-key', { expiresIn: '1h' });
    res.json({ success: true, token });
});

// Send OTP to phone
app.post('/api/login/phone/send-otp', async (req, res) => {
    const { phone } = req.body;
    const otp = Math.floor(100000 + Math.random() * 900000).toString();

    // Save OTP in memory (for demo purposes)
    emailOTPs.set(phone, otp);

    // Send OTP via Twilio
    await twilioClient.messages.create({
        body: `Your OTP is: ${otp}`,
        from: '+1234567890', // Your Twilio phone number
        to: phone,
    });

    res.json({ success: true });
});

// Verify OTP
app.post('/api/login/phone/verify-otp', async (req, res) => {
    const { phone, otp } = req.body;
    const savedOTP = emailOTPs.get(phone);

    if (savedOTP !== otp) {
        return res.status(401).json({ success: false, message: 'Invalid OTP' });
    }

    // Find or create user
    let user = await User.findOne({ phone });
    if (!user) {
        user = new User({ phone });
        await user.save();
    }

    const token = jwt.sign({ userId: user._id }, 'your-secret-key', { expiresIn: '1h' });
    res.json({ success: true, token });
});

// Start server
app.listen(5000, () => {
    console.log('Server running on http://localhost:5000');
});