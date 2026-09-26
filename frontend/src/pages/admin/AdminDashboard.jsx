import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api, { getArtworkImageUrl } from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Button from '../../components/common/Button';

const AdminDashboard = () => {
  const [statsData, setStatsData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        setLoading(true);
        const res = await api.get('/dashboard-stats.php');
        setStatsData(res.data);
      } catch (err) {
        console.error("Failed to load admin stats:", err);
      } finally {
        setLoading(false);
      }
    };
    fetchDashboard();
  }, []);

  if (loading) {
    return <Loader message="Loading dashboard statistics..." />;
  }

  const { stats, recent_artworks, recent_inquiries } = statsData || {
    stats: { artworks: {}, categories: {}, users: {}, inquiries: {} },
    recent_artworks: [],
    recent_inquiries: []
  };

  return (
    <div>
      {/* Title & Quick Actions */}
      <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
          <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
            DASHBOARD OVERVIEW
          </h2>
          <p className="text-muted small mb-0">Real-time gallery metrics, inventory status, and patron inquiries.</p>
        </div>
        <div className="d-flex gap-2">
          <Button to="/admin/artworks/new" variant="primary" size="sm" icon={<i className="bi bi-plus-lg"></i>}>
            Add Artwork
          </Button>
          <Button to="/admin/categories/new" variant="light" size="sm" icon={<i className="bi bi-folder-plus"></i>}>
            Add Category
          </Button>
        </div>
      </div>

      {/* 4 Stat Cards with Accent Gradient Icons */}
      <div className="row g-4 mb-5">
        {/* Total Artworks */}
        <div className="col-xl-3 col-sm-6">
          <Card hover={true} padding="md" className="h-100">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <span className="text-muted small fw-bold text-uppercase">Total Artworks</span>
              <div className="stat-icon-gradient">
                <i className="bi bi-palette-fill fs-5"></i>
              </div>
            </div>
            <h3 className="display-6 fw-bold mb-1" style={{ color: 'var(--primary-navy)' }}>
              {stats?.artworks?.total || 0}
            </h3>
            <div className="small text-muted">
              <span className="text-success fw-semibold">{stats?.artworks?.available || 0} Available</span> • {stats?.artworks?.sold || 0} Sold
            </div>
          </Card>
        </div>

        {/* Total Categories */}
        <div className="col-xl-3 col-sm-6">
          <Card hover={true} padding="md" className="h-100">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <span className="text-muted small fw-bold text-uppercase">Collections</span>
              <div className="stat-icon-gradient">
                <i className="bi bi-collection-fill fs-5"></i>
              </div>
            </div>
            <h3 className="display-6 fw-bold mb-1" style={{ color: 'var(--primary-navy)' }}>
              {stats?.categories?.total || 0}
            </h3>
            <div className="small text-muted">
              <span className="fw-semibold" style={{ color: 'var(--primary-blue)' }}>{stats?.categories?.active || 0} Active</span> mediums
            </div>
          </Card>
        </div>

        {/* Inquiries */}
        <div className="col-xl-3 col-sm-6">
          <Card hover={true} padding="md" className="h-100">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <span className="text-muted small fw-bold text-uppercase">Inquiries</span>
              <div className="stat-icon-gradient">
                <i className="bi bi-chat-square-dots-fill fs-5"></i>
              </div>
            </div>
            <h3 className="display-6 fw-bold mb-1" style={{ color: 'var(--primary-navy)' }}>
              {stats?.inquiries?.total || 0}
            </h3>
            <div className="small text-muted">
              <Badge variant="danger">{stats?.inquiries?.pending || 0} Pending Attention</Badge>
            </div>
          </Card>
        </div>

        {/* Registered Users */}
        <div className="col-xl-3 col-sm-6">
          <Card hover={true} padding="md" className="h-100">
            <div className="d-flex justify-content-between align-items-center mb-3">
              <span className="text-muted small fw-bold text-uppercase">Collectors & Users</span>
              <div className="stat-icon-gradient">
                <i className="bi bi-people-fill fs-5"></i>
              </div>
            </div>
            <h3 className="display-6 fw-bold mb-1" style={{ color: 'var(--primary-navy)' }}>
              {stats?.users?.total || 0}
            </h3>
            <div className="small text-muted">
              {stats?.users?.regular_users || 0} Members • {stats?.users?.admin_users || 0} Admins
            </div>
          </Card>
        </div>
      </div>

      {/* Tables Row: Recent Artworks & Recent Inquiries */}
      <div className="row g-4">
        {/* Recent Artworks */}
        <div className="col-lg-6">
          <Card hover={false} padding="md" className="h-100">
            <div className="d-flex justify-content-between align-items-center mb-4">
              <h5 className="fw-bold brand-font mb-0" style={{ color: 'var(--primary-navy)' }}>Recent Artworks</h5>
              <Button to="/admin/artworks" variant="outline" size="sm">
                View All
              </Button>
            </div>

            <div className="table-responsive">
              <table className="table align-middle">
                <thead>
                  <tr className="text-muted small">
                    <th>Piece</th>
                    <th>Price</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {recent_artworks.map((art) => (
                    <tr key={art.id}>
                      <td>
                        <div className="d-flex align-items-center gap-3">
                          <img 
                            src={getArtworkImageUrl(art)} 
                            alt={art.title}
                            className="rounded-3 object-fit-cover shadow-sm"
                            style={{ width: '44px', height: '44px' }}
                            onError={(e) => {
                              e.currentTarget.src = 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=100&q=80';
                            }}
                          />
                          <div>
                            <div className="fw-bold text-dark">{art.title}</div>
                            <div className="small text-muted">{art.category_name}</div>
                          </div>
                        </div>
                      </td>
                      <td className="fw-bold">₹{Number(art.price).toLocaleString('en-IN')}</td>
                      <td>
                        <Badge variant={art.availability_status === 'available' ? 'success' : 'danger'}>
                          {art.availability_status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        </div>

        {/* Recent Inquiries */}
        <div className="col-lg-6">
          <Card hover={false} padding="md" className="h-100">
            <div className="d-flex justify-content-between align-items-center mb-4">
              <h5 className="fw-bold brand-font mb-0" style={{ color: 'var(--primary-navy)' }}>Recent Patron Inquiries</h5>
              <Button to="/admin/inquiries" variant="outline" size="sm">
                View All
              </Button>
            </div>

            <div className="table-responsive">
              <table className="table align-middle">
                <thead>
                  <tr className="text-muted small">
                    <th>Sender</th>
                    <th>Artwork</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {recent_inquiries.map((inq) => (
                    <tr key={inq.id}>
                      <td>
                        <div className="fw-bold text-dark">{inq.name}</div>
                        <div className="small text-muted">{inq.email}</div>
                      </td>
                      <td className="small">{inq.artwork_title || 'General Inquiry'}</td>
                      <td>
                        <Badge variant={inq.status === 'pending' ? 'warning' : inq.status === 'contacted' ? 'primary' : 'success'}>
                          {inq.status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
