import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import api, { getArtworkImageUrl } from '../services/api';
import { useAuth } from '../context/AuthContext';
import Card from './common/Card';
import Badge from './common/Badge';
import Button from './common/Button';

const ArtworkCard = ({ artwork, onWishlistChange }) => {
  const { isAuthenticated } = useAuth();
  const [inWishlist, setInWishlist] = useState(artwork.in_wishlist || false);
  const [loading, setLoading] = useState(false);

  const toggleWishlist = async (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (!isAuthenticated) {
      window.location.href = '/login';
      return;
    }

    setLoading(true);
    try {
      if (inWishlist) {
        await api.post('/wishlist/remove.php', { artwork_id: artwork.id });
        setInWishlist(false);
      } else {
        await api.post('/wishlist/add.php', { artwork_id: artwork.id });
        setInWishlist(true);
      }
      if (onWishlistChange) {
        onWishlistChange(artwork.id, !inWishlist);
      }
    } catch (error) {
      console.error('Failed to update wishlist:', error);
    } finally {
      setLoading(false);
    }
  };

  const imageUrl = getArtworkImageUrl(artwork);

  return (
    <Card hover={true} padding="none" className="h-100 d-flex flex-column position-relative">
      {/* Badges Container */}
      <div className="position-absolute top-0 start-0 m-3 d-flex flex-column gap-2 z-2">
        {artwork.featured && (
          <Badge variant="gradient" icon={<i className="bi bi-star-fill me-1"></i>}>
            Featured
          </Badge>
        )}
        <Badge variant={artwork.availability_status === 'available' ? 'success' : 'danger'}>
          {artwork.availability_status === 'available' ? 'Available' : 'Sold Out'}
        </Badge>
      </div>

      {/* Wishlist Button */}
      <button 
        onClick={toggleWishlist}
        disabled={loading}
        className="btn btn-light position-absolute top-0 end-0 m-3 rounded-circle shadow-sm z-2 p-2 d-flex align-items-center justify-content-center"
        style={{ width: '42px', height: '42px', backgroundColor: 'rgba(255,255,255,0.92)' }}
        title={inWishlist ? "Remove from wishlist" : "Add to wishlist"}
      >
        <i className={`bi ${inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart text-dark'} fs-5`}></i>
      </button>

      {/* Artwork Image Link */}
      <Link to={`/artworks/${artwork.id}`} className="overflow-hidden bg-light" style={{ height: '280px', display: 'block' }}>
        <img 
          src={imageUrl} 
          alt={artwork.title}
          className="w-100 h-100 object-fit-cover"
          style={{ transition: 'transform 0.5s ease' }}
          onMouseEnter={(e) => e.currentTarget.style.transform = 'scale(1.05)'}
          onMouseLeave={(e) => e.currentTarget.style.transform = 'scale(1)'}
          onError={(e) => {
            e.currentTarget.src = 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80';
          }}
        />
      </Link>

      {/* Artwork Content */}
      <div className="p-4 d-flex flex-column flex-grow-1">
        <div className="mb-1">
          <Badge variant="primary" className="text-uppercase" style={{ fontSize: '0.72rem' }}>
            {artwork.category_name || 'Fine Art'}
          </Badge>
        </div>
        
        <h5 className="mb-2 text-truncate fw-bold mt-2">
          <Link to={`/artworks/${artwork.id}`} className="text-decoration-none text-dark hover-primary">
            {artwork.title}
          </Link>
        </h5>

        <div className="small text-muted mb-3">
          {artwork.medium} {artwork.year_created ? `• ${artwork.year_created}` : ''}
        </div>

        <div className="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
          <div>
            <span className="small text-muted d-block" style={{ fontSize: '0.75rem' }}>Price</span>
            <span className="fs-5 fw-bold" style={{ color: 'var(--primary-navy)' }}>
              ₹{Number(artwork.price).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
            </span>
          </div>

          <Button to={`/artworks/${artwork.id}`} variant="outline" size="sm">
            Details <i className="bi bi-arrow-right ms-1"></i>
          </Button>
        </div>
      </div>
    </Card>
  );
};

export default ArtworkCard;
