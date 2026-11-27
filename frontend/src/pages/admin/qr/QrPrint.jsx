import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Printer } from 'lucide-react';
import { parseApiError } from '../../../api/client';
import { getTablePrintData } from '../../../api/tables';
import Alert from '../../../components/common/Alert';
import SafeSvg from '../../../components/common/SafeSvg';
import Spinner from '../../../components/common/Spinner';
import './qrPrint.css';

export default function QrPrint() {
  const { id } = useParams();
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    getTablePrintData(id)
      .then((d) => active && setData(d))
      .catch((err) => active && setError(parseApiError(err).message));
    return () => {
      active = false;
    };
  }, [id]);

  if (error) {
    return (
      <div className="p-3">
        <Alert>{error}</Alert>
      </div>
    );
  }
  if (!data) {
    return <Spinner fullPage />;
  }

  return (
    <div className="qp-page">
      <div className="qp-toolbar d-print-none">
        <Link to={`/admin/qr/${id}`} className="btn btn-outline-secondary btn-ic">
          <ArrowLeft size={16} aria-hidden="true" />
          Back
        </Link>
        <button type="button" className="btn btn-primary btn-ic" onClick={() => window.print()}>
          <Printer size={17} aria-hidden="true" />
          Print
        </button>
      </div>

      <div className="qp-card">
        <div className="qp-restaurant">{data.restaurant_name}</div>
        <div className="qp-table">{data.table_number}</div>
        <SafeSvg svg={data.qr_svg} alt={`QR code for ${data.table_number}`} size={360} />
        <div className="qp-hint">Scan to view our menu</div>
      </div>
    </div>
  );
}
