import client from './client';

export const getRestaurantSettings = () => client.get('/settings/restaurant').then((r) => r.data.data.restaurant);

/** Multipart update (logo), sent as POST with _method=PUT. */
export const updateRestaurantSettings = (payload) => {
  const form = new FormData();
  Object.entries({ ...payload, _method: 'PUT' }).forEach(([key, value]) => {
    if (value === undefined || value === null) {
      return;
    }
    form.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
  });
  return client.post('/settings/restaurant', form).then((r) => r.data.data.restaurant);
};
