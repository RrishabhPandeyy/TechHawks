function Button({ children, variant = 'primary', size = 'medium', onClick, className = '', disabled = false, type = 'button', icon }) {
    try {
        const baseStyles = 'inline-flex items-center justify-center rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors';
        
        const variants = {
            primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
            danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            outline: 'border-2 border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500'
        };

        const sizes = {
            small: 'px-3 py-1.5 text-sm',
            medium: 'px-4 py-2 text-base',
            large: 'px-6 py-3 text-lg'
        };

        const buttonClasses = `
            ${baseStyles}
            ${variants[variant]}
            ${sizes[size]}
            ${disabled ? 'opacity-50 cursor-not-allowed' : ''}
            ${className}
        `;

        return (
            <button
                type={type}
                className={buttonClasses}
                onClick={onClick}
                disabled={disabled}
                data-name="custom-button"
            >
                {icon && <span className="mr-2">{icon}</span>}
                {children}
            </button>
        );
    } catch (error) {
        console.error('Button component error:', error);
        reportError(error);
        return null;
    }
}
