function OTPVerification({ phone, onVerified }) {
    try {
        const [otp, setOtp] = React.useState('');
        const [error, setError] = React.useState('');
        const [loading, setLoading] = React.useState(false);

        const handleSubmit = async (e) => {
            e.preventDefault();
            setLoading(true);
            setError('');

            try {
                const response = await verifyOTP(phone, otp);
                if (response.success) {
                    onVerified();
                } else {
                    setError('Invalid OTP. Please try again.');
                }
            } catch (error) {
                console.error('OTP verification failed:', error);
                setError('Failed to verify OTP. Please try again.');
            } finally {
                setLoading(false);
            }
        };

        return (
            <div data-name="otp-verification">
                <h3 className="text-lg font-semibold mb-4">Enter OTP</h3>
                <p className="text-sm text-gray-600 mb-4">
                    Please enter the OTP sent to {phone}
                </p>

                <form onSubmit={handleSubmit}>
                    <Input
                        type="text"
                        placeholder="Enter OTP"
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                        error={error}
                        required
                        className="mb-4"
                    />

                    <Button
                        type="submit"
                        disabled={loading}
                        className="w-full"
                    >
                        {loading ? 'Verifying...' : 'Verify OTP'}
                    </Button>
                </form>
            </div>
        );
    } catch (error) {
        console.error('OTP verification component error:', error);
        reportError(error);
        return null;
    }
}
