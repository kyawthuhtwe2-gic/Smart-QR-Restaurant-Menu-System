import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ meta, onPage }) {
  if (!meta || meta.last_page <= 1) {
    return null;
  }
  return (
    <nav aria-label="Pagination" className="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
      <span className="text-secondary small">
        Page {meta.current_page} of {meta.last_page} · {meta.total} total
      </span>
      <div className="d-flex gap-2">
        <button type="button" className="btn btn-outline-secondary btn-sm btn-ic" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>
          <ChevronLeft size={16} aria-hidden="true" />
          Previous
        </button>
        <button type="button" className="btn btn-outline-secondary btn-sm btn-ic" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>
          Next
          <ChevronRight size={16} aria-hidden="true" />
        </button>
      </div>
    </nav>
  );
}
