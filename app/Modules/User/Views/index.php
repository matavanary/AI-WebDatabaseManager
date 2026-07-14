<div class="p-4 w-100 h-100 d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-white mb-0"><i class="fas fa-users text-primary me-2"></i>User Management</h4>
            <p class="text-secondary mb-0">Manage system administrators and operators.</p>
        </div>
        <button class="btn btn-primary fw-bold" onclick="$('#addUserModal').modal('show')">
            <i class="fas fa-user-plus me-1"></i> Add User
        </button>
    </div>

    <div class="card border-0 shadow-sm flex-fill" style="background: var(--card-bg);">
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover table-striped w-100" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-white"><i class="fas fa-user me-2 text-secondary"></i><?= htmlspecialchars($user['username']) ?></span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'administrator'): ?>
                                    <span class="badge bg-danger">Administrator</span>
                                <?php else: ?>
                                    <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($user['role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary"><?= htmlspecialchars($user['created_at']) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info border-0 me-1" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['role']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != \App\Core\Session::get('user_id')): ?>
                                <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--card-bg); border-color: var(--border-color);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary">Username</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Password</label>
                        <input type="password" class="form-control bg-dark text-white border-secondary" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Role</label>
                        <select class="form-select bg-dark text-white border-secondary" name="role" required>
                            <option value="administrator">Administrator</option>
                            <option value="developer">Developer</option>
                            <option value="viewer" selected>Viewer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveUserBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--card-bg); border-color: var(--border-color);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary">Username</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="username" id="edit-username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" class="form-control bg-dark text-white border-secondary" name="password" id="edit-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Role</label>
                        <select class="form-select bg-dark text-white border-secondary" name="role" id="edit-role" required>
                            <option value="administrator">Administrator</option>
                            <option value="developer">Developer</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updateUserBtn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        language: { search: "_INPUT_", searchPlaceholder: "Search users..." }
    });

    $('#addUserForm').submit(function(e) {
        e.preventDefault();
        var btn = $('#saveUserBtn');
        btn.prop('disabled', true);
        
        $.ajax({
            url: window.BASE_URL + '/api/users/create',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                var json = typeof response === 'string' ? JSON.parse(response) : response;
                if (json.success) {
                    notify('success', json.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify('error', json.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                notify('error', 'Request failed');
                btn.prop('disabled', false);
            }
        });
    });

    $('#editUserForm').submit(function(e) {
        e.preventDefault();
        var btn = $('#updateUserBtn');
        btn.prop('disabled', true);
        
        $.ajax({
            url: window.BASE_URL + '/api/users/update',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                var json = typeof response === 'string' ? JSON.parse(response) : response;
                if (json.success) {
                    notify('success', json.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    notify('error', json.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                notify('error', 'Request failed');
                btn.prop('disabled', false);
            }
        });
    });
});

function editUser(id, username, role) {
    $('#edit-id').val(id);
    $('#edit-username').val(username);
    $('#edit-role').val(role);
    $('#edit-password').val('');
    $('#editUserModal').modal('show');
}

function deleteUser(id, username) {
    Swal.fire({
        title: 'Delete ' + username + '?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        background: 'var(--card-bg)',
        color: 'var(--text-primary)'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(window.BASE_URL + '/api/users/delete', { id: id }, function(res) {
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
