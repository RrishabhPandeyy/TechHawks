function Modal({ isOpen, onClose, title, children, size = 'medium' }) {
    try {
        if (!isOpen) return null;

        const sizes = {
            small: 'max-w-md',
            medium: 'max-w-lg',
            large: 'max-w-2xl',
            xlarge: 'max-w-4xl'
        };

        const handleBackdropClick = (e) => {
            if (e.target === e.currentTarget) {
                onClose();
            }
        };

        return (
            <div
                className="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center"
                onClick={handleBackdropClick}
                data-name="modal-backdrop"
            >
                <div
                    className={`bg-white rounded-lg shadow-xl w-full m-4 ${sizes[size]}`}
                    data-name="modal-container"
                >
                    <div className="flex justify-between items-center p-6 border-b" data-name="modal-header">
                        <h2 className="text-xl font-semibold text-gray-900">{title}</h2>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-500 focus:outline-none"
                            data-name="modal-close"
                        >
                            <i className="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div className="p-6" data-name="modal-content">
                        {children}
                    </div>
                </div>
            </div>
        );
    } catch (error) {
        console.error('Modal component error:', error);
        reportError(error);
        return null;
    }
}
