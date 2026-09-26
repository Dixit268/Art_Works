import React, { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import api from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Button from '../../components/common/Button';
import Modal from '../../components/common/Modal';

const CategoriesList = () => {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [deleteTarget, setDeleteTarget] = useState(null); // { id, name }
  const [deleting, setDeleting] = useState(false);
  const [togglingId, setTogglingId] = useState(null);

  const fetchCategories = async () => {
    try {
      setLoading(true);
      const res = await api.get('/categories/list.php');
      setCategories(res.data.data || []);
    } catch (err) {
      console.error("Failed to load categories:", err);
      toast.error("Failed to load categories catalogue.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

  const handleToggleStatus = async (cat) => {
    const newStatus = cat.status === 'active' ? 'inactive' : 'active';
    setTogglingId(cat.id);
    try {
      await api.post('/categories/update.php', {
        id: cat.id,
        name: cat.name,
        description: cat.description,
        status: newStatus
      });
      setCategories(prev => prev.map(c => c.id === cat.id ? { ...c, status: newStatus } : c));
      toast.success(`Category "${cat.name}" is now ${newStatus}.`);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to update category status.");
    } finally {
      setTogglingId(null);
    }
  };

  const confirmDelete = async () => {
    if (!deleteTarget) return;

    setDeleting(true);
    try {
      await api.post('/categories/delete.php', { id: deleteTarget.id });
      toast.success(`Category "${deleteTarget.name}" deleted successfully.`);
      setCategories(prev => prev.filter(c => c.id !== deleteTarget.id));
      setDeleteTarget(null);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to delete category.");
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            COLLECTIONS & MEDIUMS
          </h2>
          <p className="text-muted small mb-0">Organize exhibitions by artistic styles, historical periods, and physical media.</p>
        </div>
        <Button to="/admin/categories/new" variant="primary" icon={<i className="bi bi-plus-lg"></i>}>
          Add New Category
        </Button>
      </div>

      <Card hover={false} padding="md">
        {loading ? (
          <Loader message="Loading categories catalogue..." />
        ) : categories.length === 0 ? (
          <div className="text-center py-5">
            <i className="bi bi-collection display-4 text-muted"></i>
            <h5 className="mt-3">No categories found</h5>
            <Button to="/admin/categories/new" variant="primary" size="sm" className="mt-2">
              Create One Now
            </Button>
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table align-middle table-hover">
              <thead className="table-light text-uppercase small text-muted">
                <tr>
                  <th>Collection Name</th>
                  <th>Description</th>
                  <th>Artworks Count</th>
                  <th>Status Toggle</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {categories.map((cat) => (
                  <tr key={cat.id}>
                    <td>
                      <div className="fw-bold text-dark">{cat.name}</div>
                      <div className="small text-muted">ID #{cat.id}</div>
                    </td>
                    <td className="small text-muted" style={{ maxWidth: '300px' }}>
                      {cat.description || '—'}
                    </td>
                    <td>
                      <Badge variant="primary">
                        {cat.artworks_count || 0} Pieces
                      </Badge>
                    </td>
                    <td>
                      <button
                        onClick={() => handleToggleStatus(cat)}
                        disabled={togglingId === cat.id}
                        className={`btn btn-sm rounded-pill fw-bold px-3 py-1 ${
                          cat.status === 'active' 
                            ? 'btn-success bg-opacity-10 text-success border-success' 
                            : 'btn-secondary bg-opacity-10 text-secondary border-secondary'
                        }`}
                        title="Click to toggle active/inactive"
                      >
                        {togglingId === cat.id ? (
                          <span className="spinner-border spinner-border-sm me-1"></span>
                        ) : (
                          <i className={`bi ${cat.status === 'active' ? 'bi-check-circle-fill' : 'bi-dash-circle'} me-1`}></i>
                        )}
                        {cat.status === 'active' ? 'Active' : 'Inactive'}
                      </button>
                    </td>
                    <td className="text-end">
                      <div className="d-flex justify-content-end gap-2">
                        <Button 
                          to={`/admin/categories/edit/${cat.id}`} 
                          variant="light" 
                          size="sm"
                          className="text-primary"
                          title="Edit Category"
                        >
                          <i className="bi bi-pencil-square"></i>
                        </Button>
                        <Button 
                          onClick={() => setDeleteTarget({ id: cat.id, name: cat.name })}
                          variant="light" 
                          size="sm"
                          className="text-danger"
                          title="Delete Category"
                        >
                          <i className="bi bi-trash"></i>
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Delete Modal */}
      <Modal
        isOpen={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        title="Delete Category Confirmation"
        confirmText="Delete Category"
        confirmVariant="danger"
        confirmLoading={deleting}
        onConfirm={confirmDelete}
      >
        <p className="mb-2">Are you sure you want to delete category <strong>"{deleteTarget?.name}"</strong>?</p>
        <p className="small text-muted mb-0">Artworks assigned to this collection will remain safe in the gallery inventory, but will become uncategorized.</p>
      </Modal>
    </div>
  );
};

export default CategoriesList;
