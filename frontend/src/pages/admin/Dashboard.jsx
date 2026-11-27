import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CheckCircle2, CircleX, Plus, QrCode, RefreshCw, Settings, Tags, TableProperties, UtensilsCrossed } from 'lucide-react';
import { fetchDashboard } from '../../api/dashboard';
import { parseApiError } from '../../api/client';
import { formatPrice } from '../../utils/format';
import Alert from '../../components/common/Alert';
import EmptyState from '../../components/common/EmptyState';
import { LoadingBlock } from '../../components/common/Spinner';
import PageHeader from '../../components/common/PageHeader';
import StatCard from '../../components/common/StatCard';
import StatusBadge from '../../components/common/StatusBadge';

const QUICK_ACTIONS = [
  { to: '/admin/menu-items/new', label: 'Add menu item', icon: Plus },
  { to: '/admin/categories/new', label: 'Add category', icon: Tags },
  { to: '/admin/tables/new', label: 'Add table', icon: TableProperties },
  { to: '/admin/qr', label: 'QR codes', icon: QrCode },
  { to: '/admin/settings', label: 'Restaurant settings', icon: Settings },
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
    return <LoadingBlock />;
  }

  if (state.error) {
    return (
      <Alert
        action={
          <button type="button" className="btn btn-sm btn-outline-danger btn-ic" onClick={load}>
            <RefreshCw size={15} aria-hidden="true" />
            Retry
          </button>
        }
      >
        {state.error}
      </Alert>
    );
  }

  const { stats, recent_menu_updates: recent, restaurant } = state.data;

  return (
    <div>
      <PageHeader
        title="Dashboard"
        subtitle={restaurant.name ? `Overview of ${restaurant.name}` : 'Overview of your restaurant'}
        actions={
          <button type="button" className="btn btn-outline-secondary btn-ic" onClick={load}>
            <RefreshCw size={16} aria-hidden="true" />
            Refresh
          </button>
        }
      />

      <div className="row g-3 mb-4">
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Categories" value={stats.total_categories} icon={Tags} />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Menu items" value={stats.total_menu_items} icon={UtensilsCrossed} />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Available" value={stats.available_items} icon={CheckCircle2} tone="success" />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Unavailable" value={stats.unavailable_items} icon={CircleX} tone="danger" />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Tables" value={stats.total_tables} icon={TableProperties} />
        </div>
        <div className="col-6 col-lg-4 col-xl-2">
          <StatCard label="Active QR codes" value={stats.active_qr_codes} icon={QrCode} tone="success" />
        </div>
      </div>

      <div className="row g-3">
        <div className="col-12 col-lg-4">
          <div className="card h-100">
            <div className="card-header card-title-ic">Quick actions</div>
            <div className="list-group list-group-flush">
              {QUICK_ACTIONS.map(({ to, label, icon: Icon }) => (
                <Link key={to} to={to} className="list-group-item list-group-item-action d-flex align-items-center gap-3 bg-transparent">
                  <Icon size={18} className="text-primary" aria-hidden="true" />
                  {label}
                </Link>
              ))}
            </div>
          </div>
        </div>

        <div className="col-12 col-lg-8">
          <div className="card h-100">
            <div className="card-header card-title-ic">Recent menu updates</div>
            {recent.length === 0 ? (
              <EmptyState icon={UtensilsCrossed} title="No menu items yet" action={<Link to="/admin/menu-items/new" className="btn btn-primary btn-ic"><Plus size={16} aria-hidden="true" />Add menu item</Link>}>
                Items you add will show up here.
              </EmptyState>
            ) : (
              <div className="table-responsive">
                <table className="table app-table responsive-table align-middle mb-0">
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
                        <td data-label="Item" className="fw-semibold">
                          {item.name}
                        </td>
                        <td data-label="Category">{item.category?.name}</td>
                        <td data-label="Price" className="text-end">
                          {formatPrice(item.price, restaurant.currency)}
                        </td>
                        <td data-label="Status">
                          <StatusBadge on={item.is_available} labels={['Available', 'Unavailable']} />
                        </td>
                        <td data-label="Updated" className="text-nowrap text-secondary">
                          {new Date(item.updated_at).toLocaleString()}
                        </td>
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
