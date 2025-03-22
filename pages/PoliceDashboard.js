function PoliceDashboard() {
    try {
        const [activeTab, setActiveTab] = React.useState('alerts');
        const [notifications, setNotifications] = React.useState([]);
        const [user] = React.useState(getCurrentUser());
        const [showAddOfficer, setShowAddOfficer] = React.useState(false);
        const [officers, setOfficers] = React.useState([]);
        const [stats, setStats] = React.useState(null);

        React.useEffect(() => {
            // Fetch officers if user is superintendent
            const fetchOfficers = async () => {
                if (user.role === 'superintendent') {
                    try {
                        const response = await fetch('api/police/officers');
                        const data = await response.json();
                        setOfficers(data.officers);
                    } catch (error) {
                        console.error('Failed to fetch officers:', error);
                    }
                }
            };

            // Fetch real-time statistics
            const fetchStats = async () => {
                try {
                    const response = await fetch('api/police/statistics');
                    const data = await response.json();
                    setStats(data);
                } catch (error) {
                    console.error('Failed to fetch statistics:', error);
                }
            };

            fetchOfficers();
            fetchStats();

            // Set up real-time updates
            const statsInterval = setInterval(fetchStats, 30000);
            return () => clearInterval(statsInterval);
        }, [user.role]);

        const handleAddOfficer = async (officerData) => {
            try {
                const response = await fetch('api/police/officers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(officerData)
                });

                const data = await response.json();
                if (response.ok) {
                    setOfficers(prev => [...prev, data.officer]);
                    setShowAddOfficer(false);
                    setNotifications(prev => [
                        ...prev,
                        {
                            id: Date.now(),
                            type: 'success',
                            message: 'Officer added successfully'
                        }
                    ]);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Failed to add officer:', error);
                setNotifications(prev => [
                    ...prev,
                    {
                        id: Date.now(),
                        type: 'error',
                        message: 'Failed to add officer'
                    }
                ]);
            }
        };

        const handleLogout = () => {
            clearAuth();
            window.location.href = '/';
        };

        return (
            <div data-name="police-dashboard" className="min-h-screen bg-gray-100">
                <div className="dashboard-container">
                    <aside className="sidebar">
                        <div className="p-4">
                            <h2 className="text-xl font-bold text-white mb-6">Police Dashboard</h2>
                            <div className="space-y-2">
                                <button
                                    className={`sidebar-link ${activeTab === 'alerts' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('alerts')}
                                >
                                    <i className="fas fa-bell mr-2"></i>
                                    Emergency Alerts
                                </button>
                                <button
                                    className={`sidebar-link ${activeTab === 'analytics' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('analytics')}
                                >
                                    <i className="fas fa-chart-bar mr-2"></i>
                                    Crime Analytics
                                </button>
                                {user.role === 'superintendent' && (
                                    <button
                                        className={`sidebar-link ${activeTab === 'officers' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('officers')}
                                    >
                                        <i className="fas fa-users mr-2"></i>
                                        Manage Officers
                                    </button>
                                )}
                                <button
                                    className="sidebar-link"
                                    onClick={handleLogout}
                                >
                                    <i className="fas fa-sign-out-alt mr-2"></i>
                                    Logout
                                </button>
                            </div>
                        </div>
                    </aside>

                    <main className="dashboard-main">
                        <div className="mb-6">
                            <div className="flex justify-between items-center">
                                <h1 className="text-2xl font-bold text-gray-900">
                                    {activeTab === 'alerts' && 'Emergency Alerts'}
                                    {activeTab === 'analytics' && 'Crime Analytics'}
                                    {activeTab === 'officers' && 'Manage Officers'}
                                </h1>
                                {activeTab === 'officers' && user.role === 'superintendent' && (
                                    <Button
                                        onClick={() => setShowAddOfficer(true)}
                                        icon={<i className="fas fa-plus"></i>}
                                    >
                                        Add Officer
                                    </Button>
                                )}
                            </div>
                        </div>

                        {activeTab === 'alerts' && <EmergencyAlerts />}
                        {activeTab === 'analytics' && <CrimeAnalytics />}
                        {activeTab === 'officers' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {officers.map(officer => (
                                    <div
                                        key={officer.id}
                                        className="bg-white rounded-lg shadow p-6"
                                    >
                                        <div className="flex items-center space-x-4">
                                            <img
                                                src={officer.photoUrl || 'https://via.placeholder.com/40'}
                                                alt={officer.name}
                                                className="w-12 h-12 rounded-full"
                                            />
                                            <div>
                                                <h3 className="font-semibold">{officer.name}</h3>
                                                <p className="text-sm text-gray-600">
                                                    Badge: {officer.badgeNumber}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {showAddOfficer && (
                            <Modal
                                isOpen={showAddOfficer}
                                onClose={() => setShowAddOfficer(false)}
                                title="Add New Officer"
                            >
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        const formData = new FormData(e.target);
                                        handleAddOfficer(Object.fromEntries(formData));
                                    }}
                                    className="space-y-4"
                                >
                                    <Input
                                        name="name"
                                        label="Full Name"
                                        required
                                    />
                                    <Input
                                        name="badgeNumber"
                                        label="Badge Number"
                                        required
                                    />
                                    <Input
                                        name="password"
                                        type="password"
                                        label="Password"
                                        required
                                    />
                                    <Button type="submit">Add Officer</Button>
                                </form>
                            </Modal>
                        )}

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
                    </main>
                </div>
            </div>
        );
    } catch (error) {
        console.error('Police dashboard error:', error);
        reportError(error);
        return <div>Error loading dashboard</div>;
    }
}
