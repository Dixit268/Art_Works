import axios from 'axios';

// Default API Base URL from Vite environment or localhost fallback
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
});

// Request Interceptor: Attach token if present and handle headers
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    // If sending FormData, delete Content-Type so Axios/browser sets multipart boundary automatically
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type'];
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor: Handle auth expiration
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Token expired or invalid
      const currentPath = window.location.pathname;
      if (!currentPath.includes('/login')) {
        // Optional: clear token
      }
    }
    return Promise.reject(error);
  }
);

/**
 * Helper to resolve artwork image URLs properly
 * Supports uploaded relative paths and external links
 */
export const getArtworkImageUrl = (artwork) => {
  if (!artwork) return '/assets/img/artwork-placeholder.jpg';
  
  if (artwork.image_type === 'upload' && artwork.image_path) {
    const backendRoot = API_BASE_URL.replace(/\/api\/?$/, '');
    const cleanPath = artwork.image_path.replace(/^\/+/, '');
    return `${backendRoot}/${cleanPath}`;
  }
  
  if (artwork.image_url) {
    return artwork.image_url;
  }
  
  return artwork.display_image || 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80';
};

export default api;
