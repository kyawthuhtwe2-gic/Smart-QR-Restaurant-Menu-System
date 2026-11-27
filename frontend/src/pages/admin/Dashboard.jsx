import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchDashboard } from '../../api/dashboard';
import { parseApiError } from '../../api/client';
import { formatPrice } from '../../utils/format';
import Spinner from '../../components/common/Spinner';
import StatCard from '../../components/common/StatCard';

const QUICK_ACTIONS = [
  { to: '/admin/categories', label: 'Manage categories' },
  { to: '/admin/menu-items', label: 'Manage menu items' },
  { to: '/admin/tables', label: 'Manage tables' },
  { to: '/admin/qr', label: 'QR codes' },
  { to: '/admin/settings', label: 'Restaurant settings' },
];

export default function Dashboard() {
  const [state, setState] = useState({ loading: true, error: '', data: null });

  const load = useCallback(async () => {
    setState({ loading: true, error: '', data: null });
    try {
      setState({ loading: false, error: '', data: await fetchDashboard() });
    } catch (err) {
      setState({ loading: false, error: parseApiError(err).message, data: null });
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  if (state.loading) {
    return (
      <div className="text-center py-5">
        <Spinner />
      </div>
    );
  }

  if (state.error) {
    return (
      <div className="alert alert-danger d-flex justify-content-between align-items-center">
        <span>{state.error}</span>
        <button type="button" className="btn btn-sm btn-outline-danger" onClick={load}>
          Retry
        </button>
      </div>
    );
  }

  const { stats, recent_menu_updates: recent, restaurant } = state.data;

  return (
    <div>
      <h1 className="h4 mb-4">Dashboard{restaurant.name ? ` – ${restaurant.name}` : ''}</h1>

      <div className="row g-3 mb-4">
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Categories" value={stats.total_categories} />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Menu items" value={stats.total_menu_items} />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Available" value={stats.available_items} tone="success" />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Unavailable" value={stats.unavailable_items} tone="danger" />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Tables" value={stats.total_tables} />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Active QR codes" value={stats.active_qr_codes} tone="success" />
        </div>
      </div>

      <div className="row g-3">
        <div className="col-12 col-lg-4">
          <div className="card shadow-sm h-100">
            <div className="card-header fw-semibold">Quick actions</div>
            <div className="list-group list-group-flush">
              {QUICK_ACTIONS.map((action) => (
                <Link key={action.to} to={action.to} className="list-group-item list-group-item-action">
                  {action.label}
                </Link>
              ))}
            </div>
          </div>
        </div>

        <div className="col-12 col-lg-8">
          <div className="card shadow-sm h-100">
            <div className="card-header fw-semibold">Recent menu updates</div>
            {recent.length === 0 ? (
              <div className="card-body text-muted">No menu items yet.</div>
            ) : (
              <div className="table-responsive">
                <table className="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Category</th>
                      <th className="text-end">Price</th>
                      <th>Status</th>
                      <th>Updated</th>
                    </tr>
                  </thead>
                  <tbody>
                    {recent.map((item) => (
                      <tr key={item.id}>
                        <td>{item.name}</td>
                        <td>{item.category?.name}</td>
                        <td className="text-end">{formatPrice(item.price, restaurant.currency)}</td>
                        <td>
                          <span className={`badge ${item.is_available ? 'text-bg-success' : 'text-bg-secondary'}`}>
                            {item.is_available ? 'Available' : 'Unavailable'}
                          </span>
                        </td>
                        <td className="text-nowrap">{new Date(item.updated_at).toLocaleString()}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
