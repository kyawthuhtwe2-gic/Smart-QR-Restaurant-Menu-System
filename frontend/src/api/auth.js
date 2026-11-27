import client from './client';

export const login = (email, password) =>
  client.post('/auth/login', { email, password }).then((r) => r.data.data);

export const logout = () => client.post('/auth/logout');

export const fetchMe = () => client.get('/auth/me').then((r) => r.data.data.user);

export const forgotPassword = (email) =>
  client.post('/auth/forgot-password', { email }).then((r) => r.data);

export const resetPassword = (payload) =>
  client.post('/auth/reset-password', payload).then((r) => r.data);
