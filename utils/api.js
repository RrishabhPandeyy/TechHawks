async function handleResponse(response) {
    try {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'API request failed');
        }
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

async function emailLogin(email, password) {
    try {
        const response = await fetch('api/auth/email-login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        return handleResponse(response);
    } catch (error) {
        console.error('Email login failed:', error);
        throw error;
    }
}

async function phoneLogin(phone) {
    try {
        const response = await fetch('api/auth/phone-login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone })
        });
        return handleResponse(response);
    } catch (error) {
        console.error('Phone login failed:', error);
        throw error;
    }
}

async function verifyOTP(phone, otp) {
    try {
        const response = await fetch('api/auth/verify-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, otp })
        });
        return handleResponse(response);
    } catch (error) {
        console.error('OTP verification failed:', error);
        throw error;
    }
}

async function registerUser(formData) {
    try {
        const response = await fetch('api/auth/register', {
            method: 'POST',
            body: formData
        });
        return handleResponse(response);
    } catch (error) {
        console.error('Registration failed:', error);
        throw error;
    }
}

async function reportCrime(crimeData) {
    try {
        const response = await fetch('api/crimes/report', {
            method: 'POST',
            body: crimeData
        });
        return handleResponse(response);
    } catch (error) {
        console.error('Crime reporting failed:', error);
        throw error;
    }
}

async function triggerSOS(location) {
    try {
        const response = await fetch('api/emergency/sos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ location })
        });
        return handleResponse(response);
    } catch (error) {
        console.error('SOS trigger failed:', error);
        throw error;
    }
}

async function getPoliceStations(location) {
    try {
        const response = await fetch(`api/police-stations?lat=${location.lat}&lng=${location.lng}`);
        return handleResponse(response);
    } catch (error) {
        console.error('Failed to fetch police stations:', error);
        throw error;
    }
}

async function getCrimeStatistics(filters) {
    try {
        const response = await fetch('api/statistics/crimes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filters)
        });
        return handleResponse(response);
    } catch (error) {
        console.error('Failed to fetch crime statistics:', error);
        throw error;
    }
}

async function getEmergencyAlerts() {
    try {
        const response = await fetch('api/emergency/alerts');
        return handleResponse(response);
    } catch (error) {
        console.error('Failed to fetch emergency alerts:', error);
        throw error;
    }
}

async function respondToEmergency(alertId, response) {
    try {
        const apiResponse = await fetch(`api/emergency/respond/${alertId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(response)
        });
        return handleResponse(apiResponse);
    } catch (error) {
        console.error('Failed to respond to emergency:', error);
        throw error;
    }
}
