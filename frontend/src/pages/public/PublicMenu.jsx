import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import usePublicMenu from '../../hooks/usePublicMenu';
import { matchesSearch } from '../../utils/format';
import Spinner from '../../components/common/Spinner';
import MenuHeader from '../../components/menu/MenuHeader';
import MenuSearch from '../../components/menu/MenuSearch';
import CategoryNav from '../../components/menu/CategoryNav';
import MenuItemCard from '../../components/menu/MenuItemCard';
import './publicMenu.css';

function Notice({ title, children, action }) {
  return (
    <div className="pm-notice text-center">
      <h1 className="h4">{title}</h1>
      <p className="text-muted">{children}</p>
      {action}
    </div>
  );
}

export default function PublicMenu() {
  const { token } = useParams();
  const { status, data, retry } = usePublicMenu(token);
  const [search, setSearch] = useState('');
  const [activeId, setActiveId] = useState(null);

  const searching = search.trim() !== '';

  const visibleCategories = useMemo(() => {
    if (!data) {
      return [];
    }
    return data.categories
      .map((category) => ({ ...category, items: category.items.filter((item) => matchesSearch(item, search)) }))
      .filter((category) => category.items.length > 0);
  }, [data, search]);

  const featured = useMemo(
    () => (data ? data.categories.flatMap((c) => c.items).filter((item) => item.is_featured) : []),
    [data],
  );

  // Highlight the category currently at the top while scrolling.
  useEffect(() => {
    if (searching || visibleCategories.length === 0) {
      return undefined;
    }
    const observer = new IntersectionObserver(
      (entries) => {
        const hit = entries.find((entry) => entry.isIntersecting);
        if (hit) {
          setActiveId(Number(hit.target.dataset.categoryId));
        }
      },
      { rootMargin: '-120px 0px -70% 0px' },
    );
    document.querySelectorAll('[data-category-id]').forEach((el) => observer.observe(el));
    return () => observer.disconnect();
  }, [visibleCategories, searching]);

  const scrollToCategory = (id) => {
    setActiveId(id);
    document.getElementById(`category-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  if (status === 'loading') {
    return <Spinner fullPage />;
  }
  if (status === 'invalid') {
    return (
      <Notice title="QR code not valid">
        This QR code is invalid or no longer active. Please ask our staff for help.
      </Notice>
    );
  }
  if (status === 'error') {
    return (
      <Notice
        title="Menu unavailable"
        action={
          <button type="button" className="btn btn-primary" onClick={retry}>
            Try again
          </button>
        }
      >
        We could not load the menu. Check your connection and try again.
      </Notice>
    );
  }

  const { restaurant, table } = data;

  return (
    <div className="pm-page">
      <MenuHeader restaurant={restaurant} tableNumber={table.table_number} />

      <div className="pm-sticky">
        <MenuSearch value={search} onChange={setSearch} />
        {!searching && data.categories.length > 0 && (
          <CategoryNav categories={data.categories} activeId={activeId ?? data.categories[0].id} onSelect={scrollToCategory} />
        )}
      </div>

      <main className="pm-main">
        {!searching && featured.length > 0 && (
          <section className="pm-section" aria-labelledby="featured-title">
            <h2 id="featured-title" className="pm-section-title">
              Featured
            </h2>
            <div className="pm-grid">
              {featured.map((item) => (
                <MenuItemCard key={`featured-${item.id}`} item={item} currency={restaurant.currency} />
              ))}
            </div>
          </section>
        )}

        {visibleCategories.map((category) => (
          <section
            key={category.id}
            id={`category-${category.id}`}
            data-category-id={category.id}
            className="pm-section"
            aria-labelledby={`category-title-${category.id}`}
          >
            <h2 id={`category-title-${category.id}`} className="pm-section-title">
              {category.name}
            </h2>
            {category.description && !searching && <p className="pm-section-text">{category.description}</p>}
            <div className="pm-grid">
              {category.items.map((item) => (
                <MenuItemCard key={item.id} item={item} currency={restaurant.currency} />
              ))}
            </div>
          </section>
        ))}

        {visibleCategories.length === 0 && (
          <p className="text-center text-muted py-5">
            {searching ? `No dishes match "${search.trim()}".` : 'The menu is being updated. Please check back soon.'}
          </p>
        )}
      </main>

      <footer className="pm-footer text-center">
        {restaurant.address && <div>{restaurant.address}</div>}
        {restaurant.phone && <div>{restaurant.phone}</div>}
      </footer>
    </div>
  );
}
