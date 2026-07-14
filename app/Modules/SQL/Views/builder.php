<div class="p-4 w-100 h-100 d-flex flex-column" style="overflow-y: auto;">
    <div class="mb-4">
        <h4 class="text-white"><i class="fas fa-magic text-primary me-2"></i>Query Builder</h4>
        <p class="text-secondary">Visually generate SQL queries without writing code.</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg);">
                <div class="card-body p-4">
                    <form id="builderForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-secondary">Database</label>
                                <select class="form-select bg-dark text-white border-secondary" id="b-db" required>
                                    <option value="">-- Select Database --</option>
                                    <?php foreach ($databases as $db): ?>
                                        <option value="<?= htmlspecialchars($db) ?>"><?= htmlspecialchars($db) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary">Table</label>
                                <select class="form-select bg-dark text-white border-secondary" id="b-table" required disabled>
                                    <option value="">-- Select Table --</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary">Action</label>
                            <select class="form-select bg-dark text-white border-secondary" id="b-action">
                                <option value="SELECT">SELECT</option>
                                <option value="INSERT">INSERT</option>
                                <option value="UPDATE">UPDATE</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>

                        <div class="mb-3" id="columns-container">
                            <label class="form-label text-secondary">Columns (Ctrl+Click for multiple)</label>
                            <select multiple class="form-select bg-dark text-white border-secondary" id="b-columns" style="height: 120px;" disabled>
                                <option value="*">* (All Columns)</option>
                            </select>
                        </div>

                        <div class="mb-4" id="conditions-container">
                            <label class="form-label text-secondary d-flex justify-content-between">
                                <span>Conditions (WHERE)</span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" id="add-condition">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </label>
                            <div id="conditions-list" class="d-flex flex-column gap-2">
                                <!-- Conditions will be appended here -->
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-bolt me-1"></i> Generate SQL
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="background: var(--card-bg); top: 20px;">
                <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-code text-info me-2"></i>Generated SQL</h5>
                </div>
                <div class="card-body p-3">
                    <div class="bg-dark p-3 rounded mb-3 border border-secondary" style="min-height: 150px; font-family: monospace;">
                        <code id="generated-sql" class="text-white">-- Your SQL will appear here</code>
                    </div>
                    
                    <button class="btn btn-outline-info w-100 mb-2" id="copy-sql" disabled>
                        <i class="fas fa-copy me-1"></i> Copy to Clipboard
                    </button>
                    
                    <a href="<?= \App\Core\Application::asset('sql') ?>" class="btn btn-outline-success w-100" id="run-sql" disabled>
                        <i class="fas fa-external-link-alt me-1"></i> Open in Editor
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var tableColumns = [];
    var dbSchema = {}; // Cache the schema for the selected database

    $('#b-db').change(function() {
        var db = $(this).val();
        var tableSelect = $('#b-table');
        
        if (!db) {
            tableSelect.empty().append('<option value="">-- Select Table --</option>').prop('disabled', true);
            $('#b-columns').prop('disabled', true);
            return;
        }

        tableSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);

        // Fetch tables for dropdown
        $.get(window.BASE_URL + '/api/explorer/tables', { db: db }, function(data) {
            tableSelect.empty().append('<option value="">-- Select Table --</option>');
            if (data && data.length > 0) {
                data.forEach(function(item) {
                    tableSelect.append($('<option>', {
                        value: item.text,
                        text: item.text
                    }));
                });
                tableSelect.prop('disabled', false);
            }
        });

        // Fetch full schema for columns
        $.get(window.BASE_URL + '/api/schema/full?db=' + encodeURIComponent(db), function(res) {
            var json = typeof res === 'string' ? JSON.parse(res) : res;
            if (json.success) {
                dbSchema = json.schema;
            } else {
                dbSchema = {};
            }
        });
    });

    $('#b-table').change(function() {
        var table = $(this).val();
        var colSelect = $('#b-columns');
        
        if (!table) {
            colSelect.prop('disabled', true);
            return;
        }

        colSelect.empty().append('<option value="*">* (All Columns)</option>').prop('disabled', true);
        tableColumns = [];

        if (dbSchema[table]) {
            dbSchema[table].forEach(function(col) {
                colSelect.append($('<option>', {
                    value: col,
                    text: col
                }));
                tableColumns.push(col);
            });
            colSelect.prop('disabled', false);
        } else {
            // Fallback if schema not loaded or table not found
            colSelect.prop('disabled', false);
        }
    });

    $('#add-condition').click(function() {
        var options = tableColumns.map(c => `<option value="${c}">${c}</option>`).join('');
        var html = `
            <div class="input-group input-group-sm condition-row">
                <select class="form-select bg-dark text-white border-secondary cond-col">
                    ${options || '<option value="id">id</option>'}
                </select>
                <select class="form-select bg-dark text-white border-secondary cond-op" style="max-width: 100px;">
                    <option value="=">=</option>
                    <option value=">">></option>
                    <option value="<"><</option>
                    <option value="LIKE">LIKE</option>
                </select>
                <input type="text" class="form-control bg-dark text-white border-secondary cond-val" placeholder="Value">
                <button type="button" class="btn btn-outline-danger btn-remove-cond">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        $('#conditions-list').append(html);
    });

    $(document).on('click', '.btn-remove-cond', function() {
        $(this).closest('.condition-row').remove();
    });

    $('#builderForm').submit(function(e) {
        e.preventDefault();
        
        var conditions = [];
        $('.condition-row').each(function() {
            var col = $(this).find('.cond-col').val();
            var op = $(this).find('.cond-op').val();
            var val = $(this).find('.cond-val').val();
            if (col && val) {
                conditions.push({ column: col, operator: op, value: val });
            }
        });

        var data = {
            action: $('#b-action').val(),
            table: $('#b-table').val(),
            columns: $('#b-columns').val() || ['*'],
            conditions: conditions
        };

        $.ajax({
            url: window.BASE_URL + '/api/builder/generate',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                var json = typeof response === 'string' ? JSON.parse(response) : response;
                if (json.success) {
                    $('#generated-sql').text(json.sql);
                    $('#copy-sql, #run-sql').prop('disabled', false);
                    
                    // Save to localstorage for editor
                    localStorage.setItem('pending_sql', json.sql);
                    localStorage.setItem('pending_db', $('#b-db').val());
                } else {
                    notify('error', json.message);
                }
            }
        });
    });

    $('#copy-sql').click(function() {
        var text = $('#generated-sql').text();
        navigator.clipboard.writeText(text).then(function() {
            notify('success', 'SQL copied to clipboard');
        });
    });
});
</script>
