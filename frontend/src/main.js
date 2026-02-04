import api from './services/api.js';

// Simple router
class Router {
    constructor() {
        this.routes = {};
        window.addEventListener('popstate', () => this.handleRoute());
    }

    register(path, handler) {
        this.routes[path] = handler;
    }

    navigate(path) {
        window.history.pushState({}, '', path);
        this.handleRoute();
    }

    handleRoute() {
        const path = window.location.pathname;
        const handler = this.routes[path] || this.routes['/'];

        if (handler) {
            handler();
        }
    }
}

const router = new Router();

// Routes
router.register('/', async () => {
    const main = document.getElementById('main-content');
    main.innerHTML = '<h2>Dashboard</h2><p>Welcome to BookFlow</p>';
});

router.register('/bookings', async () => {
    const main = document.getElementById('main-content');
    main.innerHTML = '<h2>Bookings</h2><p>Loading...</p>';

    try {
        const bookings = await api.getBookings();
        main.innerHTML = `
            <h2>Bookings</h2>
            <ul>
                ${bookings.map(b => `<li>${b.title} - ${b.starts_at}</li>`).join('')}
            </ul>
        `;
    } catch (error) {
        main.innerHTML = `<h2>Error</h2><p>${error.message}</p>`;
    }
});

// Initialize
router.handleRoute();

// Export for use in other modules
export { router, api };
