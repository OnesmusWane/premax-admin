import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    const user  = ref(null)
    const token = ref(localStorage.getItem('admin_token'))

    const isLoggedIn = computed(() => !!token.value && !!user.value)

    async function login(email, password) {
        const { data } = await axios.post('/admin/login', { email, password })

        // Store token
        token.value = data.token
        localStorage.setItem('admin_token', data.token)

        // Store user directly from login response — no extra request needed
        user.value = data.user
    }

    async function tryRestore() {
        if (!token.value) return false
        try {
            // Only called on page refresh to re-hydrate user from token
            const { data } = await axios.get('/admin/me', {
                    headers: {
                        Authorization: `Bearer ${token.value}`
                    }
                })
            user.value = data
            return true
        } catch {
            token.value = null
            localStorage.removeItem('admin_token')
            return false
        }
    }

    async function logout() {
        await axios.post('/admin/logout').catch(() => {})
        token.value = null
        user.value  = null
        localStorage.removeItem('admin_token')
    }

    return { user, token, isLoggedIn, login, logout, tryRestore }
})