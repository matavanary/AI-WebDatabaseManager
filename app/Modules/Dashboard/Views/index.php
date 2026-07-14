<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Dashboard Overview</h3>
    <div>
        <span class="badge bg-success bg-opacity-10 text-success border border-success p-2">
            <i class="fas fa-server me-1"></i> Server Connected
        </span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1e2130 0%, #2a2e45 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary mb-1">Total Databases</h6>
                        <h2 class="mb-0 fw-bold"><?= $stats['db_count'] ?></h2>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-database fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1e2130 0%, #2a2e45 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary mb-1">Users</h6>
                        <h2 class="mb-0 fw-bold"><?= $stats['user_count'] ?></h2>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1e2130 0%, #2a2e45 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary mb-1">Version</h6>
                        <h5 class="mb-0 fw-bold text-truncate" title="<?= htmlspecialchars($stats['version']) ?>" style="max-width: 120px;"><?= htmlspecialchars($stats['version']) ?></h5>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-code-branch fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1e2130 0%, #2a2e45 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary mb-1">Server Status</h6>
                        <h5 class="mb-0 fw-bold text-success">Online</h5>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-heartbeat fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
                <h5 class="mb-0">Query Activity (Mock)</h5>
            </div>
            <div class="card-body p-4">
                <canvas id="queryChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
                <h5 class="mb-0">Recent Login History</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">admin</h6>
                        <small class="text-secondary">Just now • 127.0.0.1</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">admin</h6>
                        <small class="text-secondary">2 hours ago • 127.0.0.1</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if(typeof Chart !== 'undefined') {
        const ctx = document.getElementById('queryChart').getContext('2d');
        
        // Gradient for line chart
        let gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                datasets: [{
                    label: 'Queries per hour',
                    data: [12, 19, 3, 5, 2, 3, 10],
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    }
});
</script>
