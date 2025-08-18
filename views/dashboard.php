@extends('layouts.app')

@section('content')
<div class="page-header-container mb-4">
    <div class="d-flex justify-content-between align-items-center page-header">
        <div class="d-flex align-items-center">
            <div class="dashboard-logo me-3">
                <img src="{{ asset('img/jetlouge_logo.png') }}" alt="Jetlouge Travels" class="logo-img">
            </div>
            <div>
                <h2 class="fw-bold mb-1">Driver Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, John! Today is {{ date('F j, Y') }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" id="refreshDashboard">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
            <button class="btn btn-primary btn-sm" id="quickReport">
                <i class="fas fa-plus me-1"></i>Quick Report
            </button>
        </div>
    </div>
</div>

<!-- Quick Stats Overview -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm stats-card h-100">
            <div class="card-body text-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary mb-3">
                    <i class="fas fa-route"></i>
                </div>
                <h3 class="mb-1 text-primary" id="totalTrips">0</h3>
                <small class="text-muted">Total Trips</small>
                <div class="mt-2">
                    <span class="badge bg-success">+2 this week</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm stats-card h-100">
            <div class="card-body text-center">
                <div class="stats-icon bg-success bg-opacity-10 text-success mb-3">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <h3 class="mb-1 text-success" id="totalEarnings">₱0</h3>
                <small class="text-muted">Total Earnings</small>
                <div class="mt-2">
                    <span class="badge bg-success">+₱5,200 this month</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm stats-card h-100">
            <div class="card-body text-center">
                <div class="stats-icon bg-warning bg-opacity-10 text-warning mb-3">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="mb-1 text-warning" id="avgRating">0.0</h3>
                <small class="text-muted">Average Rating</small>
                <div class="mt-2">
                    <div class="rating-stars text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm stats-card h-100">
            <div class="card-body text-center">
                <div class="stats-icon bg-info bg-opacity-10 text-info mb-3">
                    <i class="fas fa-road"></i>
                </div>
                <h3 class="mb-1 text-info" id="totalDistance">0 km</h3>
                <small class="text-muted">Distance Traveled</small>
                <div class="mt-2">
                    <span class="badge bg-info">+120 km this week</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Module Overview Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm module-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="module-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Trip Assignments</h6>
                        <small class="text-muted">Manage your trips</small>
                    </div>
                </div>
                <div class="module-stats mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Pending:</span>
                        <span class="badge bg-warning">3</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Active:</span>
                        <span class="badge bg-info">1</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">Completed:</span>
                        <span class="badge bg-success">12</span>
                    </div>
                </div>
                <a href="{{ route('trip-assignment') }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="fas fa-arrow-right me-1"></i>View Assignments
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm module-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="module-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Live Tracking</h6>
                        <small class="text-muted">Real-time location</small>
                    </div>
                </div>
                <div class="module-stats mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Status:</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Current Speed:</span>
                        <span class="text-muted">-- km/h</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">Last Update:</span>
                        <span class="text-muted">--</span>
                    </div>
                </div>
                <a href="{{ route('live-tracking') }}" class="btn btn-outline-success btn-sm w-100">
                    <i class="fas fa-arrow-right me-1"></i>View Tracking
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm module-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="module-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Reports & Checklist</h6>
                        <small class="text-muted">Submit reports</small>
                    </div>
                </div>
                <div class="module-stats mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Pending Reports:</span>
                        <span class="badge bg-warning">2</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">Submitted:</span>
                        <span class="badge bg-success">8</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">This Month:</span>
                        <span class="text-muted">10 reports</span>
                    </div>
                </div>
                <a href="{{ route('reports-and-checklist') }}" class="btn btn-outline-warning btn-sm w-100">
                    <i class="fas fa-arrow-right me-1"></i>View Reports
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm module-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="module-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Performance</h6>
                        <small class="text-muted">Your statistics</small>
                    </div>
                </div>
                <div class="module-stats mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">This Month:</span>
                        <span class="badge bg-success">Excellent</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small">On-time Rate:</span>
                        <span class="text-success">95%</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">Safety Score:</span>
                        <span class="text-success">4.8/5</span>
                    </div>
                </div>
                <button class="btn btn-outline-info btn-sm w-100" data-bs-toggle="modal" data-bs-target="#performanceModal">
                    <i class="fas fa-arrow-right me-1"></i>View Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Performance Overview & Recent Activity -->
<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Performance Chart -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Driver Performance Overview
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="performancePeriod" id="week" checked>
                        <label class="btn btn-outline-primary" for="week">Week</label>
                        <input type="radio" class="btn-check" name="performancePeriod" id="month">
                        <label class="btn btn-outline-primary" for="month">Month</label>
                        <input type="radio" class="btn-check" name="performancePeriod" id="year">
                        <label class="btn btn-outline-primary" for="year">Year</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Performance Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="performance-metric">
                            <div class="metric-value text-success">95%</div>
                            <div class="metric-label">On-Time Rate</div>
                            <div class="metric-change text-success">
                                <i class="fas fa-arrow-up"></i> +2% vs last period
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="performance-metric">
                            <div class="metric-value text-primary">4.8</div>
                            <div class="metric-label">Safety Score</div>
                            <div class="metric-change text-success">
                                <i class="fas fa-arrow-up"></i> +0.2 vs last period
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="performance-metric">
                            <div class="metric-value text-warning">98%</div>
                            <div class="metric-label">Fuel Efficiency</div>
                            <div class="metric-change text-success">
                                <i class="fas fa-arrow-up"></i> +3% vs last period
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="performance-metric">
                            <div class="metric-value text-info">4.9</div>
                            <div class="metric-label">Customer Rating</div>
                            <div class="metric-change text-success">
                                <i class="fas fa-arrow-up"></i> +0.1 vs last period
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Placeholder -->
                <div class="chart-container">
                    <div class="chart-placeholder">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Performance Chart</h6>
                        <p class="text-muted small">Visual performance data will be displayed here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Recent Activity -->
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2 text-success"></i>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-timeline">
                    <div class="activity-item">
                        <div class="activity-icon bg-success">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Trip #001 Completed</div>
                            <div class="activity-desc">Manila → Batangas</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-primary">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">New Trip Assigned</div>
                            <div class="activity-desc">Quezon City → Baguio</div>
                            <div class="activity-time">4 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-warning">
                            <i class="fas fa-clipboard"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Report Submitted</div>
                            <div class="activity-desc">Daily inspection report</div>
                            <div class="activity-time">6 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-info">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Rating Received</div>
                            <div class="activity-desc">5 stars from customer</div>
                            <div class="activity-time">1 day ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-secondary">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Fuel Refill</div>
                            <div class="activity-desc">45 liters added</div>
                            <div class="activity-time">1 day ago</div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary btn-sm">View All Activity</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Upcoming -->
<div class="row">
    <div class="col-lg-6">
        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2 text-warning"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-primary w-100 quick-action-btn">
                            <i class="fas fa-plus mb-2"></i><br>
                            <span>Request Trip</span>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-success w-100 quick-action-btn">
                            <i class="fas fa-clipboard-check mb-2"></i><br>
                            <span>Submit Report</span>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-info w-100 quick-action-btn">
                            <i class="fas fa-map-marked-alt mb-2"></i><br>
                            <span>Start Tracking</span>
                        </button>
                    </div>
                    <div class="col-6 mb-3">
                        <button class="btn btn-outline-warning w-100 quick-action-btn">
                            <i class="fas fa-phone mb-2"></i><br>
                            <span>Emergency</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <!-- Upcoming Trips -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2 text-info"></i>
                    Upcoming Trips
                </h5>
            </div>
            <div class="card-body">
                <div class="upcoming-trip-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="trip-route fw-bold">Manila → Baguio</div>
                            <div class="trip-time text-muted">
                                <i class="fas fa-clock me-1"></i>Tomorrow, 6:00 AM
                            </div>
                        </div>
                        <span class="badge bg-primary">Assigned</span>
                    </div>
                    <div class="trip-details">
                        <small class="text-muted">
                            <i class="fas fa-users me-1"></i>25 passengers •
                            <i class="fas fa-road me-1"></i>250 km •
                            <i class="fas fa-peso-sign me-1"></i>₱6,800
                        </small>
                    </div>
                </div>
                <hr>
                <div class="upcoming-trip-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="trip-route fw-bold">Quezon City → Batangas</div>
                            <div class="trip-time text-muted">
                                <i class="fas fa-clock me-1"></i>Aug 20, 2:00 PM
                            </div>
                        </div>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                    <div class="trip-details">
                        <small class="text-muted">
                            <i class="fas fa-users me-1"></i>18 passengers •
                            <i class="fas fa-road me-1"></i>120 km •
                            <i class="fas fa-peso-sign me-1"></i>₱3,200
                        </small>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('trip-assignment') }}" class="btn btn-outline-primary btn-sm">View All Trips</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 1.4rem;
}

.module-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
}

.module-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.module-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.module-stats {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
}

.performance-metric {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.metric-change {
    font-size: 0.8rem;
    font-weight: 500;
}

.chart-placeholder {
    text-align: center;
    padding: 4rem 2rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.activity-timeline {
    position: relative;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    position: relative;
}

.activity-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 18px;
    top: 36px;
    width: 2px;
    height: calc(100% + 0.5rem);
    background: #e9ecef;
}

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.activity-desc {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.activity-time {
    color: #adb5bd;
    font-size: 0.8rem;
}

.quick-action-btn {
    height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.upcoming-trip-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.trip-route {
    color: #495057;
}

.trip-time {
    font-size: 0.9rem;
}

.trip-details {
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .stats-card, .module-card {
        margin-bottom: 1rem;
    }

    .performance-metric {
        margin-bottom: 0.5rem;
    }

    .metric-value {
        font-size: 1.5rem;
    }

    .quick-action-btn {
        height: 70px;
        font-size: 0.9rem;
    }
}
</style>

@endsection