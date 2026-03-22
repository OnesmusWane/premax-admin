import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '',           redirect: '/dashboard' },
            { path: 'dashboard',  name: 'dashboard', component: () => import('@/pages/Dashboard.vue') },
            { path: 'bookings',   name: 'bookings',  component: () => import('@/pages/Bookings/Index.vue') },
            { path: 'customers',   name: 'customers',  component: () => import('@/pages/Customers/Index.vue') },
            { path: 'customers/:id', name: 'customer-profile', component: () => import('@/pages/Customers/Profile.vue') },
            { path: 'vehicles',   name: 'vehicles',  component: () => import('@/pages/Vehicles/Index.vue') },
            { path: 'vehicles/:id', name: 'vehicle-detail', component: () => import('@/pages/Vehicles/Detail.vue') },
            { path: 'job-cards',   name: 'job-cards',  component: () => import('@/pages/JobCards/Index.vue') },
            { path: 'pos',   name: 'pos',  component: () => import('@/pages/POS/Index.vue') },
            { path: 'inventory',   name: 'inventory',  component: () => import('@/pages/Inventory/Index.vue') },
            { path: 'payments',   name: 'payments',  component: () => import('@/pages/Payments/Index.vue') },
            { path: 'reports',   name: 'reports',  component: () => import('@/pages/Reports/Index.vue') },
            { path: 'settings',   name: 'settings',  component: () => import('@/pages/Settings/Index.vue') },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/login' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()
    if (to.meta.requiresAuth && !auth.isLoggedIn) {
        if (!await auth.tryRestore()) return { name: 'login' }
    }
    if (to.meta.guest && auth.isLoggedIn) return { name: 'dashboard' }
})

export default router