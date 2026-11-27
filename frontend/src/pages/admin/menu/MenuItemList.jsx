import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Image as ImageIcon, Pencil, Plus, Star, Trash2, UtensilsCrossed } from 'lucide-react';
import { listCategories } from '../../../api/categories';
import { parseApiError } from '../../../api/client';
import { deleteMenuItem, listMenuItems, setMenuItemAvailability } from '../../../api/menuItems';
import { useToast } from '../../../context/ToastContext';
import useCurrency from '../../../hooks/useCurrency';
import useDebounce from '../../../hooks/useDebounce';
import { formatPrice } from '../../../utils/format';
import Alert from '../../../components/common/Alert';
import ConfirmModal from '../../../components/common/ConfirmModal';
import EmptyState from '../../../components/common/EmptyState';
import IconButton from '../../../components/common/IconButton';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import Pagination from '../../../components/common/Pagination';
import SearchInput from '../../../components/common/SearchInput';
import StatusBadge from '../../../components/common/StatusBadge';

const SORTS = [
  { value: 'sort_order:asc', label: 'Menu order' },
  { value: 'name:asc', label: 'Name A–Z' },
  { value: 'price:asc', label: 'Price low–high' },
  { value: 'price:desc', label: 'Price high–low' },
  { value: 'updated_at:desc', label: 'Recently updated' },
];

