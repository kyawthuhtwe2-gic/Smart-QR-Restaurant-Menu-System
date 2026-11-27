import { useEffect, useState } from 'react';
import { getRestaurantSettings } from '../api/settings';

/** Restaurant name + logo for the admin shell. Falls back to the product name. */
export default function useBranding() {
  const [branding, setBranding] = useState({ name: 'Smart QR Menu', logoUrl: '' });

  useEffect(() => {
    let active = true;
    getRestaurantSettings()
      .then((restaurant) => {
        if (active && restaurant) {
          setBranding({ name: restaurant.name, logoUrl: restaurant.logo_url || '' });
        }
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, []);

  return branding;
}
