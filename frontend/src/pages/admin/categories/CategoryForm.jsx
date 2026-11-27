import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, LoaderCircle, Save } from 'lucide-react';
import { createCategory, getCategory, updateCategory } from '../../../api/categories';
import { parseApiError } from '../../../api/client';
import { useToast } from '../../../context/ToastContext';
import Alert from '../../../components/common/Alert';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import CheckField from '../../../components/forms/CheckField';
import FormField from '../../../components/forms/FormField';

const EMPTY = { name: '', description: '', is_active: true, sort_order: '' };

export default function CategoryForm() {
  const { id } = useParams();
  const editing = Boolean(id);
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState(EMPTY);
  const [loading, setLoading] = useState(editing);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (!editing) {
      return undefined;
    }
    let active = true;
    getCategory(id)
      .then((c) => {
        if (active) {
          setForm({ name: c.name, description: c.description || '', is_active: c.is_active, sort_order: String(c.sort_order) });
        }
      })
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
    const payload = {
      name: form.name,
      description: form.description || null,
      is_active: form.is_active,
      ...(form.sort_order !== '' ? { sort_order: Number(form.sort_order) } : {}),
    };
    try {
      await (editing ? updateCategory(id, payload) : createCategory(payload));
      toast.success(editing ? 'Category updated.' : 'Category created.');
      navigate('/admin/categories');
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
    <div style={{ maxWidth: 600 }}>
      <PageHeader title={editing ? 'Edit category' : 'Add category'} />
      <div className="card">
        <form onSubmit={handleSubmit} noValidate className="card-body p-4">
          {error && <Alert>{error}</Alert>}
          <FormField label="Name" name="name" value={form.name} onChange={handleChange} errors={errors} maxLength={255} required />
          <FormField label="Description" name="description" as="textarea" rows={3} value={form.description} onChange={handleChange} errors={errors} maxLength={2000} />
          <FormField label="Sort order" name="sort_order" type="number" min="0" value={form.sort_order} onChange={handleChange} errors={errors} help="Lower numbers appear first. Leave empty to add at the end." />
          <CheckField label="Active (shown on the customer menu)" name="is_active" checked={form.is_active} onChange={handleChange} />
          <div className="d-flex gap-2">
            <button type="submit" className="btn btn-primary btn-ic" disabled={submitting}>
              {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <Save size={17} aria-hidden="true" />}
              {submitting ? 'Saving...' : 'Save'}
            </button>
            <Link to="/admin/categories" className="btn btn-outline-secondary btn-ic">
              <ArrowLeft size={16} aria-hidden="true" />
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
