import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

export const THEME_KEY = 'smartqr_theme';
const MODES = ['light', 'dark', 'system'];

const ThemeContext = createContext(null);

function readStoredMode() {
  try {
    const stored = localStorage.getItem(THEME_KEY);
    return MODES.includes(stored) ? stored : 'system';
  } catch {
    return 'system';
  }
}

const systemQuery = () => window.matchMedia('(prefers-color-scheme: dark)');

function resolve(mode) {
  return mode === 'system' ? (systemQuery().matches ? 'dark' : 'light') : mode;
}

function applyTheme(resolved, animate) {
  const root = document.documentElement;
  if (animate) {
    root.classList.add('theme-transition');
    window.setTimeout(() => root.classList.remove('theme-transition'), 260);
  }
  root.setAttribute('data-bs-theme', resolved);
  root.style.colorScheme = resolved;
}

export function ThemeProvider({ children }) {
  const [mode, setModeState] = useState(readStoredMode);
  const [resolved, setResolved] = useState(() => resolve(readStoredMode()));

  // Follow the OS preference live while the mode is "system".
  useEffect(() => {
    if (mode !== 'system') {
      return undefined;
    }
    const query = systemQuery();
    const onChange = () => {
      const next = query.matches ? 'dark' : 'light';
      setResolved(next);
      applyTheme(next, true);
    };
    query.addEventListener('change', onChange);
    return () => query.removeEventListener('change', onChange);
  }, [mode]);

  const setMode = useCallback((next) => {
    if (!MODES.includes(next)) {
      return;
    }
    try {
      localStorage.setItem(THEME_KEY, next);
    } catch {
      /* storage unavailable (private mode): theme still applies for this session */
    }
    const nextResolved = resolve(next);
    setModeState(next);
    setResolved(nextResolved);
    applyTheme(nextResolved, true);
  }, []);

  const value = useMemo(() => ({ mode, resolved, setMode }), [mode, resolved, setMode]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (!ctx) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return ctx;
}
