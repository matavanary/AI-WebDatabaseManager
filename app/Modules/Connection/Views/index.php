<div class="p-4 w-100 h-100 d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-white mb-0"><i class="fas fa-network-wired text-primary me-2"></i>Connection Manager</h4>
            <p class="text-secondary mb-0">Manage your remote MySQL database connections.</p>
        </div>
        <button class="btn btn-primary fw-bold" onclick="$('#addConnModal').modal('show')">
            <i class="fas fa-plus me-1"></i> Add Connection
        </button>
    </div>

    <div class="card border-0 shadow-sm flex-fill" style="background: var(--card-bg);">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover table-striped w-100" id="connTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Username</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $conn): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-white"><i class="fas fa-server me-2 text-info"></i><?= htmlspecialchars($conn['name']) ?></span>
                                <?php if (\App\Core\Session::get('active_connection_id') == $conn['id']): ?>
                                    <span class="badge bg-success ms-2">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((isset($conn['driver']) ? $conn['driver'] : 'mysql') === 'sqlsrv'): ?>
                                    <span class="badge bg-primary">SQL Server</span>
                                <?php else: ?>
                                    <span class="badge bg-info">MySQL</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($conn['host']) ?></td>
                            <td><?= htmlspecialchars($conn['port']) ?></td>
                            <td><?= htmlspecialchars($conn['username']) ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($conn['created_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= \App\Core\Application::asset('connection/switch?id=' . $conn['id']) ?>" class="btn btn-sm btn-outline-success border-0" title="Connect">
                                    <i class="fas fa-plug"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-warning border-0" onclick="editConn(<?= $conn['id'] ?>, '<?= htmlspecialchars(addslashes($conn['name'])) ?>', '<?= isset($conn['driver']) ? $conn['driver'] : 'mysql' ?>', '<?= htmlspecialchars(addslashes($conn['host'])) ?>', '<?= htmlspecialchars($conn['port']) ?>', '<?= htmlspecialchars(addslashes($conn['username'])) ?>')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteConn(<?= $conn['id'] ?>, '<?= htmlspecialchars(addslashes($conn['name'])) ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Connection Modal -->
<div class="modal fade" id="editConnModal" tabindex="-1" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--card-bg); border-color: var(--border-color);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white">Edit Connection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editConnForm">
                <input type="hidden" name="id" id="editConnId">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label class="form-label text-secondary">Connection Name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="name" id="editConnName" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-secondary">Database Type</label>
                            <select class="form-select bg-dark text-white border-secondary" name="driver" id="editConnDriver" required>
                                <option value="mysql">MySQL / MariaDB</option>
                                <?php if (in_array('sqlsrv', PDO::getAvailableDrivers())): ?>
                                <option value="sqlsrv">SQL Server (MSSQL)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label text-secondary">Host / IP</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="host" id="editConnHost" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary">Port</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="port" id="editConnPort" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Username</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="username" id="editConnUsername" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Password</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="password" placeholder="Leave blank to keep current">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-info" id="testEditConnBtn">
                        <i class="fas fa-plug me-1"></i> Test Connection
                    </button>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateConnBtn">Update Connection</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Connection Modal -->
<div class="modal fade" id="addConnModal" tabindex="-1" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--card-bg); border-color: var(--border-color);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white">Add New Connection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addConnForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label class="form-label text-secondary">Connection Name</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="name" placeholder="e.g. Production DB" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label text-secondary">Database Type</label>
                            <select class="form-select bg-dark text-white border-secondary" name="driver" required>
                                <option value="mysql" selected>MySQL / MariaDB</option>
                                <?php if (in_array('sqlsrv', PDO::getAvailableDrivers())): ?>
                                <option value="sqlsrv">SQL Server (MSSQL)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label text-secondary">Host / IP</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="host" placeholder="localhost or 192.168.1.100" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary">Port</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="port" value="3306" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Username</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Password</label>
                            <input type="password" class="form-control bg-dark text-white border-secondary" name="password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-info" id="testConnBtn">
                        <i class="fas fa-plug me-1"></i> Test Connection
                    </button>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveConnBtn">Save Connection</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#connTable').DataTable({
        language: { search: "_INPUT_", searchPlaceholder: "Search connections..." }
    });

    $('select[name="driver"]').change(function() {
        var driver = $(this).val();
        var portInput = $('input[name="port"]');
        if (driver === 'mysql' && (portInput.val() === '1433' || portInput.val() === '')) {
            portInput.val('3306');
        } else if (driver === 'sqlsrv' && (portInput.val() === '3306' || portInput.val() === '')) {
            portInput.val('1433');
        }
    });

    $('#testConnBtn').click(function() {
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testing...');
        
        $.post(window.BASE_URL + '/api/connection/test', $('#addConnForm').serialize(), function(res) {
            var json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.success) {
                notify('success', json.message);
            } else {
                notify('error', json.message);
            }
        }).fail(function() {
            notify('error', 'Request failed.');
        }).always(function() {
            btn.prop('disabled', false).html(originalText);
        });
    });

    $('#addConnForm').submit(function(e) {
        e.preventDefault();
        var btn = $('#saveConnBtn');
        btn.prop('disabled', true);
        
        $.post(window.BASE_URL + '/api/connection/create', $(this).serialize(), function(res) {
            var json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.success) {
                notify('success', json.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                notify('error', json.message);
                btn.prop('disabled', false);
            }
        }).fail(function() {
            notify('error', 'Request failed.');
            btn.prop('disabled', false);
        });
    });

    $('#editConnForm').submit(function(e) {
        e.preventDefault();
        var btn = $('#updateConnBtn');
        btn.prop('disabled', true);
        
        $.post(window.BASE_URL + '/api/connection/update', $(this).serialize(), function(res) {
            var json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.success) {
                notify('success', json.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                notify('error', json.message);
                btn.prop('disabled', false);
            }
        }).fail(function() {
            notify('error', 'Request failed.');
            btn.prop('disabled', false);
        });
    });

    $('#testEditConnBtn').click(function() {
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testing...');
        
        $.post(window.BASE_URL + '/api/connection/test', $('#editConnForm').serialize(), function(res) {
            var json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.success) {
                notify('success', json.message);
            } else {
                notify('error', json.message);
            }
        }).fail(function() {
            notify('error', 'Request failed.');
        }).always(function() {
            btn.prop('disabled', false).html(originalText);
        });
    });
});

function editConn(id, name, driver, host, port, username) {
    $('#editConnId').val(id);
    $('#editConnName').val(name);
    $('#editConnDriver').val(driver);
    $('#editConnHost').val(host);
    $('#editConnPort').val(port);
    $('#editConnUsername').val(username);
    $('#editConnForm').find('input[name="password"]').val('');
    $('#editConnModal').modal('show');
}

function deleteConn(id, name) {
    Swal.fire({
        title: 'Delete ' + name + '?',
        text: "Are you sure you want to remove this connection?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        background: 'var(--card-bg)',
        color: 'var(--text-primary)'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(window.BASE_URL + '/api/connection/delete', { id: id }, function(res) {
                var json = typeof res === 'string' ? JSON.parse(res) : res;
                if (json.success) {
                    notify('success', json.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify('error', json.message);
                }
            });
        }
    });
}
</script>
