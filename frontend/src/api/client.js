import axios from 'axios';

export const TOKEN_KEY = 'smartqr_admin_token';

const client = axios.create({
  // Default follows the host the page was opened from, so the same build works on
  // localhost and from a phone on the LAN. Set VITE_API_URL to override (production).
  baseURL: import.meta.env.VITE_API_URL || `http://${window.location.hostname}:8000/api/v1`,
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

/** Client for public (customer) endpoints: never sends admin credentials. */
export const publicClient = axios.create({
  baseURL: client.defaults.baseURL,
  headers: { Accept: 'application/json' },
});
