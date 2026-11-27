/** Safe download filename, e.g. "Table 01" -> "qr-table-01.png". */
export function qrFilename(tableNumber, id) {
  const slug = String(tableNumber)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  return `qr-${slug || `table-${id}`}.png`;
}
