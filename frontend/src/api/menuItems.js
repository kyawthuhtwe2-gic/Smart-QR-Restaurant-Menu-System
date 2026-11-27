import client from './client';

/** Build multipart form data; booleans become 1/0 and empty optional values are skipped. */
function toFormData(payload) {
  const form = new FormData();
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) {
      return;
    }
    form.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
  });
  return form;
}

export const listMenuItems = (params) => client.get('/menu-items', { params }).then((r) => r.data);
export const getMenuItem = (id) => client.get(`/menu-items/${id}`).then((r) => r.data.data);
export const createMenuItem = (payload) => client.post('/menu-items', toFormData(payload)).then((r) => r.data.data);

/** PHP cannot parse multipart PUT bodies, so updates are POST with _method=PUT. */
export const updateMenuItem = (id, payload) =>
  client.post(`/menu-items/${id}`, toFormData({ ...payload, _method: 'PUT' })).then((r) => r.data.data);

export const setMenuItemAvailability = (id, isAvailable) =>
  client.patch(`/menu-items/${id}/availability`, { is_available: isAvailable }).then((r) => r.data.data);
export const deleteMenuItem = (id) => client.delete(`/menu-items/${id}`).then((r) => r.data);
