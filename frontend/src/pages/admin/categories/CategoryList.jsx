import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Pencil, Plus, Tags, Trash2 } from 'lucide-react';
import { deleteCategory, listCategories, updateCategory } from '../../../api/categories';
import { parseApiError } from '../../../api/client';
import { useToast } from '../../../context/ToastContext';
import useDebounce from '../../../hooks/useDebounce';
import Alert from '../../../components/common/Alert';
import ConfirmModal from '../../../components/common/ConfirmModal';
import EmptyState from '../../../components/common/EmptyState';
import IconButton from '../../../components/common/IconButton';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import Pagination from '../../../components/common/Pagination';
import SearchInput from '../../../components/common/SearchInput';
import StatusBadge from '../../../components/common/StatusBadge';

export default function CategoryList() {
  const toast = useToast();
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [result, setResult] = useState({ data: [], meta: null });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [toDelete, setToDelete] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [deleteError, setDeleteError] = useState('');
  const debouncedSearch = useDebounce(search);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page, per_page: 10, sort_by: 'sort_order', sort_dir: 'asc' };
      if (debouncedSearch.trim()) {
        params.search = debouncedSearch.trim();
      }
      if (status !== '') {
        params.is_active = status;
      }
      setResult(await listCategories(params));
    } catch (err) {
      setError(parseApiError(err).message);
    } finally {
      setLoading(false);
    }
  }, [page, debouncedSearch, status]);

  useEffect(() => {
    load();
  }, [load]);

  const toggleActive = async (category) => {
    try {
      await updateCategory(category.id, { is_active: !category.is_active });
      toast.success(`${category.name} is now ${category.is_active ? 'inactive' : 'active'}.`);
      await load();
    } catch (err) {
      toast.error(parseApiError(err).message);
    }
  };

  const confirmDelete = async () => {
    setDeleting(true);
    setDeleteError('');
    try {
      await deleteCategory(toDelete.id);
      toast.success(`${toDelete.name} deleted.`);
      setToDelete(null);
      if (result.data.length === 1 && page > 1) {
        setPage(page - 1);
      } else {
        await load();
      }
    } catch (err) {
      const parsed = parseApiError(err);
      setDeleteError(parsed.errors.category?.[0] ? `${parsed.message}. ${parsed.errors.category[0]}` : parsed.message);
    } finally {
      setDeleting(false);
    }
  };

  const filtered = Boolean(debouncedSearch.trim() || status !== '');

  return (
    <div>
      <PageHeader
        title="Categories"
        subtitle="Group your dishes. Inactive categories are hidden from customers."
        actions={
          <Link to="/admin/categories/new" className="btn btn-primary btn-ic">
            <Plus size={17} aria-hidden="true" />
            Add category
          </Link>
        }
      />

      <div className="card toolbar-card">
        <div className="row g-2">
          <div className="col-12 col-md-6">
            <SearchInput
              value={search}
              onChange={(v) => {
                setSearch(v);
                setPage(1);
              }}
              placeholder="Search categories"
            />
          </div>
          <div className="col-12 col-md-3">
            <select
              className="form-select"
              aria-label="Filter by status"
              value={status}
              onChange={(e) => {
                setStatus(e.target.value);
                setPage(1);
              }}
            >
              <option value="">All statuses</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
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
            icon={Tags}
            title={filtered ? 'No matching categories' : 'No categories yet'}
            action={
              !filtered && (
                <Link to="/admin/categories/new" className="btn btn-primary btn-ic">
                  <Plus size={16} aria-hidden="true" />
                  Add category
                </Link>
              )
            }
          >
            {filtered ? 'Try a different search or status filter.' : 'Create your first category to start building the menu.'}
          </EmptyState>
        ) : (
          <div className="table-responsive">
            <table className="table app-table responsive-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Name</th>
                  <th className="text-center">Items</th>
                  <th>Status</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {result.data.map((category) => (
                  <tr key={category.id}>
                    <td data-label="Order">{category.sort_order}</td>
                    <td data-label="Name">
                      <div className="fw-semibold">{category.name}</div>
                      {category.description && <div className="text-secondary small">{category.description}</div>}
                    </td>
                    <td data-label="Items" className="text-md-center">
                      {category.menu_items_count}
                    </td>
                    <td data-label="Status">
                      <StatusBadge on={category.is_active} onClick={() => toggleActive(category)} />
                    </td>
                    <td data-label="" className="text-end text-nowrap cell-actions">
                      <div className="d-inline-flex gap-2">
                        <IconButton icon={Pencil} label={`Edit ${category.name}`} to={`/admin/categories/${category.id}/edit`} variant="outline-primary" />
                        <IconButton
                          icon={Trash2}
                          label={`Delete ${category.name}`}
                          variant="outline-danger"
                          onClick={() => {
                            setDeleteError('');
                            setToDelete(category);
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
        <ConfirmModal title="Delete category" confirmLabel="Delete" danger busy={deleting} error={deleteError} onConfirm={confirmDelete} onCancel={() => setToDelete(null)}>
          Delete <strong>{toDelete.name}</strong>?
          {toDelete.menu_items_count > 0 && (
            <div className="text-secondary small mt-2">
              This category has {toDelete.menu_items_count} menu item(s). The server will refuse to delete it until they are moved or removed.
            </div>
          )}
        </ConfirmModal>
      )}
    </div>
  );
}
