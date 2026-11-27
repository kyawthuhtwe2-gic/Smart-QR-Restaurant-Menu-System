import axios from 'axios';

export const TOKEN_KEY = 'smartqr_admin_token';

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: { Accept: 'application/json' },
});

client.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLogin = error.config?.url?.includes('/auth/login');
    if (error.response?.status === 401 && !isLogin) {
      localStorage.removeItem(TOKEN_KEY);
      window.dispatchEvent(new Event('auth:logout'));
    }
    return Promise.reject(error);
  },
);

/** Extract a readable message and field errors from an API error. */
export function parseApiError(error) {
  const data = error.response?.data;
  return {
    message: data?.message || 'Network error. Please try again.',
    errors: data?.errors && typeof data.errors === 'object' ? data.errors : {},
  };
}

export default client;
