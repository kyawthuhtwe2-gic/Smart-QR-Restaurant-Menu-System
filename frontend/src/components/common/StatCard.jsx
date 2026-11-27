export default function StatCard({ label, value, icon: Icon, tone = 'primary' }) {
  return (
    <div className="card stat-card h-100">
      <div className="card-body">
        <span className={`stat-icon tone-${tone}`}>
          <Icon size={22} aria-hidden="true" />
        </span>
        <div className="min-w-0">
          <div className="stat-label">{label}</div>
          <div className="stat-value">{value}</div>
        </div>
      </div>
    </div>
  );
}
