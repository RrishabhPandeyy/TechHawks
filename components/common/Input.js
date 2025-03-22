function Input({
    type = 'text',
    label,
    value,
    onChange,
    error,
    placeholder,
    required = false,
    className = '',
    disabled = false,
    icon,
    name
}) {
    try {
        const inputClasses = `
            w-full px-3 py-2 border rounded-md
            ${error ? 'border-red-500' : 'border-gray-300'}
            ${disabled ? 'bg-gray-100' : 'bg-white'}
            focus:outline-none focus:ring-2
            ${error ? 'focus:ring-red-500' : 'focus:ring-blue-500'}
            ${icon ? 'pl-10' : ''}
            ${className}
        `;

        return (
            <div className="relative" data-name="input-container">
                {label && (
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        {label}
                        {required && <span className="text-red-500 ml-1">*</span>}
                    </label>
                )}
                
                <div className="relative">
                    {icon && (
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            {icon}
                        </div>
                    )}
                    
                    <input
                        type={type}
                        value={value}
                        onChange={onChange}
                        placeholder={placeholder}
                        required={required}
                        disabled={disabled}
                        name={name}
                        className={inputClasses}
                        data-name="input-field"
                    />
                </div>

                {error && (
                    <p className="mt-1 text-sm text-red-600" data-name="input-error">
                        {error}
                    </p>
                )}
            </div>
        );
    } catch (error) {
        console.error('Input component error:', error);
        reportError(error);
        return null;
    }
}
