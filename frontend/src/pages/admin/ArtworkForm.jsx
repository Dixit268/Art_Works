import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import api, { getArtworkImageUrl } from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';

const ArtworkForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(id);

  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form fields matching database schema
  const [title, setTitle] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState('');
  const [medium, setMedium] = useState('');
  const [dimensions, setDimensions] = useState('');
  const [yearCreated, setYearCreated] = useState(new Date().getFullYear().toString());
  const [availabilityStatus, setAvailabilityStatus] = useState('available');
  const [featured, setFeatured] = useState(false);
  
  // Image handling
  const [imageType, setImageType] = useState('link'); // 'link' | 'upload'
  const [imageUrl, setImageUrl] = useState('');
  const [imageFile, setImageFile] = useState(null);
  const [existingImagePath, setExistingImagePath] = useState('');
  const [previewUrl, setPreviewUrl] = useState('');

  // Fetch categories and artwork data when id or editMode changes
  useEffect(() => {
    let isMounted = true;

    const init = async () => {
      try {
        setLoading(true);
        // Load categories list
        const catRes = await api.get('/categories/list.php');
        if (!isMounted) return;
        setCategories(catRes.data?.data || []);

        if (isEditMode && id) {
          const artRes = await api.get(`/artworks/detail.php?id=${id}`);
          if (!isMounted) return;
          const art = artRes.data?.data;
          if (art) {
            setTitle(art.title || '');
            setCategoryId(art.category_id ? String(art.category_id) : '');
            setDescription(art.description || '');
            setPrice(art.price !== null && art.price !== undefined ? String(art.price) : '');
            setMedium(art.medium || '');
            setDimensions(art.dimensions || '');
            setYearCreated(art.year_created ? String(art.year_created) : '');
            setAvailabilityStatus(art.availability_status || 'available');
            setFeatured(Boolean(art.featured));
            setImageType(art.image_type || 'link');
            setImageUrl(art.image_url || '');
            setExistingImagePath(art.image_path || '');
            setPreviewUrl(getArtworkImageUrl(art));
          } else {
            toast.error("Artwork not found.");
            navigate('/admin/artworks');
          }
        } else {
          // Reset fields for Create mode
          setTitle('');
          setCategoryId('');
          setDescription('');
          setPrice('');
          setMedium('');
          setDimensions('');
          setYearCreated(new Date().getFullYear().toString());
          setAvailabilityStatus('available');
          setFeatured(false);
          setImageType('link');
          setImageUrl('');
          setImageFile(null);
          setExistingImagePath('');
          setPreviewUrl('');
        }
      } catch (err) {
        console.error("Initialization error:", err);
        toast.error("Failed to load artwork data from server.");
      } finally {
        if (isMounted) {
          setLoading(false);
        }
      }
    };

    init();
    return () => { isMounted = false; };
  }, [id, isEditMode, navigate]);

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        toast.error("File size cannot exceed 5MB.");
        return;
      }
      setImageFile(file);
      setPreviewUrl(URL.createObjectURL(file));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!title.trim()) {
      toast.error("Artwork title is required.");
      return;
    }
    if (price === '' || parseFloat(price) < 0) {
      toast.error("Please enter a valid positive price.");
      return;
    }
    if (imageType === 'link' && !imageUrl.trim() && !previewUrl) {
      toast.error("Please provide an image link URL.");
      return;
    }
    if (imageType === 'upload' && !imageFile && !existingImagePath && !isEditMode) {
      toast.error("Please select an image file to upload.");
      return;
    }

    setSubmitting(true);

    try {
      const formData = new FormData();
      if (isEditMode) {
        formData.append('id', id);
      }
      formData.append('title', title.trim());
      formData.append('category_id', categoryId || '');
      formData.append('description', description.trim());
      formData.append('price', price);
      formData.append('medium', medium.trim());
      formData.append('dimensions', dimensions.trim());
      formData.append('year_created', yearCreated || '');
      formData.append('availability_status', availabilityStatus);
      formData.append('featured', featured ? '1' : '0');
      formData.append('image_type', imageType);

      if (imageType === 'upload') {
        if (imageFile) {
          formData.append('image', imageFile);
        } else if (existingImagePath) {
          formData.append('image_path', existingImagePath);
        }
      } else if (imageType === 'link') {
        formData.append('image_url', imageUrl.trim());
      }

      const endpoint = isEditMode ? '/artworks/update.php' : '/artworks/create.php';
      
      // Let Axios manage the boundary automatically
      const res = await api.post(endpoint, formData);

      toast.success(res.data.message || (isEditMode ? "Artwork updated successfully!" : "Artwork published successfully!"));
      navigate('/admin/artworks');
    } catch (err) {
      const msg = err.response?.data?.message || (err.response?.data?.errors ? Object.values(err.response.data.errors).join(' ') : 'Failed to save artwork.');
      toast.error(msg);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <Loader message="Loading artwork details..." />;
  }

  return (
    <div>
      <div className="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            {isEditMode ? `EDIT ARTWORK #${id}` : 'CREATE NEW ARTWORK'}
          </h2>
          <p className="text-muted small mb-0">Fill in catalogue metadata and manage high-res imagery.</p>
        </div>
        <Button to="/admin/artworks" variant="light" size="sm" icon={<i className="bi bi-arrow-left"></i>}>
          Back to Catalogue
        </Button>
      </div>

      <form onSubmit={handleSubmit}>
        <div className="row g-4">
          {/* Main Info */}
          <div className="col-lg-8">
            <Card hover={false} padding="md" className="mb-4">
              <h5 className="fw-bold brand-font mb-4" style={{ color: 'var(--primary-navy)' }}>
                Artwork Specifications
              </h5>
              
              <div className="mb-3">
                <label className="form-label small fw-bold text-muted text-uppercase">Artwork Title *</label>
                <input 
                  type="text" 
                  className="form-control form-control-custom"
                  required
                  placeholder="e.g. Whispers of Solitude"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                />
              </div>

              <div className="row g-3 mb-3">
                <div className="col-md-6">
                  <label className="form-label small fw-bold text-muted text-uppercase">Collection Category</label>
                  <select 
                    className="form-select form-select-custom"
                    value={categoryId}
                    onChange={(e) => setCategoryId(e.target.value)}
                  >
                    <option value="">Select Category...</option>
                    {categories.map((cat) => (
                      <option key={cat.id} value={String(cat.id)}>{cat.name}</option>
                    ))}
                  </select>
                </div>
                <div className="col-md-6">
                  <label className="form-label small fw-bold text-muted text-uppercase">Price (₹ INR) *</label>
                  <input 
                    type="number" 
                    step="0.01" 
                    className="form-control form-control-custom"
                    required
                    placeholder="25000.00"
                    value={price}
                    onChange={(e) => setPrice(e.target.value)}
                  />
                </div>
              </div>

              <div className="row g-3 mb-3">
                <div className="col-md-4">
                  <label className="form-label small fw-bold text-muted text-uppercase">Medium</label>
                  <input 
                    type="text" 
                    className="form-control form-control-custom"
                    placeholder="e.g. Oil on Canvas"
                    value={medium}
                    onChange={(e) => setMedium(e.target.value)}
                  />
                </div>
                <div className="col-md-4">
                  <label className="form-label small fw-bold text-muted text-uppercase">Dimensions</label>
                  <input 
                    type="text" 
                    className="form-control form-control-custom"
                    placeholder="e.g. 36 x 48 in"
                    value={dimensions}
                    onChange={(e) => setDimensions(e.target.value)}
                  />
                </div>
                <div className="col-md-4">
                  <label className="form-label small fw-bold text-muted text-uppercase">Year Created</label>
                  <input 
                    type="number" 
                    className="form-control form-control-custom"
                    placeholder="2024"
                    value={yearCreated}
                    onChange={(e) => setYearCreated(e.target.value)}
                  />
                </div>
              </div>

              <div className="mb-0">
                <label className="form-label small fw-bold text-muted text-uppercase">Description & Provenance</label>
                <textarea 
                  className="form-control form-control-custom"
                  rows="4"
                  placeholder="Enter artistic narrative, background, or exhibition history..."
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                ></textarea>
              </div>
            </Card>

            {/* Inventory Status & Highlighting */}
            <Card hover={false} padding="md">
              <h5 className="fw-bold brand-font mb-4" style={{ color: 'var(--primary-navy)' }}>
                Availability & Highlighting
              </h5>
              <div className="row g-3 align-items-center">
                <div className="col-md-6">
                  <label className="form-label small fw-bold text-muted text-uppercase">Acquisition Status</label>
                  <select 
                    className="form-select form-select-custom"
                    value={availabilityStatus}
                    onChange={(e) => setAvailabilityStatus(e.target.value)}
                  >
                    <option value="available">Available for Acquisition</option>
                    <option value="sold">Sold / Private Collection</option>
                  </select>
                </div>
                <div className="col-md-6 mt-4">
                  <div className="form-check form-switch fs-5">
                    <input 
                      className="form-check-input" 
                      type="checkbox" 
                      id="featuredSwitch"
                      checked={featured}
                      onChange={(e) => setFeatured(e.target.checked)}
                    />
                    <label className="form-check-label fs-6 fw-bold ms-2 text-dark" htmlFor="featuredSwitch">
                      Feature on Gallery Homepage
                    </label>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          {/* Image Upload Column */}
          <div className="col-lg-4">
            <Card hover={false} padding="md">
              <h5 className="fw-bold brand-font mb-3" style={{ color: 'var(--primary-navy)' }}>
                Artwork Imagery
              </h5>

              {/* Image Type Toggle */}
              <div className="btn-group w-100 mb-3" role="group">
                <button
                  type="button"
                  className={`btn ${imageType === 'link' ? 'btn-primary custom-btn-primary' : 'btn-light border'}`}
                  onClick={() => setImageType('link')}
                >
                  <i className="bi bi-link-45deg me-1"></i> Web URL
                </button>
                <button
                  type="button"
                  className={`btn ${imageType === 'upload' ? 'btn-primary custom-btn-primary' : 'btn-light border'}`}
                  onClick={() => setImageType('upload')}
                >
                  <i className="bi bi-cloud-arrow-up me-1"></i> File Upload
                </button>
              </div>

              {imageType === 'link' ? (
                <div className="mb-3">
                  <label className="form-label small fw-bold text-muted text-uppercase">Image Web Link</label>
                  <input 
                    type="url" 
                    className="form-control form-control-custom"
                    placeholder="https://images.unsplash.com/..."
                    value={imageUrl}
                    onChange={(e) => {
                      setImageUrl(e.target.value);
                      setPreviewUrl(e.target.value);
                    }}
                  />
                </div>
              ) : (
                <div className="mb-3">
                  <label className="form-label small fw-bold text-muted text-uppercase">Select Image File</label>
                  <input 
                    type="file" 
                    className="form-control form-control-custom"
                    accept="image/*"
                    onChange={handleFileChange}
                  />
                  <small className="text-muted d-block mt-1">Allowed: JPG, PNG, WEBP, GIF (Max 5MB)</small>
                </div>
              )}

              {/* Image Preview Box */}
              <div className="mt-3">
                <label className="form-label small fw-bold text-muted text-uppercase">Image Preview</label>
                <div 
                  className="rounded-4 overflow-hidden border bg-light d-flex align-items-center justify-content-center shadow-sm"
                  style={{ height: '240px' }}
                >
                  {previewUrl ? (
                    <img 
                      src={previewUrl} 
                      alt="Preview" 
                      className="w-100 h-100 object-fit-cover"
                      onError={(e) => {
                        e.currentTarget.src = 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=600&q=80';
                      }}
                    />
                  ) : (
                    <div className="text-center text-muted p-4">
                      <i className="bi bi-image fs-1 d-block mb-2"></i>
                      <span className="small">No image preview available</span>
                    </div>
                  )}
                </div>
              </div>

              <hr className="my-4" />

              <Button 
                type="submit" 
                variant="primary"
                loading={submitting}
                className="w-100 py-3 justify-content-center"
                icon={<i className="bi bi-check2-circle"></i>}
              >
                {isEditMode ? 'Update Artwork' : 'Publish Artwork'}
              </Button>
            </Card>
          </div>
        </div>
      </form>
    </div>
  );
};

export default ArtworkForm;
