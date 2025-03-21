function App() {
    try {
        const [user, setUser] = React.useState(null);
        const [isPolice, setIsPolice] = React.useState(false);

        React.useEffect(() => {
            // Check authentication status
            const checkAuth = async () => {
                try {
                    const userData = await getCurrentUser();
                    setUser(userData);
                    setIsPolice(userData?.role === 'police');
                } catch (error) {
                    console.error('Auth check failed:', error);
                }
            };
            
            checkAuth();
        }, []);

        const renderContent = () => {
            if (!user) {
                return <Home />;
            }
            return isPolice ? <PoliceDashboard /> : <UserDashboard />;
        };

        return (
            <div data-name="app-root" className="app-container">
                {renderContent()}
            </div>
        );
    } catch (error) {
        console.error('App render error:', error);
        reportError(error);
        return <div>Something went wrong. Please refresh the page.</div>;
    }
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<App />);
