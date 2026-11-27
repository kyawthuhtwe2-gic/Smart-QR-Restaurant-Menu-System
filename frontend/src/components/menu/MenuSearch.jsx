import { Search, X } from 'lucide-react';

export default function MenuSearch({ value, onChange }) {
  return (
    <div className="pm-search input-icon">
      <Search size={18} className="input-icon-glyph" aria-hidden="true" />
      <input
        type="search"
        className="form-control form-control-lg has-icon has-trailing"
        placeholder="Search the menu"
        aria-label="Search the menu"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        maxLength={100}
      />
      {value && (
        <button type="button" className="input-trailing" aria-label="Clear search" title="Clear search" onClick={() => onChange('')}>
          <X size={18} aria-hidden="true" />
        </button>
      )}
    </div>
  );
}
