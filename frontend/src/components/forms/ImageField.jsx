import { useEffect, useState } from 'react';
import { Image as ImageIcon, Trash2, Upload } from 'lucide-react';

const ACCEPT = 'image/jpeg,image/png,image/webp';
const MAX_BYTES = 2 * 1024 * 1024;

/**
 * Image picker with preview. `currentUrl` is the saved image; choosing a file replaces it,
 * and "Remove" asks the parent to clear it. Client checks are a convenience: the API re-validates.
 */
export default function ImageField({ label, name, currentUrl, file, removed, onFile, onRemove, errors = {} }) {
  const [preview, setPreview] = useState('');
  const [localError, setLocalError] = useState('');
  const error = localError || errors[name]?.[0];

  useEffect(() => {
    if (!file) {
      setPreview('');
      return undefined;
    }
    const url = URL.createObjectURL(file);
    setPreview(url);
    return () => URL.revokeObjectURL(url);
  }, [file]);

  const handleChange = (e) => {
    const chosen = e.target.files?.[0] || null;
    setLocalError('');
    if (chosen && chosen.size > MAX_BYTES) {
      setLocalError('Image must be 2 MB or smaller.');
      e.target.value = '';
      return;
    }
    onFile(chosen);
  };

  const shown = preview || (!removed && currentUrl) || '';

  return (
    <div className="mb-3 image-field">
      <div className="form-label">{label}</div>
      <div className="image-preview">
        {shown ? (
          <img src={shown} alt="Selected preview" />
        ) : (
          <span className="image-placeholder">
            <ImageIcon size={30} aria-hidden="true" />
            No image
          </span>
        )}
      </div>

      <div className="d-flex flex-wrap gap-2">
        <input id={name} name={name} type="file" accept={ACCEPT} className="image-input" onChange={handleChange} aria-describedby={`${name}-hint`} />
        <label htmlFor={name} className="btn btn-outline-secondary btn-sm btn-ic mb-0">
          <Upload size={16} aria-hidden="true" />
          {shown ? 'Replace image' : 'Upload image'}
        </label>
        {shown && (
          <button
            type="button"
            className="btn btn-outline-danger btn-sm btn-ic"
            onClick={() => {
              onFile(null);
              onRemove();
            }}
          >
            <Trash2 size={16} aria-hidden="true" />
            Remove
          </button>
        )}
      </div>
      <div id={`${name}-hint`} className="form-text">
        JPG, PNG or WebP, up to 2 MB.
      </div>
      {error && <div className="invalid-feedback d-block">{error}</div>}
    </div>
  );
}
