import { Outlet } from 'react-router-dom';

export default function AuthLayout() {
  return (
    <div className="min-vh-100 d-flex align-items-center bg-light">
      <div className="container" style={{ maxWidth: 440 }}>
        <h1 className="h3 text-center mb-4">Smart QR Menu</h1>
        <div className="card shadow-sm">
          <div className="card-body p-4">
            <Outlet />
          </div>
        </div>
      </div>
    </div>
  );
}
