import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Download, Printer, RefreshCw } from 'lucide-react';
import { parseApiError } from '../../../api/client';
import { downloadTableQr, getTableQr, regenerateTableQr } from '../../../api/tables';
import { useToast } from '../../../context/ToastContext';
import { qrFilename } from '../../../utils/qr';
import Alert from '../../../components/common/Alert';
import ConfirmModal from '../../../components/common/ConfirmModal';
import SafeSvg from '../../../components/common/SafeSvg';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import StatusBadge from '../../../components/common/StatusBadge';

export default function QrDetail() {
  const { id } = useParams();
  const toast = useToast();
  const [qr, setQr] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirming, setConfirming] = useState(false);
  const [regenerating, setRegenerating] = useState(false);
  const [regenError, setRegenError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      setQr(await getTableQr(id));
    } catch (err) {
      setError(parseApiError(err).message);
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    load();
  }, [load]);

  const download = async () => {
    try {
      await downloadTableQr(id, qrFilename(qr.table_number, id));
      toast.success('QR code downloaded.');
    } catch (err) {
      toast.error(parseApiError(err).message);
    }
  };

  const regenerate = async () => {
    setRegenerating(true);
    setRegenError('');
    try {
      await regenerateTableQr(id);
      setConfirming(false);
      toast.success('QR code regenerated. The previous printed code no longer works.');
      await load();
    } catch (err) {
      setRegenError(parseApiError(err).message);
    } finally {
      setRegenerating(false);
    }
  };

  if (loading && !qr) {
    return <LoadingBlock />;
  }
  if (error && !qr) {
    return <Alert>{error}</Alert>;
  }

  return (
    <div style={{ maxWidth: 560 }}>
      <Link to="/admin/qr" className="d-inline-flex align-items-center gap-1 mb-3">
        <ArrowLeft size={16} aria-hidden="true" />
        All QR codes
      </Link>
      <PageHeader title={qr.table_number} actions={<StatusBadge on={qr.is_active} />} />

      {error && <Alert>{error}</Alert>}
      {!qr.is_active && <Alert type="warning">This table is inactive, so customers who scan this QR code are rejected.</Alert>}

      <div className="card">
        <div className="card-body text-center p-4">
          <div className="qr-tile">
            <SafeSvg svg={qr.qr_svg} alt={`QR code for ${qr.table_number}`} />
          </div>
          <p className="text-secondary small text-break mt-3 mb-0">{qr.menu_url}</p>
        </div>
        <div className="card-footer bg-transparent d-flex flex-wrap gap-2">
          <button type="button" className="btn btn-primary btn-ic" onClick={download}>
            <Download size={17} aria-hidden="true" />
            Download PNG
          </button>
          <Link to={`/admin/qr/${id}/print`} className="btn btn-outline-secondary btn-ic" target="_blank" rel="noopener">
            <Printer size={17} aria-hidden="true" />
            Print
          </Link>
          <button type="button" className="btn btn-outline-danger btn-ic ms-auto" onClick={() => setConfirming(true)}>
            <RefreshCw size={16} aria-hidden="true" />
            Regenerate
          </button>
        </div>
      </div>

      {confirming && (
        <ConfirmModal title="Regenerate QR code" confirmLabel="Regenerate" danger busy={regenerating} error={regenError} onConfirm={regenerate} onCancel={() => setConfirming(false)}>
          This creates a new QR code for <strong>{qr.table_number}</strong>. The old one stops working immediately, so you must print and replace it.
        </ConfirmModal>
      )}
    </div>
  );
}
