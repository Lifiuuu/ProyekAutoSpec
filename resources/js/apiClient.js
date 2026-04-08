import axios from 'axios';

function resolveAuthorizationToken() {
    const metaToken = document.querySelector('meta[name="supabase-access-token"]')?.getAttribute('content')?.trim();
    const storedToken = window.localStorage.getItem('supabase-access-token')?.trim();

    return metaToken || storedToken || '';
}

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_SUPABASE_FUNCTIONS_URL || '/functions/v1',
    headers: {
        'Content-Type': 'application/json',
    },
});

apiClient.interceptors.request.use((config) => {
    const token = resolveAuthorizationToken();

    if (token) {
        config.headers = config.headers || {};
        config.headers.Authorization = token.toLowerCase().startsWith('bearer ') ? token : `Bearer ${token}`;
    }

    return config;
});

window.apiClient = apiClient;