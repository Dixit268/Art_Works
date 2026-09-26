import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import api, { getArtworkImageUrl } from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Button from '../../components/common/Button';
import Modal from '../../components/common/Modal';

const ArtworksList = () => {
  const navigate = useNavigate();
  const [artworks, setArtworks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null); // { id, title }
  const [deleting, setDeleting] = useState(false);

  const fetchArtworks = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/artworks/list.php?limit=100&search=${encodeURIComponent(search)}`);
      setArtworks(res.data.data || []);
    } catch (err) {
      console.error("Failed to load artworks:", err);
      toast.error("Failed to load artworks catalogue.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchArtworks();
  }, []);

  const confirmDelete = async () => {
    if (!deleteTarget) return;

    setDeleting(true);
    try {
      await api.post('/artworks/delete.php', { id: deleteTarget.id });
      toast.success(`Artwork "${deleteTarget.title}" removed successfully.`);
      setArtworks(prev => prev.filter(a => a.id !== deleteTarget.id));
      setDeleteTarget(null);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to delete artwork.");
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            MANAGE ARTWORKS
          </h2>
          <p className="text-muted small mb-0">Total inventory of original artworks, sculptures, and prints.</p>
        </div>
        <Button to="/admin/artworks/new" variant="primary" icon={<i className="bi bi-plus-lg"></i>}>
          Add New Artwork
        </Button>
      </div>

      {/* Search Bar */}
      <Card hover={false} padding="sm" className="mb-4">
        <form onSubmit={(e) => { e.preventDefault(); fetchArtworks(); }} className="d-flex gap-2">
          <div className="input-group">
            <span className="input-group-text bg-white border-end-0">
              <i className="bi bi-search text-muted"></i>
            </span>
            <input 
              type="text" 
              className="form-control form-control-custom rounded-start-0 border-start-0"
              placeholder="Search by title, medium, description..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <Button type="submit" variant="primary" className="px-4">Search</Button>
        </form>
      </Card>

      {/* Table */}
      <Card hover={false} padding="md">
        {loading ? (
          <Loader message="Loading artworks list..." />
        ) : artworks.length === 0 ? (
          <div className="text-center py-5">
            <i className="bi bi-palette display-4 text-muted"></i>
            <h5 className="mt-3">No artworks found</h5>
            <Button to="/admin/artworks/new" variant="primary" size="sm" className="mt-2">
              Create One Now
            </Button>
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table align-middle table-hover">
              <thead className="table-light text-uppercase small text-muted">
                <tr>
                  <th>Piece</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Medium / Dimensions</th>
                  <th>Availability</th>
                  <th>Featured</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {artworks.map((art) => (
                  <tr key={art.id}>
                    <td>
                      <div className="d-flex align-items-center gap-3">
                        <img 
                          src={getArtworkImageUrl(art)} 
                          alt={art.title}
                          className="rounded-3 object-fit-cover shadow-sm"
                          style={{ width: '50px', height: '50px' }}
                          onError={(e) => {
                            e.currentTarget.src = 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=100&q=80';
                          }}
                        />
                        <div>
                          <div className="fw-bold text-dark">{art.title}</div>
                          <div className="small text-muted">ID #{art.id} {art.year_created ? `(${art.year_created})` : ''}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <Badge variant="primary">
                        {art.category_name}
                      </Badge>
                    </td>
                    <td className="fw-bold text-dark">
                      ₹{Number(art.price).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                    </td>
                    <td className="small text-muted">
                      <div>{art.medium || '—'}</div>
                      <div>{art.dimensions || ''}</div>
                    </td>
                    <td>
                      <Badge variant={art.availability_status === 'available' ? 'success' : 'danger'}>
                        {art.availability_status}
                      </Badge>
                    </td>
                    <td>
                      {art.featured ? (
                        <Badge variant="warning" icon={<i className="bi bi-star-fill me-1"></i>}>
                          Yes
                        </Badge>
                      ) : (
                        <span className="text-muted small">No</span>
                      )}
                    </td>
                    <td className="text-end">
                      <div className="d-flex justify-content-end gap-2">
                        {/* Edit Button */}
                        <Link 
                          to={`/admin/artworks/edit/${art.id}`} 
                          className="btn btn-sm btn-light border text-primary rounded-3 px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                          title="Edit Artwork"
                        >
                          <i className="bi bi-pencil-square"></i>
                          <span>Edit</span>
                        </Link>
                        {/* Delete Button */}
                        <button 
                          type="button"
                          onClick={() => setDeleteTarget({ id: art.id, title: art.title })}
                          className="btn btn-sm btn-light border text-danger rounded-3 px-3 py-1"
                          title="Delete Artwork"
                        >
                          <i className="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Delete Confirmation Reusable Modal */}
      <Modal
        isOpen={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        title="Delete Artwork Confirmation"
        confirmText="Delete Artwork"
        confirmVariant="danger"
        confirmLoading={deleting}
        onConfirm={confirmDelete}
      >
        <p className="mb-2">Are you sure you want to permanently delete this artwork from the gallery catalogue?</p>
        <strong className="text-dark d-block">"{deleteTarget?.title}" (ID #{deleteTarget?.id})</strong>
        <p className="small text-muted mt-2 mb-0">This action will remove the artwork file and cascade all associated wishlist items.</p>
      </Modal>
    </div>
  );
};

export default ArtworksList;
