function PoliceLogin({ onClose }) {
    try {
        const [formData, setFormData] = React.useState({
            badgeNumber: '',
            password: ''
        });
        const [loading, setLoading] = React.useState(false);
        const [error, setError] = React.useState('');

        const handleSubmit = async (e) => {
            e.preventDefault();
            setLoading(true);
            setError('');

            try {
                const response = await fetch('api/auth/police-login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                if (response.ok) {
                    setAuthToken(data.token, { ...data.user, role: 'police' });
                    onClose();
                    window.location.reload();
                } else {
                    setError(data.message || 'Login failed');
                }
            } catch (error) {
                console.error('Police login failed:', error);
                setError('Login failed. Please try again.');
            } finally {
                setLoading(false);
            }
        };

        return (
            <div data-name="police-login" className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center">
                <div className="bg-white rounded-lg p-8 max-w-md w-full">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-bold">Police Login</h2>
                        <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
                            <i className="fas fa-times"></i>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Input
                            type="text"
                            label="Badge Number"
                            value={formData.badgeNumber}
                            onChange={(e) => setFormData(prev => ({ ...prev, badgeNumber: e.target.value }))}
                            required
                        />

                        <Input
                            type="password"
                            label="Password"
                            value={formData.password}
                            onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
                            required
                        />

                        {error && (
                            <p className="text-red-600 text-sm">{error}</p>
                        )}

                        <Button
                            type="submit"
                            disabled={loading}
                            className="w-full"
                        >
                            {loading ? 'Logging in...' : 'Login'}
                        </Button>
                    </form>
                </div>
            </div>
        );
    } catch (error) {
        console.error('Police login component error:', error);
        reportError(error);
        return null;
    }
}
