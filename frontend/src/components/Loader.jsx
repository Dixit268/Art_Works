import React from 'react';

const Loader = ({ message = 'Loading art masterpieces...' }) => {
  return (
    <div className="d-flex flex-column align-items-center justify-content-center py-5 my-5">
      <div 
        className="spinner-border text-primary" 
        style={{ width: '3.5rem', height: '3.5rem', borderWidth: '0.3rem' }} 
        role="status"
      >
        <span className="visually-hidden">Loading...</span>
      </div>
      <p className="mt-3 text-muted fw-semibold">{message}</p>
    </div>
  );
};

export default Loader;
