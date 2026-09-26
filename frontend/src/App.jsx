import React from 'react';
import { Routes, Route, Outlet } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Navbar from './components/Navbar';
import Footer from './components/Footer';

// Public Pages
import Home from './pages/Home';
import Gallery from './pages/Gallery';
import ArtworkDetails from './pages/ArtworkDetails';
import Login from './pages/Login';
import Register from './pages/Register';
import UserDashboard from './pages/UserDashboard';
import NotFound from './pages/NotFound';

// Admin Pages
import AdminLayout from './pages/admin/AdminLayout';
import AdminLogin from './pages/admin/AdminLogin';
import AdminDashboard from './pages/admin/AdminDashboard';
import ArtworksList from './pages/admin/ArtworksList';
import ArtworkForm from './pages/admin/ArtworkForm';
import CategoriesList from './pages/admin/CategoriesList';
import CategoryForm from './pages/admin/CategoryForm';
import UsersList from './pages/admin/UsersList';
import UserForm from './pages/admin/UserForm';
import InquiriesList from './pages/admin/InquiriesList';
import RevenueReport from './pages/admin/RevenueReport';

// Public Layout Component
const PublicLayout = () => {
  return (
    <>
      <Navbar />
      <main className="flex-grow-1">
        <Outlet />
      </main>
      <Footer />
    </>
  );
};

function App() {
  return (
    <AuthProvider>
      <Toaster 
        position="top-right" 
        toastOptions={{
          duration: 4000,
          style: {
            background: '#ffffff',
            color: 'var(--text-dark)',
            borderRadius: '14px',
            boxShadow: '0 10px 30px rgba(13, 43, 107, 0.12)',
            border: '1px solid rgba(27, 77, 255, 0.08)',
            fontFamily: 'Inter, sans-serif',
            fontSize: '0.9rem',
            fontWeight: 500
          },
        }}
      />
      <Routes>
        {/* Public Routes with Navbar and Footer */}
        <Route element={<PublicLayout />}>
          <Route path="/" element={<Home />} />
          <Route path="/gallery" element={<Gallery />} />
          <Route path="/artworks/:id" element={<ArtworkDetails />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route 
            path="/dashboard" 
            element={
              <ProtectedRoute>
                <UserDashboard />
              </ProtectedRoute>
            } 
          />
          <Route path="*" element={<NotFound />} />
        </Route>

        {/* Dedicated Admin Login */}
        <Route path="/admin/login" element={<AdminLogin />} />

        {/* Protected Admin Routes */}
        <Route 
          path="/admin" 
          element={
            <ProtectedRoute adminOnly={true}>
              <AdminLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<AdminDashboard />} />
          <Route path="artworks" element={<ArtworksList />} />
          <Route path="artworks/new" element={<ArtworkForm />} />
          <Route path="artworks/edit/:id" element={<ArtworkForm />} />
          <Route path="categories" element={<CategoriesList />} />
          <Route path="categories/new" element={<CategoryForm />} />
          <Route path="categories/edit/:id" element={<CategoryForm />} />
          <Route path="users" element={<UsersList />} />
          <Route path="users/new" element={<UserForm />} />
          <Route path="users/edit/:id" element={<UserForm />} />
          <Route path="inquiries" element={<InquiriesList />} />
          <Route path="revenue" element={<RevenueReport />} />
        </Route>
      </Routes>
    </AuthProvider>
  );
}

export default App;
