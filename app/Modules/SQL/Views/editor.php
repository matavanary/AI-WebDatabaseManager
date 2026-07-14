<div class="d-flex flex-column h-100 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-white"><i class="fas fa-terminal text-primary me-2"></i>SQL Editor</h4>
        <div>
            <div class="dropdown d-inline-block me-2">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-magic me-1"></i> Presets
                </button>
                <ul class="dropdown-menu dropdown-menu-dark shadow">
                    <li><a class="dropdown-item" href="#" onclick="insertPreset('SELECT * FROM [table] WHERE 1=1')"><i class="fas fa-search me-2 text-info"></i>SELECT</a></li>
                    <li><a class="dropdown-item" href="#" onclick="insertPreset('INSERT INTO [table] (col1, col2) VALUES (val1, val2)')"><i class="fas fa-plus me-2 text-success"></i>INSERT</a></li>
                    <li><a class="dropdown-item" href="#" onclick="insertPreset('UPDATE [table] SET col1=val1 WHERE id=1')"><i class="fas fa-edit me-2 text-warning"></i>UPDATE</a></li>
                    <li><a class="dropdown-item" href="#" onclick="insertPreset('DELETE FROM [table] WHERE id=1')"><i class="fas fa-trash me-2 text-danger"></i>DELETE</a></li>
                </ul>
            </div>
            <select class="form-select form-select-sm d-inline-block w-auto bg-dark text-white border-secondary me-2" id="db-selector">
                <option value="">-- Select Database --</option>
                <!-- Populate via JS -->
            </select>
            <button class="btn btn-primary btn-sm px-4 fw-bold" id="run-btn">
                <i class="fas fa-play me-1"></i> Run
            </button>
        </div>
    </div>

    <!-- Monaco Editor Container -->
    <div class="card border-0 shadow-sm mb-4" style="height: 300px; border-radius: 8px; overflow: hidden; background: #1e1e1e;">
        <div id="monaco-container" style="width: 100%; height: 100%;"></div>
    </div>

    <!-- SQL Result Container -->
    <div class="card border-0 shadow-sm flex-fill d-flex flex-column" style="background: var(--card-bg);">
        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 text-white">Result</h6>
            <div class="text-secondary small" id="result-stats">
                Ready
            </div>
        </div>
        <div class="card-body p-0 position-relative flex-fill" style="overflow: auto;">
            <div id="result-overlay" class="position-absolute w-100 h-100 d-none align-items-center justify-content-center" style="background: rgba(30, 33, 48, 0.8); z-index: 10;">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            
            <div id="result-error" class="alert alert-danger m-3 d-none"></div>
            
            <div id="result-table-container" class="w-100 h-100 p-3 d-none">
                <table id="result-table" class="table table-hover table-striped w-100 nowrap">
                    <thead id="result-thead"></thead>
                    <tbody id="result-tbody"></tbody>
                </table>
            </div>

            <div id="result-message" class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary d-none">
                <div class="text-center">
                    <i class="fas fa-check-circle fs-2 text-success mb-2"></i>
                    <h5 id="result-message-text">Query executed successfully</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RequireJS for Monaco Editor -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/require.js/2.3.6/require.min.js"></script>
