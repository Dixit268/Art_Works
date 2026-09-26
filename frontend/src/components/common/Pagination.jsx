import React from 'react';

const Pagination = ({
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  className = '',
}) => {
  if (totalPages <= 1) return null;

  const pages = [];
  for (let i = 1; i <= totalPages; i++) {
    pages.push(i);
  }

  return (
    <div className={`d-flex justify-content-center align-items-center gap-2 ${className}`}>
      <button
        type="button"
        className="btn btn-light border pagination-pill px-3"
        disabled={currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
      >
        <i className="bi bi-chevron-left me-1"></i> Prev
      </button>

      {pages.map((p) => {
        const isActive = p === currentPage;
        return (
          <button
            key={p}
            type="button"
            className={`btn pagination-pill ${isActive ? 'btn-primary custom-btn-primary shadow-sm' : 'btn-light border'}`}
            onClick={() => onPageChange(p)}
          >
            {p}
          </button>
        );
      })}

      <button
        type="button"
        className="btn btn-light border pagination-pill px-3"
        disabled={currentPage >= totalPages}
        onClick={() => onPageChange(currentPage + 1)}
      >
        Next <i className="bi bi-chevron-right ms-1"></i>
      </button>
    </div>
  );
};

export default Pagination;
