import { useEffect, useState } from 'react';
import { Coins, LoaderCircle, MapPin, Phone, Save, Store } from 'lucide-react';
import { parseApiError } from '../../../api/client';
import { getRestaurantSettings, updateRestaurantSettings } from '../../../api/settings';
import { useToast } from '../../../context/ToastContext';
import Alert from '../../../components/common/Alert';
import { LoadingBlock } from '../../../components/common/Spinner';
import PageHeader from '../../../components/common/PageHeader';
import CheckField from '../../../components/forms/CheckField';
import FormField from '../../../components/forms/FormField';
import ImageField from '../../../components/forms/ImageField';

const EMPTY = { name: '', description: '', address: '', phone: '', currency: 'USD', is_active: true };

function Section({ icon: Icon, title, children }) {
  return (
    <section className="mb-4">
      <h2 className="h6 card-title-ic text-secondary text-uppercase mb-3">
        <Icon size={17} aria-hidden="true" />
        {title}
      </h2>
      {children}
    </section>
  );
}

export default function Settings() {
  const toast = useToast();
  const [form, setForm] = useState(EMPTY);
  const [logoUrl, setLogoUrl] = useState('');
  const [file, setFile] = useState(null);
  const [removeLogo, setRemoveLogo] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});

  const apply = (restaurant) => {
    if (!restaurant) {
      return;
    }
    setForm({
      name: restaurant.name,
      description: restaurant.description || '',
      address: restaurant.address || '',
      phone: restaurant.phone || '',
      currency: restaurant.currency,
      is_active: restaurant.is_active,
    });
    setLogoUrl(restaurant.logo_url || '');
  };

  useEffect(() => {
    let active = true;
    getRestaurantSettings()
      .then((restaurant) => active && apply(restaurant))
      .catch((err) => active && setError(parseApiError(err).message))
      .finally(() => active && setLoading(false));
    return () => {
      active = false;
    };
  }, []);

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
      const restaurant = await updateRestaurantSettings({
        ...form,
        ...(file ? { logo: file } : {}),
        ...(removeLogo && !file ? { remove_logo: true } : {}),
      });
      apply(restaurant);
      setFile(null);
      setRemoveLogo(false);
      toast.success('Settings saved. The customer menu is updated.');
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
    <div style={{ maxWidth: 720 }}>
      <PageHeader title="Restaurant settings" subtitle="Shown to customers at the top of the menu." />
      <div className="card">
        <form onSubmit={handleSubmit} noValidate className="card-body p-4">
          {error && <Alert>{error}</Alert>}

          <Section icon={Store} title="Profile">
            <FormField label="Restaurant name" name="name" value={form.name} onChange={handleChange} errors={errors} maxLength={255} required />
            <FormField label="Description" name="description" as="textarea" rows={3} value={form.description} onChange={handleChange} errors={errors} maxLength={2000} />
            <ImageField
              label="Logo"
              name="logo"
              currentUrl={logoUrl}
              file={file}
              removed={removeLogo}
              onFile={(f) => {
                setFile(f);
                if (f) {
                  setRemoveLogo(false);
                }
              }}
              onRemove={() => setRemoveLogo(true)}
              errors={errors}
            />
          </Section>

          <Section icon={MapPin} title="Contact information">
            <FormField label="Address" name="address" icon={MapPin} value={form.address} onChange={handleChange} errors={errors} maxLength={255} />
            <FormField label="Phone" name="phone" type="tel" icon={Phone} value={form.phone} onChange={handleChange} errors={errors} maxLength={50} />
          </Section>

          <Section icon={Coins} title="Currency">
            <FormField
              label="Currency code"
              name="currency"
              value={form.currency}
              onChange={handleChange}
              errors={errors}
              maxLength={3}
              style={{ textTransform: 'uppercase', maxWidth: 160 }}
              help="3-letter ISO code, for example USD, EUR, THB or MMK."
              required
            />
          </Section>

          <Section icon={Store} title="Visibility">
            <CheckField label="Restaurant active" name="is_active" checked={form.is_active} onChange={handleChange} help="When off, every customer QR code shows as invalid." />
          </Section>

          <button type="submit" className="btn btn-primary btn-ic" disabled={submitting}>
            {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <Save size={17} aria-hidden="true" />}
            {submitting ? 'Saving...' : 'Save settings'}
          </button>
        </form>
      </div>
    </div>
  );
}
