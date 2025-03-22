import React from 'react';

const registerUser = async (formData) => {
    const response = await fetch('http://localhost:5000/api/signup', {
        method: 'POST',
        body: formData, // FormData object
    });
    return response.json();
};

function Signup({ onClose }) {
    const [formData, setFormData] = React.useState({
        name: '',
        username: '',
        password: '',
        confirmPassword: '',
        aadharNumber: '',
        relativePhone: '',
        phone: '',
        address: '',
        photo: null,
    });
    const [location, setLocation] = React.useState(null);
    const mapRef = React.useRef(null);

    React.useEffect(() => {
        // Initialize map
        if (!mapRef.current) {
            const map = new google.maps.Map(document.getElementById('signup-map'), {
                center: { lat: 20.5937, lng: 78.9629 },
                zoom: 5,
            });
            mapRef.current = map;

            map.addListener('click', (e) => {
                setLocation({
                    lat: e.latLng.lat(),
                    lng: e.latLng.lng(),
                });
            });
        }
    }, []);

    const handlePhotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setFormData({ ...formData, photo: file });
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (formData.password !== formData.confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            const formDataToSend = new FormData();
            Object.entries(formData).forEach(([key, value]) => {
                formDataToSend.append(key, value);
            });
            formDataToSend.append('location', JSON.stringify(location));

            const response = await registerUser(formDataToSend);
            if (response.success) {
                onClose();
                window.location.reload();
            } else {
                alert(response.message || 'Signup failed. Please try again.');
            }
        } catch (error) {
            console.error('Signup failed:', error);
            alert('Signup failed. Please try again.');
        }
    };

    return (
        <div data-name="signup-modal" className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen p-4">
                <div className="bg-white rounded-lg p-8 max-w-2xl w-full">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-bold">Sign Up</h2>
                        <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
                            <i className="fas fa-times"></i>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Profile Photo
                                </label>
                                <div
                                    className="profile-upload w-24 h-24 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center cursor-pointer"
                                    onClick={() => document.getElementById('photo-input').click()}
                                >
                                    {formData.photo ? (
                                        <img
                                            src={URL.createObjectURL(formData.photo)}
                                            alt="Profile"
                                            className="w-full h-full object-cover rounded-full"
                                        />
                                    ) : (
                                        <i className="fas fa-camera text-gray-400 text-3xl"></i>
                                    )}
                                </div>
                                <input
                                    id="photo-input"
                                    type="file"
                                    accept="image/*"
                                    onChange={handlePhotoChange}
                                    className="hidden"
                                />
                            </div>

                            <div className="space-y-4">
                                <input
                                    type="text"
                                    placeholder="Full Name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    className="w-full p-2 border rounded"
                                    required
                                />
                                <input
                                    type="text"
                                    placeholder="Username"
                                    value={formData.username}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                    className="w-full p-2 border rounded"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <input
                                type="password"
                                placeholder="Password"
                                value={formData.password}
                                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                className="w-full p-2 border rounded"
                                required
                            />
                            <input
                                type="password"
                                placeholder="Confirm Password"
                                value={formData.confirmPassword}
                                onChange={(e) => setFormData({ ...formData, confirmPassword: e.target.value })}
                                className="w-full p-2 border rounded"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <input
                                type="text"
                                placeholder="Aadhar Number"
                                value={formData.aadharNumber}
                                onChange={(e) => setFormData({ ...formData, aadharNumber: e.target.value })}
                                className="w-full p-2 border rounded"
                                required
                            />
                            <input
                                type="tel"
                                placeholder="Phone Number"
                                value={formData.phone}
                                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                className="w-full p-2 border rounded"
                                required
                            />
                        </div>

                        <input
                            type="tel"
                            placeholder="Relative's Phone Number"
                            value={formData.relativePhone}
                            onChange={(e) => setFormData({ ...formData, relativePhone: e.target.value })}
                            className="w-full p-2 border rounded"
                            required
                        />

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Select Your Location
                            </label>
                            <div id="signup-map" className="w-full h-64 rounded-lg mb-4"></div>
                        </div>

                        <button
                            type="submit"
                            className="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Sign Up
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}

export default Signup;