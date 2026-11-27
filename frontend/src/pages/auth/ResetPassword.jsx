import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ArrowLeft, KeyRound, LoaderCircle, Lock, Mail } from 'lucide-react';
import { resetPassword } from '../../api/auth';
import { parseApiError } from '../../api/client';
import Alert from '../../components/common/Alert';
import FormField from '../../components/forms/FormField';

export default function ResetPassword() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: params.get('email') || '', password: '', password_confirmation: '' });
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const token = params.get('token') || '';

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setErrors({});
    try {
      await resetPassword({ ...form, token });
      navigate('/admin/login', { replace: true });
    } catch (err) {
      const parsed = parseApiError(err);
      setError(parsed.message);
      setErrors(parsed.errors);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <h2 className="h5 fw-bold mb-1">Reset password</h2>
      <p className="text-secondary mb-4">Choose a new password (at least 8 characters).</p>
      {!token && <Alert type="warning">Reset token missing. Use the link from your email.</Alert>}
      {error && <Alert>{error}</Alert>}
      <FormField label="Email" name="email" type="email" icon={Mail} value={form.email} onChange={handleChange} errors={errors} required />
      <FormField label="New password" name="password" type="password" icon={Lock} value={form.password} onChange={handleChange} errors={errors} autoComplete="new-password" required />
      <FormField label="Confirm password" name="password_confirmation" type="password" icon={Lock} value={form.password_confirmation} onChange={handleChange} errors={errors} autoComplete="new-password" required />
      <button type="submit" className="btn btn-primary btn-ic w-100" disabled={submitting || !token}>
        {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <KeyRound size={17} aria-hidden="true" />}
        {submitting ? 'Resetting...' : 'Reset password'}
      </button>
      <div className="text-center mt-3">
        <Link to="/admin/login" className="d-inline-flex align-items-center gap-1">
          <ArrowLeft size={16} aria-hidden="true" />
          Back to login
        </Link>
      </div>
    </form>
  );
}
