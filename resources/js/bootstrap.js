import axios from 'axios'

axios.defaults.baseURL = '/api'
axios.defaults.headers.common['Accept'] = 'application/json'

axios.interceptors.request.use(config => {
    const token = localStorage.getItem('admin_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

axios.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401) {
            localStorage.removeItem('admin_token')
            window.location.href = '/login'
        }
        return Promise.reject(err)
    }
)

window.axios = axios