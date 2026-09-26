import React from 'react';
import { Link } from 'react-router-dom';
import Button from './common/Button';

const Footer = () => {
  return (
    <footer className="footer-custom mt-auto pt-5 pb-4">
      <div className="container">
        <div className="row g-4 pb-4 border-bottom border-secondary border-opacity-25">
          {/* Brand & About */}
          <div className="col-lg-4 col-md-6">
            <div className="d-flex align-items-center gap-2 mb-3">
              <div 
                className="d-flex align-items-center justify-content-center text-white rounded-3 shadow-sm" 
                style={{ 
                  width: '38px', 
                  height: '38px', 
                  background: 'var(--accent-gradient)' 
                }}
              >
                <i className="bi bi-palette-fill fs-6 text-dark"></i>
              </div>
              <span className="brand-font fs-4 fw-bold tracking-wide text-white">
                AURA<span style={{ color: '#38BDF8' }}>LUXE</span>
              </span>
            </div>
            <p className="text-white-50 small pe-lg-4 mb-4">
              Curating exceptional contemporary artworks, fine sculptures, and transcendent oil compositions from world-renowned masters and emerging visionary artists.
            </p>
            <div className="d-flex gap-3">
              <a href="#instagram" className="btn btn-sm btn-outline-light rounded-circle" style={{ width: '38px', height: '38px' }}>
                <i className="bi bi-instagram"></i>
              </a>
              <a href="#twitter" className="btn btn-sm btn-outline-light rounded-circle" style={{ width: '38px', height: '38px' }}>
                <i className="bi bi-twitter-x"></i>
              </a>
              <a href="#facebook" className="btn btn-sm btn-outline-light rounded-circle" style={{ width: '38px', height: '38px' }}>
                <i className="bi bi-facebook"></i>
              </a>
            </div>
          </div>

          {/* Quick Navigation */}
          <div className="col-lg-2 col-md-6 col-6">
            <h6 className="text-white fw-bold mb-3 brand-font text-uppercase">Navigation</h6>
            <ul className="list-unstyled d-flex flex-column gap-2 small">
              <li><Link to="/" className="text-white-50 text-decoration-none hover-white">Home</Link></li>
              <li><Link to="/gallery" className="text-white-50 text-decoration-none hover-white">Exhibitions</Link></li>
              <li><Link to="/gallery?availability=available" className="text-white-50 text-decoration-none hover-white">Available Art</Link></li>
              <li><Link to="/dashboard" className="text-white-50 text-decoration-none hover-white">My Wishlist</Link></li>
            </ul>
          </div>

          {/* Art Collections */}
          <div className="col-lg-2 col-md-6 col-6">
            <h6 className="text-white fw-bold mb-3 brand-font text-uppercase">Collections</h6>
            <ul className="list-unstyled d-flex flex-column gap-2 small">
              <li><Link to="/gallery?category=1" className="text-white-50 text-decoration-none hover-white">Oil Paintings</Link></li>
              <li><Link to="/gallery?category=2" className="text-white-50 text-decoration-none hover-white">Sculptures</Link></li>
              <li><Link to="/gallery?category=3" className="text-white-50 text-decoration-none hover-white">Photography</Link></li>
              <li><Link to="/gallery?category=4" className="text-white-50 text-decoration-none hover-white">Abstract Works</Link></li>
            </ul>
          </div>

          {/* Curator's Newsletter */}
          <div className="col-lg-4 col-md-6">
            <h6 className="text-white fw-bold mb-3 brand-font text-uppercase">Curator's Newsletter</h6>
            <p className="text-white-50 small mb-3">
              Subscribe to receive exclusive invitations to private gallery previews and new artwork drops.
            </p>
            <div className="input-group">
              <input 
                type="email" 
                className="form-control border-0 rounded-start-pill px-3 py-2" 
                placeholder="Enter your email..." 
                aria-label="Email Address"
              />
              <Button variant="gradient" className="rounded-end-pill px-4">
                Join
              </Button>
            </div>
          </div>
        </div>

        {/* Copyright Footer */}
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-center pt-4 small text-white-50">
          <p className="mb-0">© {new Date().getFullYear()} Aura Luxe Art Gallery. All rights reserved.</p>
          <div className="d-flex gap-3 mt-2 mt-md-0">
            <Link to="/admin/login" className="text-white-50 text-decoration-none">Curator Portal</Link>
            <span>•</span>
            <span className="text-white-50">Terms & Provenance Guarantee</span>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
