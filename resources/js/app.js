import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import '../css/app.css';

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
