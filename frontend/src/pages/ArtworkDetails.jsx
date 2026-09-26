import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import api, { getArtworkImageUrl } from '../services/api';
import { useAuth } from '../context/AuthContext';
import Loader from '../components/Loader';
import Card from '../components/common/Card';
import Button from '../components/common/Button';
import Badge from '../components/common/Badge';

const ArtworkDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { isAuthenticated, user } = useAuth();
  
  const [artwork, setArtwork] = useState(null);
  const [loading, setLoading] = useState(true);
  const [inWishlist, setInWishlist] = useState(false);
  const [wishlistLoading, setWishlistLoading] = useState(false);

  // Inquiry Form State
  const [inquiryData, setInquiryData] = useState({
    name: '',
    email: '',
    phone: '',
    message: '',
  });
  const [inquirySubmitting, setInquirySubmitting] = useState(false);

  useEffect(() => {
    if (user) {
      setInquiryData(prev => ({
        ...prev,
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || ''
      }));
    }
  }, [user]);

  useEffect(() => {
    const fetchDetail = async () => {
      try {
        setLoading(true);
        const res = await api.get(`/artworks/detail.php?id=${id}`);
        setArtwork(res.data.data);
        setInWishlist(res.data.data.in_wishlist || false);
      } catch (err) {
        console.error("Failed to load artwork detail:", err);
        toast.error("Failed to load artwork details.");
      } finally {
        setLoading(false);
      }
    };
    fetchDetail();
  }, [id]);

  const toggleWishlist = async () => {
    if (!isAuthenticated) {
      toast.error("Please sign in to save artworks to your wishlist.");
      navigate('/login');
      return;
    }

    setWishlistLoading(true);
    try {
      if (inWishlist) {
        await api.post('/wishlist/remove.php', { artwork_id: artwork.id });
        setInWishlist(false);
        toast.success("Removed from your wishlist.");
      } else {
        await api.post('/wishlist/add.php', { artwork_id: artwork.id });
        setInWishlist(true);
        toast.success(`"${artwork.title}" added to your wishlist!`);
      }
    } catch (err) {
      console.error("Wishlist toggle failed:", err);
      toast.error("Failed to update wishlist.");
    } finally {
      setWishlistLoading(false);
    }
  };

  const handleInquirySubmit = async (e) => {
    e.preventDefault();

    if (!inquiryData.name.trim()) {
      toast.error("Please enter your name.");
      return;
    }
    if (!inquiryData.email.trim()) {
      toast.error("Please enter your email address.");
      return;
    }
    if (!inquiryData.message.trim()) {
      toast.error("Please enter your inquiry message.");
      return;
    }

    setInquirySubmitting(true);

    try {
      const res = await api.post('/inquiries/create.php', {
        ...inquiryData,
        artwork_id: artwork.id
      });
      toast.success(res.data.message || "Your inquiry has been submitted to the curator!");
      setInquiryData(prev => ({ ...prev, message: '' }));
    } catch (err) {
      const msg = err.response?.data?.message || "Failed to submit inquiry. Please try again.";
      toast.error(msg);
    } finally {
      setInquirySubmitting(false);
    }
  };

  if (loading) {
    return <Loader message="Loading artwork composition..." />;
  }

  if (!artwork) {
    return (
      <div className="container py-5 text-center my-5">
        <h2 className="fw-bold">Artwork Not Found</h2>
        <p className="text-muted">The requested piece could not be located in our collection catalogue.</p>
        <Button to="/gallery" variant="primary" className="mt-3">
          Back to Gallery
        </Button>
      </div>
    );
  }

  const imageUrl = getArtworkImageUrl(artwork);

  return (
    <div className="py-5">
      <div className="container">
        {/* Breadcrumbs */}
        <nav aria-label="breadcrumb" className="mb-4">
          <ol className="breadcrumb">
            <li className="breadcrumb-item"><Link to="/" className="text-decoration-none text-muted">Home</Link></li>
            <li className="breadcrumb-item"><Link to="/gallery" className="text-decoration-none text-muted">Gallery</Link></li>
            {artwork.category_name && (
              <li className="breadcrumb-item">
                <Link to={`/gallery?category=${artwork.category_id}`} className="text-decoration-none text-muted">
                  {artwork.category_name}
                </Link>
              </li>
            )}
            <li className="breadcrumb-item active text-dark fw-semibold" aria-current="page">{artwork.title}</li>
          </ol>
        </nav>

        <div className="row g-5">
          {/* Left Column: Big Image Display */}
          <div className="col-lg-7">
            <Card hover={false} padding="sm" className="bg-white sticky-top" style={{ top: '100px' }}>
              <div className="rounded-4 overflow-hidden position-relative bg-light" style={{ minHeight: '480px' }}>
                <img 
                  src={imageUrl} 
                  alt={artwork.title}
                  className="w-100 h-auto object-fit-contain"
                  style={{ maxHeight: '650px' }}
                  onError={(e) => {
                    e.currentTarget.src = 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=1000&q=80';
                  }}
                />
                {artwork.featured && (
                  <Badge variant="gradient" className="position-absolute top-0 start-0 m-3 shadow" icon={<i className="bi bi-star-fill me-1"></i>}>
                    Featured Piece
                  </Badge>
                )}
              </div>
            </Card>
          </div>

          {/* Right Column: Info & Acquisition */}
          <div className="col-lg-5">
            <div className="d-flex align-items-center justify-content-between mb-2">
              <Badge variant="primary" className="text-uppercase">
                {artwork.category_name || 'Fine Art'}
              </Badge>
              <Badge variant={artwork.availability_status === 'available' ? 'success' : 'danger'}>
                {artwork.availability_status === 'available' ? 'Available for Acquisition' : 'Acquired / Sold'}
              </Badge>
            </div>

            <h1 className="heading-display display-6 fw-bold mb-3" style={{ color: 'var(--primary-navy)' }}>
              {artwork.title}
            </h1>

            <div className="d-flex align-items-baseline gap-3 mb-4 pb-3 border-bottom">
              <span className="display-6 fw-bold" style={{ color: 'var(--primary-blue)' }}>
                ₹{Number(artwork.price).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </span>
              <span className="text-muted small">INR (Taxes & Provenance Included)</span>
            </div>

            {/* Description */}
            <div className="mb-4">
              <h6 className="fw-bold text-uppercase small text-muted">Artwork Overview</h6>
              <p className="text-secondary leading-relaxed">
                {artwork.description || "An exceptional contemporary piece exhibiting intricate textures, distinctive tonal depth, and high artistic provenance."}
              </p>
            </div>

            {/* Technical Specifications */}
            <Card hover={false} padding="md" className="mb-4 bg-light bg-opacity-50">
              <h6 className="fw-bold text-uppercase small text-muted mb-3">Specifications</h6>
              <div className="row g-3 small">
                <div className="col-6">
                  <span className="text-muted d-block">Medium:</span>
                  <strong className="text-dark">{artwork.medium || 'N/A'}</strong>
                </div>
                <div className="col-6">
                  <span className="text-muted d-block">Dimensions:</span>
                  <strong className="text-dark">{artwork.dimensions || 'N/A'}</strong>
                </div>
                <div className="col-6">
                  <span className="text-muted d-block">Year Created:</span>
                  <strong className="text-dark">{artwork.year_created || 'Contemporary'}</strong>
                </div>
                <div className="col-6">
                  <span className="text-muted d-block">Provenance:</span>
                  <strong className="text-dark">Original with Certificate</strong>
                </div>
              </div>
            </Card>

            {/* Wishlist Button (Interactive) */}
            <div className="d-flex gap-3 mb-5">
              <Button 
                onClick={toggleWishlist}
                loading={wishlistLoading}
                variant={inWishlist ? 'danger' : 'outline'}
                size="lg"
                className="w-100 py-3"
                icon={<i className={`bi ${inWishlist ? 'bi-heart-fill' : 'bi-heart'}`}></i>}
              >
                {inWishlist ? 'In Your Wishlist' : 'Add to Wishlist'}
              </Button>
            </div>

            {/* Curator Inquiry Card */}
            <Card hover={false} padding="md" className="border border-primary border-opacity-25" style={{ backgroundColor: '#F8FAFF' }}>
              <div className="d-flex align-items-center gap-2 mb-2" style={{ color: 'var(--primary-blue)' }}>
                <i className="bi bi-chat-heart-fill fs-5"></i>
                <h5 className="fw-bold mb-0 brand-font">Curator Inquiry</h5>
              </div>
              <p className="small text-muted mb-3">
                Interested in acquiring this piece or scheduling a private viewing? Send a message directly to our curators.
              </p>

              <form onSubmit={handleInquirySubmit}>
                <div className="mb-3">
                  <input 
                    type="text" 
                    className="form-control form-control-custom"
                    placeholder="Your Full Name *"
                    required
                    value={inquiryData.name}
                    onChange={(e) => setInquiryData({ ...inquiryData, name: e.target.value })}
                  />
                </div>
                <div className="row g-2 mb-3">
                  <div className="col-6">
                    <input 
                      type="email" 
                      className="form-control form-control-custom"
                      placeholder="Email Address *"
                      required
                      value={inquiryData.email}
                      onChange={(e) => setInquiryData({ ...inquiryData, email: e.target.value })}
                    />
                  </div>
                  <div className="col-6">
                    <input 
                      type="tel" 
                      className="form-control form-control-custom"
                      placeholder="Phone (Optional)"
                      value={inquiryData.phone}
                      onChange={(e) => setInquiryData({ ...inquiryData, phone: e.target.value })}
                    />
                  </div>
                </div>
                <div className="mb-3">
                  <textarea 
                    className="form-control form-control-custom"
                    rows="3"
                    placeholder="Questions regarding shipping, framing, or provenance..."
                    required
                    value={inquiryData.message}
                    onChange={(e) => setInquiryData({ ...inquiryData, message: e.target.value })}
                  ></textarea>
                </div>
                <Button 
                  type="submit" 
                  variant="primary"
                  loading={inquirySubmitting}
                  className="w-100 py-3"
                  icon={<i className="bi bi-send-fill ms-1"></i>}
                >
                  Submit Acquisition Request
                </Button>
              </form>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ArtworkDetails;
