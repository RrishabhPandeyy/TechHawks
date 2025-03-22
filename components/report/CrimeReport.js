function CrimeReport({ onSubmit }) {
    try {
        const [formData, setFormData] = React.useState({
            type: '',
            description: '',
            location: null,
            files: []
        });
        const [loading, setLoading] = React.useState(false);
        const [error, setError] = React.useState('');
        const mapRef = React.useRef(null);

        React.useEffect(() => {
            if (!mapRef.current) {
                getUserLocation().then(location => {
                    const map = initializeMap('report-map', {
                        center: location,
                        zoom: 15
                    });

                    map.addListener('click', (e) => {
                        setFormData(prev => ({
                            ...prev,
                            location: {
                                lat: e.latLng.lat(),
                                lng: e.latLng.lng()
                            }
                        }));
                    });

                    mapRef.current = map;
                });
            }
        }, []);

        const handleSubmit = async (e) => {
            e.preventDefault();
            setLoading(true);
            setError('');

            try {
                if (!formData.location) {
                    throw new Error('Please select a location on the map');
                }

                const formDataToSend = new FormData();
                Object.entries(formData).forEach(([key, value]) => {
                    if (key === 'files') {
                        value.forEach(file => formDataToSend.append('files', file));
                    } else if (key === 'location') {
                        formDataToSend.append('location', JSON.stringify(value));
                    } else {
                        formDataToSend.append(key, value);
                    }
                });

                await reportCrime(formDataToSend);
                onSubmit?.();
            } catch (error) {
                console.error('Crime report submission failed:', error);
                setError(error.message || 'Failed to submit report');
            } finally {
                setLoading(false);
            }
        };

        const handleFileChange = (e) => {
            const files = Array.from(e.target.files);
            setFormData(prev => ({
                ...prev,
                files: [...prev.files, ...files]
            }));
        };

        return (
            <div data-name="crime-report" className="p-6">
                <h2 className="text-2xl font-bold mb-6">Report a Crime</h2>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Crime Type
                        </label>
                        <select
                            value={formData.type}
                            onChange={(e) => setFormData(prev => ({ ...prev, type: e.target.value }))}
                            className="w-full p-2 border rounded"
                            required
                        >
                            <option value="">Select type</option>
                            <option value="violence">Violence</option>
                            <option value="theft">Theft</option>
                            <option value="cybercrime">Cybercrime</option>
                            <option value="harassment">Harassment</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                            className="w-full p-2 border rounded"
                            rows={4}
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Location
                        </label>
                        <div id="report-map" className="h-64 w-full rounded-lg mb-2"></div>
                        <p className="text-sm text-gray-500">Click on the map to select location</p>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Evidence (Photos/Videos)
                        </label>
                        <input
                            type="file"
                            accept="image/*,video/*"
                            multiple
                            onChange={handleFileChange}
                            className="w-full"
                        />
                    </div>

                    {error && (
                        <p className="text-red-600 text-sm">{error}</p>
                    )}

                    <Button
                        type="submit"
                        disabled={loading}
                        className="w-full"
                    >
                        {loading ? 'Submitting...' : 'Submit Report'}
                    </Button>
                </form>
            </div>
        );
    } catch (error) {
        console.error('Crime report component error:', error);
        reportError(error);
        return null;
    }
}
