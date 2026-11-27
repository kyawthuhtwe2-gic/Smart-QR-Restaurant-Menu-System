import { CheckCircle2, CircleX } from 'lucide-react';

/**
 * Status pill with icon + text (never color alone). Pass `onClick` to make it a toggle button.
 * `labels` = [on, off].
 */
export default function StatusBadge({ on, labels = ['Active', 'Inactive'], onClick, title }) {
  const Icon = on ? CheckCircle2 : CircleX;
  const className = `app-badge ${on ? 'app-badge-success' : 'app-badge-muted'}`;
  const content = (
    <>
      <Icon size={14} aria-hidden="true" />
      {on ? labels[0] : labels[1]}
    </>
  );

  if (onClick) {
    return (
      <button type="button" className={className} onClick={onClick} title={title || 'Click to toggle'} aria-pressed={on}>
        {content}
      </button>
    );
  }
  return <span className={className}>{content}</span>;
}
