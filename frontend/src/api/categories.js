import client from './client';

export const listCategories = (params) => client.get('/categories', { params }).then((r) => r.data);
export const getCategory = (id) => client.get(`/categories/${id}`).then((r) => r.data.data);
export const createCategory = (payload) => client.post('/categories', payload).then((r) => r.data.data);
export const updateCategory = (id, payload) => client.put(`/categories/${id}`, payload).then((r) => r.data.data);
export const deleteCategory = (id) => client.delete(`/categories/${id}`).then((r) => r.data);
