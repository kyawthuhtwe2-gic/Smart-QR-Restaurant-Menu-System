/**
 * Renders a server-generated QR SVG as an <img> data URI. Using <img> (not inline HTML)
 * means any script content in the markup can never execute.
 */
export default function SafeSvg({ svg, alt, size = 280 }) {
  const src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
  return <img src={src} alt={alt} width={size} height={size} style={{ maxWidth: '100%', height: 'auto' }} />;
}
