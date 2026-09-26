import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import api, { getArtworkImageUrl } from '../services/api';
import { useAuth } from '../context/AuthContext';
import ArtworkCard from '../components/ArtworkCard';
import Loader from '../components/Loader';
import Card from '../components/common/Card';
import Badge from '../components/common/Badge';
import Button from '../components/common/Button';

const UserDashboard = () => {
  const { user, updateUserProfile } = useAuth();
  const [activeTab, setActiveTab] = useState('wishlist'); // 'wishlist', 'profile', 'password', 'inquiries'

  // 1. Wishlist State
  const [wishlistItems, setWishlistItems] = useState([]);
  const [wishlistLoading, setWishlistLoading] = useState(true);

  // 2. Profile Form State
  const [profileName, setProfileName] = useState(user?.name || '');
  const [profilePhone, setProfilePhone] = useState(user?.phone || '');
  const [profileUpdating, setProfileUpdating] = useState(false);

  // 3. Password Form State
  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    new_password: '',
    confirm_password: '',
  });
  const [passwordUpdating, setPasswordUpdating] = useState(false);

  // 4. Inquiries State
  const [myInquiries, setMyInquiries] = useState([]);
  const [inquiriesLoading, setInquiriesLoading] = useState(false);

  // Load wishlist
  const fetchWishlist = async () => {
    try {
      setWishlistLoading(true);
      const res = await api.get('/wishlist/list.php');
      setWishlistItems(res.data.data || []);
    } catch (err) {
      console.error("Failed to load wishlist:", err);
      toast.error("Failed to load wishlist items.");
    } finally {
      setWishlistLoading(false);
    }
  };

  // Load inquiries
  const fetchMyInquiries = async () => {
    try {
      setInquiriesLoading(true);
      const res = await api.get('/inquiries/my.php');
      setMyInquiries(res.data.data || []);
    } catch (err) {
      console.error("Failed to load inquiries:", err);
    } finally {
      setInquiriesLoading(false);
    }
  };

  useEffect(() => {
    fetchWishlist();
  }, []);

  useEffect(() => {
    if (activeTab === 'inquiries') {
      fetchMyInquiries();
    }
  }, [activeTab]);

  const handleWishlistChange = (artworkId, inWishlist) => {
    if (!inWishlist) {
      setWishlistItems(prev => prev.filter(item => item.artwork.id !== artworkId));
      toast.success("Artwork removed from your wishlist.");
    }
  };

  const handleProfileSubmit = async (e) => {
    e.preventDefault();
    if (!profileName.trim()) {
      toast.error("Name cannot be empty.");
      return;
    }

    setProfileUpdating(true);
    try {
      const res = await api.post('/users/profile.php', {
        action: 'update_profile',
        name: profileName,
        phone: profilePhone
      });
      updateUserProfile(res.data.user);
      toast.success(res.data.message || "Profile updated successfully!");
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to update profile.");
    } finally {
      setProfileUpdating(false);
    }
  };

  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    if (!passwordForm.current_password) {
      toast.error("Please enter your current password.");
      return;
    }
    if (passwordForm.new_password.length < 6) {
      toast.error("New password must be at least 6 characters.");
      return;
    }
    if (passwordForm.new_password !== passwordForm.confirm_password) {
      toast.error("New passwords do not match.");
      return;
    }

    setPasswordUpdating(true);
    try {
      const res = await api.post('/users/profile.php', {
        action: 'change_password',
        current_password: passwordForm.current_password,
        new_password: passwordForm.new_password
      });
      toast.success(res.data.message || "Password changed successfully!");
      setPasswordForm({ current_password: '', new_password: '', confirm_password: '' });
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to change password.");
    } finally {
      setPasswordUpdating(false);
    }
  };

  return (
    <div className="py-5">
      <div className="container">
        {/* Header Profile Summary */}
        <Card hover={false} padding="lg" className="mb-5 bg-white">
          <div className="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
            <div className="d-flex align-items-center gap-4">
              <div 
                className="d-flex align-items-center justify-content-center text-white rounded-circle shadow-sm"
                style={{ width: '80px', height: '80px', background: 'linear-gradient(135deg, var(--primary-blue), #38BDF8)', fontSize: '2rem', fontWeight: 'bold' }}
              >
                {user?.name?.charAt(0).toUpperCase() || 'U'}
              </div>
              <div>
                <Badge variant={user?.role === 'admin' ? 'primary' : 'gradient'} className="mb-1 text-uppercase small">
                  {user?.role === 'admin' ? 'Administrator' : 'Collector Member'}
                </Badge>
                <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>{user?.name}</h2>
                <p className="text-muted mb-0 small">
                  <i className="bi bi-envelope me-1"></i> {user?.email} {user?.phone ? `• 📞 ${user.phone}` : ''}
                </p>
              </div>
            </div>

            <div className="d-flex gap-2">
              <Button to="/gallery" variant="primary">
                <i className="bi bi-compass-fill me-1"></i> Explore Artworks
              </Button>
            </div>
          </div>
        </Card>

        {/* Dashboard Tabs */}
        <div className="d-flex flex-wrap gap-2 mb-4">
          <button 
            className={`btn rounded-pill px-4 py-2 fw-semibold ${activeTab === 'wishlist' ? 'btn-primary custom-btn-primary' : 'btn-light border'}`}
            onClick={() => setActiveTab('wishlist')}
          >
            <i className="bi bi-heart-fill me-2"></i> Saved Wishlist ({wishlistItems.length})
          </button>
          <button 
            className={`btn rounded-pill px-4 py-2 fw-semibold ${activeTab === 'inquiries' ? 'btn-primary custom-btn-primary' : 'btn-light border'}`}
            onClick={() => setActiveTab('inquiries')}
          >
            <i className="bi bi-chat-square-text-fill me-2"></i> My Inquiries
          </button>
          <button 
            className={`btn rounded-pill px-4 py-2 fw-semibold ${activeTab === 'profile' ? 'btn-primary custom-btn-primary' : 'btn-light border'}`}
            onClick={() => setActiveTab('profile')}
          >
            <i className="bi bi-person-lines-fill me-2"></i> Profile Info
          </button>
          <button 
            className={`btn rounded-pill px-4 py-2 fw-semibold ${activeTab === 'password' ? 'btn-primary custom-btn-primary' : 'btn-light border'}`}
            onClick={() => setActiveTab('password')}
          >
            <i className="bi bi-shield-lock-fill me-2"></i> Change Password
          </button>
        </div>

        {/* Tab 1: Wishlist */}
        {activeTab === 'wishlist' && (
          <div>
            {wishlistLoading ? (
              <Loader message="Loading your curated wishlist..." />
            ) : wishlistItems.length === 0 ? (
              <Card hover={false} padding="lg" className="text-center py-5">
                <div className="display-1 text-muted mb-3"><i className="bi bi-heartbreak"></i></div>
                <h4 className="fw-bold">Your Wishlist is Empty</h4>
                <p className="text-muted">Save your favorite pieces while browsing the exhibition to view them later.</p>
                <Button to="/gallery" variant="primary" className="mt-2">
                  Browse Exhibition
                </Button>
              </Card>
            ) : (
              <div className="row g-4">
                {wishlistItems.map((item) => (
                  <div key={item.wishlist_id} className="col-lg-4 col-md-6">
                    <ArtworkCard 
                      artwork={{ ...item.artwork, in_wishlist: true }} 
                      onWishlistChange={handleWishlistChange}
                    />
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Tab 2: My Inquiries */}
        {activeTab === 'inquiries' && (
          <div>
            {inquiriesLoading ? (
              <Loader message="Loading your inquiries..." />
            ) : myInquiries.length === 0 ? (
              <Card hover={false} padding="lg" className="text-center py-5">
                <div className="display-1 text-muted mb-3"><i className="bi bi-chat-dots"></i></div>
                <h4 className="fw-bold">No Inquiries Found</h4>
                <p className="text-muted">You haven't submitted any artwork acquisition or exhibition inquiries yet.</p>
                <Button to="/gallery" variant="primary" className="mt-2">
                  Discover Artworks
                </Button>
              </Card>
            ) : (
              <Card hover={false} padding="md">
                <div className="table-responsive">
                  <table className="table align-middle table-hover">
                    <thead className="table-light text-uppercase small text-muted">
                      <tr>
                        <th>Artwork</th>
                        <th>Your Message</th>
                        <th>Date Submitted</th>
                        <th>Curator Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {myInquiries.map((inq) => (
                        <tr key={inq.id}>
                          <td>
                            {inq.artwork_title ? (
                              <div className="d-flex align-items-center gap-3">
                                {inq.artwork_image && (
                                  <img 
                                    src={getArtworkImageUrl({ image_path: inq.artwork_image, image_type: 'upload' })} 
                                    alt="thumb" 
                                    className="rounded-3 object-fit-cover shadow-sm"
                                    style={{ width: '46px', height: '46px' }}
                                    onError={(e) => {
                                      e.currentTarget.src = inq.artwork_image;
                                    }}
                                  />
                                )}
                                <div>
                                  <div className="fw-bold text-dark">{inq.artwork_title}</div>
                                  {inq.artwork_price && (
                                    <div className="small text-muted">₹{Number(inq.artwork_price).toLocaleString('en-IN')}</div>
                                  )}
                                </div>
                              </div>
                            ) : (
                              <Badge variant="light">General Inquiry</Badge>
                            )}
                          </td>
                          <td style={{ maxWidth: '350px' }}>
                            <div className="small text-secondary" style={{ whiteSpace: 'pre-wrap' }}>
                              {inq.message}
                            </div>
                          </td>
                          <td className="small text-muted">
                            {new Date(inq.created_at).toLocaleDateString()}
                          </td>
                          <td>
                            <Badge variant={inq.status === 'pending' ? 'warning' : inq.status === 'contacted' ? 'primary' : 'success'}>
                              {inq.status === 'pending' ? 'Curator Reviewing' : inq.status === 'contacted' ? 'Curator Contacted' : 'Resolved'}
                            </Badge>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </Card>
            )}
          </div>
        )}

        {/* Tab 3: Profile Info (View / Edit) */}
        {activeTab === 'profile' && (
          <div className="row">
            <div className="col-lg-6">
              <Card hover={false} padding="md">
                <h5 className="fw-bold brand-font mb-4" style={{ color: 'var(--primary-navy)' }}>
                  Personal Information
                </h5>

                <form onSubmit={handleProfileSubmit}>
                  <div className="mb-3">
                    <label className="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                    <input 
                      type="text" 
                      className="form-control form-control-custom"
                      required
                      value={profileName}
                      onChange={(e) => setProfileName(e.target.value)}
                    />
                  </div>

                  <div className="mb-3">
                    <label className="form-label small fw-bold text-muted text-uppercase">Email Address (Read-only)</label>
                    <input 
                      type="email" 
                      className="form-control form-control-custom bg-light"
                      disabled
                      value={user?.email || ''}
                    />
                    <small className="text-muted">Email address is permanently linked to your account.</small>
                  </div>

                  <div className="mb-4">
                    <label className="form-label small fw-bold text-muted text-uppercase">Contact Phone</label>
                    <input 
                      type="tel" 
                      className="form-control form-control-custom"
                      placeholder="+1 (555) 000-0000"
                      value={profilePhone}
                      onChange={(e) => setProfilePhone(e.target.value)}
                    />
                  </div>

                  <Button 
                    type="submit" 
                    variant="primary"
                    loading={profileUpdating}
                    icon={<i className="bi bi-check2-circle"></i>}
                  >
                    Save Changes
                  </Button>
                </form>
              </Card>
            </div>
          </div>
        )}

        {/* Tab 4: Change Password */}
        {activeTab === 'password' && (
          <div className="row">
            <div className="col-lg-6">
              <Card hover={false} padding="md">
                <h5 className="fw-bold brand-font mb-4" style={{ color: 'var(--primary-navy)' }}>
                  Security & Password
                </h5>

                <form onSubmit={handlePasswordSubmit}>
                  <div className="mb-3">
                    <label className="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                    <input 
                      type="password" 
                      className="form-control form-control-custom"
                      required
                      placeholder="Enter current password"
                      value={passwordForm.current_password}
                      onChange={(e) => setPasswordForm({ ...passwordForm, current_password: e.target.value })}
                    />
                  </div>

                  <div className="mb-3">
                    <label className="form-label small fw-bold text-muted text-uppercase">New Password</label>
                    <input 
                      type="password" 
                      className="form-control form-control-custom"
                      required
                      placeholder="Min. 6 characters"
                      value={passwordForm.new_password}
                      onChange={(e) => setPasswordForm({ ...passwordForm, new_password: e.target.value })}
                    />
                  </div>

                  <div className="mb-4">
                    <label className="form-label small fw-bold text-muted text-uppercase">Confirm New Password</label>
                    <input 
                      type="password" 
                      className="form-control form-control-custom"
                      required
                      placeholder="Repeat new password"
                      value={passwordForm.confirm_password}
                      onChange={(e) => setPasswordForm({ ...passwordForm, confirm_password: e.target.value })}
                    />
                  </div>

                  <Button 
                    type="submit" 
                    variant="primary"
                    loading={passwordUpdating}
                    icon={<i className="bi bi-shield-check"></i>}
                  >
                    Update Password
                  </Button>
                </form>
              </Card>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default UserDashboard;
