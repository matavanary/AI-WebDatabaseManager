<div class="p-4 w-100 h-100 d-flex flex-column" style="overflow-y: auto;">
    <div class="mb-4">
        <h4 class="text-white"><i class="fas fa-search text-primary me-2"></i>Global Search</h4>
        <p class="text-secondary">Search for data across all tables in a database simultaneously.</p>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg);">
        <div class="card-body p-4">
            <form id="searchForm">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label text-secondary">Database</label>
                        <select class="form-select bg-dark text-white border-secondary" id="s-db" name="db" required>
                            <option value="">-- Select Database --</option>
                            <?php foreach ($databases as $db): ?>
                                <option value="<?= htmlspecialchars($db) ?>"><?= htmlspecialchars($db) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-secondary">Keyword</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" name="keyword" placeholder="What are you looking for?" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="searchBtn">
                            <i class="fas fa-search me-1"></i> Search Everywhere
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="search-stats" class="text-secondary mb-3 d-none">
        Found matches in <strong id="match-tables-count" class="text-white">0</strong> tables. 
        (Searched <span id="total-tables-count">0</span> tables in <span id="exec-time">0</span> ms)
    </div>

    <div id="search-results-container">
        <!-- Results will be appended here -->
    </div>
</div>

<script>
$(document).ready(function() {
    $('#searchForm').submit(function(e) {
        e.preventDefault();
        
        var btn = $('#searchBtn');
        var originalText = btn.html();
        var container = $('#search-results-container');
        var stats = $('#search-stats');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Searching...');
        container.html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-secondary">Scanning all tables...</div></div>');
        stats.addClass('d-none');

        $.ajax({
            url: window.BASE_URL + '/api/search/execute',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                var json = typeof response === 'string' ? JSON.parse(response) : response;
                container.empty();
                
                if (json.success) {
                    $('#match-tables-count').text(json.results.length);
                    $('#total-tables-count').text(json.tablesSearched);
                    $('#exec-time').text(json.executionTime);
                    stats.removeClass('d-none');

                    if (json.results.length === 0) {
                        container.html(`
                            <div class="card border-0 shadow-sm" style="background: var(--card-bg);">
                                <div class="card-body text-center p-5">
                                    <i class="fas fa-search-minus fs-1 text-secondary mb-3 opacity-50"></i>
                                    <h5 class="text-secondary">No results found for your keyword.</h5>
                                </div>
                            </div>
                        `);
                        return;
                    }

                    json.results.forEach(function(res, index) {
                        var card = $(`
                            <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg);">
                                <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 text-white"><i class="fas fa-table text-info me-2"></i>${res.table}</h5>
                                    <span class="badge bg-primary rounded-pill">${res.count} match${res.count > 1 ? 'es' : ''}</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive p-3">
                                        <table class="table table-hover table-striped w-100" id="res-table-${index}">
                                            <thead><tr id="res-head-${index}"></tr></thead>
                                            <tbody id="res-body-${index}"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        var trHead = $('<tr>');
                        var dtCols = [];
                        res.columns.forEach(function(col) {
                            trHead.append($('<th>').text(col));
                            dtCols.push({
                                data: col,
                                render: function(d) {
                                    if (d === null) return '<span class="text-secondary font-italic">NULL</span>';
                                    if (typeof d === 'string' && d.length > 50) return $('<div>').text(d.substring(0, 50) + '...').html();
                                    return $('<div>').text(d).html();
                                }
                            });
                        });
                        card.find(`#res-head-${index}`).replaceWith(trHead);
                        container.append(card);

                        $(`#res-table-${index}`).DataTable({
                            data: res.data,
                            columns: dtCols,
                            scrollX: true,
                            pageLength: 10,
                            lengthMenu: [5, 10, 25],
                            dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                                 "<'row'<'col-sm-12'tr>>" +
                                 "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                        });
                    });
                } else {
                    notify('error', json.message);
                }
            },
            error: function() {
                container.empty();
                notify('error', 'Network error occurred during search.');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
