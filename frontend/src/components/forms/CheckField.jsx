export default function CheckField({ label, name, checked, onChange, help }) {
  return (
    <div className="form-check form-switch mb-3">
      <input id={name} name={name} type="checkbox" role="switch" className="form-check-input" checked={checked} onChange={onChange} />
      <label htmlFor={name} className="form-check-label">
        {label}
      </label>
      {help && <div className="form-text">{help}</div>}
    </div>
  );
}
