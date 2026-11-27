import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';

/**
 * Labelled input/select/textarea with validation message.
 * Props: `icon` (Lucide component, leading glyph), password fields get a show/hide toggle.
 */
export default function FormField({ label, name, type = 'text', value, onChange, errors = {}, as = 'input', children, help, icon: Icon, ...rest }) {
  const [revealed, setRevealed] = useState(false);
  const error = errors[name]?.[0];
  const Tag = as;
  const isPassword = as === 'input' && type === 'password';
  const inputType = isPassword && revealed ? 'text' : type;
  const control = as === 'select' ? 'form-select' : 'form-control';
  const classes = [control, error ? 'is-invalid' : '', Icon ? 'has-icon' : '', isPassword ? 'has-trailing' : ''].filter(Boolean).join(' ');

  return (
    <div className="mb-3">
      <label htmlFor={name} className="form-label">
        {label}
      </label>
      <div className="input-icon">
        {Icon && <Icon size={17} className="input-icon-glyph" aria-hidden="true" />}
        <Tag
          id={name}
          name={name}
          {...(as === 'input' ? { type: inputType } : {})}
          value={value}
          onChange={onChange}
          className={classes}
          aria-invalid={error ? 'true' : undefined}
          aria-describedby={error ? `${name}-error` : help ? `${name}-help` : undefined}
          {...rest}
        >
          {children}
        </Tag>
        {isPassword && (
          <button
            type="button"
            className="input-trailing"
            aria-label={revealed ? 'Hide password' : 'Show password'}
            title={revealed ? 'Hide password' : 'Show password'}
            aria-pressed={revealed}
            onClick={() => setRevealed((v) => !v)}
          >
            {revealed ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}
          </button>
        )}
      </div>
      {help && !error && (
        <div id={`${name}-help`} className="form-text">
          {help}
        </div>
      )}
      {error && (
        <div id={`${name}-error`} className="invalid-feedback d-block">
          {error}
        </div>
      )}
    </div>
  );
}
