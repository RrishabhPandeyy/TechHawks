function Notification({ type = 'info', message, onClose, duration = 5000 }) {
    try {
        const [isVisible, setIsVisible] = React.useState(true);

        React.useEffect(() => {
            if (duration > 0) {
                const timer = setTimeout(() => {
                    setIsVisible(false);
                    onClose?.();
                }, duration);

                return () => clearTimeout(timer);
            }
        }, [duration, onClose]);

        if (!isVisible) return null;

        const types = {
            success: {
                bgColor: 'bg-green-50',
                textColor: 'text-green-800',
                borderColor: 'border-green-400',
                icon: 'fas fa-check-circle'
            },
            error: {
                bgColor: 'bg-red-50',
                textColor: 'text-red-800',
                borderColor: 'border-red-400',
                icon: 'fas fa-exclamation-circle'
            },
            warning: {
                bgColor: 'bg-yellow-50',
                textColor: 'text-yellow-800',
                borderColor: 'border-yellow-400',
                icon: 'fas fa-exclamation-triangle'
            },
            info: {
                bgColor: 'bg-blue-50',
                textColor: 'text-blue-800',
                borderColor: 'border-blue-400',
                icon: 'fas fa-info-circle'
            }
        };

        const { bgColor, textColor, borderColor, icon } = types[type];

        return (
            <div
                className={`fixed top-4 right-4 z-50 p-4 rounded-lg border ${bgColor} ${borderColor} shadow-lg max-w-md`}
                data-name="notification"
            >
                <div className="flex items-center">
                    <div className={`flex-shrink-0 ${textColor}`}>
                        <i className={`${icon} text-lg`}></i>
                    </div>
                    <div className={`ml-3 ${textColor}`}>
                        <p className="text-sm font-medium">{message}</p>
                    </div>
                    <div className="ml-auto pl-3">
                        <button
                            onClick={() => {
                                setIsVisible(false);
                                onClose?.();
                            }}
                            className={`${textColor} hover:${textColor} focus:outline-none`}
                            data-name="notification-close"
                        >
                            <i className="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        );
    } catch (error) {
        console.error('Notification component error:', error);
        reportError(error);
        return null;
    }
}
