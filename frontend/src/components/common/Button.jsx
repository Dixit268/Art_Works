import React from 'react';
import { Link } from 'react-router-dom';

const Button = ({
  children,
  variant = 'primary',
  size = 'md',
  icon = null,
  loading = false,
  disabled = false,
  onClick,
  type = 'button',
  className = '',
  to = null,
  ...props
}) => {
  const variantClass = `custom-btn-${variant}`;
  const sizeClass = size !== 'md' ? `custom-btn-${size}` : '';
  const combinedClasses = `custom-btn ${variantClass} ${sizeClass} ${className}`.trim();

  const content = (
    <>
      {loading && (
        <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
      )}
      {!loading && icon && <span className="d-inline-flex">{icon}</span>}
      {children}
    </>
  );

  if (to) {
    return (
      <Link to={to} className={combinedClasses} onClick={onClick} {...props}>
        {content}
      </Link>
    );
  }

  return (
    <button
      type={type}
      className={combinedClasses}
      disabled={disabled || loading}
      onClick={onClick}
      {...props}
    >
      {content}
    </button>
  );
};

export default Button;
