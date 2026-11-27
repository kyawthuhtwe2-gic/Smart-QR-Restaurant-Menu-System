import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Pencil, Plus, QrCode, TableProperties, Trash2 } from 'lucide-react';
import { parseApiError } from '../../../api/client';
import { deleteTable, listTables, setTableActive } from '../../../api/tables';
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

export default function TableList() {
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
      const params = { page, per_page: 10 };
      if (debouncedSearch.trim()) {
        params.search = debouncedSearch.trim();
      }
      if (status !== '') {
        params.is_active = status;
      }
      setResult(await listTables(params));
    } catch (err) {
      setError(parseApiError(err).message);
    } finally {
      setLoading(false);
    }
  }, [page, debouncedSearch, status]);

  useEffect(() => {
    load();
  }, [load]);

  const toggleActive = async (table) => {
    try {
      await setTableActive(table.id, !table.is_active);
      toast.success(`${table.table_number} ${table.is_active ? 'deactivated' : 'activated'}.`);
      await load();
    } catch (err) {
      toast.error(parseApiError(err).message);
    }
  };

  const confirmDelete = async () => {
    setDeleting(true);
    setDeleteError('');
    try {
      await deleteTable(toDelete.id);
      toast.success(`${toDelete.table_number} deleted.`);
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

  const filtered = Boolean(debouncedSearch.trim() || status !== '');

  return (
    <div>
      <PageHeader
        title="Tables"
        subtitle="Each table has its own QR code. Inactive tables reject QR scans."
        actions={
          <Link to="/admin/tables/new" className="btn btn-primary btn-ic">
            <Plus size={17} aria-hidden="true" />
            Add table
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
              placeholder="Search tables"
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
            icon={TableProperties}
            title={filtered ? 'No matching tables' : 'No tables yet'}
            action={
              !filtered && (
                <Link to="/admin/tables/new" className="btn btn-primary btn-ic">
                  <Plus size={16} aria-hidden="true" />
                  Add table
                </Link>
              )
            }
          >
            {filtered ? 'Try a different search or status filter.' : 'Add a table to generate its QR code.'}
          </EmptyState>
        ) : (
          <div className="table-responsive">
            <table className="table app-table responsive-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Table</th>
                  <th>Status</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {result.data.map((table) => (
                  <tr key={table.id}>
                    <td data-label="Table" className="fw-semibold">
                      {table.table_number}
                    </td>
                    <td data-label="Status">
                      <StatusBadge on={table.is_active} onClick={() => toggleActive(table)} title="Click to toggle. Inactive tables reject QR scans." />
                    </td>
                    <td data-label="" className="text-end text-nowrap cell-actions">
                      <div className="d-inline-flex gap-2">
                        <IconButton icon={QrCode} label={`QR code for ${table.table_number}`} to={`/admin/qr/${table.id}`} />
                        <IconButton icon={Pencil} label={`Edit ${table.table_number}`} to={`/admin/tables/${table.id}/edit`} variant="outline-primary" />
                        <IconButton
                          icon={Trash2}
                          label={`Delete ${table.table_number}`}
                          variant="outline-danger"
                          onClick={() => {
                            setDeleteError('');
                            setToDelete(table);
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
        <ConfirmModal title="Delete table" confirmLabel="Delete" danger busy={deleting} error={deleteError} onConfirm={confirmDelete} onCancel={() => setToDelete(null)}>
          Delete <strong>{toDelete.table_number}</strong>? Its printed QR code will stop working.
        </ConfirmModal>
      )}
    </div>
  );
}
