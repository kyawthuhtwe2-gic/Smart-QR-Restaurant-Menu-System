import { LoaderCircle } from 'lucide-react';

export default function Spinner({ fullPage = false, label = 'Loading' }) {
  const spinner = (
    <span className="d-inline-flex align-items-center gap-2 text-primary" role="status">
      <LoaderCircle size={fullPage ? 32 : 22} className="spin" aria-hidden="true" />
      <span className="visually-hidden">{label}</span>
    </span>
  );
  if (!fullPage) {
    return spinner;
  }
  return <div className="d-flex justify-content-center align-items-center vh-100">{spinner}</div>;
}

/** Centered loading block for cards and tables. */
export function LoadingBlock() {
  return (
    <div className="text-center py-5">
      <Spinner />
    </div>
  );
}
