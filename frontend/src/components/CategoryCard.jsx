import React from 'react';
import { Link } from 'react-router-dom';
import Card from './common/Card';
import Badge from './common/Badge';

const CategoryCard = ({ category }) => {
  return (
    <Link 
      to={`/gallery?category=${category.id}`} 
      className="text-decoration-none text-dark h-100 d-block"
    >
      <Card hover={true} padding="md" className="h-100 d-flex flex-column justify-content-between">
        <div>
          <div className="d-flex align-items-center justify-content-between mb-3">
            <div 
              className="d-flex align-items-center justify-content-center text-white rounded-4 shadow-sm"
              style={{ 
                width: '48px', 
                height: '48px', 
                background: 'var(--accent-gradient)' 
              }}
            >
              <i className="bi bi-collection-fill fs-5 text-dark"></i>
            </div>
            <Badge variant="primary">
              {category.artworks_count || 0} Artworks
            </Badge>
          </div>

          <h5 className="fw-bold mb-2 brand-font" style={{ color: 'var(--primary-navy)' }}>
            {category.name}
          </h5>
          <p className="text-muted small mb-0 line-clamp-2">
            {category.description || 'Explore stunning compositions in this specialized medium.'}
          </p>
        </div>

        <div className="pt-4 mt-2 d-flex align-items-center fw-semibold small" style={{ color: 'var(--primary-blue)' }}>
          <span>Explore Collection</span>
          <i className="bi bi-arrow-right ms-2"></i>
        </div>
      </Card>
    </Link>
  );
};

export default CategoryCard;
