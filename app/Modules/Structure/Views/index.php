<div class="p-4 w-100 h-100 d-flex flex-column" style="overflow-y: auto;">
    <div class="mb-4">
        <h4 class="text-white"><i class="fas fa-table text-primary me-2"></i>Structure Management</h4>
        <p class="text-secondary">Visually create new tables without writing SQL.</p>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg);">
        <div class="card-body p-4">
            <form id="createTableForm">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Target Database</label>
                        <select class="form-select bg-dark text-white border-secondary" id="s-db" required>
                            <option value="">-- Select Database --</option>
                            <?php foreach ($databases as $db): ?>
                                <option value="<?= htmlspecialchars($db) ?>"><?= htmlspecialchars($db) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Table Name</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" id="s-table" placeholder="e.g. users" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white mb-0">Columns</h5>
                    <button type="button" class="btn btn-sm btn-outline-info" id="addColumnBtn">
                        <i class="fas fa-plus"></i> Add Column
                    </button>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered border-secondary" id="columnsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Length/Values</th>
                                <th class="text-center" title="Primary Key">PK</th>
                                <th class="text-center" title="Auto Increment">A_I</th>
                                <th class="text-center" title="Nullable">Null</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="columnsBody">
                            <!-- Columns will be added here -->
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-save me-1"></i> Create Table
                </button>
            </form>
        </div>
    </div>
</div>

<template id="columnTemplate">
    <tr class="column-row">
        <td><input type="text" class="form-control form-control-sm bg-dark text-white border-secondary col-name" placeholder="Column Name" required></td>
        <td>
            <select class="form-select form-select-sm bg-dark text-white border-secondary col-type">
                <option value="INT">INT</option>
                <option value="VARCHAR">VARCHAR</option>
                <option value="TEXT">TEXT</option>
                <option value="DATE">DATE</option>
                <option value="DATETIME">DATETIME</option>
                <option value="TIMESTAMP">TIMESTAMP</option>
                <option value="BOOLEAN">BOOLEAN</option>
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm bg-dark text-white border-secondary col-length"></td>
        <td class="text-center align-middle"><input class="form-check-input col-pk" type="checkbox"></td>
        <td class="text-center align-middle"><input class="form-check-input col-ai" type="checkbox"></td>
        <td class="text-center align-middle"><input class="form-check-input col-null" type="checkbox"></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-col border-0"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
</template>

<script>
$(document).ready(function() {
    function addColumnRow() {
        var tmpl = $('#columnTemplate').html();
        $('#columnsBody').append(tmpl);
    }

    // Add first column by default (usually id)
    addColumnRow();
    
    // Setup first column as primary key AI
    setTimeout(function() {
        var firstRow = $('.column-row').first();
        firstRow.find('.col-name').val('id');
        firstRow.find('.col-type').val('INT');
        firstRow.find('.col-pk').prop('checked', true);
        firstRow.find('.col-ai').prop('checked', true);
    }, 100);

    $('#addColumnBtn').click(addColumnRow);

    $(document).on('click', '.btn-remove-col', function() {
        if ($('.column-row').length > 1) {
            $(this).closest('tr').remove();
        }
    });

    $('#createTableForm').submit(function(e) {
        e.preventDefault();
        
        var columns = [];
        $('.column-row').each(function() {
            columns.push({
                name: $(this).find('.col-name').val(),
                type: $(this).find('.col-type').val(),
                length: $(this).find('.col-length').val(),
                pk: $(this).find('.col-pk').is(':checked'),
                ai: $(this).find('.col-ai').is(':checked'),
                nullable: $(this).find('.col-null').is(':checked')
            });
        });

        var data = {
            db: $('#s-db').val(),
            table: $('#s-table').val(),
            columns: columns
        };

        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating...');

        $.ajax({
            url: window.BASE_URL + '/api/structure/create',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                var json = typeof response === 'string' ? JSON.parse(response) : response;
                if (json.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: json.message,
                        background: 'var(--card-bg)',
                        color: 'var(--text-primary)'
                    }).then(() => {
                        window.location.href = '<?= \App\Core\Application::asset('explorer') ?>';
                    });
                } else {
                    notify('error', json.message);
                }
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
