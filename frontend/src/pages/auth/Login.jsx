import { useState } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { LoaderCircle, LogIn, Mail, Lock } from 'lucide-react';
import { useAuth } from '../../context/AuthContext';
import { parseApiError } from '../../api/client';
import Alert from '../../components/common/Alert';
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
      <h2 className="h5 fw-bold mb-1">Welcome back</h2>
      <p className="text-secondary mb-4">Sign in to manage your menu.</p>
      {error && <Alert>{error}</Alert>}
      <FormField label="Email" name="email" type="email" icon={Mail} value={form.email} onChange={handleChange} errors={errors} autoComplete="username" required />
      <FormField label="Password" name="password" type="password" icon={Lock} value={form.password} onChange={handleChange} errors={errors} autoComplete="current-password" required />
      <button type="submit" className="btn btn-primary btn-ic w-100" disabled={submitting}>
        {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <LogIn size={17} aria-hidden="true" />}
        {submitting ? 'Signing in...' : 'Sign in'}
      </button>
      <div className="text-center mt-3">
        <Link to="/admin/forgot-password">Forgot password?</Link>
      </div>
    </form>
  );
}
