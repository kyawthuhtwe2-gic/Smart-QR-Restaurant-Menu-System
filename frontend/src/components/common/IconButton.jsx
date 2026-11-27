import { Link } from 'react-router-dom';

/**
 * Icon-only button/link. `label` is required: it becomes the tooltip (title) and the
 * accessible name (aria-label). Pass `to` to render a router link.
 */
export default function IconButton({ icon: Icon, label, to, variant = 'outline-secondary', size = 17, className = '', ...rest }) {
  const classes = `btn btn-${variant} btn-sm btn-icon ${className}`.trim();
  const glyph = <Icon size={size} aria-hidden="true" />;

  if (to) {
    return (
      <Link to={to} className={classes} title={label} aria-label={label} {...rest}>
        {glyph}
      </Link>
    );
  }
  return (
    <button type="button" className={classes} title={label} aria-label={label} {...rest}>
      {glyph}
    </button>
  );
}
