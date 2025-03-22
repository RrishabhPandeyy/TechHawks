function getCurrentUser() {
    try {
        const token = localStorage.getItem('authToken');
        if (!token) return null;

        const user = JSON.parse(localStorage.getItem('user'));
        return user;
    } catch (error) {
        console.error('Get current user failed:', error);
        return null;
    }
}

function setAuthToken(token, user) {
    try {
        localStorage.setItem('authToken', token);
        localStorage.setItem('user', JSON.stringify(user));
    } catch (error) {
        console.error('Set auth token failed:', error);
        throw error;
    }
}

function clearAuth() {
    try {
        localStorage.removeItem('authToken');
        localStorage.removeItem('user');
    } catch (error) {
        console.error('Clear auth failed:', error);
        throw error;
    }
}

function isAuthenticated() {
    return !!getCurrentUser();
}

function checkAuthToken() {
    try {
        const token = localStorage.getItem('authToken');
        if (!token) return false;

        // Check if token is expired
        const tokenData = JSON.parse(atob(token.split('.')[1]));
        const expirationTime = tokenData.exp * 1000;
        
        if (Date.now() >= expirationTime) {
            clearAuth();
            return false;
        }

        return true;
    } catch (error) {
        console.error('Check auth token failed:', error);
        return false;
    }
}

function refreshToken() {
    try {
        const currentToken = localStorage.getItem('authToken');
        if (!currentToken) return;

        return fetch('api/auth/refresh-token', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${currentToken}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.token) {
                setAuthToken(data.token, data.user);
            }
        });
    } catch (error) {
        console.error('Refresh token failed:', error);
        throw error;
    }
}

function setupAuthInterceptor() {
    try {
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            if (checkAuthToken()) {
                const token = localStorage.getItem('authToken');
                if (token) {
                    if (typeof args[1] === 'object') {
                        args[1] = {
                            ...args[1],
                            headers: {
                                ...args[1]?.headers,
                                'Authorization': `Bearer ${token}`
                            }
                        };
                    }
                }
            }

            try {
                const response = await originalFetch(...args);
                if (response.status === 401) {
                    clearAuth();
                    window.location.href = '/';
                }
                return response;
            } catch (error) {
                console.error('Fetch interceptor error:', error);
                throw error;
            }
        };
    } catch (error) {
        console.error('Setup auth interceptor failed:', error);
        throw error;
    }
}
