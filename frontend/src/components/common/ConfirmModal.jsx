import { useEffect, useRef } from 'react';
import { LoaderCircle, TriangleAlert, CircleAlert } from 'lucide-react';

export default function ConfirmModal({ title, children, confirmLabel = 'Confirm', danger = false, busy = false, error = '', onConfirm, onCancel }) {
  const cancelRef = useRef(null);

  useEffect(() => {
    cancelRef.current?.focus();
    const onKey = (e) => e.key === 'Escape' && !busy && onCancel();
    document.addEventListener('keydown', onKey);
    document.body.classList.add('modal-open');
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.classList.remove('modal-open');
    };
  }, [busy, onCancel]);

  const Icon = danger ? TriangleAlert : CircleAlert;

  return (
    <>
      <div className="modal d-block" tabIndex="-1" role="dialog" aria-modal="true" aria-labelledby="confirm-title" onMouseDown={(e) => e.target === e.currentTarget && !busy && onCancel()}>
        <div className="modal-dialog modal-dialog-centered">
          <div className="modal-content">
            <div className="modal-body p-4">
              <div className="d-flex gap-3">
                <span className={`confirm-icon ${danger ? 'danger' : 'primary'}`}>
                  <Icon size={22} aria-hidden="true" />
                </span>
                <div className="flex-grow-1">
                  <h2 className="h5 mb-2" id="confirm-title">
                    {title}
                  </h2>
                  {error && (
                    <div className="alert alert-danger py-2" role="alert">
                      <CircleAlert size={16} className="mt-1" aria-hidden="true" />
                      <div>{error}</div>
                    </div>
                  )}
                  <div>{children}</div>
                </div>
              </div>
            </div>
            <div className="modal-footer">
              <button ref={cancelRef} type="button" className="btn btn-outline-secondary" onClick={onCancel} disabled={busy}>
                Cancel
              </button>
              <button type="button" className={`btn btn-ic ${danger ? 'btn-danger' : 'btn-primary'}`} onClick={onConfirm} disabled={busy}>
                {busy && <LoaderCircle size={16} className="spin" aria-hidden="true" />}
                {busy ? 'Working...' : confirmLabel}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div className="modal-backdrop show" />
    </>
  );
}
