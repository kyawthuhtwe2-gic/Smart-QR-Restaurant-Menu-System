import { useCallback, useEffect, useState } from 'react';
import { fetchPublicMenu } from '../api/publicMenu';

const REFRESH_MS = 60000;

/**
 * Loads the customer menu and keeps it fresh so price/availability changes
 * show up without a new QR code: refetch on tab focus and once a minute.
 * status: 'loading' | 'ready' | 'invalid' | 'error'
 */
export default function usePublicMenu(token) {
  const [state, setState] = useState({ status: 'loading', data: null });

  const load = useCallback(
    async (silent) => {
      try {
        const data = await fetchPublicMenu(token);
        setState({ status: 'ready', data });
      } catch (err) {
        if (err.response?.status === 404) {
          setState({ status: 'invalid', data: null });
        } else if (!silent) {
          setState({ status: 'error', data: null });
        }
      }
    },
    [token],
  );

  useEffect(() => {
    load(false);

    const refresh = () => {
      if (document.visibilityState === 'visible') {
        load(true);
      }
    };
    const timer = setInterval(refresh, REFRESH_MS);
    document.addEventListener('visibilitychange', refresh);

    return () => {
      clearInterval(timer);
      document.removeEventListener('visibilitychange', refresh);
    };
  }, [load]);

  return { ...state, retry: () => load(false) };
}
