export default function StatCard({ label, value, tone = 'primary' }) {
  return (
    <div className="card h-100 shadow-sm">
      <div className="card-body">
        <div className="text-muted small text-uppercase">{label}</div>
        <div className={`display-6 fw-semibold text-${tone}`}>{value}</div>
      </div>
    </div>
  );
}
