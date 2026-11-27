export default function Spinner({ fullPage = false }) {
  const spinner = (
    <div className="spinner-border text-primary" role="status">
      <span className="visually-hidden">Loading...</span>
    </div>
  );
  if (!fullPage) {
    return spinner;
  }
  return <div className="d-flex justify-content-center align-items-center vh-100">{spinner}</div>;
}
