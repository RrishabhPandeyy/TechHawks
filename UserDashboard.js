function UserDashboard() {
    try {
        const [showReportModal, setShowReportModal] = React.useState(false);
        const [selectedLocation, setSelectedLocation] = React.useState(null);
        const [notifications, setNotifications] = React.useState([]);
        const [user] = React.useState(getCurrentUser());
        const [crimeReports, setCrimeReports] = React.useState([]);
        const [showNearbyPolice, setShowNearbyPolice] = React.useState(false);

        React.useEffect(() => {
            // Get user's location and check for nearby hotspots
            const checkHotspots = async () => {
                try {
                    const location = await getUserLocation();
                    const response = await fetch(`api/hotspots/nearby?lat=${location.lat}&lng=${location.lng}`);
                    const hotspots = await response.json();

                    if (hotspots.length > 0) {
                        setNotifications(prev => [
                            ...prev,
                            {
                                id: Date.now(),
                                type: 'warning',
                                message: 'You are entering a high-crime area. Stay alert!'
                            }
                        ]);
                    }
                } catch (error) {
                    console.error('Failed to check hotspots:', error);
                }
            };

            // Fetch user's crime reports
            const fetchCrimeReports = async () => {
                try {
                    const response = await fetch('api/crimes/user-reports');
                    const data = await response.json();
                    setCrimeReports(data.reports);
                } catch (error) {
                    console.error('Failed to fetch crime reports:', error);
                }
            };

            checkHotspots();
            fetchCrimeReports();

            // Set up real-time notifications (would be implemented with WebSocket)
            const notificationInterval = setInterval(checkHotspots, 300000); // Check every 5 minutes
            return () => clearInterval(notificationInterval);
        }, []);

        const handleSOS = (type) => {
            setNotifications(prev => [
                ...prev,
                {
                    id: Date.now(),
                    type: 'success',
                    message: `${type} SOS alert sent. Help is on the way!`
                }
            ]);
        };

        const handleLogout = () => {
            clearAuth();
            window.location.href = '/';
        };

        return (
            <div data-name="user-dashboard" className="min-h-screen bg-gray-100">
                <nav className="bg-white shadow-lg">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <h1 className="text-2xl font-bold text-gray-900">Crime Alert</h1>
                            </div>
                            <div className="flex items-center space-x-4">
                                <button
                                    onClick={() => setShowReportModal(true)}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Report Crime
                                </button>
                                <button
                                    onClick={() => setShowNearbyPolice(!showNearbyPolice)}
                                    className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                >
                                    {showNearbyPolice ? 'Hide Police Stations' : 'Show Police Stations'}
                                </button>
                                <div className="relative">
                                    <button
                                        className="flex items-center space-x-2"
                                        onClick={handleLogout}
                                    >
                                        <img
                                            src={user.photoUrl || 'https://via.placeholder.com/40'}
                                            alt="Profile"
                                            className="w-8 h-8 rounded-full"
                                        />
                                        <span className="text-gray-700">{user.name}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    <div className="px-4 py-6 sm:px-0">
                        <MapView
                            center={selectedLocation}
                            zoom={13}
                            markers={crimeReports.map(report => ({
                                position: report.location,
                                data: {
                                    title: report.type,
                                    description: report.description,
                                    timestamp: report.createdAt,
                                    type: report.type
                                }
                            }))}
                            showPoliceStations={showNearbyPolice}
                        />

                        {notifications.map(notification => (
                            <Notification
                                key={notification.id}
                                type={notification.type}
                                message={notification.message}
                                onClose={() => setNotifications(prev =>
                                    prev.filter(n => n.id !== notification.id)
                                )}
                            />
                        ))}

                        <SOSButton onTrigger={handleSOS} />

                        {showReportModal && (
                            <Modal
                                isOpen={showReportModal}
                                onClose={() => setShowReportModal(false)}
                                title="Report Crime"
                                size="large"
                            >
                                <CrimeReport
                                    onSubmit={() => {
                                        setShowReportModal(false);
                                        setNotifications(prev => [
                                            ...prev,
                                            {
                                                id: Date.now(),
                                                type: 'success',
                                                message: 'Crime report submitted successfully'
                                            }
                                        ]);
                                    }}
                                />
                            </Modal>
                        )}
                    </div>
                </main>
            </div>
        );
    } catch (error) {
        console.error('User dashboard error:', error);
        reportError(error);
        return <div>Error loading dashboard</div>;
    }
}
