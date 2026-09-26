import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import api from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';

const CategoryForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(id);

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState('active');
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      const fetchCategory = async () => {
        try {
          setLoading(true);
          // Try direct detail endpoint first
          try {
            const res = await api.get(`/categories/detail.php?id=${id}`);
            if (res.data?.data) {
              setName(res.data.data.name || '');
              setDescription(res.data.data.description || '');
              setStatus(res.data.data.status || 'active');
              return;
            }
          } catch {
            // Fallback to list
            const listRes = await api.get('/categories/list.php');
            const found = (listRes.data.data || []).find(c => String(c.id) === String(id));
            if (found) {
              setName(found.name || '');
              setDescription(found.description || '');
              setStatus(found.status || 'active');
              return;
            }
          }
          toast.error("Category record not found.");
          navigate('/admin/categories');
        } catch (err) {
          console.error("Failed to load category:", err);
          toast.error("Failed to fetch category details.");
        } finally {
          setLoading(false);
        }
      };
      fetchCategory();
    }
  }, [id, isEditMode, navigate]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!name.trim()) {
      toast.error("Category name is required.");
      return;
    }

    setSubmitting(true);

    try {
      const payload = {
        name: name.trim(),
        description: description.trim(),
        status
      };

      if (isEditMode) {
        payload.id = id;
        const res = await api.post('/categories/update.php', payload);
        toast.success(res.data.message || "Category updated successfully!");
      } else {
        const res = await api.post('/categories/create.php', payload);
        toast.success(res.data.message || "Category created successfully!");
      }
      navigate('/admin/categories');
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to save category.");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <Loader message="Loading category information..." />;
  }

  return (
    <div className="max-w-xl">
      <div className="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            {isEditMode ? `EDIT CATEGORY #${id}` : 'CREATE NEW CATEGORY'}
          </h2>
          <p className="text-muted small mb-0">Define collection mediums and descriptive highlights.</p>
        </div>
        <Button to="/admin/categories" variant="light" size="sm" icon={<i className="bi bi-arrow-left"></i>}>
          Back to Categories
        </Button>
      </div>

      <Card hover={false} padding="md">
        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label className="form-label small fw-bold text-muted text-uppercase">Category Name *</label>
            <input 
              type="text" 
              className="form-control form-control-custom"
              required
              placeholder="e.g. Sculptures & Bronze"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>

          <div className="mb-3">
            <label className="form-label small fw-bold text-muted text-uppercase">Description</label>
            <textarea 
              className="form-control form-control-custom"
              rows="4"
              placeholder="Brief editorial summary of this artwork category..."
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            ></textarea>
          </div>

          <div className="mb-4">
            <label className="form-label small fw-bold text-muted text-uppercase">Status</label>
            <select 
              className="form-select form-select-custom"
              value={status}
              onChange={(e) => setStatus(e.target.value)}
            >
              <option value="active">Active (Visible in Gallery)</option>
              <option value="inactive">Inactive (Hidden)</option>
            </select>
          </div>

          <Button 
            type="submit" 
            variant="primary"
            loading={submitting}
            className="w-100 py-3 justify-content-center"
            icon={<i className="bi bi-check2-circle"></i>}
          >
            {isEditMode ? 'Update Category' : 'Save Category'}
          </Button>
        </form>
      </Card>
    </div>
  );
};

export default CategoryForm;
