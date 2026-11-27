import { useState } from 'react';
import { Star, UtensilsCrossed } from 'lucide-react';
import { formatPrice } from '../../utils/format';

export default function MenuItemCard({ item, currency }) {
  const [imageFailed, setImageFailed] = useState(false);
  const showImage = item.image_url && !imageFailed;

  return (
    <article className="pm-card">
      {showImage ? (
        <img src={item.image_url} alt={item.name} className="pm-card-img" loading="lazy" onError={() => setImageFailed(true)} />
      ) : (
        <div className="pm-card-img pm-card-img-empty" aria-hidden="true">
          <UtensilsCrossed size={28} />
        </div>
      )}
      <div className="pm-card-body">
        <div className="pm-card-head">
          <h3 className="pm-card-title">{item.name}</h3>
          <span className="pm-price">{formatPrice(item.price, currency)}</span>
        </div>
        {item.is_featured && (
          <span className="pm-featured">
            <Star size={12} aria-hidden="true" />
            Featured
          </span>
        )}
        {item.description && <p className="pm-card-text">{item.description}</p>}
      </div>
    </article>
  );
}
