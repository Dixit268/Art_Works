import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import api from '../services/api';
import ArtworkCard from '../components/ArtworkCard';
import { ArtworkSkeleton } from '../components/common/Skeleton';
import Card from '../components/common/Card';
import Button from '../components/common/Button';
import Badge from '../components/common/Badge';
import Pagination from '../components/common/Pagination';

const Gallery = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [artworks, setArtworks] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState({
    total: 0,
    page: 1,
    limit: 9,
    total_pages: 1,
  });

  // Filter States initialized from URL params
  const [search, setSearch] = useState(searchParams.get('search') || '');
  const [category, setCategory] = useState(searchParams.get('category') || '');
  const [availability, setAvailability] = useState(searchParams.get('availability') || '');
  const [sort, setSort] = useState(searchParams.get('sort') || 'newest');
  const [priceMin, setPriceMin] = useState(searchParams.get('price_min') || '');
  const [priceMax, setPriceMax] = useState(searchParams.get('price_max') || '');

  // Load categories once
  useEffect(() => {
    api.get('/categories/list.php?status=active')
      .then(res => setCategories(res.data.data || []))
      .catch(err => console.error("Error fetching categories:", err));
  }, []);

  // Fetch Artworks whenever URL search params change
  useEffect(() => {
    const fetchArtworks = async () => {
      try {
        setLoading(true);
        const params = new URLSearchParams(searchParams);
        if (!params.has('limit')) params.set('limit', '9');
        
        const res = await api.get(`/artworks/list.php?${params.toString()}`);
        setArtworks(res.data.data || []);
        if (res.data.pagination) {
          setPagination(res.data.pagination);
        }
      } catch (err) {
        console.error("Error loading artworks:", err);
        toast.error("Failed to load artworks catalogue.");
      } finally {
        setLoading(false);
      }
    };

    fetchArtworks();
  }, [searchParams]);

  const handleApplyFilters = (e) => {
    if (e) e.preventDefault();
    const newParams = new URLSearchParams();
    if (search.trim()) newParams.set('search', search.trim());
    if (category) newParams.set('category', category);
    if (availability) newParams.set('availability', availability);
    if (sort) newParams.set('sort', sort);
    if (priceMin) newParams.set('price_min', priceMin);
    if (priceMax) newParams.set('price_max', priceMax);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleResetFilters = () => {
    setSearch('');
    setCategory('');
    setAvailability('');
    setSort('newest');
    setPriceMin('');
    setPriceMax('');
    setSearchParams({});
    toast.success("Filters reset to default.");
  };

  const handlePageChange = (newPage) => {
    const nextParams = new URLSearchParams(searchParams);
    nextParams.set('page', newPage.toString());
    setSearchParams(nextParams);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="py-5">
      <div className="container">
        {/* Page Header */}
        <div className="text-center mb-5">
          <Badge variant="primary" className="mb-2">
            The Complete Collection
          </Badge>
          <h1 className="heading-display display-5 fw-bold" style={{ color: 'var(--primary-navy)' }}>
            GALLERY EXHIBITION
          </h1>
          <p className="text-muted lead max-w-lg mx-auto">
            Browse our complete portfolio of original creations, curated across diverse media and movements.
          </p>
        </div>

        {/* Filter Card */}
        <Card hover={false} padding="md" className="mb-5">
          <form onSubmit={handleApplyFilters} className="row g-3 align-items-end">
            {/* Search */}
            <div className="col-lg-3 col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">Keyword Search</label>
              <div className="input-group">
                <span className="input-group-text bg-white border-end-0 rounded-start-4">
                  <i className="bi bi-search text-muted"></i>
                </span>
                <input 
                  type="text" 
                  className="form-control form-control-custom border-start-0 rounded-start-0" 
                  placeholder="Search title, medium..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>

            {/* Category */}
            <div className="col-lg-2 col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">Collection</label>
              <select 
                className="form-select form-select-custom"
                value={category}
                onChange={(e) => setCategory(e.target.value)}
              >
                <option value="">All Categories</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>

            {/* Price Range Filter */}
            <div className="col-lg-2 col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">Price Range</label>
              <div className="d-flex gap-1">
                <input 
                  type="number"
                  placeholder="₹ Min"
                  className="form-control form-control-custom px-2"
                  value={priceMin}
                  onChange={(e) => setPriceMin(e.target.value)}
                />
                <input 
                  type="number"
                  placeholder="₹ Max"
                  className="form-control form-control-custom px-2"
                  value={priceMax}
                  onChange={(e) => setPriceMax(e.target.value)}
                />
              </div>
            </div>

            {/* Availability */}
            <div className="col-lg-2 col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">Availability</label>
              <select 
                className="form-select form-select-custom"
                value={availability}
                onChange={(e) => setAvailability(e.target.value)}
              >
                <option value="">All Statuses</option>
                <option value="available">Available Only</option>
                <option value="sold">Sold</option>
              </select>
            </div>

            {/* Sort */}
            <div className="col-lg-3 col-md-12 d-flex gap-2">
              <div className="flex-grow-1">
                <label className="form-label small fw-bold text-muted text-uppercase">Sort By</label>
                <select 
                  className="form-select form-select-custom"
                  value={sort}
                  onChange={(e) => setSort(e.target.value)}
                >
                  <option value="newest">Newest First</option>
                  <option value="price_asc">Price: Low to High</option>
                  <option value="price_desc">Price: High to Low</option>
                  <option value="title_asc">Title: A-Z</option>
                </select>
              </div>
              <div className="d-flex align-items-end gap-1">
                <Button type="submit" variant="primary" className="px-3" title="Apply Filter">
                  <i className="bi bi-funnel-fill"></i>
                </Button>
                <Button 
                  type="button" 
                  variant="light"
                  onClick={handleResetFilters} 
                  className="px-3"
                  title="Reset Filters"
                >
                  <i className="bi bi-arrow-counterclockwise"></i>
                </Button>
              </div>
            </div>
          </form>
        </Card>

        {/* Artworks Grid */}
        {loading ? (
          <div className="row g-4 mb-5">
            {Array.from({ length: 6 }).map((_, idx) => (
              <div key={idx} className="col-lg-4 col-md-6">
                <ArtworkSkeleton />
              </div>
            ))}
          </div>
        ) : artworks.length === 0 ? (
          <Card hover={false} padding="lg" className="text-center py-5 my-5">
            <div className="display-1 text-muted mb-3"><i className="bi bi-image"></i></div>
            <h4 className="fw-bold">No Artworks Found</h4>
            <p className="text-muted">No pieces match your active filter criteria. Try adjusting your filters.</p>
            <Button onClick={handleResetFilters} variant="primary" className="mt-2">
              Clear All Filters
            </Button>
          </Card>
        ) : (
          <>
            <div className="d-flex justify-content-between align-items-center mb-4 text-muted small">
              <span>Showing {artworks.length} of {pagination.total} artworks</span>
              <span>Page {pagination.page} of {pagination.total_pages}</span>
            </div>

            <div className="row g-4 mb-5">
              {artworks.map((art) => (
                <div key={art.id} className="col-lg-4 col-md-6">
                  <ArtworkCard artwork={art} />
                </div>
              ))}
            </div>

            {/* Reusable Pagination */}
            <Pagination 
              currentPage={pagination.page}
              totalPages={pagination.total_pages}
              onPageChange={handlePageChange}
            />
          </>
        )}
      </div>
    </div>
  );
};

export default Gallery;
