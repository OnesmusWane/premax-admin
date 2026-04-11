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
            { path: 'dashboard',  name: 'dashboard', component: () => import('@/pages/Dashboard.vue'), meta: { permission: 'dashboard.view' } },
            { path: 'bookings',   name: 'bookings',  component: () => import('@/pages/Bookings/Index.vue'), meta: { permission: 'bookings.manage' } },
            { path: 'customers',   name: 'customers',  component: () => import('@/pages/Customers/Index.vue'), meta: { permission: 'customers.manage' } },
            { path: 'customers/:id', name: 'customer-profile', component: () => import('@/pages/Customers/Profile.vue'), meta: { permission: 'customers.manage' } },
            { path: 'vehicles',   name: 'vehicles',  component: () => import('@/pages/Vehicles/Index.vue'), meta: { permission: 'vehicles.manage' } },
            { path: 'vehicles/:id', name: 'vehicle-detail', component: () => import('@/pages/Vehicles/Detail.vue'), meta: { permission: 'vehicles.manage' } },
            { path: 'job-cards',   name: 'job-cards',  component: () => import('@/pages/JobCards/Index.vue'), meta: { permission: 'job_cards.manage' } },
            { path: 'pos',   name: 'pos',  component: () => import('@/pages/POS/Index.vue'), meta: { permission: 'pos.manage' } },
            { path: 'inventory',   name: 'inventory',  component: () => import('@/pages/Inventory/Index.vue'), meta: { permission: 'inventory.manage' } },
            { path: 'payments',   name: 'payments',  component: () => import('@/pages/Payments/Index.vue'), meta: { permission: 'payments.manage' } },
            { path: 'gallery',    name: 'gallery',   component: () => import('@/pages/Gallery/Index.vue'), meta: { permission: 'gallery.manage' } },
            { path: 'reports',   name: 'reports',  component: () => import('@/pages/Reports/Index.vue'), meta: { permission: 'reports.view' } },
            { path: 'settings',   name: 'settings',  component: () => import('@/pages/Settings/Index.vue'), meta: { permission: 'settings.manage' } },
            { path: 'feedback',   name: 'feedback',  component: () => import('@/pages/Feedback/Index.vue'), meta: { permission: 'feedback.manage' } },
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
    if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
        const fallback = routes[1].children.find(route => route.meta?.permission && auth.hasPermission(route.meta.permission))
        return fallback ? { name: fallback.name } : { name: 'login' }
    }
})

export default router
