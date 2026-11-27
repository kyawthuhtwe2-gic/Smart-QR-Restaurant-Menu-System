import { Search } from 'lucide-react';

export default function SearchInput({ value, onChange, placeholder = 'Search', label }) {
  return (
    <div className="input-icon">
      <Search size={17} className="input-icon-glyph" aria-hidden="true" />
      <input
        type="search"
        className="form-control has-icon"
        placeholder={placeholder}
        aria-label={label || placeholder}
        value={value}
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  );
}
