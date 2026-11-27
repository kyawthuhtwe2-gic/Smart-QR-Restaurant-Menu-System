import { Outlet } from 'react-router-dom';
import { QrCode } from 'lucide-react';
import ThemeToggle from '../components/common/ThemeToggle';

export default function AuthLayout() {
  return (
    <div className="auth-wrap">
      <div className="auth-theme">
        <ThemeToggle />
      </div>
      <div style={{ width: '100%', maxWidth: 420 }}>
        <div className="auth-brand">
          <span className="brand-mark" aria-hidden="true">
            <QrCode size={26} />
          </span>
          <h1 className="h4 mb-0 fw-bold">Smart QR Menu</h1>
        </div>
        <div className="card">
          <div className="card-body p-4">
            <Outlet />
          </div>
        </div>
      </div>
    </div>
  );
}