<script>
    var editor;
    var resultTable;

    var dbSchema = {};
    var schemaCompletionProvider = null;

    $(document).ready(function() {
        // Load databases for dropdown
        $.get(window.BASE_URL + '/api/explorer/databases', function(data) {
            var select = $('#db-selector');
            data.forEach(function(db) {
                if (db.type === 'database') {
                    select.append($('<option>', {
                        value: db.text,
                        text: db.text
                    }));
                }
            });
        });

        $('#db-selector').change(function() {
            var dbName = $(this).val();
            if (!dbName) return;
            
            $.get(window.BASE_URL + '/api/schema/full?db=' + encodeURIComponent(dbName), function(res) {
                var json = typeof res === 'string' ? JSON.parse(res) : res;
                if (json.success) {
                    dbSchema = json.schema;
                    registerAutocomplete();
                    notify('success', 'Database schema loaded for autocomplete.');
                }
            });
        });

        // Initialize Monaco Editor
        require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
        require(['vs/editor/editor.main'], function() {
            editor = monaco.editor.create(document.getElementById('monaco-container'), {
                value: "-- Write your SQL query here\n",
                language: 'sql',
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: { enabled: false },
                fontSize: 14,
                fontFamily: "'Fira Code', Consolas, monospace",
                scrollBeyondLastLine: false
            });

            // Add shortcut Ctrl+Enter or Cmd+Enter to run
            editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, function() {
                runQuery();
            });
        });

        $('#run-btn').click(function() {
            runQuery();
        });
    });

    function registerAutocomplete() {
        require(['vs/editor/editor.main'], function() {
            if (schemaCompletionProvider) {
                schemaCompletionProvider.dispose();
            }
            
            schemaCompletionProvider = monaco.languages.registerCompletionItemProvider('sql', {
                triggerCharacters: [' ', '.'],
                provideCompletionItems: function(model, position) {
                    var word = model.getWordUntilPosition(position);
                    var range = {
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: word.startColumn,
                        endColumn: word.endColumn
                    };
                    
                    var suggestions = [];
                    
                    // Add Tables and Columns
                    for (var tableName in dbSchema) {
                        suggestions.push({
                            label: tableName,
                            kind: monaco.languages.CompletionItemKind.Class,
                            insertText: tableName,
                            range: range,
                            detail: 'Table'
                        });
                        
                        dbSchema[tableName].forEach(function(col) {
                            suggestions.push({
                                label: col,
                                kind: monaco.languages.CompletionItemKind.Field,
                                insertText: col,
                                range: range,
                                detail: 'Column (' + tableName + ')'
                            });
                        });
                    }
                    
                    return { suggestions: suggestions };
                }
            });
        });
    }

    function insertPreset(sql) {
        if (!editor) return;
        var position = editor.getPosition();
        editor.executeEdits("preset", [{
            range: new monaco.Range(position.lineNumber, position.column, position.lineNumber, position.column),
            text: sql + "\n"
        }]);
        editor.focus();
    }

    function runQuery() {
        if (!editor) return;

        // Get selected text or full text
        var selection = editor.getSelection();
        var query = editor.getModel().getValueInRange(selection);
        if (!query.trim()) {
            query = editor.getValue();
        }

        if (!query.trim()) {
            notify('warning', 'Please enter a query to execute');
            return;
        }

        var dbName = $('#db-selector').val();
        
        // Prevent running queries without selecting a DB to avoid accidentally running against the default system DB
        if (!dbName) {
            // Allow generic queries like SHOW DATABASES even without selection
            var upperQuery = query.toUpperCase();
            if (!upperQuery.includes('SHOW DATABASES') && !upperQuery.includes('CREATE DATABASE')) {
                notify('warning', 'Please select a database from the dropdown first.');
                return;
            }
        }

        // UI updates
        $('#result-overlay').removeClass('d-none').addClass('d-flex');
        $('#result-error, #result-table-container, #result-message').addClass('d-none');
        $('#result-stats').text('Executing...');

        $.ajax({
            url: window.BASE_URL + '/api/sql/execute',
            method: 'POST',
            data: {
                query: query,
                db: dbName
            },
            success: function(response) {
                $('#result-overlay').removeClass('d-flex').addClass('d-none');
                
                if (response.success) {
                    var statsText = `Execution: ${response.executionTime} ms | Affected rows: ${response.affectedRows}`;
                    $('#result-stats').html(`<span class="text-success"><i class="fas fa-check"></i> Success</span> &bull; ${statsText}`);
                    
                    if (response.type === 'select' && response.columns && response.columns.length > 0) {
                        // Show table
                        renderResultTable(response.columns, response.data);
                        $('#result-table-container').removeClass('d-none');
                    } else {
                        // Show success message
                        $('#result-message-text').text(`Query executed successfully. Affected rows: ${response.affectedRows}`);
                        $('#result-message').removeClass('d-none').addClass('d-flex');
                    }
                } else {
                    $('#result-stats').html(`<span class="text-danger"><i class="fas fa-times"></i> Error</span>`);
                    $('#result-error').text(`Error ${response.code || ''}: ${response.error}`).removeClass('d-none');
                }
            },
            error: function(xhr) {
                $('#result-overlay').removeClass('d-flex').addClass('d-none');
                $('#result-stats').html(`<span class="text-danger"><i class="fas fa-times"></i> Error</span>`);
                $('#result-error').text('An unexpected error occurred during execution.').removeClass('d-none');
            }
        });
    }

    function renderResultTable(columns, data) {
        if ($.fn.DataTable.isDataTable('#result-table')) {
            $('#result-table').DataTable().destroy();
            $('#result-thead').empty();
            $('#result-tbody').empty();
        }

        // Build header
        var tr = $('<tr>');
        var dataTableColumns = [];
        columns.forEach(function(col) {
            tr.append($('<th>').text(col));
            dataTableColumns.push({ 
                data: col,
                render: function(d) {
                    if (d === null) return '<span class="text-secondary font-italic">NULL</span>';
                    if (typeof d === 'object') return JSON.stringify(d); // for JSON columns
                    // Truncate long text
                    if (typeof d === 'string' && d.length > 100) {
                        return $('<div>').text(d.substring(0, 100) + '...').html();
                    }
                    return $('<div>').text(d).html();
                },
                defaultContent: ''
            });
        });
        $('#result-thead').html(tr);

        // Init DataTable
        resultTable = $('#result-table').DataTable({
            data: data,
            columns: dataTableColumns,
            scrollX: true,
            scrollY: 'calc(100vh - 550px)',
            scrollCollapse: true,
            pageLength: 50,
            dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Filter results..."
            }
        });
    }
</script>

<style>
/* Adjust monaco editor context menu z-index to stay inside the page */
.monaco-menu-container {
    z-index: 1050 !important;
}
</style>
