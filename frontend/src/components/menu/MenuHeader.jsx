import { MapPin, Phone } from 'lucide-react';
import ThemeToggle from '../common/ThemeToggle';

export default function MenuHeader({ restaurant, tableNumber }) {
  return (
    <header className="pm-header">
      <div className="pm-theme">
        <ThemeToggle />
      </div>
      {restaurant.logo_url ? (
        <img src={restaurant.logo_url} alt={`${restaurant.name} logo`} className="pm-logo" />
      ) : (
        <div className="pm-logo pm-logo-fallback" aria-hidden="true">
          {restaurant.name.charAt(0).toUpperCase()}
        </div>
      )}
      <h1 className="pm-title">{restaurant.name}</h1>
      {restaurant.description && <p className="pm-subtitle">{restaurant.description}</p>}
      <span className="pm-table-badge">{tableNumber}</span>
      {(restaurant.address || restaurant.phone) && (
        <div className="pm-contact">
          {restaurant.address && (
            <span>
              <MapPin size={14} aria-hidden="true" />
              {restaurant.address}
            </span>
          )}
          {restaurant.phone && (
            <span>
              <Phone size={14} aria-hidden="true" />
              {restaurant.phone}
            </span>
          )}
        </div>
      )}
    </header>
  );
}
