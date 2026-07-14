<div class="p-4 w-100 h-100 d-flex flex-column">
    <div class="mb-4">
        <h4 class="text-white"><i class="fas fa-heartbeat text-primary me-2"></i>Server Monitor</h4>
        <p class="text-secondary">Real-time status and running processes of the active database server.</p>
    </div>

    <!-- Server Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="background: var(--card-bg);">
                <div class="card-body">
                    <h6 class="text-secondary mb-2">Database Engine Version</h6>
                    <h4 class="text-white mb-0"><i class="fas fa-code-branch text-info me-2"></i><?= htmlspecialchars($version) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="background: var(--card-bg);">
                <div class="card-body">
                    <h6 class="text-secondary mb-2">Uptime</h6>
                    <h4 class="text-white mb-0"><i class="fas fa-clock text-success me-2"></i><?= htmlspecialchars($uptime) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="background: var(--card-bg);">
                <div class="card-body">
                    <h6 class="text-secondary mb-2">Active Threads</h6>
                    <h4 class="text-white mb-0"><i class="fas fa-network-wired text-warning me-2"></i><?= htmlspecialchars($threads) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Process List -->
    <div class="card border-0 shadow-sm flex-fill" style="background: var(--card-bg);">
        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="fas fa-tasks text-primary me-2"></i>Process List</h5>
            <button class="btn btn-sm btn-outline-primary" id="refreshProcesses">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover table-striped w-100" id="processTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Host</th>
                            <th>Database</th>
                            <th>Command</th>
                            <th>Time</th>
                            <th>State</th>
                            <th>Info</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#processTable').DataTable({
        ajax: {
            url: window.BASE_URL + '/api/monitor/processes',
            dataSrc: function (json) {
                if(!json.success) {
                    notify('error', json.message);
                    return [];
                }
                return json.data;
            }
        },
        columns: [
            { data: 'Id', render: function(d) { return `<span class="fw-bold">${d}</span>`; } },
            { data: 'User' },
            { data: 'Host' },
            { data: 'db', render: function(d) { return d ? d : '<span class="text-secondary font-italic">NULL</span>'; } },
            { data: 'Command', render: function(d) {
                let badge = 'bg-secondary';
                if(d === 'Query') badge = 'bg-primary';
                if(d === 'Sleep') badge = 'bg-secondary';
                return `<span class="badge ${badge}">${d}</span>`;
            }},
            { data: 'Time', render: function(d) { return d + ' s'; } },
            { data: 'State', render: function(d) { return d ? d : '-'; } },
            { data: 'Info', render: function(d) { 
                if(!d) return '-';
                if(d.length > 50) return `<span title="${d}">${d.substring(0,50)}...</span>`;
                return d;
            }},
            { data: null, render: function(data, type, row) {
                if (row.Command === 'Daemon' || row.Command === 'Binlog Dump') return '';
                return `
                    <button class="btn btn-sm btn-outline-danger border-0" onclick="killProcess(${row.Id})" title="Kill Process">
                        <i class="fas fa-times-circle"></i>
                    </button>
                `;
            }, className: 'text-end'}
        ],
        order: [[5, 'desc']], // Order by time descending
        pageLength: 50
    });

    $('#refreshProcesses').click(function() {
        var btn = $(this);
        var icon = btn.find('i');
        icon.addClass('fa-spin');
        table.ajax.reload(function() {
            icon.removeClass('fa-spin');
        }, false);
    });
    
    // Auto refresh every 10 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 10000);
});

function killProcess(id) {
    Swal.fire({
        title: 'Kill process ' + id + '?',
        text: "Are you sure you want to terminate this query?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, kill it',
        background: 'var(--card-bg)',
        color: 'var(--text-primary)'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(window.BASE_URL + '/api/monitor/kill', { id: id }, function(res) {
                var json = typeof res === 'string' ? JSON.parse(res) : res;
                if (json.success) {
                    notify('success', json.message);
                    $('#processTable').DataTable().ajax.reload(null, false);
                } else {
                    notify('error', json.message);
                }
            });
        }
    });
}
</script>
