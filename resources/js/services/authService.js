import { authApiClient } from '../apiClient';

function normalizeAuthResponse(data) {
  return {
    token: data?.token || data?.access_token || '',
    user: data?.user || {
      id: null,
      name: '',
      email: '',
    },
    message: data?.message || 'Auth success',
  };
}

export async function loginWithPassword(payload) {
  const response = await authApiClient.post('/auth/login', payload, {
    headers: {
      Accept: 'application/json',
    },
  });

  return normalizeAuthResponse(response.data);
}

export async function registerWithPassword(payload) {
  const response = await authApiClient.post('/auth/register', payload, {
    headers: {
      Accept: 'application/json',
    },
  });

  return normalizeAuthResponse(response.data);
}

export async function signInWithGoogle(payload = {}) {
  const response = await authApiClient.post('/auth/google', payload, {
    headers: {
      Accept: 'application/json',
    },
  });

  return normalizeAuthResponse(response.data);
}
