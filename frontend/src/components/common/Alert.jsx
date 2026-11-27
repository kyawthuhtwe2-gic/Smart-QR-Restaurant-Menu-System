import { CheckCircle2, CircleAlert, TriangleAlert } from 'lucide-react';

const ICONS = { danger: CircleAlert, success: CheckCircle2, warning: TriangleAlert };

export default function Alert({ type = 'danger', children, action, className = '' }) {
  const Icon = ICONS[type] || CircleAlert;
  return (
    <div className={`alert alert-${type} ${className}`.trim()} role={type === 'danger' ? 'alert' : 'status'}>
      <Icon size={18} className="mt-1" aria-hidden="true" />
      <div className="flex-grow-1">{children}</div>
      {action}
    </div>
  );
}
