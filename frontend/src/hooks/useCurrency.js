import { useEffect, useState } from 'react';
import { getRestaurantSettings } from '../api/settings';

/** Restaurant currency code for formatting prices in admin screens. */
export default function useCurrency() {
  const [currency, setCurrency] = useState('USD');

  useEffect(() => {
    let active = true;
    getRestaurantSettings()
      .then((restaurant) => active && restaurant?.currency && setCurrency(restaurant.currency))
      .catch(() => {});
    return () => {
      active = false;
    };
  }, []);

  return currency;
}
