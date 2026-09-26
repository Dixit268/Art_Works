import React, { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../context/AuthContext';
import Card from '../components/common/Card';
import Button from '../components/common/Button';

const Login = () => {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });
  const [loading, setLoading] = useState(false);

  const from = location.state?.from?.pathname || '/dashboard';

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.email.trim()) {
      toast.error("Please enter your email address.");
      return;
    }
    if (!formData.password) {
      toast.error("Please enter your password.");
      return;
    }

    setLoading(true);

    try {
      const user = await login(formData.email, formData.password);
      toast.success(`Welcome back, ${user.name}!`);
      if (user.role === 'admin') {
        navigate('/admin');
      } else {
        navigate(from, { replace: true });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'Invalid email or password. Please try again.';
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="py-5 my-auto">
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-5 col-md-7">
            <Card hover={false} padding="lg">
              <div className="text-center mb-4">
                <div 
                  className="d-inline-flex align-items-center justify-content-center text-white rounded-4 mb-3 shadow-sm"
                  style={{ width: '56px', height: '56px', background: 'linear-gradient(135deg, var(--primary-blue), #38BDF8)' }}
                >
                  <i className="bi bi-person-lock fs-3"></i>
                </div>
                <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>SIGN IN</h2>
                <p className="text-muted small">Access your personal art collection and wishlist</p>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label className="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                  <div className="input-group">
                    <span className="input-group-text bg-white rounded-start-4 border-end-0">
                      <i className="bi bi-envelope text-muted"></i>
                    </span>
                    <input 
                      type="email" 
                      className="form-control form-control-custom rounded-start-0 border-start-0"
                      placeholder="user@example.com"
                      required
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    />
                  </div>
                </div>

                <div className="mb-4">
                  <label className="form-label small fw-bold text-muted text-uppercase">Password</label>
                  <div className="input-group">
                    <span className="input-group-text bg-white rounded-start-4 border-end-0">
                      <i className="bi bi-key text-muted"></i>
                    </span>
                    <input 
                      type="password" 
                      className="form-control form-control-custom rounded-start-0 border-start-0"
                      placeholder="••••••••"
                      required
                      value={formData.password}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    />
                  </div>
                </div>

                <Button 
                  type="submit" 
                  variant="primary"
                  loading={loading}
                  className="w-100 py-3 mb-3"
                  icon={<i className="bi bi-box-arrow-in-right ms-1"></i>}
                >
                  Sign In
                </Button>
              </form>

              <div className="text-center pt-3 border-top small text-muted">
                Don't have an account yet?{' '}
                <Link to="/register" className="fw-bold text-decoration-none" style={{ color: 'var(--primary-blue)' }}>
                  Create Account
                </Link>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
