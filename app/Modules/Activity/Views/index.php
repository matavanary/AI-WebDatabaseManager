<div class="p-4 w-100 h-100 d-flex flex-column">
    <div class="mb-4">
        <h4 class="text-white"><i class="fas fa-history text-primary me-2"></i>Activity Log</h4>
        <p class="text-secondary">View user actions and system events.</p>
    </div>

    <div class="card border-0 shadow-sm flex-fill" style="background: var(--card-bg);">
        <div class="card-body p-0">
            <div class="table-responsive h-100 p-3">
                <table id="logTable" class="table table-hover table-striped w-100 nowrap">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Target DB</th>
                            <th>Target Table</th>
                            <th>IP Address</th>
                            <th>Details</th>
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
    $('#logTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: window.BASE_URL + '/api/logs/data',
            type: 'GET'
        },
        columns: [
            { 
                data: 'created_at',
                render: function(data) {
                    return `<span class="text-secondary"><i class="far fa-clock me-1"></i>${data}</span>`;
                }
            },
            { 
                data: 'username',
                render: function(data) {
                    return `<span class="fw-bold text-info"><i class="fas fa-user me-1"></i>${data}</span>`;
                }
            },
            { 
                data: 'action',
                render: function(data) {
                    let badgeClass = 'bg-secondary';
                    if (data === 'Login') badgeClass = 'bg-success';
                    else if (data === 'Logout') badgeClass = 'bg-warning text-dark';
                    else if (data === 'Execute SQL') badgeClass = 'bg-primary';
                    else if (data === 'Export Data') badgeClass = 'bg-info text-dark';
                    else if (data === 'Import Data') badgeClass = 'bg-warning text-dark';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { data: 'target_database' },
            { data: 'target_table' },
            { data: 'ip_address' },
            { 
                data: 'details',
                render: function(data) {
                    if (!data) return '';
                    if (data.length > 50) {
                        return `<span title="${data}">${data.substring(0, 50)}...</span>`;
                    }
                    return data;
                }
            }
        ],
        order: [[0, 'desc']],
        scrollX: true,
        scrollY: 'calc(100vh - 350px)',
        scrollCollapse: true,
        pageLength: 50,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search logs..."
        }
    });
});
</script>
