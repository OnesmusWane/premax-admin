import { ref } from 'vue'
import axios from 'axios'
 
export function useApi() {
    const loading = ref(false)
    const error   = ref(null)
 
    async function request(method, url, data = null, params = {}) {
        loading.value = true
        error.value   = null
        try {
            const res = await axios({ method, url, data, params })
            return res.data
        } catch (e) {
            error.value = e.response?.data?.message ?? 'An error occurred.'
            throw e
        } finally {
            loading.value = false
        }
    }
 
    const get    = (url, params)       => request('get',    url, null, params)
    const post   = (url, data)         => request('post',   url, data)
    const put    = (url, data)         => request('put',    url, data)
    const patch  = (url, data)         => request('patch',  url, data)
    const del    = (url)               => request('delete', url)
 
    return { loading, error, get, post, put, patch, del }
}