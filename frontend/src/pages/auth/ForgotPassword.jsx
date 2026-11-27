import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, LoaderCircle, Mail, Send } from 'lucide-react';
import { forgotPassword } from '../../api/auth';
import { parseApiError } from '../../api/client';
import Alert from '../../components/common/Alert';
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
      <h2 className="h5 fw-bold mb-1">Forgot password</h2>
      <p className="text-secondary mb-4">Enter your email and we will send a reset link.</p>
      {message && <Alert type="success">{message}</Alert>}
      {error && <Alert>{error}</Alert>}
      <FormField label="Email" name="email" type="email" icon={Mail} value={email} onChange={(e) => setEmail(e.target.value)} errors={errors} required />
      <button type="submit" className="btn btn-primary btn-ic w-100" disabled={submitting}>
        {submitting ? <LoaderCircle size={17} className="spin" aria-hidden="true" /> : <Send size={17} aria-hidden="true" />}
        {submitting ? 'Sending...' : 'Send reset link'}
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
