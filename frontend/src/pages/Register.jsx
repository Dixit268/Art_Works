import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../context/AuthContext';
import Card from '../components/common/Card';
import Button from '../components/common/Button';

const Register = () => {
  const { register } = useAuth();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    confirmPassword: '',
  });
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!formData.name.trim()) {
      toast.error("Please enter your full name.");
      return;
    }
    if (!formData.email.trim()) {
      toast.error("Please enter your email address.");
      return;
    }
    if (formData.password !== formData.confirmPassword) {
      toast.error("Passwords do not match.");
      return;
    }
    if (formData.password.length < 6) {
      toast.error("Password must be at least 6 characters.");
      return;
    }

    setLoading(true);

    try {
      const user = await register(formData.name, formData.email, formData.phone, formData.password);
      toast.success(`Account created successfully! Welcome, ${user.name}.`);
      navigate('/dashboard');
    } catch (err) {
      const msg = err.response?.data?.message || 'Registration failed. Please verify your details.';
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="py-5 my-auto">
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-6 col-md-8">
            <Card hover={false} padding="lg">
              <div className="text-center mb-4">
                <div 
                  className="d-inline-flex align-items-center justify-content-center text-white rounded-4 mb-3 shadow-sm"
                  style={{ width: '56px', height: '56px', background: 'var(--accent-gradient)' }}
                >
                  <i className="bi bi-person-plus-fill fs-3 text-dark"></i>
                </div>
                <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>CREATE ACCOUNT</h2>
                <p className="text-muted small">Join our community of art collectors and connoisseurs</p>
              </div>

              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label className="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                  <input 
                    type="text" 
                    className="form-control form-control-custom"
                    placeholder="Eleanor Vance"
                    required
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  />
                </div>

                <div className="row g-3 mb-3">
                  <div className="col-md-6">
                    <label className="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                    <input 
                      type="email" 
                      className="form-control form-control-custom"
                      placeholder="name@domain.com"
                      required
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label small fw-bold text-muted text-uppercase">Phone Number</label>
                    <input 
                      type="tel" 
                      className="form-control form-control-custom"
                      placeholder="+1 (555) 000-0000"
                      value={formData.phone}
                      onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    />
                  </div>
                </div>

                <div className="row g-3 mb-4">
                  <div className="col-md-6">
                    <label className="form-label small fw-bold text-muted text-uppercase">Password</label>
                    <input 
                      type="password" 
                      className="form-control form-control-custom"
                      placeholder="Min. 6 characters"
                      required
                      value={formData.password}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    />
                  </div>
                  <div className="col-md-6">
                    <label className="form-label small fw-bold text-muted text-uppercase">Confirm Password</label>
                    <input 
                      type="password" 
                      className="form-control form-control-custom"
                      placeholder="Repeat password"
                      required
                      value={formData.confirmPassword}
                      onChange={(e) => setFormData({ ...formData, confirmPassword: e.target.value })}
                    />
                  </div>
                </div>

                <Button 
                  type="submit" 
                  variant="gradient"
                  loading={loading}
                  className="w-100 py-3 mb-3 text-dark fw-bold"
                  icon={<i className="bi bi-arrow-right ms-1"></i>}
                >
                  Join Aura Luxe
                </Button>
              </form>

              <div className="text-center pt-3 border-top small text-muted">
                Already registered?{' '}
                <Link to="/login" className="fw-bold text-decoration-none" style={{ color: 'var(--primary-blue)' }}>
                  Sign In
                </Link>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Register;
