import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, LoaderCircle, Save } from 'lucide-react';
import { listCategories } from '../../../api/categories';
import { parseApiError } from '../../../api/client';
import { createMenuItem, getMenuItem, updateMenuItem } from '../../../api/menuItems';
import { useToast } from '../../../context/ToastContext';
import Alert from '../../../components/common/Alert';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import CheckField from '../../../components/forms/CheckField';
import FormField from '../../../components/forms/FormField';
import ImageField from '../../../components/forms/ImageField';

const EMPTY = { category_id: '', name: '', description: '', price: '', is_available: true, is_featured: false, sort_order: '' };

export default function MenuItemForm() {
  const { id } = useParams();
  const editing = Boolean(id);
  const navigate = useNavigate();
  const toast = useToast();
  const [form, setForm] = useState(EMPTY);
  const [categories, setCategories] = useState([]);
  const [currentImage, setCurrentImage] = useState('');
  const [file, setFile] = useState(null);
  const [removeImage, setRemoveImage] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});

  useEffect(() => {
    let active = true;
    const requests = [listCategories({ per_page: 100, sort_by: 'sort_order' })];
    if (editing) {
      requests.push(getMenuItem(id));
    }
    Promise.all(requests)
      .then(([cats, item]) => {
        if (!active) {
          return;
        }
        setCategories(cats.data);
        if (item) {
          setForm({
            category_id: String(item.category_id),
            name: item.name,
            description: item.description || '',
            price: item.price,
            is_available: item.is_available,
            is_featured: item.is_featured,
            sort_order: String(item.sort_order),
          });
          setCurrentImage(item.image_url || '');
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
      category_id: form.category_id,
      name: form.name,
      description: form.description,
      price: form.price,
      is_available: form.is_available,
      is_featured: form.is_featured,
      ...(form.sort_order !== '' ? { sort_order: form.sort_order } : {}),
      ...(file ? { image: file } : {}),
      ...(editing && removeImage && !file ? { remove_image: true } : {}),
    };
    try {
      await (editing ? updateMenuItem(id, payload) : createMenuItem(payload));
      toast.success(editing ? 'Menu item updated.' : 'Menu item created.');
      navigate('/admin/menu-items');
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
    <div style={{ maxWidth: 680 }}>
      <PageHeader title={editing ? 'Edit menu item' : 'Add menu item'} />
      <div className="card">
        <form onSubmit={handleSubmit} noValidate className="card-body p-4">
          {error && <Alert>{error}</Alert>}
          <FormField label="Category" name="category_id" as="select" value={form.category_id} onChange={handleChange} errors={errors} required>
            <option value="">Select a category</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </FormField>
          <FormField label="Name" name="name" value={form.name} onChange={handleChange} errors={errors} maxLength={255} required />
          <FormField label="Description" name="description" as="textarea" rows={3} value={form.description} onChange={handleChange} errors={errors} maxLength={2000} />
          <FormField label="Price" name="price" type="number" step="0.01" min="0" value={form.price} onChange={handleChange} errors={errors} required />
          <ImageField
            label="Food image"
            name="image"
            currentUrl={currentImage}
            file={file}
            removed={removeImage}
            onFile={(f) => {
              setFile(f);
              if (f) {
                setRemoveImage(false);
              }
            }}
            onRemove={() => setRemoveImage(true)}
            errors={errors}
          />
          <FormField label="Sort order" name="sort_order" type="number" min="0" value={form.sort_order} onChange={handleChange} errors={errors} help="Lower numbers appear first within the category. Leave empty to add at the end." />
          <CheckField label="Available (shown on the customer menu)" name="is_available" checked={form.is_available} onChange={handleChange} />
          <CheckField label="Featured" name="is_featured" checked={form.is_featured} onChange={handleChange} />
          <div className="d-flex gap-2">
            <button type="submit" className="btn btn-primary btn-ic" disabled={submitting}>
              {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <Save size={17} aria-hidden="true" />}
              {submitting ? 'Saving...' : 'Save'}
            </button>
            <Link to="/admin/menu-items" className="btn btn-outline-secondary btn-ic">
              <ArrowLeft size={16} aria-hidden="true" />
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
