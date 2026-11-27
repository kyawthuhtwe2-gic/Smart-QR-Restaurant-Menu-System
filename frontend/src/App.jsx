import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './routes/ProtectedRoute';
import AuthLayout from './layouts/AuthLayout';
import AdminLayout from './layouts/AdminLayout';
import Login from './pages/auth/Login';
import ForgotPassword from './pages/auth/ForgotPassword';
import ResetPassword from './pages/auth/ResetPassword';
import Dashboard from './pages/admin/Dashboard';
import CategoryList from './pages/admin/categories/CategoryList';
import CategoryForm from './pages/admin/categories/CategoryForm';
import MenuItemList from './pages/admin/menu/MenuItemList';
import MenuItemForm from './pages/admin/menu/MenuItemForm';
import TableList from './pages/admin/tables/TableList';
import TableForm from './pages/admin/tables/TableForm';
import QrList from './pages/admin/qr/QrList';
import QrDetail from './pages/admin/qr/QrDetail';
import QrPrint from './pages/admin/qr/QrPrint';
import Settings from './pages/admin/settings/Settings';
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
        <Route path="/admin/qr/:id/print" element={<QrPrint />} />

        <Route element={<AdminLayout />}>
          <Route path="/admin" element={<Dashboard />} />
          <Route path="/admin/categories" element={<CategoryList />} />
          <Route path="/admin/categories/new" element={<CategoryForm />} />
          <Route path="/admin/categories/:id/edit" element={<CategoryForm />} />
          <Route path="/admin/menu-items" element={<MenuItemList />} />
          <Route path="/admin/menu-items/new" element={<MenuItemForm />} />
          <Route path="/admin/menu-items/:id/edit" element={<MenuItemForm />} />
          <Route path="/admin/tables" element={<TableList />} />
          <Route path="/admin/tables/new" element={<TableForm />} />
          <Route path="/admin/tables/:id/edit" element={<TableForm />} />
          <Route path="/admin/qr" element={<QrList />} />
          <Route path="/admin/qr/:id" element={<QrDetail />} />
          <Route path="/admin/settings" element={<Settings />} />
        </Route>
      </Route>

      <Route path="*" element={<Navigate to="/admin" replace />} />
    </Routes>
  );
}
