import React from 'react';

const Card = ({
  children,
  className = '',
  hover = true,
  padding = 'md',
  onClick,
  style = {},
  ...props
}) => {
  const paddingMap = {
    none: 'p-0',
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-4 p-md-5',
  };

  const padClass = paddingMap[padding] || 'p-4';
  const hoverClass = hover ? 'hover-lift' : '';
  const combinedClasses = `custom-card ${padClass} ${hoverClass} ${className}`.trim();

  return (
    <div className={combinedClasses} onClick={onClick} style={style} {...props}>
      {children}
    </div>
  );
};

export default Card;
