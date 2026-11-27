import { Monitor, Moon, Sun } from 'lucide-react';
import { useTheme } from '../../context/ThemeContext';

const OPTIONS = [
  { mode: 'light', label: 'Light mode', Icon: Sun },
  { mode: 'system', label: 'System theme', Icon: Monitor },
  { mode: 'dark', label: 'Dark mode', Icon: Moon },
];

/** Three-way switch: Light / System / Dark. The thumb slides between options. */
export default function ThemeToggle() {
  const { mode, setMode } = useTheme();
  const index = Math.max(0, OPTIONS.findIndex((o) => o.mode === mode));

  const onKeyDown = (e) => {
    if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') {
      return;
    }
    e.preventDefault();
    const step = e.key === 'ArrowRight' ? 1 : -1;
    setMode(OPTIONS[(index + step + OPTIONS.length) % OPTIONS.length].mode);
  };

  return (
    <div className="theme-toggle" role="radiogroup" aria-label="Color theme" onKeyDown={onKeyDown}>
      <span className="theme-toggle-thumb" style={{ transform: `translateX(${index * 30}px)` }} aria-hidden="true" />
      {OPTIONS.map(({ mode: value, label, Icon }) => (
        <button
          key={value}
          type="button"
          role="radio"
          aria-checked={mode === value}
          aria-label={label}
          title={label}
          tabIndex={mode === value ? 0 : -1}
          onClick={() => setMode(value)}
        >
          <Icon size={16} aria-hidden="true" />
        </button>
      ))}
    </div>
  );
}
