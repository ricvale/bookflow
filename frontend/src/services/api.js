// API Client for BookFlow backend
class ApiClient {
    constructor(baseUrl = 'http://localhost:8000/api') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('auth_token');
    }

    async request(endpoint, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers,
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        const response = await fetch(`${this.baseUrl}${endpoint}`, {
            ...options,
            headers,
        });

        if (response.status === 401) {
            // Token expired, redirect to login
            this.logout();
            window.location.href = '/login';
            return;
        }

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Request failed');
        }

        return response.json();
    }

    async login(email, password) {
        const data = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        });

        this.token = data.token;
        localStorage.setItem('auth_token', data.token);
        
        return data;
    }

    logout() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }

    // Booking endpoints
    async getBookings() {
        return this.request('/bookings');
    }

    async createBooking(bookingData) {
        return this.request('/bookings', {
            method: 'POST',
            body: JSON.stringify(bookingData),
        });
    }

    async cancelBooking(bookingId) {
        return this.request(`/bookings/${bookingId}`, {
            method: 'DELETE',
        });
    }
}

export default new ApiClient();
