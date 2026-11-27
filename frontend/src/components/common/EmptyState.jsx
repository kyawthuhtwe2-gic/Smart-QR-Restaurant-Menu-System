export default function EmptyState({ icon: Icon, title, children, action }) {
  return (
    <div className="empty-state">
      {Icon && (
        <div className="empty-icon">
          <Icon size={26} aria-hidden="true" />
        </div>
      )}
      <h2>{title}</h2>
      {children && <p className="mb-0">{children}</p>}
      {action && <div className="mt-3">{action}</div>}
    </div>
  );
}
