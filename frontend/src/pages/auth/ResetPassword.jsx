import { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { resetPassword } from '../../api/auth';
import { parseApiError } from '../../api/client';
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
      <h2 className="h5 mb-3">Reset password</h2>
      {!token && <div className="alert alert-warning">Reset token missing. Use the link from your email.</div>}
      {error && <div className="alert alert-danger">{error}</div>}
      <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} errors={errors} required />
      <FormField label="New password" name="password" type="password" value={form.password} onChange={handleChange} errors={errors} autoComplete="new-password" required />
      <FormField label="Confirm password" name="password_confirmation" type="password" value={form.password_confirmation} onChange={handleChange} errors={errors} autoComplete="new-password" required />
      <button type="submit" className="btn btn-primary w-100" disabled={submitting || !token}>
        {submitting ? 'Resetting...' : 'Reset password'}
      </button>
      <div className="text-center mt-3">
        <Link to="/admin/login">Back to login</Link>
      </div>
    </form>
  );
}
