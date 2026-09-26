import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
import Card from '../../components/common/Card';
import Button from '../../components/common/Button';

const AdminLogin = () => {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [email, setEmail] = useState('admin@gallery.com');
  const [password, setPassword] = useState('Admin@123');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!email.trim()) {
      toast.error("Please enter your admin email.");
      return;
    }
    if (!password) {
      toast.error("Please enter your admin password.");
      return;
    }

    setLoading(true);

    try {
      const user = await login(email, password);
      if (user.role === 'admin') {
        toast.success(`Welcome to Curator Portal, ${user.name}!`);
        navigate('/admin');
      } else {
        toast.error("Access denied. Administrator privileges required.");
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Authentication failed. Please verify admin credentials.';
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-vh-100 d-flex align-items-center justify-content-center p-3" style={{ background: 'var(--primary-navy)' }}>
      <div className="w-100" style={{ maxWidth: '440px' }}>
        <Card hover={false} padding="lg">
          <div className="text-center mb-4">
            <div 
              className="d-inline-flex align-items-center justify-content-center text-white rounded-4 mb-3 shadow"
              style={{ width: '60px', height: '60px', background: 'linear-gradient(135deg, var(--primary-blue), #38BDF8)' }}
            >
              <i className="bi bi-shield-lock-fill fs-2"></i>
            </div>
            <h3 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>CURATOR PORTAL</h3>
            <p className="text-muted small">Art Gallery Administration & Management</p>
          </div>

          <form onSubmit={handleSubmit}>
            <div className="mb-3">
              <label className="form-label small fw-bold text-muted text-uppercase">Admin Email</label>
              <input 
                type="email" 
                className="form-control form-control-custom"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>

            <div className="mb-4">
              <label className="form-label small fw-bold text-muted text-uppercase">Admin Password</label>
              <input 
                type="password" 
                className="form-control form-control-custom"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>

            <Button 
              type="submit" 
              variant="primary"
              loading={loading}
              className="w-100 py-3 justify-content-center mb-3"
              icon={<i className="bi bi-arrow-right ms-1"></i>}
            >
              Access Admin Panel
            </Button>
          </form>

          <div className="text-center mt-3 pt-3 border-top small">
            <Link to="/" className="text-muted text-decoration-none">
              <i className="bi bi-arrow-left me-1"></i> Back to Public Gallery
            </Link>
          </div>
        </Card>
      </div>
    </div>
  );
};

export default AdminLogin;
