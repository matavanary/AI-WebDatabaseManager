<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css">
<div class="page-container">
    <div class="page-heading"><div><h1 class="page-title">Explorer</h1><p>Browse your databases and work with table data.</p></div></div>
    <div class="explorer-workspace">
        <section class="explorer-panel" aria-label="Database explorer">
            <div class="explorer-panel-header"><h2>Databases</h2><button class="icon-button" id="refresh-tree" aria-label="Refresh databases"><i class="fas fa-rotate" aria-hidden="true"></i></button></div>
            <div class="p-3 pb-0"><label for="tree-search" class="visually-hidden">Find a database or table</label><input type="search" class="form-control" id="tree-search" placeholder="Find a database or table..."></div>
            <div class="tree-container"><div id="db-tree"></div></div>
        </section>
        <section id="table-viewer-container" aria-label="Table data">
            <div class="empty-state"><i class="fas fa-table" aria-hidden="true"></i><strong>Select a table to get started</strong><p>Choose a database and table from the explorer.</p></div>
        </section>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initTree();

    $('#tree-search').on('keyup', function () {
        var v = $('#tree-search').val();
        $('#db-tree').jstree(true).search(v);
    });

    $('#refresh-tree').on('click', function() {
        $('#db-tree').jstree('refresh');
    });

    function initTree() {
        $('#db-tree').on('loaded.jstree refresh.jstree', function() {
            $('#tree-empty').remove();
            if (!$('#db-tree').jstree(true).get_json().length) {
                $('#db-tree').after('<p class="text-secondary small p-2" id="tree-empty">No databases available. Select a server connection from the menu above.</p>');
            }
        });
        $('#db-tree').jstree({
            'core': {
                'data': {
                    'url': function (node) {
                        return node.id === '#' ? window.BASE_URL + '/api/explorer/databases' : window.BASE_URL + '/api/explorer/tables';
                    },
                    'data': function (node) {
                        if (node.id !== '#') {
                            return { 'db': node.a_attr['data-db'] };
                        }
                        return {};
                    }
                },
                'themes': {
                    'name': 'default',
                    'dots': true,
                    'icons': true
                }
            },
            'plugins': ['search', 'types', 'wholerow'],
            'search': {
                'show_only_matches': true,
                'show_only_matches_children': true
            },
            'types': {
                'database': {
                    'icon': 'fas fa-database text-warning'
                },
                'table': {
                    'icon': 'fas fa-table text-primary'
                }
            }
        });

        $('#db-tree').on('select_node.jstree', function (e, data) {
            if (data.node.type === 'table') {
                const db = data.node.a_attr['data-db'];
                const table = data.node.a_attr['data-table'];
                loadTableViewer(db, table);
            }
        });
    }

    function loadTableViewer(db, table) {
        $('#table-viewer-container').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');

        // We will implement this in the Table Viewer Module
        $.get(window.BASE_URL + '/table/view', { db: db, table: table }, function(response) {
            $('#table-viewer-container').html(response);
        }).fail(function(err) {
            $('#table-viewer-container').html('<div class="alert alert-danger w-75 m-auto">Failed to load table viewer.</div>');
            notify('error', 'Failed to load table');
        });
    }
});
</script>
