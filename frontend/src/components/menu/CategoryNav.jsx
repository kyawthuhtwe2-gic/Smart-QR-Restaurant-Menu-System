import { useEffect, useRef } from 'react';

export default function CategoryNav({ categories, activeId, onSelect }) {
  const activeRef = useRef(null);

  useEffect(() => {
    activeRef.current?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
  }, [activeId]);

  return (
    <nav className="pm-nav" aria-label="Menu categories">
      <ul className="pm-nav-list">
        {categories.map((category) => {
          const active = category.id === activeId;
          return (
            <li key={category.id}>
              <button
                type="button"
                ref={active ? activeRef : null}
                className={`pm-chip${active ? ' active' : ''}`}
                aria-current={active ? 'true' : undefined}
                onClick={() => onSelect(category.id)}
              >
                {category.name}
              </button>
            </li>
          );
        })}
      </ul>
    </nav>
  );
}
