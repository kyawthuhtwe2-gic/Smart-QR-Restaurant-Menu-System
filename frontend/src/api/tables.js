import client from './client';

export const listTables = (params) => client.get('/tables', { params }).then((r) => r.data);
export const getTable = (id) => client.get(`/tables/${id}`).then((r) => r.data.data);
export const createTable = (payload) => client.post('/tables', payload).then((r) => r.data.data);
export const updateTable = (id, payload) => client.put(`/tables/${id}`, payload).then((r) => r.data.data);
export const setTableActive = (id, isActive) =>
  client.patch(`/tables/${id}/status`, { is_active: isActive }).then((r) => r.data.data);
export const deleteTable = (id) => client.delete(`/tables/${id}`).then((r) => r.data);

export const getTableQr = (id) => client.get(`/tables/${id}/qr`).then((r) => r.data.data);
export const getTablePrintData = (id) => client.get(`/tables/${id}/qr/print`).then((r) => r.data.data);
export const regenerateTableQr = (id) => client.post(`/tables/${id}/qr/regenerate`).then((r) => r.data.data);

/** Download needs the Bearer header, so fetch as a blob and save it from memory. */
export async function downloadTableQr(id, filename) {
  const response = await client.get(`/tables/${id}/qr/download`, { responseType: 'blob' });
  const url = URL.createObjectURL(response.data);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}
