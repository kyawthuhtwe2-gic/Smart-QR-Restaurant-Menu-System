import { useState } from 'react';
import { Link } from 'react-router-dom';
import { forgotPassword } from '../../api/auth';
import { parseApiError } from '../../api/client';
import FormField from '../../components/forms/FormField';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError('');
    setErrors({});
    setMessage('');
    try {
      const res = await forgotPassword(email);
      setMessage(res.message);
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
      <h2 className="h5 mb-3">Forgot password</h2>
      {message && <div className="alert alert-success">{message}</div>}
      {error && <div className="alert alert-danger">{error}</div>}
      <FormField label="Email" name="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} errors={errors} required />
      <button type="submit" className="btn btn-primary w-100" disabled={submitting}>
        {submitting ? 'Sending...' : 'Send reset link'}
      </button>
      <div className="text-center mt-3">
        <Link to="/admin/login">Back to login</Link>
      </div>
    </form>
  );
}
