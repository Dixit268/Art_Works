import React from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import Button from './common/Button';
import Badge from './common/Badge';

const Navbar = () => {
  const { user, isAuthenticated, isAdmin, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/');
  };

  return (
    <nav className="navbar navbar-expand-lg navbar-custom sticky-top py-3">
      <div className="container">
        {/* Brand Logo with Sticky White & Blue Text */}
        <Link className="navbar-brand d-flex align-items-center gap-2" to="/">
          <div 
            className="d-flex align-items-center justify-content-center text-white rounded-3 shadow-sm" 
            style={{ 
              width: '40px', 
              height: '40px', 
              background: 'linear-gradient(135deg, var(--primary-blue), #38BDF8)' 
            }}
          >
            <i className="bi bi-palette-fill fs-5"></i>
          </div>
          <span className="navbar-brand-text fs-4">
            AURA<span className="navbar-brand-accent">LUXE</span>
          </span>
        </Link>

        {/* Mobile Toggle */}
        <button 
          className="navbar-toggler border-0 shadow-none" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#navbarMain" 
          aria-controls="navbarMain" 
          aria-expanded="false" 
          aria-label="Toggle navigation"
        >
          <i className="bi bi-list fs-2" style={{ color: 'var(--primary-blue)' }}></i>
        </button>

        {/* Links & User State */}
        <div className="collapse navbar-collapse" id="navbarMain">
          <ul className="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">
            <li className="nav-item">
              <NavLink 
                className={({ isActive }) => `nav-link nav-link-custom ${isActive ? 'active' : ''}`} 
                to="/"
              >
                Home
              </NavLink>
            </li>
            <li className="nav-item">
              <NavLink 
                className={({ isActive }) => `nav-link nav-link-custom ${isActive ? 'active' : ''}`} 
                to="/gallery"
              >
                Exhibitions & Gallery
              </NavLink>
            </li>
          </ul>

          <div className="d-flex align-items-center gap-3 mt-3 mt-lg-0">
            {isAuthenticated ? (
              <div className="d-flex align-items-center gap-2">
                {isAdmin && (
                  <Button 
                    to="/admin" 
                    variant="outline" 
                    size="sm"
                    icon={<i className="bi bi-shield-lock-fill"></i>}
                  >
                    Admin Panel
                  </Button>
                )}
                
                <Link to="/dashboard" className="btn btn-sm btn-light border rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-2">
                  <i className="bi bi-person-circle fs-5" style={{ color: 'var(--primary-blue)' }}></i>
                  <span>{user?.name?.split(' ')[0] || 'My Account'}</span>
                </Link>

                <Button 
                  onClick={handleLogout} 
                  variant="light" 
                  size="sm"
                  className="text-danger rounded-pill px-3 py-2"
                  title="Logout"
                >
                  <i className="bi bi-box-arrow-right"></i>
                </Button>
              </div>
            ) : (
              <div className="d-flex align-items-center gap-2">
                <Link to="/login" className="btn btn-link text-decoration-none fw-semibold text-dark px-3">
                  Sign In
                </Link>
                <Button to="/register" variant="gradient" size="sm" className="px-4 py-2">
                  Register <i className="bi bi-arrow-right ms-1"></i>
                </Button>
              </div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
