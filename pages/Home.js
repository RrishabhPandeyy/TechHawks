function Home() {
    try {
        const [showLogin, setShowLogin] = React.useState(false);
        const [showSignup, setShowSignup] = React.useState(false);
        const [showPoliceLogin, setShowPoliceLogin] = React.useState(false);

        return (
            <div data-name="home-container" className="min-h-screen bg-gray-100">
                <nav data-name="home-nav" className="bg-white shadow-lg">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <h1 className="text-2xl font-bold text-gray-900">Crime Alert</h1>
                            </div>
                            <div className="flex items-center space-x-4">
                                <button
                                    data-name="login-btn"
                                    className="px-4 py-2 text-gray-700 hover:text-gray-900"
                                    onClick={() => setShowLogin(true)}
                                >
                                    Login
                                </button>
                                <button
                                    data-name="signup-btn"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                    onClick={() => setShowSignup(true)}
                                >
                                    Sign Up
                                </button>
                                <button
                                    data-name="police-login-btn"
                                    className="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-900"
                                    onClick={() => setShowPoliceLogin(true)}
                                >
                                    Police Login
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>

                <main data-name="home-main" className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <h2 className="text-4xl font-extrabold text-gray-900 mb-8">
                            Report Crimes & Stay Safe
                        </h2>
                        <p className="text-xl text-gray-600 mb-12">
                            A real-time crime reporting system with instant police response and emergency features.
                        </p>
                    </div>

                    {showLogin && (
                        <Login onClose={() => setShowLogin(false)} />
                    )}
                    {showSignup && (
                        <Signup onClose={() => setShowSignup(false)} />
                    )}
                    {showPoliceLogin && (
                        <PoliceLogin onClose={() => setShowPoliceLogin(false)} />
                    )}
                </main>
            </div>
        );
    } catch (error) {
        console.error('Home page error:', error);
        reportError(error);
        return <div>Error loading home page</div>;
    }
}
