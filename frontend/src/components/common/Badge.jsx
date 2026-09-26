import React from 'react';

const Badge = ({
  children,
  variant = 'primary',
  icon = null,
  className = '',
  ...props
}) => {
  const variantClass = `custom-badge-${variant}`;
  const combinedClasses = `custom-badge ${variantClass} ${className}`.trim();

  return (
    <span className={combinedClasses} {...props}>
      {icon && <span className="d-inline-flex">{icon}</span>}
      {children}
    </span>
  );
};

export default Badge;