export default function MenuItemList() {
  const toast = useToast();
  const currency = useCurrency();
  const [filters, setFilters] = useState({ search: '', category_id: '', is_available: '', is_featured: '', sort: 'sort_order:asc' });
  const [page, setPage] = useState(1);
  const [categories, setCategories] = useState([]);
  const [result, setResult] = useState({ data: [], meta: null });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [toDelete, setToDelete] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [deleteError, setDeleteError] = useState('');
  const debouncedSearch = useDebounce(filters.search);

  useEffect(() => {
    listCategories({ per_page: 100, sort_by: 'sort_order' })
      .then((r) => setCategories(r.data))
      .catch(() => {});
  }, []);

  const { category_id: categoryId, is_available: isAvailable, is_featured: isFeatured, sort } = filters;

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [sort_by, sort_dir] = sort.split(':');
      const params = { page, per_page: 10, sort_by, sort_dir };
      if (debouncedSearch.trim()) {
        params.search = debouncedSearch.trim();
      }
      if (categoryId !== '') {
        params.category_id = categoryId;
      }
      if (isAvailable !== '') {
        params.is_available = isAvailable;
      }
      if (isFeatured !== '') {
        params.is_featured = isFeatured;
      }
      setResult(await listMenuItems(params));
    } catch (err) {
      setError(parseApiError(err).message);
    } finally {
      setLoading(false);
    }
  }, [page, debouncedSearch, categoryId, isAvailable, isFeatured, sort]);

  useEffect(() => {
    load();
  }, [load]);

  const setFilter = (name) => (e) => {
    setFilters({ ...filters, [name]: e.target.value });
    setPage(1);
  };

  const toggleAvailability = async (item) => {
    try {
      await setMenuItemAvailability(item.id, !item.is_available);
      toast.success(`${item.name} is now ${item.is_available ? 'unavailable' : 'available'}.`);
      await load();
    } catch (err) {
      toast.error(parseApiError(err).message);
    }
  };

  const confirmDelete = async () => {
    setDeleting(true);
    setDeleteError('');
    try {
      await deleteMenuItem(toDelete.id);
      toast.success(`${toDelete.name} deleted.`);
      setToDelete(null);
      if (result.data.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        await load();
      }
    } catch (err) {
      setDeleteError(parseApiError(err).message);
    } finally {
      setDeleting(false);
    }
  };

  const filtered = Boolean(debouncedSearch.trim() || categoryId || isAvailable || isFeatured);

  return (
    <div>
      <PageHeader
        title="Menu items"
        subtitle="Dishes, prices and availability. Changes appear on the customer menu immediately."
        actions={
          <Link to="/admin/menu-items/new" className="btn btn-primary btn-ic">
            <Plus size={17} aria-hidden="true" />
            Add menu item
          </Link>
        }
      />

      <div className="card toolbar-card">
        <div className="row g-2">
          <div className="col-12 col-lg-3">
            <SearchInput value={filters.search} onChange={(v) => { setFilters({ ...filters, search: v }); setPage(1); }} placeholder="Search by name" />
          </div>
          <div className="col-6 col-lg-2">
            <select className="form-select" aria-label="Category" value={filters.category_id} onChange={setFilter('category_id')}>
              <option value="">All categories</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>
          <div className="col-6 col-lg-2">
            <select className="form-select" aria-label="Availability" value={filters.is_available} onChange={setFilter('is_available')}>
              <option value="">Any availability</option>
              <option value="1">Available</option>
              <option value="0">Unavailable</option>
            </select>
          </div>
          <div className="col-6 col-lg-2">
            <select className="form-select" aria-label="Featured" value={filters.is_featured} onChange={setFilter('is_featured')}>
              <option value="">Featured or not</option>
              <option value="1">Featured</option>
              <option value="0">Not featured</option>
            </select>
          </div>
          <div className="col-6 col-lg-3">
            <select className="form-select" aria-label="Sort" value={filters.sort} onChange={setFilter('sort')}>
              {SORTS.map((s) => (
                <option key={s.value} value={s.value}>
                  {s.label}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {error && <Alert>{error}</Alert>}

      <div className="card">
        {loading ? (
          <LoadingBlock />
        ) : result.data.length === 0 ? (
          <EmptyState
            icon={UtensilsCrossed}
            title={filtered ? 'No matching menu items' : 'No menu items yet'}
            action={
              !filtered && (
                <Link to="/admin/menu-items/new" className="btn btn-primary btn-ic">
                  <Plus size={16} aria-hidden="true" />
                  Add menu item
                </Link>
              )
            }
          >
            {filtered ? 'Try changing or clearing the filters.' : 'Add your first dish to build the menu.'}
          </EmptyState>
        ) : (
          <div className="table-responsive">
            <table className="table app-table responsive-table align-middle mb-0">
              <thead>
                <tr>
                  <th style={{ width: 72 }}>
                    <span className="visually-hidden">Image</span>
                  </th>
                  <th>Name</th>
                  <th>Category</th>
                  <th className="text-end">Price</th>
                  <th>Availability</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {result.data.map((item) => (
                  <tr key={item.id}>
                    <td data-label="">
                      {item.image_url ? (
                        <img src={item.image_url} alt="" className="thumb" />
                      ) : (
                        <span className="thumb" aria-hidden="true">
                          <ImageIcon size={20} />
                        </span>
                      )}
                    </td>
                    <td data-label="Name">
                      <span className="fw-semibold">{item.name}</span>
                      {item.is_featured && (
                        <span className="app-badge app-badge-warning ms-2">
                          <Star size={13} aria-hidden="true" />
                          Featured
                        </span>
                      )}
                    </td>
                    <td data-label="Category">{item.category?.name}</td>
                    <td data-label="Price" className="text-end">
                      {formatPrice(item.price, currency)}
                    </td>
                    <td data-label="Availability">
                      <StatusBadge on={item.is_available} labels={['Available', 'Unavailable']} onClick={() => toggleAvailability(item)} />
                    </td>
                    <td data-label="" className="text-end text-nowrap cell-actions">
                      <div className="d-inline-flex gap-2">
                        <IconButton icon={Pencil} label={`Edit ${item.name}`} to={`/admin/menu-items/${item.id}/edit`} variant="outline-primary" />
                        <IconButton
                          icon={Trash2}
                          label={`Delete ${item.name}`}
                          variant="outline-danger"
                          onClick={() => {
                            setDeleteError('');
                            setToDelete(item);
                          }}
                        />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <Pagination meta={result.meta} onPage={setPage} />

      {toDelete && (
        <ConfirmModal title="Delete menu item" confirmLabel="Delete" danger busy={deleting} error={deleteError} onConfirm={confirmDelete} onCancel={() => setToDelete(null)}>
          Delete <strong>{toDelete.name}</strong>? Its image will also be removed.
        </ConfirmModal>
      )}
    </div>
  );
}
