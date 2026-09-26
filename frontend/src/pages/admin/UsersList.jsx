import React, { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import api from '../../services/api';
import Loader from '../../components/Loader';
import { useAuth } from '../../context/AuthContext';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Button from '../../components/common/Button';
import Modal from '../../components/common/Modal';

const UsersList = () => {
  const { user: currentUser } = useAuth();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null); // { id, name }
  const [deleting, setDeleting] = useState(false);

  const fetchUsers = async () => {
    try {
      setLoading(true);
      const res = await api.get(`/users/list.php?limit=100&search=${encodeURIComponent(search)}`);
      setUsers(res.data.data || []);
    } catch (err) {
      console.error("Failed to load users:", err);
      toast.error("Failed to load user accounts.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const confirmDelete = async () => {
    if (!deleteTarget) return;

    if (deleteTarget.id === currentUser?.id) {
      toast.error("You cannot delete your own active administrator account.");
      setDeleteTarget(null);
      return;
    }

    setDeleting(true);
    try {
      await api.post('/users/delete.php', { id: deleteTarget.id });
      toast.success(`User "${deleteTarget.name}" removed successfully.`);
      setUsers(prev => prev.filter(u => u.id !== deleteTarget.id));
      setDeleteTarget(null);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to delete user.");
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            USER & COLLECTOR ACCOUNTS
          </h2>
          <p className="text-muted small mb-0">Manage registered collectors, art advisors, and gallery administrator permissions.</p>
        </div>
        <Button to="/admin/users/new" variant="primary" icon={<i className="bi bi-person-plus-fill"></i>}>
          Add New User
        </Button>
      </div>

      {/* Search Filter */}
      <Card hover={false} padding="sm" className="mb-4">
        <form onSubmit={(e) => { e.preventDefault(); fetchUsers(); }} className="d-flex gap-2">
          <div className="input-group">
            <span className="input-group-text bg-white border-end-0">
              <i className="bi bi-search text-muted"></i>
            </span>
            <input 
              type="text" 
              className="form-control form-control-custom rounded-start-0 border-start-0"
              placeholder="Search by name, email, or phone..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <Button type="submit" variant="primary" className="px-4">Search</Button>
        </form>
      </Card>

      {/* Users Table */}
      <Card hover={false} padding="md">
        {loading ? (
          <Loader message="Loading user accounts..." />
        ) : users.length === 0 ? (
          <div className="text-center py-5">
            <i className="bi bi-people display-4 text-muted"></i>
            <h5 className="mt-3">No user accounts found</h5>
            <Button to="/admin/users/new" variant="primary" size="sm" className="mt-2">
              Add New User
            </Button>
          </div>
        ) : (
          <div className="table-responsive">
            <table className="table align-middle table-hover">
              <thead className="table-light text-uppercase small text-muted">
                <tr>
                  <th>User Profile</th>
                  <th>Contact Info</th>
                  <th>System Role</th>
                  <th>Saved Wishlist</th>
                  <th>Registration Date</th>
                  <th className="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.map((u) => (
                  <tr key={u.id}>
                    <td>
                      <div className="d-flex align-items-center gap-3">
                        <div 
                          className="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                          style={{ 
                            width: '42px', 
                            height: '42px', 
                            background: u.role === 'admin' ? 'var(--primary-navy)' : 'linear-gradient(135deg, var(--primary-blue), #38BDF8)' 
                          }}
                        >
                          {u.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                          <div className="fw-bold text-dark">{u.name}</div>
                          <div className="small text-muted">ID #{u.id} {u.id === currentUser?.id ? '(You)' : ''}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div>{u.email}</div>
                      <div className="small text-muted">{u.phone || 'No phone provided'}</div>
                    </td>
                    <td>
                      <Badge variant={u.role === 'admin' ? 'primary' : 'light'}>
                        {u.role === 'admin' ? 'Admin' : 'Collector'}
                      </Badge>
                    </td>
                    <td>
                      <Badge variant="primary">
                        {u.wishlist_count || 0} saved
                      </Badge>
                    </td>
                    <td className="small text-muted">
                      {new Date(u.created_at).toLocaleDateString()}
                    </td>
                    <td className="text-end">
                      <div className="d-flex justify-content-end gap-2">
                        {/* Edit Button */}
                        <Button 
                          to={`/admin/users/edit/${u.id}`} 
                          variant="light" 
                          size="sm"
                          className="text-primary"
                          title="Edit User"
                        >
                          <i className="bi bi-pencil-square"></i>
                        </Button>
                        {/* Delete Button */}
                        {u.id !== currentUser?.id && (
                          <Button 
                            onClick={() => setDeleteTarget({ id: u.id, name: u.name })}
                            variant="light" 
                            size="sm"
                            className="text-danger"
                            title="Delete User"
                          >
                            <i className="bi bi-trash"></i>
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {/* Delete User Modal */}
      <Modal
        isOpen={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        title="Delete User Account Confirmation"
        confirmText="Delete Account"
        confirmVariant="danger"
        confirmLoading={deleting}
        onConfirm={confirmDelete}
      >
        <p className="mb-2">Are you sure you want to delete user account <strong>"{deleteTarget?.name}"</strong>?</p>
        <p className="small text-muted mb-0">This will revoke all active authentication tokens and purge their saved wishlist.</p>
      </Modal>
    </div>
  );
};

export default UsersList;
