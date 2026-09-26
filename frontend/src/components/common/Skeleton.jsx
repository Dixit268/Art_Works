import React from 'react';
import Card from './Card';

export const ArtworkSkeleton = () => {
  return (
    <Card hover={false} padding="none" className="h-100 placeholder-glow">
      <div className="placeholder bg-secondary bg-opacity-25 w-100" style={{ height: '280px' }}></div>
      <div className="p-4">
        <span className="placeholder col-4 rounded-pill mb-2 bg-secondary bg-opacity-25"></span>
        <h5 className="placeholder col-8 rounded mb-2 bg-secondary bg-opacity-25"></h5>
        <div className="placeholder col-6 rounded small mb-4 bg-secondary bg-opacity-25"></div>
        <div className="d-flex justify-content-between align-items-center pt-3 border-top">
          <span className="placeholder col-3 rounded bg-secondary bg-opacity-25"></span>
          <span className="placeholder col-3 rounded-pill bg-secondary bg-opacity-25"></span>
        </div>
      </div>
    </Card>
  );
};

export const CategorySkeleton = () => {
  return (
    <Card hover={false} padding="md" className="h-100 placeholder-glow">
      <div className="d-flex justify-content-between mb-3">
        <span className="placeholder rounded-4 bg-secondary bg-opacity-25" style={{ width: '48px', height: '48px' }}></span>
        <span className="placeholder col-3 rounded-pill bg-secondary bg-opacity-25"></span>
      </div>
      <h5 className="placeholder col-7 rounded mb-2 bg-secondary bg-opacity-25"></h5>
      <p className="placeholder col-11 rounded mb-1 bg-secondary bg-opacity-25"></p>
      <p className="placeholder col-9 rounded mb-0 bg-secondary bg-opacity-25"></p>
    </Card>
  );
};

export default ArtworkSkeleton;
