<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default-dark/style.min.css" />

<div class="row h-100 g-0">
    <div class="col-md-3 h-100 border-end border-secondary border-opacity-25 bg-dark">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Explorer</h6>
            <button class="btn btn-sm btn-outline-secondary border-0" id="refresh-tree">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="p-2">
            <div class="input-group input-group-sm mb-3">
                <span class="input-group-text bg-transparent border-secondary text-secondary"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control bg-transparent border-secondary text-light" id="tree-search" placeholder="Search...">
            </div>
        </div>
        <div class="tree-container p-2" style="overflow-y: auto; height: calc(100vh - 180px);">
            <div id="db-tree"></div>
        </div>
    </div>
    <div class="col-md-9 h-100 bg-dark d-flex flex-column">
        <div id="table-viewer-container" class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
            <div class="text-center">
                <i class="fas fa-table fs-1 mb-3 text-secondary opacity-50"></i>
                <h5>Select a table from the explorer</h5>
            </div>
        </div>
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
                    'name': 'default-dark',
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

<style>
/* Adjust jstree for modern dark theme */
.jstree-default-dark .jstree-wholerow-hovered {
    background: rgba(59, 130, 246, 0.1);
}
.jstree-default-dark .jstree-wholerow-clicked {
    background: rgba(59, 130, 246, 0.2);
}
.jstree-default-dark .jstree-node {
    color: var(--text-primary);
}
.content-wrapper.p-4 {
    padding: 0 !important; /* Remove padding for explorer view to fit screen */
}
</style>
