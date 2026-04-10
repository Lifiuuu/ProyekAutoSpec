import axios from 'axios';

function resolveAuthorizationToken() {
    const metaToken = document.querySelector('meta[name="autospec-auth-token"]')?.getAttribute('content')?.trim();
    const storedToken = window.localStorage.getItem('autospec-auth-token')?.trim();
    const authToken = window.localStorage.getItem('autospec-auth-token')?.trim();

    return authToken || metaToken || storedToken || '';
}

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

export const authApiClient = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
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

authApiClient.interceptors.request.use((config) => {
    const token = resolveAuthorizationToken();

    if (token) {
        config.headers = config.headers || {};
        config.headers.Authorization = token.toLowerCase().startsWith('bearer ') ? token : `Bearer ${token}`;
    }

    return config;
});

window.apiClient = apiClient;
window.authApiClient = authApiClient;
