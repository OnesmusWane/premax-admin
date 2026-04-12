import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    const user  = ref(null)
    const token = ref(localStorage.getItem('admin_token'))

    const isLoggedIn = computed(() => !!token.value && !!user.value)
    const permissionSlugs = computed(() => user.value?.permission_slugs ?? [])

    async function login(email, password) {
        const { data } = await axios.post('/admin/login', { email, password })
        if (data.token && data.user) {
            applySession(data.token, data.user)
        }
        return data
    }

    async function verifyTwoFactor(challengeToken, code) {
        const { data } = await axios.post('/admin/2fa/verify', {
            challenge_token: challengeToken,
            code,
        })

        if (data.token && data.user) {
            applySession(data.token, data.user)
        }

        return data
    }

    async function requestPasswordReset(email) {
        const { data } = await axios.post('/admin/password/email', { email })
        return data
    }

    async function resetPassword(payload) {
        const { data } = await axios.post('/admin/password/reset', payload)
        return data
    }

    async function requestTwoFactorRecovery(email) {
        const { data } = await axios.post('/admin/2fa/recovery/request', { email })
        return data
    }

    async function verifyTwoFactorRecovery(challengeToken, code) {
        const { data } = await axios.post('/admin/2fa/recovery/verify', {
            challenge_token: challengeToken,
            code,
        })
        return data
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
        clearSession()
    }

    function applySession(nextToken, nextUser) {
        token.value = nextToken
        user.value = nextUser
        localStorage.setItem('admin_token', nextToken)
    }

    function clearSession() {
        token.value = null
        user.value  = null
        localStorage.removeItem('admin_token')
    }

    function hasPermission(permission) {
        return permissionSlugs.value.includes(permission)
    }

    return {
        user,
        token,
        isLoggedIn,
        permissionSlugs,
        login,
        verifyTwoFactor,
        requestPasswordReset,
        resetPassword,
        requestTwoFactorRecovery,
        verifyTwoFactorRecovery,
        logout,
        tryRestore,
        hasPermission,
    }
})
