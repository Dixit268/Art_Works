import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import api from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';

const UserForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(id);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [role, setRole] = useState('user');
  const [password, setPassword] = useState('');
  
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      const fetchUser = async () => {
        try {
          setLoading(true);
          // Try direct user detail endpoint
          try {
            const res = await api.get(`/users/detail.php?id=${id}`);
            if (res.data?.data) {
              setName(res.data.data.name || '');
              setEmail(res.data.data.email || '');
              setPhone(res.data.data.phone || '');
              setRole(res.data.data.role || 'user');
              return;
            }
          } catch {
            // Fallback to users list
            const listRes = await api.get('/users/list.php?limit=100');
            const found = (listRes.data.data || []).find(u => String(u.id) === String(id));
            if (found) {
              setName(found.name || '');
              setEmail(found.email || '');
              setPhone(found.phone || '');
              setRole(found.role || 'user');
              return;
            }
          }
          toast.error("User account not found.");
          navigate('/admin/users');
        } catch (err) {
          console.error("Failed to load user:", err);
          toast.error("Failed to fetch user details.");
        } finally {
          setLoading(false);
        }
      };
      fetchUser();
    }
  }, [id, isEditMode, navigate]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!name.trim()) {
      toast.error("User name is required.");
      return;
    }
    if (!email.trim()) {
      toast.error("Email address is required.");
      return;
    }
    if (!isEditMode && (!password || password.length < 6)) {
      toast.error("Password must be at least 6 characters for new accounts.");
      return;
    }
    if (isEditMode && password && password.length < 6) {
      toast.error("New password must be at least 6 characters.");
      return;
    }

    setSubmitting(true);

    try {
      const payload = {
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim(),
        role
      };

      // Only include password if provided
      if (password) {
        payload.password = password;
      }

      if (isEditMode) {
        payload.id = id;
        const res = await api.post('/users/update.php', payload);
        toast.success(res.data.message || "User updated successfully!");
      } else {
        const res = await api.post('/users/create.php', payload);
        toast.success(res.data.message || "User created successfully!");
      }
      navigate('/admin/users');
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to save user account.");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <Loader message="Loading user profile..." />;
  }

  return (
    <div className="max-w-xl">
      <div className="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            {isEditMode ? `EDIT USER #${id}` : 'NEW USER ACCOUNT'}
          </h2>
          <p className="text-muted small mb-0">Manage collector credentials and system access permissions.</p>
        </div>
        <Button to="/admin/users" variant="light" size="sm" icon={<i className="bi bi-arrow-left"></i>}>
          Back to Users
        </Button>
      </div>

      <Card hover={false} padding="md">
        <form onSubmit={handleSubmit}>
          <div className="mb-3">
            <label className="form-label small fw-bold text-muted text-uppercase">Full Name *</label>
            <input 
              type="text" 
              className="form-control form-control-custom"
              required
              placeholder="e.g. Eleanor Vance"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>

          <div className="row g-3 mb-3">
            <div className="col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">Email Address *</label>
              <input 
                type="email" 
                className="form-control form-control-custom"
                required
                placeholder="name@domain.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            <div className="col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">Phone</label>
              <input 
                type="tel" 
                className="form-control form-control-custom"
                placeholder="+1 (555) 000-0000"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>
          </div>

          <div className="row g-3 mb-4">
            <div className="col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">System Access Role</label>
              <select 
                className="form-select form-select-custom"
                value={role}
                onChange={(e) => setRole(e.target.value)}
              >
                <option value="user">User (Collector Member)</option>
                <option value="admin">Administrator (Curator Admin)</option>
              </select>
            </div>
            <div className="col-md-6">
              <label className="form-label small fw-bold text-muted text-uppercase">
                {isEditMode ? 'Change Password (Optional)' : 'Password *'}
              </label>
              <input 
                type="password" 
                className="form-control form-control-custom"
                required={!isEditMode}
                placeholder={isEditMode ? 'Leave blank to retain current' : 'Min. 6 characters'}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>

          <Button 
            type="submit" 
            variant="primary"
            loading={submitting}
            className="w-100 py-3 justify-content-center"
            icon={<i className="bi bi-check2-circle"></i>}
          >
            {isEditMode ? 'Update User Account' : 'Create User Account'}
          </Button>
        </form>
      </Card>
    </div>
  );
};

export default UserForm;
