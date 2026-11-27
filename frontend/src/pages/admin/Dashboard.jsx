import { useAuth } from '../../context/AuthContext';

export default function Dashboard() {
  const { user } = useAuth();
  return (
    <div>
      <h1 className="h4">Dashboard</h1>
      <p className="text-muted">Signed in as {user?.email}.</p>
    </div>
  );
}
