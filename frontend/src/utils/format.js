/** Format a decimal-string price in the restaurant currency; falls back to "1,234.50 CODE". */
export function formatPrice(price, currency) {
  const amount = Number(price);
  if (Number.isNaN(amount)) {
    return String(price);
  }
  try {
    // Some currencies (MMK, JPY) default to 0 decimals, which would silently round 4.75 to 5.
    // Keep the currency's usual minimum but never show fewer than 2 decimals of precision when needed.
    const { minimumFractionDigits, maximumFractionDigits } = new Intl.NumberFormat(undefined, { style: 'currency', currency }).resolvedOptions();
    return new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency,
      minimumFractionDigits,
      maximumFractionDigits: Math.max(maximumFractionDigits, 2),
    }).format(amount);
  } catch {
    const plain = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return currency ? `${plain} ${currency}` : plain;
  }
}

/** Case-insensitive match against name and description. */
export function matchesSearch(item, term) {
  const needle = term.trim().toLowerCase();
  if (!needle) {
    return true;
  }
  return `${item.name} ${item.description || ''}`.toLowerCase().includes(needle);
}
