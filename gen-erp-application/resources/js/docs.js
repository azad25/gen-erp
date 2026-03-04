import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import DocsApp from './components/docs/DocsApp.vue';

const router = createRouter({
    history: createWebHistory('/docs/'),
    routes: [
        { path: '/:path(.*)*', component: { template: '<div />' } },
    ],
    scrollBehavior(to, from, savedPosition) {
        if (to.hash) {
            return { el: to.hash, behavior: 'smooth', top: 80 };
        }
        return savedPosition || { top: 0 };
    },
});

createApp(DocsApp).use(router).mount('#docs-app');

