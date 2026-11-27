import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './routes/ProtectedRoute';
import AuthLayout from './layouts/AuthLayout';
import AdminLayout from './layouts/AdminLayout';
import Login from './pages/auth/Login';
import ForgotPassword from './pages/auth/ForgotPassword';
import ResetPassword from './pages/auth/ResetPassword';
import Dashboard from './pages/admin/Dashboard';
import PublicMenu from './pages/public/PublicMenu';

export default function App() {
  return (
    <Routes>
      <Route path="/menu/:token" element={<PublicMenu />} />

      <Route element={<AuthLayout />}>
        <Route path="/admin/login" element={<Login />} />
        <Route path="/admin/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
      </Route>

      <Route element={<ProtectedRoute />}>
        <Route element={<AdminLayout />}>
          <Route path="/admin" element={<Dashboard />} />
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/admin" replace />} />
    </Routes>
  );
}
