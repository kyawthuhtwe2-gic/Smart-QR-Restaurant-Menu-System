import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Download, Eye, Plus, Printer, QrCode } from 'lucide-react';
import { parseApiError } from '../../../api/client';
import { downloadTableQr, listTables } from '../../../api/tables';
import { useToast } from '../../../context/ToastContext';
import { qrFilename } from '../../../utils/qr';
import Alert from '../../../components/common/Alert';
import EmptyState from '../../../components/common/EmptyState';
import IconButton from '../../../components/common/IconButton';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import Pagination from '../../../components/common/Pagination';
import StatusBadge from '../../../components/common/StatusBadge';

export default function QrList() {
  const toast = useToast();
  const [page, setPage] = useState(1);
  const [result, setResult] = useState({ data: [], meta: null });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      setResult(await listTables({ page, per_page: 12 }));
    } catch (err) {
      setError(parseApiError(err).message);
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => {
    load();
  }, [load]);

  const download = async (table) => {
    try {
      await downloadTableQr(table.id, qrFilename(table.table_number, table.id));
      toast.success(`QR code for ${table.table_number} downloaded.`);
    } catch (err) {
      toast.error(parseApiError(err).message);
    }
  };

  return (
    <div>
      <PageHeader
        title="QR codes"
        subtitle="Preview, download or print the QR code for each table."
        actions={
          <Link to="/admin/tables/new" className="btn btn-primary btn-ic">
            <Plus size={17} aria-hidden="true" />
            Add table
          </Link>
        }
      />

      {error && <Alert>{error}</Alert>}

      {loading ? (
        <LoadingBlock />
      ) : result.data.length === 0 ? (
        <div className="card">
          <EmptyState
            icon={QrCode}
            title="No QR codes yet"
            action={
              <Link to="/admin/tables/new" className="btn btn-primary btn-ic">
                <Plus size={16} aria-hidden="true" />
                Add table
              </Link>
            }
          >
            Add a table to generate its QR code.
          </EmptyState>
        </div>
      ) : (
        <div className="row g-3">
          {result.data.map((table) => (
            <div className="col-12 col-sm-6 col-xl-4" key={table.id}>
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h2 className="h5 mb-0 card-title-ic">
                      <QrCode size={19} className="text-primary" aria-hidden="true" />
                      {table.table_number}
                    </h2>
                    <StatusBadge on={table.is_active} />
                  </div>
                  <p className="text-secondary small text-break mb-0">{table.menu_url}</p>
                </div>
                <div className="card-footer bg-transparent d-flex gap-2">
                  <IconButton icon={Eye} label={`Preview QR for ${table.table_number}`} to={`/admin/qr/${table.id}`} variant="primary" />
                  <IconButton icon={Download} label={`Download PNG for ${table.table_number}`} onClick={() => download(table)} />
                  <IconButton icon={Printer} label={`Print QR for ${table.table_number}`} to={`/admin/qr/${table.id}/print`} target="_blank" rel="noopener" />
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <Pagination meta={result.meta} onPage={setPage} />
    </div>
  );
}
