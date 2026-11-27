import { useState } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { parseApiError } from '../../api/client';
import FormField from '../../components/forms/FormField';

export default function Login() {
  const { user, login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  if (user) {
    return <Navigate to="/admin" replace />;
  }

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setErrors({});
    try {
      await login(form.email, form.password);
      navigate(location.state?.from?.pathname || '/admin', { replace: true });
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
      <h2 className="h5 mb-3">Admin login</h2>
      {error && <div className="alert alert-danger">{error}</div>}
      <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} errors={errors} autoComplete="username" required />
      <FormField label="Password" name="password" type="password" value={form.password} onChange={handleChange} errors={errors} autoComplete="current-password" required />
      <button type="submit" className="btn btn-primary w-100" disabled={submitting}>
        {submitting ? 'Signing in...' : 'Sign in'}
      </button>
      <div className="text-center mt-3">
        <Link to="/admin/forgot-password">Forgot password?</Link>
      </div>
    </form>
  );
}
