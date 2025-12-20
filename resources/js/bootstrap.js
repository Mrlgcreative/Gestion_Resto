import axios from 'axios';
window.axios = axios;

// Configuration du base URL pour XAMPP
const getBasePath = () => {
    try {
        const baseUrl = window.__BASE_URL__ || '';
        const url = new URL(baseUrl);
        return url.pathname.replace(/\/$/, '');
    } catch {
        return '';
    }
};

window.axios.defaults.baseURL = getBasePath();
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
