import React, { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import api, { getArtworkImageUrl } from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Button from '../../components/common/Button';
import Modal from '../../components/common/Modal';

const InquiriesList = () => {
  const [inquiries, setInquiries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('all');
  const [updatingId, setUpdatingId] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const [selectedInquiry, setSelectedInquiry] = useState(null);

  const fetchInquiries = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/inquiries/list.php?status=${statusFilter}&limit=100`);
      setInquiries(res.data.data || []);
    } catch (err) {
      console.error("Failed to load inquiries:", err);
      toast.error("Failed to load patron inquiries.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInquiries();
  }, [statusFilter]);

  const handleStatusChange = async (id, newStatus) => {
    setUpdatingId(id);
    try {
      await api.post('/inquiries/update-status.php', { id, status: newStatus });
      setInquiries(prev => prev.map(inq => inq.id === id ? { ...inq, status: newStatus } : inq));
      toast.success(`Inquiry #${id} marked as "${newStatus}".`);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to update status.");
    } finally {
      setUpdatingId(null);
    }
  };

  const confirmDelete = async () => {
    if (!deleteTarget) return;

    setDeleting(true);
    try {
      await api.post('/inquiries/delete.php', { id: deleteTarget.id });
      setInquiries(prev => prev.filter(inq => inq.id !== deleteTarget.id));
      toast.success(`Inquiry #${deleteTarget.id} deleted successfully.`);
      setDeleteTarget(null);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to delete inquiry.");
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            PATRON INQUIRIES
          </h2>
          <p className="text-muted small mb-0">Client acquisition requests, private viewings, and artwork questions.</p>
        </div>

        {/* Status Filter */}
        <div className="d-flex align-items-center gap-2">
          <label className="small fw-bold text-muted text-uppercase mb-0">Filter:</label>
          <select 
            className="form-select form-select-custom py-2"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="all">All Statuses</option>
            <option value="pending">Pending Only</option>
            <option value="contacted">Contacted</option>
            <option value="resolved">Resolved</option>
          </select>
        </div>
      </div>

      <Card hover={false} padding="md">
        {loading ? (
          <Loader message="Loading inquiries..." />
        ) : inquiries.length === 0 ? (
          <div className="text-center py-5">
            <i className="bi bi-chat-square-dots display-4 text-muted"></i>
            <h5 className="mt-3">No inquiries found</h5>
            <p className="text-muted small">No client messages match your current filter.</p>
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table align-middle table-hover">
              <thead className="table-light text-uppercase small text-muted">
                <tr>
                  <th>Client</th>
                  <th>Artwork</th>
                  <th>Message Preview</th>
                  <th>Date</th>
                  <th>Status Action</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {inquiries.map((inq) => (
                  <tr key={inq.id}>
                    <td>
                      <div className="fw-bold text-dark">{inq.name}</div>
                      <div className="small text-muted">{inq.email}</div>
                      {inq.phone && <div className="small text-muted">📞 {inq.phone}</div>}
                    </td>
                    <td>
                      {inq.artwork_title ? (
                        <div className="d-flex align-items-center gap-2">
                          {inq.artwork_image && (
                            <img 
                              src={getArtworkImageUrl({ image_path: inq.artwork_image, image_type: 'upload' })} 
                              alt="thumb" 
                              className="rounded-2 object-fit-cover shadow-sm"
                              style={{ width: '36px', height: '36px' }}
                              onError={(e) => {
                                e.currentTarget.src = inq.artwork_image;
                              }}
                            />
                          )}
                          <div>
                            <div className="small fw-semibold">{inq.artwork_title}</div>
                            {inq.artwork_price && (
                              <div className="small text-muted">₹{Number(inq.artwork_price).toLocaleString('en-IN')}</div>
                            )}
                          </div>
                        </div>
                      ) : (
                        <Badge variant="light">General Inquiry</Badge>
                      )}
                    </td>
                    <td style={{ maxWidth: '280px' }}>
                      <div className="text-truncate small text-secondary">{inq.message}</div>
                      <button 
                        onClick={() => setSelectedInquiry(inq)} 
                        className="btn btn-link btn-sm p-0 fw-bold small text-decoration-none"
                        style={{ color: 'var(--primary-blue)' }}
                      >
                        Read Full Message
                      </button>
                    </td>
                    <td className="small text-muted">
                      {new Date(inq.created_at).toLocaleDateString()}
                    </td>
                    <td>
                      <select 
                        className={`form-select form-select-sm rounded-pill fw-bold ${
                          inq.status === 'pending' ? 'bg-warning bg-opacity-25 text-dark border-warning' :
                          inq.status === 'contacted' ? 'bg-info bg-opacity-25 text-dark border-info' :
                          'bg-success bg-opacity-25 text-success border-success'
                        }`}
                        style={{ width: '130px' }}
                        value={inq.status}
                        disabled={updatingId === inq.id}
                        onChange={(e) => handleStatusChange(inq.id, e.target.value)}
                      >
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="resolved">Resolved</option>
                      </select>
                    </td>
                    <td className="text-end">
                      <Button 
                        onClick={() => setDeleteTarget({ id: inq.id, name: inq.name })}
                        variant="light" 
                        size="sm"
                        className="text-danger"
                        title="Delete Inquiry"
                      >
                        <i className="bi bi-trash"></i>
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Modal for Reading Inquiry Message */}
      <Modal
        isOpen={Boolean(selectedInquiry)}
        onClose={() => setSelectedInquiry(null)}
        title={`Inquiry from ${selectedInquiry?.name}`}
        confirmText="Reply via Email"
        confirmVariant="primary"
        onConfirm={() => {
          if (selectedInquiry) {
            window.location.href = `mailto:${selectedInquiry.email}?subject=Re: Inquiry regarding ${selectedInquiry.artwork_title || 'Art Gallery'}`;
          }
        }}
      >
        <div className="bg-light rounded-3 p-3 mb-3 small">
          <div><strong>Email:</strong> {selectedInquiry?.email}</div>
          {selectedInquiry?.phone && <div><strong>Phone:</strong> {selectedInquiry.phone}</div>}
          {selectedInquiry?.artwork_title && <div><strong>Artwork:</strong> {selectedInquiry.artwork_title}</div>}
          <div><strong>Submitted:</strong> {selectedInquiry?.created_at}</div>
        </div>
        <h6 className="fw-bold small text-muted text-uppercase mb-2">Message</h6>
        <div className="p-3 bg-white border rounded-3 text-secondary" style={{ whiteSpace: 'pre-wrap' }}>
          {selectedInquiry?.message}
        </div>
      </Modal>

      {/* Delete Inquiry Modal */}
      <Modal
        isOpen={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        title="Delete Inquiry Confirmation"
        confirmText="Delete Inquiry"
        confirmVariant="danger"
        confirmLoading={deleting}
        onConfirm={confirmDelete}
      >
        <p className="mb-0">Are you sure you want to delete inquiry #{deleteTarget?.id} from <strong>"{deleteTarget?.name}"</strong>?</p>
      </Modal>
    </div>
  );
};

export default InquiriesList;
