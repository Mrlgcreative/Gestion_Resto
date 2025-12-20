import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import '../css/app.css';

// Helper pour construire les URLs avec le base path
const getBasePath = () => {
    try {
        const baseUrl = window.__BASE_URL__ || '';
        const url = new URL(baseUrl);
        return url.pathname.replace(/\/$/, '');
    } catch {
        return '';
    }
};

const basePath = getBasePath();

// Configurer Axios avec le base path
axios.defaults.baseURL = basePath;
window.axios = axios;

// Fonction globale pour construire les URLs
window.appUrl = (path) => {
    const cleanPath = path.startsWith('/') ? path : '/' + path;
    return basePath + cleanPath;
};

// Intercepteur pour Inertia - ajoute le base path aux requêtes
router.on('before', (event) => {
    const url = event.detail.visit.url;
    if (url.pathname.startsWith('/') && !url.pathname.startsWith(basePath) && basePath) {
        url.pathname = basePath + url.pathname;
    }
});

createInertiaApp({
    title: (title) => title ? `${title} - ${window.__APP_NAME__ || 'RestoApp'}` : (window.__APP_NAME__ || 'RestoApp'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        // Store app name globally for title
        window.__APP_NAME__ = props.initialPage.props?.app?.name || 'RestoApp';
        
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Détecter la fermeture de l'onglet et déconnecter l'utilisateur
let isNavigatingAway = false;

// Marquer quand on navigue via Inertia (navigation interne à l'app)
router.on('before', () => {
    isNavigatingAway = true;
});

router.on('finish', () => {
    // Réinitialiser après que la navigation soit terminée
    isNavigatingAway = false;
});

router.on('error', () => {
    isNavigatingAway = false;
});

router.on('cancel', () => {
    isNavigatingAway = false;
});

// Détecter les clics sur les liens externes
document.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (link && link.href && !link.href.includes(window.location.host)) {
        isNavigatingAway = true;
    }
});

// Détecter les soumissions de formulaires
document.addEventListener('submit', () => {
    isNavigatingAway = true;
});

// Quand l'onglet/fenêtre est fermé(e)
window.addEventListener('pagehide', (event) => {
    // persisted = true signifie que la page est mise en cache (bfcache), pas fermée
    if (!event.persisted && !isNavigatingAway) {
        // Envoyer une requête de déconnexion via beacon
        const logoutUrl = basePath + '/beacon-logout';
        navigator.sendBeacon(logoutUrl);
    }
});
