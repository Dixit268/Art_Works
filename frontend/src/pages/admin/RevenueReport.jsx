import React, { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import api, { getArtworkImageUrl } from '../../services/api';
import Loader from '../../components/Loader';
import Card from '../../components/common/Card';
import Badge from '../../components/common/Badge';
import Button from '../../components/common/Button';
import { useAuth } from '../../context/AuthContext';

const RevenueReport = () => {
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [year, setYear] = useState(new Date().getFullYear());

  const fetchRevenueData = async (targetYear) => {
    try {
      setLoading(true);
      const res = await api.get(`/revenue/monthly.php?year=${targetYear}`);
      setData(res.data);
    } catch (err) {
      console.error("Failed to load revenue data:", err);
      toast.error("Failed to load revenue analytics.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRevenueData(year);
  }, [year]);

  if (loading && !data) {
    return <Loader message="Analyzing gallery sales & monthly revenue..." />;
  }

  const summary = data?.summary || {};
  const monthlyData = data?.monthly_data || [];
  const maxMonthlyRevenue = data?.max_monthly_revenue || 1;
  const categoryBreakdown = data?.category_breakdown || [];
  const soldArtworks = data?.sold_artworks || [];
  const availableYears = data?.available_years || [new Date().getFullYear()];

  return (
    <div className="revenue-report-page">
      {/* SCREEN HEADER & CONTROLS (Hidden on print) */}
      <div className="d-print-none">
        <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
          <div>
            <h2 className="heading-display mb-1" style={{ color: 'var(--primary-navy)' }}>
              REVENUE & SALES ANALYTICS
            </h2>
            <p className="text-muted small mb-0">
              Month-wise sales performance, realized gallery revenue, and art acquisitions in Indian Rupees (₹ INR).
            </p>
          </div>

          <div className="d-flex align-items-center gap-2">
            <div className="d-flex align-items-center gap-2">
              <label htmlFor="yearSelect" className="small fw-bold text-muted text-nowrap">Exhibition Year:</label>
              <select 
                id="yearSelect"
                className="form-select form-select-sm rounded-pill fw-bold px-3 border-primary text-primary"
                style={{ width: '140px' }}
                value={year}
                onChange={(e) => setYear(Number(e.target.value))}
              >
                {availableYears.map(y => (
                  <option key={y} value={y}>FY {y}</option>
                ))}
              </select>
            </div>

            <Button 
              variant="primary" 
              size="sm" 
              onClick={() => window.print()}
              icon={<i className="bi bi-file-earmark-pdf-fill"></i>}
            >
              Print / Save PDF
            </Button>
          </div>
        </div>

        {/* KPI Stats Cards */}
        <div className="row g-3 mb-4">
          {/* Total Lifetime Revenue */}
          <div className="col-sm-6 col-xl-3">
            <div className="admin-stat-card revenue-stat-card">
              <div className="d-flex align-items-center justify-content-between w-100 mb-2">
                <span className="stat-label">Total Realized Revenue</span>
                <div 
                  className="stat-icon-wrapper text-white"
                  style={{ background: 'linear-gradient(135deg, #22C55E, #10B981)' }}
                >
                  <i className="bi bi-currency-rupee fs-5"></i>
                </div>
              </div>
              <div className="stat-value text-success">
                ₹{Number(summary.total_realized_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="stat-meta">
                <i className="bi bi-check2-all text-success me-1"></i>
                <span><strong>{summary.total_sold_count || 0}</strong> original pieces acquired</span>
              </div>
            </div>
          </div>

          {/* Selected Year Revenue */}
          <div className="col-sm-6 col-xl-3">
            <div className="admin-stat-card revenue-stat-card">
              <div className="d-flex align-items-center justify-content-between w-100 mb-2">
                <span className="stat-label">Year {year} Revenue</span>
                <div 
                  className="stat-icon-wrapper text-dark"
                  style={{ background: 'var(--accent-gradient)' }}
                >
                  <i className="bi bi-graph-up-arrow fs-5"></i>
                </div>
              </div>
              <div className="stat-value" style={{ color: 'var(--primary-blue)' }}>
                ₹{Number(summary.year_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="stat-meta">
                <i className="bi bi-calendar-check text-primary me-1"></i>
                <span><strong>{summary.year_sold_count || 0}</strong> pieces sold in {year}</span>
              </div>
            </div>
          </div>

          {/* Current Month Revenue */}
          <div className="col-sm-6 col-xl-3">
            <div className="admin-stat-card revenue-stat-card">
              <div className="d-flex align-items-center justify-content-between w-100 mb-2">
                <span className="stat-label">Current Month</span>
                <div 
                  className="stat-icon-wrapper text-white"
                  style={{ background: 'linear-gradient(135deg, #1B4DFF, #38BDF8)' }}
                >
                  <i className="bi bi-wallet2 fs-5"></i>
                </div>
              </div>
              <div className="stat-value" style={{ color: 'var(--primary-navy)' }}>
                ₹{Number(summary.current_month_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="stat-meta">
                <i className="bi bi-bag-check text-info me-1"></i>
                <span><strong>{summary.current_month_sold_count || 0}</strong> acquisitions this month</span>
              </div>
            </div>
          </div>

          {/* Available Stock Value */}
          <div className="col-sm-6 col-xl-3">
            <div className="admin-stat-card revenue-stat-card">
              <div className="d-flex align-items-center justify-content-between w-100 mb-2">
                <span className="stat-label">Available Inventory</span>
                <div 
                  className="stat-icon-wrapper text-white"
                  style={{ background: 'linear-gradient(135deg, #F59E0B, #FBBF24)' }}
                >
                  <i className="bi bi-box-seam fs-5"></i>
                </div>
              </div>
              <div className="stat-value text-dark">
                ₹{Number(summary.available_inventory_value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="stat-meta text-muted">
                <i className="bi bi-palette me-1"></i>
                <span><strong>{summary.available_count || 0}</strong> active pieces in gallery</span>
              </div>
            </div>
          </div>
        </div>

        {/* MONTHLY REVENUE BAR CHART */}
        <Card hover={false} padding="lg" className="mb-4">
          <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 pb-2 border-bottom">
            <div>
              <h4 className="heading-display mb-1" style={{ color: 'var(--primary-navy)', fontSize: '1.25rem' }}>
                <i className="bi bi-bar-chart-fill text-primary me-2"></i>
                MONTH-BY-MONTH REVENUE TRAJECTORY ({year})
              </h4>
              <p className="text-muted small mb-0">Visual distribution of artwork sales performance across all 12 months in ₹ INR.</p>
            </div>
            <Badge variant="primary" className="px-3 py-2 fw-semibold">
              Annual Total: ₹{Number(summary.year_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
            </Badge>
          </div>

          {/* CSS Flex Bar Chart */}
          <div className="d-flex align-items-end justify-content-between gap-2 pt-4 pb-2 px-2 overflow-auto" style={{ minHeight: '220px' }}>
            {monthlyData.map((m) => {
              const hasRevenue = m.revenue > 0;
              const barHeightPercent = maxMonthlyRevenue > 0 ? Math.max(10, Math.round((m.revenue / maxMonthlyRevenue) * 100)) : 10;

              return (
                <div key={m.month} className="d-flex flex-column align-items-center flex-grow-1" style={{ minWidth: '55px' }}>
                  {/* Revenue Label */}
                  <div 
                    className="small fw-bold mb-2 text-center" 
                    style={{ 
                      fontSize: '0.72rem', 
                      color: hasRevenue ? 'var(--primary-blue)' : '#9CA3AF' 
                    }}
                  >
                    {hasRevenue 
                      ? `₹${m.revenue >= 1000 ? (m.revenue / 1000).toFixed(1) + 'k' : m.revenue}`
                      : '₹0'}
                  </div>

                  {/* Bar */}
                  <div 
                    className="w-100 rounded-top-3 position-relative"
                    style={{
                      height: `${barHeightPercent * 1.5}px`,
                      background: hasRevenue 
                        ? 'linear-gradient(180deg, #38BDF8 0%, #1B4DFF 100%)' 
                        : '#E2E8F0',
                      boxShadow: hasRevenue ? '0 4px 12px rgba(27,77,255,0.2)' : 'none',
                      transition: 'all 0.3s ease',
                      cursor: 'pointer'
                    }}
                    title={`${m.month_name}: ₹${Number(m.revenue).toLocaleString('en-IN')} (${m.sold_count} pieces)`}
                  />

                  {/* Month Name */}
                  <div className="small fw-semibold mt-2 text-muted" style={{ fontSize: '0.75rem' }}>
                    {m.month_short}
                  </div>
                  <div className="badge rounded-pill bg-light text-muted border mt-1" style={{ fontSize: '0.65rem', padding: '0.2rem 0.4rem' }}>
                    {m.sold_count} pcs
                  </div>
                </div>
              );
            })}
          </div>
        </Card>
      </div>

      {/* ========================================================================= */}
      {/* OFFICIAL PDF / PRINTABLE EXECUTIVE STATEMENT (Always Clean & Printable)  */}
      {/* ========================================================================= */}
      <div className="bg-white p-4 p-md-5 rounded-4 shadow-sm mb-4 border">
        {/* Formal Header */}
        <div className="d-flex flex-wrap justify-content-between align-items-start border-bottom pb-4 mb-4">
          <div>
            <div className="d-flex align-items-center gap-2 mb-2">
              <div style={{ width: '38px', height: '38px', borderRadius: '10px', background: 'var(--accent-gradient)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <i className="bi bi-palette-fill text-dark fs-5"></i>
              </div>
              <div>
                <h3 className="h4 font-heading fw-bold mb-0" style={{ color: 'var(--primary-navy)' }}>ART GALLERY</h3>
                <span className="small text-muted text-uppercase" style={{ letterSpacing: '0.12em', fontSize: '0.7rem' }}>Executive Curator Portal</span>
              </div>
            </div>
            <h1 className="h5 font-heading fw-bold mt-2" style={{ color: 'var(--primary-navy)' }}>
              EXECUTIVE REVENUE & SALES STATEMENT
            </h1>
            <p className="text-muted small mb-0">Official financial ledger and monthly acquisition breakdown.</p>
          </div>

          <div className="text-sm-end mt-3 mt-sm-0">
            <span className="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fw-bold rounded-pill mb-2 d-inline-block">
              FISCAL YEAR {year}
            </span>
            <div className="small text-muted"><strong>Generated:</strong> {new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
            <div className="small text-muted"><strong>Curator In-Charge:</strong> {user?.name || 'Administrator'}</div>
            <div className="small text-muted"><strong>Currency:</strong> INR (₹ Indian Rupees)</div>
          </div>
        </div>

        {/* KPI Mini-Cards for PDF */}
        <div className="row g-3 mb-4">
          <div className="col-3">
            <div className="p-3 border rounded-3 bg-light">
              <div className="small text-muted text-uppercase fw-bold" style={{ fontSize: '0.7rem' }}>Total Revenue</div>
              <div className="h5 fw-bold text-success mb-0 mt-1">
                ₹{Number(summary.total_realized_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="small text-muted" style={{ fontSize: '0.72rem' }}>{summary.total_sold_count || 0} total sales</div>
            </div>
          </div>
          <div className="col-3">
            <div className="p-3 border rounded-3 bg-light">
              <div className="small text-muted text-uppercase fw-bold" style={{ fontSize: '0.7rem' }}>FY {year} Revenue</div>
              <div className="h5 fw-bold text-primary mb-0 mt-1">
                ₹{Number(summary.year_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="small text-muted" style={{ fontSize: '0.72rem' }}>{summary.year_sold_count || 0} sold in {year}</div>
            </div>
          </div>
          <div className="col-3">
            <div className="p-3 border rounded-3 bg-light">
              <div className="small text-muted text-uppercase fw-bold" style={{ fontSize: '0.7rem' }}>Avg Ticket Size</div>
              <div className="h5 fw-bold text-dark mb-0 mt-1">
                ₹{Number(summary.avg_sale_price || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="small text-muted" style={{ fontSize: '0.72rem' }}>Per acquisition</div>
            </div>
          </div>
          <div className="col-3">
            <div className="p-3 border rounded-3 bg-light">
              <div className="small text-muted text-uppercase fw-bold" style={{ fontSize: '0.7rem' }}>Active Inventory</div>
              <div className="h5 fw-bold text-secondary mb-0 mt-1">
                ₹{Number(summary.available_inventory_value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
              </div>
              <div className="small text-muted" style={{ fontSize: '0.72rem' }}>{summary.available_count || 0} pieces in stock</div>
            </div>
          </div>
        </div>

        {/* 12-Month Detailed Ledger Table */}
        <div className="mb-4">
          <h4 className="h6 font-heading fw-bold mb-2" style={{ color: 'var(--primary-navy)' }}>
            1. Month-Wise Sales & Acquisition Ledger (FY {year})
          </h4>
          <div className="table-responsive border rounded-3 overflow-hidden">
            <table className="table table-bordered align-middle mb-0" style={{ fontSize: '0.85rem' }}>
              <thead className="table-light text-uppercase">
                <tr>
                  <th style={{ width: '25%' }}>Month</th>
                  <th style={{ width: '20%' }} className="text-center">Artworks Sold</th>
                  <th style={{ width: '25%' }}>Realized Revenue (₹)</th>
                  <th style={{ width: '15%' }} className="text-center">Share (%)</th>
                  <th style={{ width: '15%' }} className="text-end">Avg / Piece (₹)</th>
                </tr>
              </thead>
              <tbody>
                {monthlyData.map((m) => {
                  const avgPiece = m.sold_count > 0 ? m.revenue / m.sold_count : 0;
                  return (
                    <tr key={m.month}>
                      <td className="fw-semibold">
                        <i className="bi bi-calendar3 me-2 text-primary d-print-none"></i>
                        {m.month_name}
                      </td>
                      <td className="text-center fw-bold">
                        {m.sold_count}
                      </td>
                      <td className="fw-bold" style={{ color: m.revenue > 0 ? 'var(--primary-blue)' : '#64748B' }}>
                        ₹{Number(m.revenue).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                      </td>
                      <td className="text-center">
                        {m.percentage_of_year}%
                      </td>
                      <td className="text-end">
                        {m.sold_count > 0 ? `₹${Number(avgPiece).toLocaleString('en-IN', { minimumFractionDigits: 2 })}` : '—'}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
              <tfoot className="table-light fw-bold">
                <tr>
                  <td>Total Fiscal Year {year}</td>
                  <td className="text-center">{summary.year_sold_count || 0} pieces</td>
                  <td className="text-primary">₹{Number(summary.year_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                  <td className="text-center">100%</td>
                  <td className="text-end">
                    {summary.year_sold_count > 0 
                      ? `₹${Number((summary.year_revenue || 0) / summary.year_sold_count).toLocaleString('en-IN', { minimumFractionDigits: 2 })}` 
                      : '—'}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        {/* Category Contribution Summary */}
        <div className="mb-4">
          <h4 className="h6 font-heading fw-bold mb-2" style={{ color: 'var(--primary-navy)' }}>
            2. Revenue Contribution by Art Medium / Genre
          </h4>
          <div className="table-responsive border rounded-3 overflow-hidden">
            <table className="table table-bordered table-sm align-middle mb-0" style={{ fontSize: '0.85rem' }}>
              <thead className="table-light text-uppercase">
                <tr>
                  <th>Medium / Category</th>
                  <th className="text-center">Sold Count</th>
                  <th>Realized Revenue (₹)</th>
                  <th className="text-center">Share of Total</th>
                </tr>
              </thead>
              <tbody>
                {categoryBreakdown.length > 0 ? (
                  categoryBreakdown.map((cb, idx) => (
                    <tr key={idx}>
                      <td className="fw-semibold">{cb.category_name}</td>
                      <td className="text-center">{cb.sold_count}</td>
                      <td className="fw-bold">₹{Number(cb.revenue).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                      <td className="text-center">{cb.percentage}%</td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="4" className="text-center py-3 text-muted">No sales categorized yet.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Formal Sign-Off Box */}
        <div className="row pt-4 mt-4 border-top">
          <div className="col-6">
            <div className="small text-muted">Curator Verification:</div>
            <div className="fw-bold mt-1" style={{ color: 'var(--primary-navy)' }}>{user?.name || 'Administrator'}</div>
            <div className="small text-muted">Authorized Gallery Registrar</div>
          </div>
          <div className="col-6 text-end">
            <div className="small text-muted">Authorized Signature & Seal:</div>
            <div className="d-inline-block border-bottom border-dark mt-4" style={{ width: '200px' }}></div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default RevenueReport;
