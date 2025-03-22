function EmergencyAlerts() {
    try {
        const [alerts, setAlerts] = React.useState([]);
        const [loading, setLoading] = React.useState(true);

        React.useEffect(() => {
            const fetchAlerts = async () => {
                try {
                    const response = await getEmergencyAlerts();
                    setAlerts(response.alerts);
                } catch (error) {
                    console.error('Failed to fetch emergency alerts:', error);
                } finally {
                    setLoading(false);
                }
            };

            fetchAlerts();

            // Set up real-time updates (would be implemented with WebSocket)
            const interval = setInterval(fetchAlerts, 30000);
            return () => clearInterval(interval);
        }, []);

        const handleRespond = async (alertId) => {
            try {
                await respondToEmergency(alertId, {
                    status: 'responding',
                    respondedAt: new Date().toISOString()
                });

                setAlerts(prev => prev.map(alert => 
                    alert.id === alertId 
                        ? { ...alert, status: 'responding' }
                        : alert
                ));
            } catch (error) {
                console.error('Failed to respond to emergency:', error);
                alert('Failed to respond to emergency');
            }
        };

        if (loading) {
            return <div>Loading alerts...</div>;
        }

        return (
            <div data-name="emergency-alerts" className="space-y-4">
                {alerts.map(alert => (
                    <div
                        key={alert.id}
                        className={`p-4 rounded-lg ${
                            alert.type === 'emergency' ? 'emergency-alert' : 'bg-yellow-50'
                        }`}
                    >
                        <div className="flex justify-between items-start">
                            <div>
                                <h3 className="font-semibold">
                                    {alert.type === 'emergency' ? 'Emergency SOS' : 'SOS Alert'}
                                </h3>
                                <p className="text-sm">
                                    Location: {alert.location.address}
                                </p>
                                <p className="text-sm">
                                    Time: {new Date(alert.timestamp).toLocaleString()}
                                </p>
                            </div>
                            <Button
                                onClick={() => handleRespond(alert.id)}
                                disabled={alert.status === 'responding'}
                                variant={alert.status === 'responding' ? 'success' : 'danger'}
                            >
                                {alert.status === 'responding' ? 'Responding' : 'Respond'}
                            </Button>
                        </div>
                    </div>
                ))}

                {alerts.length === 0 && (
                    <p className="text-gray-500 text-center">No active alerts</p>
                )}
            </div>
        );
    } catch (error) {
        console.error('Emergency alerts component error:', error);
        reportError(error);
        return null;
    }
}
