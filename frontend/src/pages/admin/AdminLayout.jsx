import React from 'react';
import { NavLink, Outlet, useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';

const AdminLayout = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/admin/login');
  };

  return (
    <div className="d-flex min-vh-100 bg-light">
      {/* Sidebar */}
      <aside className="admin-sidebar d-flex flex-column flex-shrink-0 p-3">
        {/* Brand */}
        <Link to="/admin" className="d-flex align-items-center gap-2 mb-4 p-2 text-white text-decoration-none">
          <div 
            className="d-flex align-items-center justify-content-center text-white rounded-3" 
            style={{ width: '38px', height: '38px', background: 'linear-gradient(135deg, #38BDF8, #1B4DFF)' }}
          >
            <i className="bi bi-shield-shaded fs-5"></i>
          </div>
          <div>
            <span className="brand-font fs-5 fw-bold text-white tracking-wide d-block">AURA LUXE</span>
            <span className="small text-white-50 text-uppercase" style={{ fontSize: '0.7rem' }}>Curator Portal</span>
          </div>
        </Link>

        <hr className="border-secondary border-opacity-50 my-2" />

        {/* Navigation links */}
        <ul className="nav nav-pills flex-column mb-auto gap-1">
          <li className="nav-item">
            <NavLink 
              to="/admin" 
              end
              className={({ isActive }) => `admin-nav-item ${isActive ? 'active' : ''}`}
            >
              <i className="bi bi-grid-1x2-fill"></i>
              <span>Dashboard</span>
            </NavLink>
          </li>
          <li className="nav-item">
            <NavLink 
              to="/admin/artworks" 
              className={({ isActive }) => `admin-nav-item ${isActive ? 'active' : ''}`}
            >
              <i className="bi bi-palette-fill"></i>
              <span>Artworks</span>
            </NavLink>
          </li>
          <li className="nav-item">
            <NavLink 
              to="/admin/categories" 
              className={({ isActive }) => `admin-nav-item ${isActive ? 'active' : ''}`}
            >
              <i className="bi bi-collection-fill"></i>
              <span>Categories</span>
            </NavLink>
          </li>
          <li className="nav-item">
            <NavLink 
              to="/admin/inquiries" 
              className={({ isActive }) => `admin-nav-item ${isActive ? 'active' : ''}`}
            >
              <i className="bi bi-chat-square-text-fill"></i>
              <span>Inquiries</span>
            </NavLink>
          </li>
          <li className="nav-item">
            <NavLink 
              to="/admin/revenue" 
              className={({ isActive }) => `admin-nav-item ${isActive ? 'active' : ''}`}
            >
              <i className="bi bi-graph-up-arrow"></i>
              <span>Revenue</span>
            </NavLink>
          </li>
          <li className="nav-item">
            <NavLink 
              to="/admin/users" 
              className={({ isActive }) => `admin-nav-item ${isActive ? 'active' : ''}`}
            >
              <i className="bi bi-people-fill"></i>
              <span>Users</span>
            </NavLink>
          </li>
        </ul>

        <hr className="border-secondary border-opacity-50 my-3" />

        {/* User & Actions */}
        <div className="p-2 mb-2">
          <div className="small text-white-50">Signed in as</div>
          <div className="fw-bold text-white text-truncate">{user?.name || 'Administrator'}</div>
          <div className="badge bg-primary mt-1">Curator Admin</div>
        </div>

        <div className="d-flex flex-column gap-2">
          <Link to="/" className="btn btn-sm btn-outline-light rounded-3 text-start">
            <i className="bi bi-eye-fill me-2"></i> View Public Site
          </Link>
          <button onClick={handleLogout} className="btn btn-sm btn-danger rounded-3 text-start">
            <i className="bi bi-box-arrow-left me-2"></i> Sign Out
          </button>
        </div>
      </aside>

      {/* Main Content Area */}
      <div className="flex-grow-1 d-flex flex-column overflow-auto">
        <header className="bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
          <h5 className="mb-0 fw-bold brand-font text-dark">Management Console</h5>
          <div className="d-flex align-items-center gap-3">
            <span className="small text-muted">Aura Luxe v2.0 REST</span>
            <div className="rounded-circle bg-success" style={{ width: '10px', height: '10px' }} title="API Connected"></div>
          </div>
        </header>

        <main className="p-4 flex-grow-1">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default AdminLayout;
