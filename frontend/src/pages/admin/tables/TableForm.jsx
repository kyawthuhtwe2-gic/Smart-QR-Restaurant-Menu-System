import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, LoaderCircle, Save } from 'lucide-react';
import { parseApiError } from '../../../api/client';
import { createTable, getTable, updateTable } from '../../../api/tables';
import { useToast } from '../../../context/ToastContext';
import Alert from '../../../components/common/Alert';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import CheckField from '../../../components/forms/CheckField';
import FormField from '../../../components/forms/FormField';

export default function TableForm() {
  const { id } = useParams();
  const editing = Boolean(id);
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState({ table_number: '', is_active: true });
  const [loading, setLoading] = useState(editing);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (!editing) {
      return undefined;
    }
    let active = true;
    getTable(id)
      .then((t) => active && setForm({ table_number: t.table_number, is_active: t.is_active }))
      .catch((err) => active && setError(parseApiError(err).message))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, [editing, id]);

  const handleChange = (e) => {
    const { name, type, checked, value } = e.target;
    setForm({ ...form, [name]: type === 'checkbox' ? checked : value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setErrors({});
    try {
      const saved = await (editing ? updateTable(id, form) : createTable(form));
      toast.success(editing ? 'Table updated.' : 'Table created. Its QR code is ready.');
      navigate(editing ? '/admin/tables' : `/admin/qr/${saved.id}`);
    } catch (err) {
      const parsed = parseApiError(err);
      setError(parsed.message);
      setErrors(parsed.errors);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <LoadingBlock />;
  }

  return (
    <div style={{ maxWidth: 520 }}>
      <PageHeader title={editing ? 'Edit table' : 'Add table'} />
      <div className="card">
        <form onSubmit={handleSubmit} noValidate className="card-body p-4">
          {error && <Alert>{error}</Alert>}
          <FormField label="Table number" name="table_number" value={form.table_number} onChange={handleChange} errors={errors} maxLength={50} placeholder="Table 06" required />
          <CheckField label="Active" name="is_active" checked={form.is_active} onChange={handleChange} help="Inactive tables reject QR scans. Renaming a table keeps its QR code." />
          <div className="d-flex gap-2">
            <button type="submit" className="btn btn-primary btn-ic" disabled={submitting}>
              {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <Save size={17} aria-hidden="true" />}
              {submitting ? 'Saving...' : 'Save'}
            </button>
            <Link to="/admin/tables" className="btn btn-outline-secondary btn-ic">
              <ArrowLeft size={16} aria-hidden="true" />
              Cancel
            </Link>
          </div>
          {!editing && <p className="text-secondary small mt-3 mb-0">A unique QR code is generated automatically when the table is created.</p>}
        </form>
      </div>
    </div>
  );
}
