import { publicClient } from './client';

/** Fetch the full customer menu for a scanned QR token. */
export const fetchPublicMenu = (token) =>
  publicClient.get(`/public/menu/${encodeURIComponent(token)}`).then((r) => r.data.data);
