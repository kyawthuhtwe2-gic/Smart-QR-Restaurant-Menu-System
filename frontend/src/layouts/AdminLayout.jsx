import { useEffect, useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { LayoutDashboard, LogOut, Menu, QrCode, Settings, Tags, TableProperties, UserRound, UtensilsCrossed, X } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import useBranding from '../hooks/useBranding';
import ThemeToggle from '../components/common/ThemeToggle';
import IconButton from '../components/common/IconButton';

const NAV = [
  { to: '/admin', label: 'Dashboard', icon: LayoutDashboard, end: true },
  { to: '/admin/categories', label: 'Categories', icon: Tags },
  { to: '/admin/menu-items', label: 'Menu items', icon: UtensilsCrossed },
  { to: '/admin/tables', label: 'Tables', icon: TableProperties },
  { to: '/admin/qr', label: 'QR codes', icon: QrCode },
  { to: '/admin/settings', label: 'Settings', icon: Settings },
];

export default function AdminLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const branding = useBranding();
  const [open, setOpen] = useState(false);

  // Close the drawer after navigating, and lock page scroll while it is open.
  useEffect(() => {
    setOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    document.body.classList.toggle('nav-open', open);
    const onKey = (e) => e.key === 'Escape' && setOpen(false);
    document.addEventListener('keydown', onKey);
    return () => {
      document.body.classList.remove('nav-open');
      document.removeEventListener('keydown', onKey);
    };
  }, [open]);

  const handleLogout = async () => {
    await logout();
    navigate('/admin/login', { replace: true });
  };

  const initial = branding.name.charAt(0).toUpperCase();

  return (
    <>
      <aside id="admin-sidebar" className={`app-sidebar${open ? ' open' : ''}`} aria-label="Primary">
        <div className="sidebar-brand">
          <span className="brand-mark" aria-hidden="true">
            {branding.logoUrl ? <img src={branding.logoUrl} alt="" /> : initial}
          </span>
          <div className="min-w-0">
            <div className="brand-name">{branding.name}</div>
            <div className="brand-sub">Menu manager</div>
          </div>
          <IconButton icon={X} label="Close menu" variant="outline-secondary" className="ms-auto d-lg-none" onClick={() => setOpen(false)} />
        </div>

        <nav className="sidebar-nav">
          {NAV.map(({ to, label, icon: Icon, end }) => (
            <NavLink key={to} to={to} end={end} className="app-nav-link">
              <Icon size={19} aria-hidden="true" />
              {label}
            </NavLink>
          ))}
        </nav>
      </aside>

      {open && <div className="app-overlay d-lg-none" onClick={() => setOpen(false)} aria-hidden="true" />}

      <div className="app-main">
        <header className="app-topbar">
          <div className="d-flex align-items-center gap-2 min-w-0">
            <IconButton
              icon={Menu}
              label="Open menu"
              className="d-lg-none"
              aria-expanded={open}
              aria-controls="admin-sidebar"
              onClick={() => setOpen(true)}
            />
            <span className="topbar-brand d-lg-none">{branding.name}</span>
          </div>

          <div className="d-flex align-items-center gap-2 gap-sm-3">
            <ThemeToggle />
            <div className="user-chip">
              <span className="user-avatar" aria-hidden="true">
                <UserRound size={18} />
              </span>
              <div className="d-none d-md-block">
                <div className="user-name">{user?.name}</div>
                <div className="user-email">{user?.email}</div>
              </div>
            </div>
            <IconButton icon={LogOut} label="Log out" onClick={handleLogout} />
          </div>
        </header>

        <main className="app-content">
          <Outlet />
        </main>
      </div>
    </>
  );
}
