<?php
// index.php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();

$tables = getAllTables($conn);
$selectedTable = $_GET['table'] ?? '';
$columns = [];
$primaryKey = null;
$totalRows = 0;

if ($selectedTable && in_array($selectedTable, $tables)) {
    $columns = getTableColumns($conn, $selectedTable);
    $primaryKey = getPrimaryKey($conn, $selectedTable);
    
    // Get total row count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM `$selectedTable`");
    $countStmt->execute();
    $totalRows = $countStmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-size: 13px;
        }
        .sidebar { 
            min-height: 100vh; 
            background-color: #343a40; 
            color: white; 
            position: sticky; 
            top: 0;
            overflow-y: auto;
            max-height: 100vh;
        }
        .sidebar .nav-link { 
            color: rgba(255,255,255,0.8); 
            padding: 8px 15px;
            font-size: 13px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { 
            color: white; 
            background-color: rgba(255,255,255,0.1); 
        }
        
        /* FIXED HEIGHT SCROLLABLE TABLE */
        .table-wrapper {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid #dee2e6;
            position: relative;
        }
        
        .table-wrapper table {
            margin-bottom: 0;
            font-size: 12px;
        }
        
        /* STICKY HEADER */
        .table-wrapper thead th {
            position: sticky;
            top: 0;
            background-color: #495057;
            color: white;
            z-index: 10;
            white-space: nowrap;
            font-size: 12px;
            padding: 8px;
        }
        
        .table-wrapper tbody td {
            padding: 6px 8px;
            font-size: 12px;
        }
        
        .data-cell { 
            max-width: 200px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            white-space: nowrap; 
        }
        
        .btn-action { 
            padding: 0.2rem 0.4rem; 
            font-size: 11px; 
            margin: 1px;
        }
        
        .card-header h5 {
            font-size: 16px;
            margin: 0;
        }
        
        .loading-indicator {
            text-align: center;
            padding: 10px;
            display: none;
        }
        
        .loading-indicator.active {
            display: block;
        }
        
        h3 {
            font-size: 20px;
        }
        
        .btn-sm {
            font-size: 12px;
            padding: 4px 8px;
        }
        .toggle-header {
            cursor: pointer;
            background: #1e88e5;
            color: white;
            padding: 8px;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toggle-content {
            display: none;
            margin-top: 10px;
        }

        .rotate {
            transform: rotate(90deg);
        }
       
    </style>
</head>
<body>




    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h5 class="text-white" style="font-size: 16px;">Database Manager</h5>
                    <p class="text-white small mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <a href="logout.php" class="btn btn-sm btn-outline-light mt-2">Logout</a>
                    <a href="settings.php" class="btn btn-sm btn-outline-light mt-2" onclick="openSettingsModal()">Change Username / Password</a>
                </div>
                <hr class="text-white">
                <div class="px-3 mb-3">
                    <button class="btn btn-success w-100 mb-2 btn-sm" data-bs-toggle="modal" data-bs-target="#createTableModal">
                        <i class="bi bi-plus-circle"></i> Create Table
                    </button>
                    <a href="upload.php" class="btn btn-primary w-100 mb-2 btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Upload Excel
                    </a>
                    <a href="paste_data.php" class="btn btn-info w-100 btn-sm">
                        <i class="bi bi-clipboard-data"></i> Paste Data
                    </a>
                </div>
                <div class="px-3">
                    <h6 class="text-white-50 mb-2" style="font-size: 13px;">Tables (<?php echo count($tables); ?>)</h6>
                    <!-- Search Box for Tables -->
                    <div class="mb-3">
                        <div class="position-relative">
                            <input type="text" id="tableSearch" placeholder="Search tables..." class="form-control form-control-sm ps-5" onkeyup="filterTables()">
                            <i class="bi bi-search position-absolute" style="left: 10px; top: 7px; color: #adb5bd;"></i>
                        </div>
                    </div>
                    <nav class="nav flex-column" id="tablesNav">
                        <?php foreach ($tables as $table): ?>
                            <a href="?table=<?php echo urlencode($table); ?>" 
                               class="nav-link <?php echo $selectedTable === $table ? 'active' : ''; ?>" data-table="<?php echo htmlspecialchars($table); ?>">
                                <i class="bi bi-table"></i> <?php echo htmlspecialchars($table); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                <script>
                    function filterTables() {
                        const searchInput = document.getElementById('tableSearch');
                        const filter = searchInput.value.toLowerCase();
                        const navLinks = document.querySelectorAll('#tablesNav .nav-link');
                        
                        navLinks.forEach(link => {
                            const tableName = link.getAttribute('data-table');
                            if (tableName.toLowerCase().indexOf(filter) > -1) {
                                link.style.display = '';
                            } else {
                                link.style.display = 'none';
                            }
                        });
                    }
                </script>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-4 py-3">
                <?php if ($selectedTable): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3><i class="bi bi-table"></i> <?php echo htmlspecialchars($selectedTable); ?></h3>
                        <div>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addColumnModal">
                                <i class="bi bi-plus"></i> Add Column
                            </button>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addRowModal">
                                <i class="bi bi-plus"></i> Add Row
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="renameTable('<?php echo htmlspecialchars($selectedTable); ?>')">
                                <i class="bi bi-pencil"></i> Rename
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteTable('<?php echo htmlspecialchars($selectedTable); ?>')">
                                <i class="bi bi-trash"></i> Delete Table
                            </button>
                        </div>
                    </div>

                    <!-- Table Structure -->
                    <!-- Table Structure Card with Expand/Collapse -->
<div class="card mb-4">

    <!-- COLLAPSIBLE HEADER -->
    <div class="card-header d-flex justify-content-between align-items-center"
         style="cursor: pointer;" onclick="toggleTableStructure()">

        <h5 class="mb-0">
            <i class="bi bi-table"></i> Table Structure
        </h5>

        <i id="ts-arrow" class="bi bi-chevron-right" style="transition: 0.3s;"></i>
    </div>

    <!-- COLLAPSIBLE CONTENT -->
    <div id="tableStructureBodyDiv" class="card-body" style="display: none;">

        <div style="overflow-x: auto;">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $column): ?>
                        <tr>
                            <td><?= htmlspecialchars($column['Field']); ?></td>
                            <td><?= htmlspecialchars($column['Type']); ?></td>
                            <td><?= htmlspecialchars($column['Null']); ?></td>
                            <td><?= htmlspecialchars($column['Key']); ?></td>
                            <td><?= htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                            <td><?= htmlspecialchars($column['Extra']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-danger btn-action"
                                        onclick="deleteColumn('<?= htmlspecialchars($selectedTable); ?>', '<?= htmlspecialchars($column['Field']); ?>')">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
<script>
function toggleTableStructure() {
    const bodyDiv = document.getElementById("tableStructureBodyDiv");
    const arrow = document.getElementById("ts-arrow");

    if (bodyDiv.style.display === "none" || bodyDiv.style.display === "") {
        bodyDiv.style.display = "block";
        arrow.style.transform = "rotate(90deg)";
    } else {
        bodyDiv.style.display = "none";
        arrow.style.transform = "rotate(0deg)";
    }
}
</script>


                    <!-- Table Data with Infinite Scroll -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                Table Data 
                                (<span id="loadedRows">0</span> / <?php echo $totalRows; ?> rows)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-wrapper" id="tableWrapper">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <?php foreach ($columns as $col): ?>
                                                <th><?php echo htmlspecialchars($col['Field']); ?></th>
                                            <?php endforeach; ?>
                                            <th style="min-width: 90px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <!-- Data loaded via JavaScript -->
                                    </tbody>
                                </table>
                                <div class="loading-indicator" id="loadingIndicator">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading more rows...
                                </div>
                            </div>
                            <?php if ($totalRows === 0): ?>
                                <p class="text-muted text-center py-4">No data in this table.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-database" style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">Select a table from the sidebar</h4>
                        <p class="text-muted">Or create a new table to get started</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Table Modal -->
    <div class="modal fade" id="createTableModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createTableForm">
                        <div class="mb-3">
                            <label class="form-label">Table Name</label>
                            <input type="text" class="form-control" name="table_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Number of Columns</label>
                            <input type="number" class="form-control" name="num_columns" min="1" value="3" required>
                        </div>
                        <div id="columnsContainer"></div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="generateColumnFields()">Generate Fields</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="createTable()">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Column Modal -->
    <div class="modal fade" id="addColumnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Column</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addColumnForm">
                        <div class="mb-3">
                            <label class="form-label">Column Name</label>
                            <input type="text" class="form-control" name="column_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data Type</label>
                            <select class="form-select" name="data_type" required>
                                <option value="INT">INT</option>
                                <option value="VARCHAR(255)">VARCHAR(255)</option>
                                <option value="TEXT">TEXT</option>
                                <option value="DATE">DATE</option>
                                <option value="DATETIME">DATETIME</option>
                                <option value="DECIMAL(10,2)">DECIMAL(10,2)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="nullable" id="nullable">
                                <label class="form-check-label" for="nullable">Allow NULL</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addColumn()">Add</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Row Modal -->
    <div class="modal fade" id="addRowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Row</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRowForm">
                        <?php foreach ($columns as $column): ?>
                            <?php if ($column['Extra'] !== 'auto_increment'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo htmlspecialchars($column['Field']); ?></label>
                                    <input type="text" class="form-control form-control-sm" name="<?php echo htmlspecialchars($column['Field']); ?>" 
                                           <?php echo $column['Null'] === 'NO' && $column['Default'] === null ? 'required' : ''; ?>>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="addRow()">Add</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Row Modal -->
    <div class="modal fade" id="editRowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Row</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editRowForm"></form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="updateRow()">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Update Login Credentials</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="settingsContent">
        <!-- settings.php content will load here -->
        <div class="text-center p-3">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading...</p>
        </div>
      </div>
    </div>
  </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const currentTable = '<?php echo addslashes($selectedTable); ?>';
        const primaryKey = '<?php echo addslashes($primaryKey); ?>';
        const columns = <?php echo json_encode($columns); ?>;
        const totalRows = <?php echo $totalRows; ?>;
        
        let currentOffset = 0;
        let isLoading = false;
        let allDataLoaded = false;
        const ROWS_PER_LOAD = 100;

        // Load initial data
        if (currentTable) {
            loadMoreRows();
        }

        // Infinite scroll
        document.getElementById('tableWrapper')?.addEventListener('scroll', function() {
            const wrapper = this;
            const scrollPosition = wrapper.scrollTop + wrapper.clientHeight;
            const scrollHeight = wrapper.scrollHeight;
            
            // Load more when 100px from bottom
            if (scrollPosition >= scrollHeight - 100 && !isLoading && !allDataLoaded) {
                loadMoreRows();
            }
        });

        function loadMoreRows() {
            if (isLoading || allDataLoaded) return;
            
            isLoading = true;
            document.getElementById('loadingIndicator').classList.add('active');
            
            fetch(`api.php?action=get_table_data&table=${encodeURIComponent(currentTable)}&offset=${currentOffset}&limit=${ROWS_PER_LOAD}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        appendRows(data.data);
                        currentOffset += data.data.length;
                        document.getElementById('loadedRows').textContent = currentOffset;
                        
                        if (currentOffset >= totalRows) {
                            allDataLoaded = true;
                        }
                    } else {
                        allDataLoaded = true;
                    }
                })
                .catch(error => {
                    console.error('Error loading data:', error);
                    alert('Error loading data: ' + error.message);
                })
                .finally(() => {
                    isLoading = false;
                    document.getElementById('loadingIndicator').classList.remove('active');
                });
        }

        function appendRows(rows) {
            const tbody = document.getElementById('tableBody');
            
            rows.forEach(row => {
                const tr = document.createElement('tr');
                
                // Add data cells
                columns.forEach(col => {
                    const td = document.createElement('td');
                    td.className = 'data-cell';
                    const value = row[col.Field] !== null ? row[col.Field] : 'NULL';
                    td.textContent = value;
                    td.title = value;
                    tr.appendChild(td);
                });
                
                // Add action buttons
                const actionTd = document.createElement('td');
                actionTd.innerHTML = `
                    <button class="btn btn-sm btn-warning btn-action" onclick='editRowData(${JSON.stringify(row).replace(/'/g, "&apos;")})'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-action" onclick="deleteRowData('${row[primaryKey]}')">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                tr.appendChild(actionTd);
                
                tbody.appendChild(tr);
            });
        }

        function editRowData(rowData) {
            const form = document.getElementById('editRowForm');
            form.innerHTML = '';
            
            for (const [key, value] of Object.entries(rowData)) {
                const column = columns.find(c => c.Field === key);
                const isAuto = column && column.Extra === 'auto_increment';
                const isPrimary = key === primaryKey;
                
                form.innerHTML += `
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 13px;">${key} ${isPrimary ? '(Primary Key)' : ''}</label>
                        <input type="text" class="form-control form-control-sm" name="${key}" 
                               value="${value !== null ? value : ''}" 
                               ${isAuto ? 'readonly' : ''}>
                    </div>
                `;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editRowModal'));
            modal.show();
        }

        function deleteRowData(pkValue) {
            if (!confirm('⚠️ Are you sure you want to delete this row?\n\nThis action cannot be undone!')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_row');
            formData.append('table_name', currentTable);
            formData.append('pk_column', primaryKey);
            formData.append('pk_value', pkValue);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Row deleted successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        // Rest of the functions...
        function generateColumnFields() {
            const numColumns = document.querySelector('[name="num_columns"]').value;
            const container = document.getElementById('columnsContainer');
            container.innerHTML = '';

            for (let i = 0; i < numColumns; i++) {
                container.innerHTML += `
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6 style="font-size: 14px;">Column ${i + 1}</h6>
                            <div class="mb-2">
                                <label class="form-label" style="font-size: 13px;">Name</label>
                                <input type="text" class="form-control form-control-sm" name="col_name_${i}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" style="font-size: 13px;">Type</label>
                                <select class="form-select form-select-sm" name="col_type_${i}">
                                    <option value="INT">INT</option>
                                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                                    <option value="TEXT">TEXT</option>
                                    <option value="DATE">DATE</option>
                                </select>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="col_pk_${i}" id="col_pk_${i}">
                                <label class="form-check-label" for="col_pk_${i}" style="font-size: 12px;">Primary Key</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="col_ai_${i}" id="col_ai_${i}">
                                <label class="form-check-label" for="col_ai_${i}" style="font-size: 12px;">Auto Increment</label>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        function createTable() {
            const formData = new FormData(document.getElementById('createTableForm'));
            formData.append('action', 'create_table');
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Table created successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function deleteTable(tableName) {
            if (!confirm(`⚠️ Are you sure you want to delete table "${tableName}"?\n\nThis will permanently delete all data!`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_table');
            formData.append('table_name', tableName);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Table deleted successfully!');
                    window.location.href = 'index.php';
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function renameTable(oldName) {
            const newName = prompt('Enter new table name:', oldName);
            if (!newName || newName === oldName) return;
            
            const formData = new FormData();
            formData.append('action', 'rename_table');
            formData.append('old_name', oldName);
            formData.append('new_name', newName);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Table renamed successfully!');
                    window.location.href = `index.php?table=${encodeURIComponent(newName)}`;
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function addColumn() {
            const formData = new FormData(document.getElementById('addColumnForm'));
            formData.append('action', 'add_column');
            formData.append('table_name', currentTable);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Column added successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function deleteColumn(tableName, columnName) {
            if (!confirm(`⚠️ Are you sure you want to delete column "${columnName}"?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_column');
            formData.append('table_name', tableName);
            formData.append('column_name', columnName);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Column deleted successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function addRow() {
            const formData = new FormData(document.getElementById('addRowForm'));
            formData.append('action', 'add_row');
            formData.append('table_name', currentTable);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Row added successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }

        function updateRow() {
            const formData = new FormData(document.getElementById('editRowForm'));
            formData.append('action', 'update_row');
            formData.append('table_name', currentTable);
            formData.append('primary_key', primaryKey);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Row updated successfully!');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
        }
    </script>

    <script>
function openSettingsModal() {
    document.getElementById("settingsContent").innerHTML = `
        <div class="text-center p-3">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading...</p>
        </div>
    `;

    // Load settings.php page inside modal
    fetch("settings.php")
        .then(response => response.text())
        .then(data => {
            document.getElementById("settingsContent").innerHTML = data;
        });

    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('settingsModal'));
    modal.show();
}
</script>

</body>
</html>