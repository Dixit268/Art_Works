import React from 'react';
import { Link } from 'react-router-dom';

const NotFound = () => {
  return (
    <div className="container py-5 my-auto text-center">
      <div className="gallery-card p-5 max-w-lg mx-auto">
        <div className="display-1 fw-bold text-primary mb-2">404</div>
        <h2 className="heading-display mb-3">EXHIBITION NOT FOUND</h2>
        <p className="text-muted mb-4">
          The canvas you are looking for has either been moved or doesn't exist in our gallery archives.
        </p>
        <Link to="/" className="btn btn-primary-blue px-4 py-2">
          <i className="bi bi-house-door-fill me-2"></i> Return to Gallery Home
        </Link>
      </div>
    </div>
  );
};

export default NotFound;
