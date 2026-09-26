import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import ArtworkCard from '../components/ArtworkCard';
import CategoryCard from '../components/CategoryCard';
import Loader from '../components/Loader';
import Button from '../components/common/Button';
import Card from '../components/common/Card';
import Badge from '../components/common/Badge';

const Home = () => {
  const [featuredArtworks, setFeaturedArtworks] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const [artsRes, catsRes] = await Promise.all([
          api.get('/artworks/list.php?featured=1&limit=6'),
          api.get('/categories/list.php?status=active')
        ]);
        setFeaturedArtworks(artsRes.data.data || []);
        setCategories(catsRes.data.data || []);
      } catch (err) {
        console.error("Failed to load home data:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  return (
    <div>
      {/* Hero Section with Decorative Gradient SVG Wave/Blob */}
      <section className="hero-banner py-5 py-lg-6 mb-5 position-relative overflow-hidden">
        {/* Decorative Wave/Blob SVG */}
        <svg 
          className="hero-blob-svg" 
          viewBox="0 0 500 500" 
          xmlns="http://www.w3.org/2000/svg"
        >
          <defs>
            <linearGradient id="heroGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#38BDF8" stopOpacity="0.45" />
              <stop offset="100%" stopColor="#4ADEDE" stopOpacity="0.15" />
            </linearGradient>
          </defs>
          <path 
            fill="url(#heroGradient)" 
            d="M424,310.5Q380,371,316.5,404.5Q253,438,187,411Q121,384,77,327Q33,270,61,202Q89,134,153,94.5Q217,55,286,67.5Q355,80,411.5,130Q468,180,424,310.5Z"
          />
        </svg>

        <div className="container position-relative z-1 py-4 py-lg-5">
          <div className="row align-items-center g-5">
            <div className="col-lg-7">
              <Badge 
                variant="gradient" 
                className="mb-3 px-3 py-2"
                icon={<i className="bi bi-stars me-1"></i>}
              >
                International Contemporary Gallery
              </Badge>
              <h1 className="display-4 fw-bold text-white mb-4 lh-sm">
                DISCOVER & COLLECT <br />
                <span style={{ background: 'var(--accent-gradient)', WebkitBackgroundClip: 'text', WebkitTextFillColor: 'transparent' }}>
                  TIMELESS MASTERPIECES
                </span>
              </h1>
              <p className="lead text-white-50 mb-5 pe-lg-5">
                Explore an exclusive portfolio of curated paintings, avant-garde sculptures, and archival photography by master creators worldwide.
              </p>
              <div className="d-flex flex-wrap gap-3">
                <Button to="/gallery" variant="gradient" size="lg">
                  Explore Gallery <i className="bi bi-arrow-right ms-2"></i>
                </Button>
                <Button to="/gallery?availability=available" variant="outline" size="lg" className="border-light text-white">
                  Available Artworks
                </Button>
              </div>
            </div>

            <div className="col-lg-5">
              <div className="position-relative">
                <div 
                  className="rounded-4 overflow-hidden shadow-lg border border-2 border-white border-opacity-25"
                  style={{ transform: 'rotate(2deg)', transition: 'transform 0.3s ease' }}
                >
                  <img 
                    src="https://i.pinimg.com/1200x/04/5c/44/045c44daa439b4e40ea9de68f60bf324.jpg" 
                    alt="Hero Artwork" 
                    className="w-100 object-fit-cover"
                    style={{ height: '420px' }}
                  />
                  <div className="p-4 bg-white text-dark">
                    <div className="d-flex justify-content-between align-items-center">
                      <div>
                        <span className="small fw-bold text-uppercase" style={{ color: 'var(--primary-blue)' }}>Featured Highlight</span>
                        <h5 className="mb-0 fw-bold brand-font" style={{ color: 'var(--primary-navy)' }}>Whispers of Solitude</h5>
                      </div>
                      <Badge variant="success">Available</Badge>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Featured Artworks Section */}
      <section className="container my-5 py-4">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
          <div>
            <span className="fw-bold text-uppercase small" style={{ color: 'var(--primary-blue)' }}>Curator's Selection</span>
            <h2 className="heading-display mt-1" style={{ color: 'var(--primary-navy)' }}>FEATURED ARTWORKS</h2>
          </div>
          <Button to="/gallery" variant="outline">
            View All Artworks <i className="bi bi-arrow-right ms-1"></i>
          </Button>
        </div>

        {loading ? (
          <Loader message="Loading curated artworks..." />
        ) : (
          <div className="row g-4">
            {featuredArtworks.map((art) => (
              <div key={art.id} className="col-lg-4 col-md-6">
                <ArtworkCard artwork={art} />
              </div>
            ))}
          </div>
        )}
      </section>

      {/* Categories Section */}
      <section className="py-5 my-5" style={{ backgroundColor: '#EDF4FF' }}>
        <div className="container py-3">
          <div className="text-center mb-5 max-w-xl mx-auto">
            <span className="fw-bold text-uppercase small" style={{ color: 'var(--primary-blue)' }}>Explore By Medium</span>
            <h2 className="heading-display mt-1" style={{ color: 'var(--primary-navy)' }}>ART COLLECTIONS</h2>
            <p className="text-muted">Explore our curated collections by technique, style, and media formats.</p>
          </div>

          <div className="row g-4">
            {categories.map((cat) => (
              <div key={cat.id} className="col-lg-4 col-md-6">
                <CategoryCard category={cat} />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Trust & Acquisition Card */}
      <section className="container my-5 py-4">
        <Card padding="lg" hover={false} style={{ background: 'linear-gradient(135deg, var(--primary-navy), var(--primary-blue))', color: '#fff' }}>
          <div className="row align-items-center g-4">
            <div className="col-lg-8">
              <h2 className="heading-display text-white mb-3">SEAMLESS ART ACQUISITIONS</h2>
              <p className="text-white-50 lead mb-0">
                Every piece in our gallery comes with a verified Certificate of Authenticity, insured white-glove international shipping, and direct advisory consultations with our chief curators.
              </p>
            </div>
            <div className="col-lg-4 text-lg-end">
              <Button to="/gallery" variant="gradient" size="lg">
                Acquire Art Now <i className="bi bi-arrow-up-right ms-2"></i>
              </Button>
            </div>
          </div>
        </Card>
      </section>
    </div>
  );
};

export default Home;
