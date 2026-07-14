<div class="h-100 d-flex flex-column w-100 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-white"><i class="fas fa-table text-primary me-2"></i><?= htmlspecialchars($tableName) ?></h4>
            <small class="text-secondary"><?= htmlspecialchars($dbName) ?></small>
        </div>
        <div>
            <button class="btn btn-primary" onclick="showAddModal()">
                <i class="fas fa-plus me-1"></i> Add Row
            </button>
            <button class="btn btn-outline-secondary ms-2" onclick="table.ajax.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm flex-fill" style="background: var(--card-bg);">
        <div class="card-body p-0">
            <div class="table-responsive h-100 p-3">
                <table id="dataTable" class="table table-hover table-striped w-100 nowrap">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Actions</th>
                            <?php foreach ($columns as $col): ?>
                                <th><?= htmlspecialchars($col['Field']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Simple Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--card-bg); border-color: var(--border-color);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white">Add Row</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <!-- Note: For Phase 1 we use basic input texts. Ideally, these should vary by data type -->
                    <?php foreach ($columns as $col): 
                        if ($col['Extra'] === 'auto_increment' || strpos(strtolower($col['Extra']), 'identity') !== false) continue;
                    ?>
                        <div class="mb-3">
                            <label class="form-label text-secondary"><?= htmlspecialchars($col['Field']) ?> <small>(<?= htmlspecialchars($col['Type']) ?>)</small></label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="<?= htmlspecialchars($col['Field']) ?>">
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveRow()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true" data-bs-theme="dark">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--card-bg); border-color: var(--border-color);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white">Edit Row</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="pk_value" id="edit_pk_value">
                    <?php foreach ($columns as $col): 
                        $isPk = ($col['Field'] === $primaryKey);
                    ?>
                        <div class="mb-3">
                            <label class="form-label text-secondary"><?= htmlspecialchars($col['Field']) ?> <small>(<?= htmlspecialchars($col['Type']) ?>)</small></label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="<?= htmlspecialchars($col['Field']) ?>" <?= $isPk ? 'readonly' : '' ?>>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEdit()">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
var table;
$(document).ready(function() {
    // Check if DataTable already exists and destroy it if it does
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable().destroy();
    }

    table = $('#dataTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: window.BASE_URL + '/api/table/data',
            type: 'POST',
            data: function (d) {
                d.db = '<?= $dbName ?>';
                d.table = '<?= $tableName ?>';
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    const pkValue = row['<?= $primaryKey ?>'];
                    return `
                        <button class="btn btn-sm btn-outline-info border-0 me-1" onclick="editRow('${pkValue}', this)"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteRow('${pkValue}')"><i class="fas fa-trash"></i></button>
                    `;
                }
            },
            <?php foreach ($columns as $col): ?>
            { 
                data: '<?= htmlspecialchars($col['Field']) ?>',
                render: function(data, type, row) {
                    if (data === null) return '<span class="text-secondary font-italic">NULL</span>';
                    // Truncate long text
                    if (typeof data === 'string' && data.length > 50) {
                        return $('<div>').text(data.substring(0, 50) + '...').html();
                    }
                    return $('<div>').text(data).html();
                }
            },
            <?php endforeach; ?>
        ],
        scrollX: true,
        scrollY: 'calc(100vh - 350px)',
        scrollCollapse: true,
        pageLength: 50,
        dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records..."
        }
    });
});

function showAddModal() {
    $('#addForm')[0].reset();
    var modal = new bootstrap.Modal(document.getElementById('addModal'));
    modal.show();
}

function saveRow() {
    var formData = $('#addForm').serializeArray();
    var data = {
        db: '<?= $dbName ?>',
        table: '<?= $tableName ?>',
        data: {}
    };
    
    $.each(formData, function() {
        data.data[this.name] = this.value;
    });

    $.post(window.BASE_URL + '/api/table/insert', data, function(res) {
        var json = typeof res === 'string' ? JSON.parse(res) : res;
        if (json.success) {
            notify('success', 'Record inserted successfully');
            $('#addModal').modal('hide');
            table.ajax.reload(null, false);
        } else {
            notify('error', json.message || 'Error inserting record');
        }
    }).fail(function() {
        notify('error', 'Request failed');
    });
}

function editRow(id, btn) {
    var tr = $(btn).closest('tr');
    var rowData = table.row(tr).data();
    
    $('#editForm')[0].reset();
    $('#edit_pk_value').val(id);
    
    for (var key in rowData) {
        if (rowData.hasOwnProperty(key)) {
            var input = $('#editForm').find('[name="' + key + '"]');
            if (input.length) {
                input.val(rowData[key]);
            }
        }
    }
    
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function saveEdit() {
    var formData = $('#editForm').serializeArray();
    var data = {
        db: '<?= $dbName ?>',
        table: '<?= $tableName ?>',
        pk: '<?= $primaryKey ?>',
        pk_value: $('#edit_pk_value').val(),
        data: {}
    };
    
    $.each(formData, function() {
        if (this.name !== 'pk_value' && this.name !== '<?= $primaryKey ?>') {
            data.data[this.name] = this.value;
        }
    });

    $.post(window.BASE_URL + '/api/table/update', data, function(res) {
        var json = typeof res === 'string' ? JSON.parse(res) : res;
        if (json.success) {
            notify('success', 'Record updated successfully');
            $('#editModal').modal('hide');
            table.ajax.reload(null, false);
        } else {
            notify('error', json.message || 'Error updating record');
        }
    }).fail(function(xhr) {
        notify('error', xhr.responseJSON ? xhr.responseJSON.message : 'Request failed');
    });
}

function deleteRow(id) {
    Swal.fire({
        title: 'Are you sure?',
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
            $.post(window.BASE_URL + '/api/table/delete', {
                db: '<?= $dbName ?>',
                table: '<?= $tableName ?>',
                pk: '<?= $primaryKey ?>',
                id: id
            }, function(res) {
                var json = typeof res === 'string' ? JSON.parse(res) : res;
                if (json.success) {
                    notify('success', 'Record deleted successfully');
                    table.ajax.reload(null, false);
                } else {
                    notify('error', json.message || 'Error deleting record');
                }
            }).fail(function() {
                notify('error', 'Request failed');
            });
        }
    });
}
</script>
