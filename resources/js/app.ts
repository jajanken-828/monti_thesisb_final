import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, DefineComponent } from 'vue';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// ─── GLOBAL JAVASCRIPT ERROR HANDLERS ──────────────────────────────────
// Catches errors that happen outside Vue (e.g., in setTimeout, fetch, etc.)
window.onerror = function (message, source, lineno, colno, error) {
    console.error('🔥 Window Error:', { message, source, lineno, colno, error });
    alert(`Uncaught Error: ${message}`);
};

// Catches unhandled Promise rejections
window.onunhandledrejection = function (event) {
    console.error('🔥 Unhandled Promise Rejection:', event.reason);
    alert(`Unhandled Promise Rejection: ${event.reason}`);
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // ─── VUE ERROR HANDLER ──────────────────────────────────────────
        // Catches errors during component rendering, lifecycle, and event handlers
        app.config.errorHandler = (err, vm, info) => {
            console.error('🔥 Vue Error:', err, info);
            alert(`Vue Error: ${err.message}\n\nInfo: ${info}\n\nSee console for details.`);
        };

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});