/**
 * TokenFlow Pro — API Client
 * AJAX/Fetch wrapper for all API communications
 */

const API = {
    /**
     * Make an API request
     */
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : `${TF.apiUrl}${endpoint}`;
        
        const config = {
            method: options.method || 'GET',
            headers: {
                'X-CSRF-TOKEN': TF.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        };

        if (options.body) {
            if (options.body instanceof FormData) {
                config.body = options.body;
            } else {
                config.headers['Content-Type'] = 'application/json';
                config.body = JSON.stringify(options.body);
            }
        }

        try {
            const response = await fetch(url, config);
            
            // Handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return { success: true, data: await response.text() };
            }

            const data = await response.json();

            if (!response.ok) {
                // Handle auth errors
                if (response.status === 401) {
                    Toast.error('Session Expired', 'Please log in again.');
                    setTimeout(() => {
                        window.location.href = `${TF.baseUrl}pages/public/login.php`;
                    }, 1500);
                    return data;
                }
                
                if (response.status === 403) {
                    Toast.error('Access Denied', data.message || 'You don\'t have permission.');
                    return data;
                }
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            Toast.error('Connection Error', 'Could not reach the server. Please try again.');
            return { success: false, message: error.message };
        }
    },

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const query = new URLSearchParams(params).toString();
        const url = query ? `${endpoint}?${query}` : endpoint;
        return this.request(url);
    },

    /**
     * POST request
     */
    async post(endpoint, body = {}) {
        return this.request(endpoint, { method: 'POST', body });
    },

    /**
     * PUT request
     */
    async put(endpoint, body = {}) {
        return this.request(endpoint, { method: 'PUT', body });
    },

    /**
     * DELETE request
     */
    async delete(endpoint, body = {}) {
        return this.request(endpoint, { method: 'DELETE', body });
    },

    /**
     * Upload file
     */
    async upload(endpoint, formData) {
        return this.request(endpoint, { method: 'POST', body: formData });
    }
};
