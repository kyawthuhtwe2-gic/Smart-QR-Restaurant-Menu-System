export default function MenuSearch({ value, onChange }) {
  return (
    <div className="pm-search">
      <input
        type="search"
        className="form-control form-control-lg"
        placeholder="Search the menu"
        aria-label="Search the menu"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        maxLength={100}
      />
    </div>
  );
}
