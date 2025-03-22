import React from 'react';

const emailLogin = async (email, password) => {
    const response = await fetch('http://localhost:5000/api/login/email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
    });
    return response.json();
};

const sendOTP = async (phone) => {
    const response = await fetch('http://localhost:5000/api/login/phone/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone }),
    });
    return response.json();
};

const verifyOTP = async (phone, otp) => {
    const response = await fetch('http://localhost:5000/api/login/phone/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone, otp }),
    });
    return response.json();
};

const OTPVerification = ({ phone, onVerified }) => {
    const [otp, setOTP] = React.useState('');

    const handleVerify = async () => {
        const response = await verifyOTP(phone, otp);
        if (response.success) {
            onVerified();
            window.location.reload();
        } else {
            alert('Invalid OTP');
        }
    };

    return (
        <div>
            <input
                type="text"
                placeholder="Enter OTP"
                value={otp}
                onChange={(e) => setOTP(e.target.value)}
                className="w-full p-2 border rounded"
            />
            <button
                onClick={handleVerify}
                className="w-full mt-4 py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
                Verify OTP
            </button>
        </div>
    );
};

function Login({ onClose }) {
    const [loginMethod, setLoginMethod] = React.useState('email'); // 'email' or 'phone'
    const [email, setEmail] = React.useState('');
    const [phone, setPhone] = React.useState('');
    const [password, setPassword] = React.useState('');
    const [showOTP, setShowOTP] = React.useState(false);

    const handleLogin = async (e) => {
        e.preventDefault();
        try {
            if (loginMethod === 'email') {
                // Handle email login
                const response = await emailLogin(email, password);
                if (response.success) {
                    onClose();
                    window.location.reload();
                }
            } else {
                // Handle phone login with OTP
                await sendOTP(phone);
                setShowOTP(true);
            }
        } catch (error) {
            console.error('Login failed:', error);
            alert('Login failed. Please try again.');
        }
    };

    return (
        <div data-name="login-modal" className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
            <div className="bg-white rounded-lg p-8 max-w-md w-full">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-2xl font-bold">Login</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
                        <i className="fas fa-times"></i>
                    </button>
                </div>

                <div className="mb-6">
                    <div className="flex gap-4 mb-4">
                        <button
                            data-name="email-method-btn"
                            className={`flex-1 py-2 px-4 rounded ${loginMethod === 'email' ? 'bg-blue-600 text-white' : 'bg-gray-200'}`}
                            onClick={() => setLoginMethod('email')}
                        >
                            Email
                        </button>
                        <button
                            data-name="phone-method-btn"
                            className={`flex-1 py-2 px-4 rounded ${loginMethod === 'phone' ? 'bg-blue-600 text-white' : 'bg-gray-200'}`}
                            onClick={() => setLoginMethod('phone')}
                        >
                            Phone
                        </button>
                    </div>

                    {!showOTP ? (
                        <form onSubmit={handleLogin}>
                            {loginMethod === 'email' ? (
                                <div className="space-y-4">
                                    <input
                                        type="email"
                                        placeholder="Email"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        className="w-full p-2 border rounded"
                                        required
                                    />
                                    <input
                                        type="password"
                                        placeholder="Password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        className="w-full p-2 border rounded"
                                        required
                                    />
                                </div>
                            ) : (
                                <input
                                    type="tel"
                                    placeholder="Phone Number"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    className="w-full p-2 border rounded"
                                    required
                                />
                            )}
                            <button
                                type="submit"
                                className="w-full mt-4 py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700"
                            >
                                {loginMethod === 'email' ? 'Login' : 'Send OTP'}
                            </button>
                        </form>
                    ) : (
                        <OTPVerification phone={phone} onVerified={onClose} />
                    )}
                </div>
            </div>
        </div>
    );
}

export default Login;