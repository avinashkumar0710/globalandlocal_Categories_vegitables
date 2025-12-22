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
    <link href="public/assets/css/custom.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-size: 13px;
        }
        .sidebar { 
            height: 100vh; 
            background-color: #343a40; 
            color: white; 
            position: sticky; 
            top: 0;
            overflow-y: auto;
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
            max-height: 60vh;
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
                    <button class="btn btn-sm btn-outline-light mt-2" onclick="openMailConfigModal()">Mail Settings</button>
                </div>
                <hr class="text-white">
                <div class="px-3 mb-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-success w-100 btn-sm d-flex flex-column align-items-center py-2" data-bs-toggle="modal" data-bs-target="#createTableModal">
                                <i class="bi bi-plus-circle fs-5"></i>
                                <small>Create Table</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <a href="upload.php" class="btn btn-primary w-100 btn-sm d-flex flex-column align-items-center py-2">
                                <i class="bi bi-file-earmark-excel fs-5"></i>
                                <small>Upload Excel</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="paste_data.php" class="btn btn-info w-100 btn-sm d-flex flex-column align-items-center py-2">
                                <i class="bi bi-clipboard-data fs-5"></i>
                                <small>Paste Data</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-secondary w-100 btn-sm d-flex flex-column align-items-center py-2" data-bs-toggle="modal" data-bs-target="#chaptersModal">
                                <i class="bi bi-journal-bookmark fs-5"></i>
                                <small>Manage Chapters</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-warning w-100 btn-sm d-flex flex-column align-items-center py-2" data-bs-toggle="modal" data-bs-target="#homePageModal">
                                <i class="bi bi-house-door fs-5"></i>
                                <small>Home Page</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-danger w-100 btn-sm d-flex flex-column align-items-center py-2" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                <i class="bi bi-chat-dots fs-5"></i>
                                <small>Feedback/Issues</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-dark w-100 btn-sm d-flex flex-column align-items-center py-2" onclick="openPrivacyPolicyModal()">
                                <i class="bi bi-shield-lock fs-5"></i>
                                <small>Privacy Policy</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-info w-100 btn-sm d-flex flex-column align-items-center py-2" onclick="showTotalVisitors()">
                                <i class="bi bi-people fs-5"></i>
                                <small>Total Visitors</small>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-primary w-100 btn-sm d-flex flex-column align-items-center py-2" onclick="openTableVisibilityModal()">
                                <i class="bi bi-table fs-5"></i>
                                <small>Table Visibility</small>
                            </button>
                        </div>
                        
                    </div>
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

    <!-- Chapters Management Modal -->
    <div class="modal fade" id="chaptersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="bi bi-journal-bookmark"></i> Manage Chapters</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Create New Chapter Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Chapter</h6>
                        </div>
                        <div class="card-body">
                            <form id="createChapterForm" class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label">Chapter Name</label>
                                    <input type="text" class="form-control form-control-sm" name="chapter_name" placeholder="Enter chapter name" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-success btn-sm w-100" onclick="createChapter()">
                                        <i class="bi bi-plus"></i> Create Chapter
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-info btn-sm w-100" onclick="openDocumentEditor()">
                                        <i class="bi bi-file-earmark-richtext"></i> Create Document
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Upload Document to Chapter Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-upload"></i> Upload Document to Chapter</h6>
                        </div>
                        <div class="card-body">
                            <form id="uploadChapterDocForm" enctype="multipart/form-data">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="form-label">Select Chapter</label>
                                        <select class="form-select form-select-sm" name="chapter_id" id="uploadChapterSelect" required>
                                            <option value="">-- Select Chapter --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Document File</label>
                                        <input type="file" class="form-control form-control-sm" name="chapter_document" accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.ppt,.pptx" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="uploadChapterDocument()">
                                            <i class="bi bi-upload"></i> Upload
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Supported: PDF, DOC, DOCX, TXT, XLS, XLSX, PPT, PPTX</small>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Chapters List -->
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="bi bi-list"></i> Existing Chapters</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>#</th>
                                            <th>Chapter Name</th>
                                            <th>Documents</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="chaptersTableBody">
                                        <tr><td colspan="5" class="text-center text-muted">Loading chapters...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chapter Documents Modal -->
    <div class="modal fade" id="chapterDocsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Chapter Documents</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="chapterDocsTitle"></h6>
                    <div id="chapterDocsList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Editor Modal -->
    <div class="modal fade" id="documentEditorModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-richtext"></i> Document Editor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" id="docEditorTitle" placeholder="Document Title" required>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="docEditorChapter" onchange="loadChapterDocumentsForEdit()">
                                <option value="">-- Select Chapter --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="docEditorExisting" onchange="loadExistingDocument()">
                                <option value="">-- New Document --</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-1">
                            <button type="button" class="btn btn-success btn-sm flex-fill" onclick="saveDocument()">
                                <i class="bi bi-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-info btn-sm flex-fill" onclick="copyDocumentToClipboard()">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                            <button type="button" class="btn btn-warning btn-sm flex-fill" onclick="downloadDocumentAsWord()">
                                <i class="bi bi-download"></i> Download
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="editingDocId" value="">
                    <textarea id="richTextEditor"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Home Page Management Modal -->
    <div class="modal fade" id="homePageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-house-door"></i> Manage Home Page Content</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Add New Section -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Section</h6>
                        </div>
                        <div class="card-body">
                            <form id="addHomeSectionForm">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Section Title</label>
                                        <input type="text" class="form-control form-control-sm" name="section_title" placeholder="e.g., Welcome, About Us" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Section Type</label>
                                        <select class="form-select form-select-sm" name="section_type">
                                            <option value="hero">Hero / Banner</option>
                                            <option value="about">About / Summary</option>
                                            <option value="features">Features</option>
                                            <option value="services">Services</option>
                                            <option value="cta">Call to Action</option>
                                            <option value="contact">Contact Info</option>
                                            <option value="custom">Custom Content</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Order</label>
                                        <input type="number" class="form-control form-control-sm" name="section_order" value="1" min="1">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-success btn-sm w-100" onclick="addHomeSection()">
                                            <i class="bi bi-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Sections List -->
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0"><i class="bi bi-list"></i> Home Page Sections</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Order</th>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="homeSectionsTableBody">
                                        <tr><td colspan="5" class="text-center text-muted">Loading sections...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="home.php" target="_blank" class="btn btn-info btn-sm">
                        <i class="bi bi-eye"></i> Preview Home Page
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Home Section Modal -->
    <div class="modal fade" id="editHomeSectionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header bg-warning py-2">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Section Content</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-2">
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <input type="hidden" id="editSectionId">
                            <input type="text" class="form-control form-control-sm" id="editSectionTitle" placeholder="Section Title">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" id="editSectionType">
                                <option value="hero">Hero / Banner</option>
                                <option value="about">About / Summary</option>
                                <option value="features">Features</option>
                                <option value="services">Services</option>
                                <option value="cta">Call to Action</option>
                                <option value="contact">Contact Info</option>
                                <option value="custom">Custom Content</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <input type="number" class="form-control form-control-sm" id="editSectionOrder" min="1" placeholder="Order">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" id="editSectionStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-1">
                            <button type="button" class="btn btn-success btn-sm flex-fill" onclick="saveHomeSection()">
                                <i class="bi bi-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm flex-fill" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <textarea id="homeSectionEditor"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Management Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-chat-dots"></i> Feedback & Issues</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0" style="min-height: 500px;">
                        <!-- Feedback List -->
                        <div class="col-md-5 border-end">
                            <div class="p-2 bg-light border-bottom">
                                <h6 class="mb-0">Submissions <span id="feedbackCount" class="badge bg-secondary">0</span></h6>
                            </div>
                            <div id="feedbackList" class="list-group list-group-flush" style="height: calc(100% - 40px); overflow-y: auto;">
                                <div class="text-center p-3 text-muted">Loading feedback...</div>
                            </div>
                        </div>
                        
                        <!-- Feedback Detail & Reply -->
                        <div class="col-md-7">
                            <div id="feedbackDetailContainer">
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-chat-dots fs-1"></i>
                                    <p class="mt-2 mb-0">Select a feedback to view details</p>
                                    <small>Select a message from the list to read and reply</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Composer Modal -->
    <div class="modal fade" id="replyComposerModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-reply"></i> Reply to Feedback</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="replyForm">
                        <input type="hidden" id="replyFeedbackId">
                        <div class="mb-3">
                            <label class="form-label">To:</label>
                            <input type="email" class="form-control form-control-sm" id="replyToEmail" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject:</label>
                            <input type="text" class="form-control form-control-sm" id="replySubject" placeholder="Re: [Feedback Subject]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message:</label>
                            <textarea class="form-control" id="replyMessage" rows="8" placeholder="Type your reply here..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="sendReply()">
                        <i class="bi bi-send"></i> Send Reply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rename Chapter Modal -->
    <div class="modal fade" id="renameChapterModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Rename Chapter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="renameChapterForm">
                        <input type="hidden" name="chapter_id" id="renameChapterId">
                        <div class="mb-3">
                            <label class="form-label">New Chapter Name</label>
                            <input type="text" class="form-control form-control-sm" name="new_name" id="renameChapterName" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="renameChapter()">Rename</button>
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

<!-- Mail Configuration Modal -->
<div class="modal fade" id="mailConfigModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Mail Configuration</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="mailConfigForm">
          <div class="mb-3">
            <label class="form-label">SMTP Host</label>
            <input type="text" class="form-control form-control-sm" id="smtpHost" placeholder="ssl://smtp.gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label">SMTP Port</label>
            <input type="number" class="form-control form-control-sm" id="smtpPort" placeholder="465">
          </div>
          <div class="mb-3">
            <label class="form-label">SMTP Username</label>
            <input type="email" class="form-control form-control-sm" id="smtpUsername" placeholder="your-email@gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label">SMTP Password</label>
            <input type="password" class="form-control form-control-sm" id="smtpPassword" placeholder="your password">
          </div>
          <div class="mb-3">
            <label class="form-label">From Email</label>
            <input type="email" class="form-control form-control-sm" id="fromEmail" placeholder="your-email@gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label">From Name</label>
            <input type="text" class="form-control form-control-sm" id="fromName" placeholder="Admin Team">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-info btn-sm" onclick="saveMailConfig()">Save</button>
      </div>
    </div>
  </div>
</div>

    <!-- Summernote Rich Text Editor (Free - No API Key Required) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
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
        // Chapter Management Functions
        function loadChapters() {
            fetch('api.php?action=get_chapters')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderChaptersTable(data.chapters);
                        updateChapterSelect(data.chapters);
                    } else {
                        document.getElementById('chaptersTableBody').innerHTML = 
                            '<tr><td colspan="5" class="text-center text-danger">Error loading chapters</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('chaptersTableBody').innerHTML = 
                        '<tr><td colspan="5" class="text-center text-danger">Error loading chapters</td></tr>';
                });
        }

        function renderChaptersTable(chapters) {
            const tbody = document.getElementById('chaptersTableBody');
            if (chapters.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No chapters found. Create one above.</td></tr>';
                return;
            }
            
            tbody.innerHTML = chapters.map((chapter, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(chapter.name)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info btn-action" onclick="viewChapterDocs(${chapter.id}, '${escapeHtml(chapter.name)}')">
                            <i class="bi bi-folder"></i> ${chapter.doc_count} files
                        </button>
                    </td>
                    <td><small>${chapter.created_at}</small></td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-action" onclick="openRenameChapter(${chapter.id}, '${escapeHtml(chapter.name)}')" title="Rename">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action" onclick="deleteChapter(${chapter.id}, '${escapeHtml(chapter.name)}')" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function updateChapterSelect(chapters) {
            const select = document.getElementById('uploadChapterSelect');
            select.innerHTML = '<option value="">-- Select Chapter --</option>' + 
                chapters.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createChapter() {
            const form = document.getElementById('createChapterForm');
            const chapterName = form.querySelector('[name="chapter_name"]').value.trim();
            
            if (!chapterName) {
                alert('Please enter a chapter name');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_chapter');
            formData.append('chapter_name', chapterName);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Chapter created successfully!');
                    form.reset();
                    loadChapters();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function uploadChapterDocument() {
            const form = document.getElementById('uploadChapterDocForm');
            const formData = new FormData(form);
            formData.append('action', 'upload_chapter_document');
            
            const chapterId = form.querySelector('[name="chapter_id"]').value;
            const fileInput = form.querySelector('[name="chapter_document"]');
            
            if (!chapterId) {
                alert('Please select a chapter');
                return;
            }
            if (!fileInput.files.length) {
                alert('Please select a file to upload');
                return;
            }
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Document uploaded successfully!');
                    form.reset();
                    loadChapters();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function openRenameChapter(chapterId, currentName) {
            document.getElementById('renameChapterId').value = chapterId;
            document.getElementById('renameChapterName').value = currentName;
            const modal = new bootstrap.Modal(document.getElementById('renameChapterModal'));
            modal.show();
        }

        function renameChapter() {
            const chapterId = document.getElementById('renameChapterId').value;
            const newName = document.getElementById('renameChapterName').value.trim();
            
            if (!newName) {
                alert('Please enter a new name');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'rename_chapter');
            formData.append('chapter_id', chapterId);
            formData.append('new_name', newName);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Chapter renamed successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('renameChapterModal')).hide();
                    loadChapters();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function deleteChapter(chapterId, chapterName) {
            if (!confirm(`⚠️ Are you sure you want to delete chapter "${chapterName}"?\n\nThis will also delete all documents in this chapter!`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_chapter');
            formData.append('chapter_id', chapterId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Chapter deleted successfully!');
                    loadChapters();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function viewChapterDocs(chapterId, chapterName) {
            document.getElementById('chapterDocsTitle').textContent = 'Documents in: ' + chapterName;
            document.getElementById('chapterDocsList').innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>';
            
            const modal = new bootstrap.Modal(document.getElementById('chapterDocsModal'));
            modal.show();
            
            fetch(`api.php?action=get_chapter_documents&chapter_id=${chapterId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.documents.length === 0) {
                            document.getElementById('chapterDocsList').innerHTML = '<p class="text-muted text-center">No documents in this chapter.</p>';
                        } else {
                            document.getElementById('chapterDocsList').innerHTML = `
                                <ul class="list-group">
                                    ${data.documents.map(doc => `
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-file-earmark-text"></i>
                                                <a href="${doc.file_path}" target="_blank">${escapeHtml(doc.original_name)}</a>
                                                <small class="text-muted d-block">${doc.uploaded_at}</small>
                                            </div>
                                            <div>
                                                ${doc.is_rich_doc ? `<button class="btn btn-sm btn-info me-1" onclick="editRichDocument(${doc.id})" title="Edit"><i class="bi bi-pencil"></i></button>` : ''}
                                                <button class="btn btn-sm btn-secondary me-1" onclick="copyDocumentContent(${doc.id}, '${doc.is_rich_doc ? 'rich' : 'file'}')" title="Copy">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteChapterDocument(${doc.id}, ${chapterId}, '${escapeHtml(chapterName)}')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </li>
                                    `).join('')}
                                </ul>
                            `;
                        }
                    } else {
                        document.getElementById('chapterDocsList').innerHTML = '<p class="text-danger">Error loading documents</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('chapterDocsList').innerHTML = '<p class="text-danger">Error loading documents</p>';
                });
        }

        function deleteChapterDocument(docId, chapterId, chapterName) {
            if (!confirm('Are you sure you want to delete this document?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_chapter_document');
            formData.append('document_id', docId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Document deleted!');
                    viewChapterDocs(chapterId, chapterName);
                    loadChapters();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        // Load chapters when modal opens
        document.getElementById('chaptersModal')?.addEventListener('show.bs.modal', function() {
            loadChapters();
        });

        // Initialize Summernote when document editor opens
        let summernoteInitialized = false;
        
        function initSummernote() {
            if (summernoteInitialized) {
                $('#richTextEditor').summernote('destroy');
            }
            
            $('#richTextEditor').summernote({
                height: $(window).height() - 220,
                placeholder: 'Start typing your document here...',
                tabsize: 2,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['forecolor', 'backcolor']],
                    ['para', ['ul', 'ol', 'paragraph', 'height']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'undo', 'redo', 'help']]
                ],
                fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Impact', 'Lucida Console', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'],
                fontNamesIgnoreCheck: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Impact', 'Lucida Console', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'],
                fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '22', '24', '26', '28', '36', '48', '72'],
                styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre'],
                callbacks: {
                    onInit: function() {
                        summernoteInitialized = true;
                    }
                }
            });
        }

        function openDocumentEditor(docId = null) {
            document.getElementById('editingDocId').value = docId || '';
            document.getElementById('docEditorTitle').value = '';
            document.getElementById('docEditorExisting').innerHTML = '<option value="">-- New Document --</option>';
            
            // Populate chapter dropdown
            fetch('api.php?action=get_chapters')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('docEditorChapter');
                        select.innerHTML = '<option value="">-- Select Chapter --</option>' + 
                            data.chapters.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
                    }
                });

            // Close chapters modal if open
            const chaptersModal = bootstrap.Modal.getInstance(document.getElementById('chaptersModal'));
            if (chaptersModal) chaptersModal.hide();

            // Open editor modal
            const editorModal = new bootstrap.Modal(document.getElementById('documentEditorModal'));
            editorModal.show();

            // Initialize Summernote after modal is shown
            setTimeout(() => {
                initSummernote();
                $('#richTextEditor').summernote('code', '');
                
                // If editing existing document, load content
                if (docId) {
                    loadDocumentForEdit(docId);
                }
            }, 300);
        }

        function loadDocumentForEdit(docId) {
            fetch(`api.php?action=get_document_content&document_id=${docId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('docEditorTitle').value = data.document.title || '';
                        document.getElementById('docEditorChapter').value = data.document.chapter_id || '';
                        $('#richTextEditor').summernote('code', data.document.content || '');
                        
                        // Load documents for this chapter if chapter is selected
                        if (data.document.chapter_id) {
                            loadChapterDocumentsForEdit(data.document.chapter_id, docId);
                        }
                    }
                });
        }

        function loadChapterDocumentsForEdit(preselectedChapterId = null, preselectedDocId = null) {
            const chapterId = preselectedChapterId || document.getElementById('docEditorChapter').value;
            const existingSelect = document.getElementById('docEditorExisting');
            
            if (!chapterId) {
                existingSelect.innerHTML = '<option value="">-- New Document --</option>';
                // Clear editor for new document
                document.getElementById('editingDocId').value = '';
                document.getElementById('docEditorTitle').value = '';
                $('#richTextEditor').summernote('code', '');
                return;
            }
            
            fetch(`api.php?action=get_chapter_documents&chapter_id=${chapterId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Filter only rich documents
                        const richDocs = data.documents.filter(d => d.is_rich_doc);
                        
                        existingSelect.innerHTML = '<option value="">-- New Document --</option>' + 
                            richDocs.map(d => `<option value="${d.id}" ${preselectedDocId == d.id ? 'selected' : ''}>${escapeHtml(d.title || d.original_name)}</option>`).join('');
                        
                        // If there are documents and none preselected, ask user if they want to load one
                        if (richDocs.length > 0 && !preselectedDocId && !preselectedChapterId) {
                            // Auto-select first document to help user see what's available
                            // User can select "New Document" to create new
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading chapter documents:', error);
                });
        }

        function loadExistingDocument() {
            const docId = document.getElementById('docEditorExisting').value;
            
            if (!docId) {
                // User selected "New Document" - clear the editor
                document.getElementById('editingDocId').value = '';
                document.getElementById('docEditorTitle').value = '';
                $('#richTextEditor').summernote('code', '');
                return;
            }
            
            // Load the selected document
            document.getElementById('editingDocId').value = docId;
            
            fetch(`api.php?action=get_document_content&document_id=${docId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('docEditorTitle').value = data.document.title || '';
                        $('#richTextEditor').summernote('code', data.document.content || '');
                    }
                })
                .catch(error => {
                    alert('Error loading document: ' + error.message);
                });
        }

        function editRichDocument(docId) {
            openDocumentEditor(docId);
        }

        function saveDocument() {
            const title = document.getElementById('docEditorTitle').value.trim();
            const chapterId = document.getElementById('docEditorChapter').value;
            const docId = document.getElementById('editingDocId').value;
            const content = $('#richTextEditor').summernote('code');
            
            if (!title) {
                alert('Please enter a document title');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', docId ? 'update_rich_document' : 'create_rich_document');
            formData.append('title', title);
            formData.append('content', content);
            formData.append('chapter_id', chapterId);
            if (docId) formData.append('document_id', docId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Document saved successfully!');
                    document.getElementById('editingDocId').value = data.document_id || docId;
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function copyDocumentToClipboard() {
            const content = $('#richTextEditor').summernote('code');
            
            // Strip HTML to get plain text
            const temp = document.createElement('div');
            temp.innerHTML = content;
            const textContent = temp.textContent || temp.innerText;
            
            navigator.clipboard.writeText(textContent).then(() => {
                alert('✅ Document content copied to clipboard!');
            }).catch(err => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = textContent;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('✅ Document content copied to clipboard!');
            });
        }

        function copyDocumentContent(docId, docType) {
            fetch(`api.php?action=get_document_content&document_id=${docId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.document) {
                        // Create temporary element to strip HTML
                        const temp = document.createElement('div');
                        temp.innerHTML = data.document.content || '';
                        const textContent = temp.textContent || temp.innerText;
                        
                        navigator.clipboard.writeText(textContent).then(() => {
                            alert('✅ Document content copied to clipboard!');
                        }).catch(err => {
                            const textarea = document.createElement('textarea');
                            textarea.value = textContent;
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                            alert('✅ Document content copied to clipboard!');
                        });
                    } else {
                        alert('❌ Could not load document content');
                    }
                })
                .catch(error => alert('❌ Error: ' + error.message));
        }

        function downloadDocumentAsWord() {
            const title = document.getElementById('docEditorTitle').value.trim() || 'document';
            const content = $('#richTextEditor').summernote('code');
            
            // Create Word-compatible HTML document
            const htmlContent = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" 
                      xmlns:w="urn:schemas-microsoft-com:office:word" 
                      xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; }
                        table { border-collapse: collapse; }
                        td, th { border: 1px solid #000; padding: 5px; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `;
            
            const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = title + '.doc';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Cleanup Summernote when modal closes
        document.getElementById('documentEditorModal')?.addEventListener('hidden.bs.modal', function() {
            if (summernoteInitialized) {
                $('#richTextEditor').summernote('destroy');
                summernoteInitialized = false;
            }
        });

        // ==================== HOME PAGE MANAGEMENT ====================
        // Initialize Summernote for home section editor
        let homeSectionEditorInitialized = false;
        
        function initHomeSectionEditor() {
            if (homeSectionEditorInitialized) {
                $('#homeSectionEditor').summernote('destroy');
            }
            
            $('#homeSectionEditor').summernote({
                height: $(window).height() - 220,
                placeholder: 'Enter section content here...',
                tabsize: 2,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['forecolor', 'backcolor']],
                    ['para', ['ul', 'ol', 'paragraph', 'height']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'undo', 'redo', 'help']]
                ],
                fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Impact', 'Lucida Console', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'],
                fontNamesIgnoreCheck: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Impact', 'Lucida Console', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'],
                fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '22', '24', '26', '28', '36', '48', '72'],
                styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre'],
                callbacks: {
                    onInit: function() {
                        homeSectionEditorInitialized = true;
                    }
                }
            });
        }

        function loadHomeSections() {
            fetch('api.php?action=get_home_sections')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderHomeSectionsTable(data.sections);
                    } else {
                        document.getElementById('homeSectionsTableBody').innerHTML = 
                            '<tr><td colspan="5" class="text-center text-danger">Error loading sections</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('homeSectionsTableBody').innerHTML = 
                        '<tr><td colspan="5" class="text-center text-danger">Error loading sections</td></tr>';
                });
        }

        function renderHomeSectionsTable(sections) {
            const tbody = document.getElementById('homeSectionsTableBody');
            if (sections.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No sections found. Add one above.</td></tr>';
                return;
            }
            
            // Sort by order
            sections.sort((a, b) => a.order - b.order);
            
            tbody.innerHTML = sections.map((section, index) => `
                <tr>
                    <td>${section.order}</td>
                    <td>${escapeHtml(section.title)}</td>
                    <td><span class="badge bg-info">${section.type}</span></td>
                    <td><span class="badge ${section.status === 'active' ? 'bg-success' : 'bg-secondary'}">${section.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-action" onclick="editHomeSection(${section.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-${section.status === 'active' ? 'secondary' : 'success'} btn-action" onclick="toggleHomeSectionStatus(${section.id}, '${section.status}')" title="${section.status === 'active' ? 'Deactivate' : 'Activate'}">
                            <i class="bi ${section.status === 'active' ? 'bi-eye-slash' : 'bi-eye'}"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action" onclick="deleteHomeSection(${section.id}, '${escapeHtml(section.title)}')" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function addHomeSection() {
            const form = document.getElementById('addHomeSectionForm');
            const title = form.querySelector('[name="section_title"]').value.trim();
            const type = form.querySelector('[name="section_type"]').value;
            const order = parseInt(form.querySelector('[name="section_order"]').value);
            
            if (!title) {
                alert('Please enter a section title');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_home_section');
            formData.append('title', title);
            formData.append('type', type);
            formData.append('order', order);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Section added successfully!');
                    form.reset();
                    loadHomeSections();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function editHomeSection(sectionId) {
            fetch(`api.php?action=get_home_section&id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const section = data.section;
                        document.getElementById('editSectionId').value = section.id;
                        document.getElementById('editSectionTitle').value = section.title;
                        document.getElementById('editSectionType').value = section.type;
                        document.getElementById('editSectionOrder').value = section.order;
                        document.getElementById('editSectionStatus').value = section.status;
                        
                        // Close home page modal
                        const homeModal = bootstrap.Modal.getInstance(document.getElementById('homePageModal'));
                        if (homeModal) homeModal.hide();
                        
                        // Open edit modal
                        const editModal = new bootstrap.Modal(document.getElementById('editHomeSectionModal'));
                        editModal.show();
                        
                        // Initialize editor after modal is shown
                        setTimeout(() => {
                            initHomeSectionEditor();
                            $('#homeSectionEditor').summernote('code', section.content || '');
                        }, 300);
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(error => alert('❌ Error: ' + error.message));
        }

        function saveHomeSection() {
            const sectionId = document.getElementById('editSectionId').value;
            const title = document.getElementById('editSectionTitle').value.trim();
            const type = document.getElementById('editSectionType').value;
            const order = parseInt(document.getElementById('editSectionOrder').value);
            const status = document.getElementById('editSectionStatus').value;
            const content = $('#homeSectionEditor').summernote('code');
            
            if (!title) {
                alert('Please enter a section title');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_home_section');
            formData.append('section_id', sectionId);
            formData.append('title', title);
            formData.append('type', type);
            formData.append('order', order);
            formData.append('status', status);
            formData.append('content', content);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Section saved successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editHomeSectionModal')).hide();
                    loadHomeSections();
                    
                    // Re-open home page modal
                    const homeModal = new bootstrap.Modal(document.getElementById('homePageModal'));
                    homeModal.show();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function toggleHomeSectionStatus(sectionId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            const formData = new FormData();
            formData.append('action', 'update_home_section_status');
            formData.append('section_id', sectionId);
            formData.append('status', newStatus);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadHomeSections();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function deleteHomeSection(sectionId, sectionTitle) {
            if (!confirm(`⚠️ Are you sure you want to delete section "${sectionTitle}"?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_home_section');
            formData.append('section_id', sectionId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Section deleted successfully!');
                    loadHomeSections();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        // Load home sections when modal opens
        document.getElementById('homePageModal')?.addEventListener('show.bs.modal', function() {
            loadHomeSections();
        });

        // Cleanup Summernote when home section editor modal closes
        document.getElementById('editHomeSectionModal')?.addEventListener('hidden.bs.modal', function() {
            if (homeSectionEditorInitialized) {
                $('#homeSectionEditor').summernote('destroy');
                homeSectionEditorInitialized = false;
            }
        });

        // ==================== FEEDBACK MANAGEMENT ====================
        function loadFeedbackList() {
            fetch('api.php?action=get_feedback_list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderFeedbackList(data.feedback);
                    } else {
                        document.getElementById('feedbackList').innerHTML = 
                            '<div class="text-center p-3 text-danger">Error loading feedback</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('feedbackList').innerHTML = 
                        '<div class="text-center p-3 text-danger">Error loading feedback</div>';
                });
        }

        function renderFeedbackList(feedbackList) {
            const container = document.getElementById('feedbackList');
            document.getElementById('feedbackCount').textContent = feedbackList.length;
            
            if (feedbackList.length === 0) {
                container.innerHTML = '<div class="text-center p-3 text-muted">No feedback submissions yet</div>';
                return;
            }
            
            // Sort by date (newest first)
            feedbackList.sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
            
            container.innerHTML = feedbackList.map(feedback => `
                <a href="javascript:void(0)" class="list-group-item list-group-item-action border-0" 
                   onclick="loadFeedbackDetail(${feedback.id})">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong class="d-block">${escapeHtml(feedback.name || 'Anonymous')}</strong>
                            <small class="text-muted">${escapeHtml(feedback.subject || 'No subject')}</small>
                        </div>
                        <small class="text-muted">${formatDate(feedback.submitted_at)}</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-truncate" style="max-width: 70%;">${escapeHtml(feedback.email)}</small>
                        ${feedback.status === 'replied' ? 
                            '<span class="badge bg-success">Replied</span>' : 
                            '<span class="badge bg-warning">New</span>'}
                    </div>
                </a>
            `).join('');
        }

        function loadFeedbackDetail(feedbackId) {
            fetch(`api.php?action=get_feedback_detail&id=${feedbackId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderFeedbackDetail(data.feedback);
                    } else {
                        document.getElementById('feedbackDetailContainer').innerHTML = 
                            '<div class="p-3 text-danger">Error loading feedback detail</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('feedbackDetailContainer').innerHTML = 
                        '<div class="p-3 text-danger">Error loading feedback detail</div>';
                });
        }

        function renderFeedbackDetail(feedback) {
            const container = document.getElementById('feedbackDetailContainer');
            
            container.innerHTML = `
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">${escapeHtml(feedback.subject || 'No Subject')}</h5>
                            <p class="mb-1 text-muted">From: ${escapeHtml(feedback.name || 'Anonymous')} &lt;${escapeHtml(feedback.email)}&gt;</p>
                        </div>
                        <small class="text-muted">${formatDateTime(feedback.submitted_at)}</small>
                    </div>
                    ${feedback.status === 'replied' ? 
                        '<span class="badge bg-success">Replied</span>' : 
                        '<span class="badge bg-warning">New</span>'}
                </div>
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-2">Message:</h6>
                    <div class="bg-white p-3 rounded">${escapeHtml(feedback.message || '').replace(/\n/g, '<br>')}</div>
                </div>
                ${feedback.reply_message ? `
                <div class="p-3 border-bottom bg-light">
                    <h6 class="mb-2">Reply:</h6>
                    <div class="bg-white p-3 rounded">
                        <p class="mb-1 text-muted">Sent: ${formatDateTime(feedback.reply_sent_at)}</p>
                        <div>${escapeHtml(feedback.reply_message || '').replace(/\n/g, '<br>')}</div>
                    </div>
                </div>` : ''}
                <div class="p-3">
                    <button class="btn btn-primary btn-sm" onclick="openReplyComposer(${feedback.id}, '${escapeHtml(feedback.email)}', '${escapeHtml(feedback.subject || '')}')">
                        <i class="bi bi-reply"></i> ${feedback.status === 'replied' ? 'Reply Again' : 'Reply'}
                    </button>
                </div>
            `;
        }

        function openReplyComposer(feedbackId, userEmail, originalSubject) {
            document.getElementById('replyFeedbackId').value = feedbackId;
            document.getElementById('replyToEmail').value = userEmail;
            document.getElementById('replySubject').value = originalSubject.startsWith('Re:') ? originalSubject : `Re: ${originalSubject}`;
            document.getElementById('replyMessage').value = '';
            
            const replyModal = new bootstrap.Modal(document.getElementById('replyComposerModal'));
            replyModal.show();
        }

        function sendReply() {
            const feedbackId = document.getElementById('replyFeedbackId').value;
            const toEmail = document.getElementById('replyToEmail').value;
            const subject = document.getElementById('replySubject').value.trim();
            const message = document.getElementById('replyMessage').value.trim();
            
            if (!subject || !message) {
                alert('Please enter both subject and message');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'send_feedback_reply');
            formData.append('feedback_id', feedbackId);
            formData.append('to_email', toEmail);
            formData.append('subject', subject);
            formData.append('message', message);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Reply sent successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('replyComposerModal')).hide();
                    loadFeedbackList();
                    loadFeedbackDetail(feedbackId);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => alert('❌ Error: ' + error.message));
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString(undefined, { 
                year: 'numeric', month: 'short', day: 'numeric', 
                hour: '2-digit', minute: '2-digit' 
            });
        }

        // Load feedback when modal opens
        document.getElementById('feedbackModal')?.addEventListener('show.bs.modal', function() {
            loadFeedbackList();
        });
    </script>

    <script>
    function openMailConfigModal() {
        // Load current mail config
        fetch('api.php?action=get_mail_config')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('smtpHost').value = data.config.smtp_host || '';
                    document.getElementById('smtpPort').value = data.config.smtp_port || '';
                    document.getElementById('smtpUsername').value = data.config.smtp_username || '';
                    // Password is not returned for security reasons
                    document.getElementById('fromEmail').value = data.config.from_email || '';
                    document.getElementById('fromName').value = data.config.from_name || '';
                }
            })
            .catch(error => {
                console.error('Error loading mail config:', error);
            });
        
        var modal = new bootstrap.Modal(document.getElementById('mailConfigModal'));
        modal.show();
    }

    function saveMailConfig() {
        const config = {
            smtp_host: document.getElementById('smtpHost').value,
            smtp_port: document.getElementById('smtpPort').value,
            smtp_username: document.getElementById('smtpUsername').value,
            smtp_password: document.getElementById('smtpPassword').value,
            from_email: document.getElementById('fromEmail').value,
            from_name: document.getElementById('fromName').value
        };
        
        fetch('api.php?action=save_mail_config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(config)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Mail configuration saved successfully!');
                bootstrap.Modal.getInstance(document.getElementById('mailConfigModal')).hide();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        });
    }
    </script>

    <!-- Privacy Policy Management Modal -->
    <div class="modal fade" id="privacyPolicyModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Privacy Policy Management</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Add/Edit Privacy Policy Form -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Add/Edit Privacy Policy</h6>
                        </div>
                        <div class="card-body">
                            <form id="privacyPolicyForm">
                                <input type="hidden" id="policyId" value="">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control form-control-sm" id="policyTitle" placeholder="Privacy Policy Title" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Version</label>
                                        <input type="text" class="form-control form-control-sm" id="policyVersion" placeholder="1.0" value="1.0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select form-select-sm" id="policyStatus">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Content</label>
                                    <textarea id="policyContentEditor"></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success btn-sm" onclick="savePrivacyPolicy()">
                                        <i class="bi bi-save"></i> Save Policy
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="resetPrivacyPolicyForm()">
                                        <i class="bi bi-x"></i> Reset
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="openVisitorList()">
                                        <i class="bi bi-people"></i> View Visitors
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Policies List -->
                    <div class="card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-list"></i> Existing Privacy Policies</h6>
                            <button class="btn btn-sm btn-primary" onclick="loadPrivacyPolicies()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Version</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="privacyPoliciesTableBody">
                                        <tr><td colspan="7" class="text-center text-muted">Loading policies...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Visibility Management Modal -->
    <div class="modal fade" id="tableVisibilityModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-table"></i> Table Visibility Management</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> Control which tables are visible to users in the dashboard
                    </div>
                    
                    <div id="tableVisibilityAlert"></div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-list"></i> Database Tables</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Table Name</th>
                                            <th>Visibility Status</th>
                                            <th>Show in Dashboard</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableVisibilityBody">
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                                Loading tables...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="saveTableVisibility()">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Privacy Policy Functions
    function openPrivacyPolicyModal() {
        // Initialize Summernote editor
        if (typeof $('#policyContentEditor').summernote === 'function') {
            $('#policyContentEditor').summernote('destroy');
        }
        
        $('#policyContentEditor').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
        
        // Load existing policies
        loadPrivacyPolicies();
        
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('privacyPolicyModal'));
        modal.show();
    }

    function loadPrivacyPolicies() {
        fetch('api.php?action=get_privacy_policies')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderPrivacyPoliciesTable(data.policies);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
    }

    function renderPrivacyPoliciesTable(policies) {
        const tbody = document.getElementById('privacyPoliciesTableBody');
        if (policies.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No privacy policies found.</td></tr>';
            return;
        }
        
        tbody.innerHTML = policies.map(policy => `
            <tr>
                <td>${policy.id}</td>
                <td>${escapeHtml(policy.title)}</td>
                <td>${escapeHtml(policy.version)}</td>
                <td><span class="badge ${policy.status === 'active' ? 'bg-success' : 'bg-secondary'}">${policy.status}</span></td>
                <td>${formatDate(policy.created_at)}</td>
                <td>${formatDate(policy.updated_at)}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-action" onclick="editPrivacyPolicy(${policy.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-${policy.status === 'active' ? 'secondary' : 'success'} btn-action" onclick="togglePrivacyPolicyStatus(${policy.id}, '${policy.status}')" title="${policy.status === 'active' ? 'Deactivate' : 'Activate'}">
                        <i class="bi ${policy.status === 'active' ? 'bi-eye-slash' : 'bi-eye'}"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-action" onclick="deletePrivacyPolicy(${policy.id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function editPrivacyPolicy(policyId) {
        fetch('api.php?action=get_privacy_policies')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const policy = data.policies.find(p => p.id == policyId);
                    if (policy) {
                        document.getElementById('policyId').value = policy.id;
                        document.getElementById('policyTitle').value = policy.title;
                        document.getElementById('policyVersion').value = policy.version;
                        document.getElementById('policyStatus').value = policy.status;
                        $('#policyContentEditor').summernote('code', policy.content);
                    }
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            });
    }

    function savePrivacyPolicy() {
        const formData = new FormData();
        formData.append('policy_id', document.getElementById('policyId').value);
        formData.append('title', document.getElementById('policyTitle').value);
        formData.append('version', document.getElementById('policyVersion').value);
        formData.append('status', document.getElementById('policyStatus').value);
        formData.append('content', $('#policyContentEditor').summernote('code'));
        
        fetch('api.php?action=save_privacy_policy', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Privacy policy saved successfully!');
                resetPrivacyPolicyForm();
                loadPrivacyPolicies();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        });
    }

    function deletePrivacyPolicy(policyId) {
        if (!confirm('Are you sure you want to delete this privacy policy?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('policy_id', policyId);
        
        fetch('api.php?action=delete_privacy_policy', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Privacy policy deleted successfully!');
                loadPrivacyPolicies();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        });
    }

    function togglePrivacyPolicyStatus(policyId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        const formData = new FormData();
        formData.append('policy_id', policyId);
        formData.append('status', newStatus);
        
        fetch('api.php?action=toggle_privacy_policy_status', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Privacy policy status updated successfully!');
                loadPrivacyPolicies();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Error: ' + error.message);
        });
    }

    function resetPrivacyPolicyForm() {
        document.getElementById('policyId').value = '';
        document.getElementById('policyTitle').value = '';
        document.getElementById('policyVersion').value = '1.0';
        document.getElementById('policyStatus').value = 'active';
        $('#policyContentEditor').summernote('code', '');
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    function openVisitorList() {
        // Create visitor list modal dynamically if it doesn't exist
        if (!document.getElementById('visitorListModal')) {
            const modalHtml = `
                <div class="modal fade" id="visitorListModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-dark text-white">
                                <h5 class="modal-title"><i class="bi bi-people"></i> Visitor List</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="card">
                                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="bi bi-list"></i> Recent Visitors</h6>
                                        <button class="btn btn-sm btn-light" onclick="loadVisitorData()">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th>Username</th>
                                                        <th>Action</th>
                                                        <th>IP Address</th>
                                                        <th>Location</th>
                                                        <th>Date & Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="visitorDataTable">
                                                    <tr><td colspan="5" class="text-center text-muted">Loading visitor data...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        
        // Load visitor data
        loadVisitorData();
        
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('visitorListModal'));
        modal.show();
    }
    
    function loadVisitorData() {
        // Use absolute path to avoid path issues
        const baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
        fetch(baseUrl + 'public_api.php?action=get_visitor_data')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderVisitorTable(data.visitors);
                } else {
                    document.getElementById('visitorDataTable').innerHTML = '<tr><td colspan="5" class="text-center text-danger">❌ Error: ' + data.message + '</td></tr>';
                }
            })
            .catch(error => {
                document.getElementById('visitorDataTable').innerHTML = '<tr><td colspan="5" class="text-center text-danger">❌ Error: ' + error.message + '</td></tr>';
            });
    }
    
    function renderVisitorTable(visitors) {
        const tbody = document.getElementById('visitorDataTable');
        if (visitors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No visitor data found.</td></tr>';
            return;
        }
        
        tbody.innerHTML = visitors.map(visitor => `
            <tr>
                <td>${escapeHtml(visitor.username || 'Guest')}</td>
                <td>${escapeHtml(visitor.action)}</td>
                <td>${escapeHtml(visitor.ip_address)}</td>
                <td>${escapeHtml(visitor.location || 'Unknown')}</td>
                <td>${formatDate(visitor.created_at)}</td>
            </tr>
        `).join('');
    }
    
    function showTotalVisitors() {
        // Show the detailed visitor list directly with IP addresses and location
        openVisitorList();
    }
    
    // Table Visibility Management Functions
    function openTableVisibilityModal() {
        // Show the modal
        var modal = new bootstrap.Modal(document.getElementById('tableVisibilityModal'));
        modal.show();
        
        // Load table data
        loadTableVisibilityData();
    }
    
    function loadTableVisibilityData() {
        // Clear any previous alerts
        document.getElementById('tableVisibilityAlert').innerHTML = '';
        
        // Show loading state
        document.getElementById('tableVisibilityBody').innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    Loading tables...
                </td>
            </tr>
        `;
        
        // Fetch table data
        fetch('api/table_visibility.php?action=get_tables')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTableVisibilityTable(data.tables);
                } else {
                    document.getElementById('tableVisibilityBody').innerHTML = `
                        <tr>
                            <td colspan="3" class="text-center text-danger">
                                Error loading tables: ${data.message}
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('tableVisibilityBody').innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-danger">
                            Error loading tables: ${error.message}
                        </td>
                    </tr>
                `;
            });
    }
    
    function renderTableVisibilityTable(tables) {
        const tbody = document.getElementById('tableVisibilityBody');
        
        if (tables.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        No tables found in the database
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = tables.map(table => `
            <tr>
                <td class="align-middle">${escapeHtml(table.name)}</td>
                <td class="align-middle">
                    ${table.visible ? 
                        '<span class="badge bg-success"><i class="bi bi-eye"></i> Visible</span>' : 
                        '<span class="badge bg-danger"><i class="bi bi-eye-slash"></i> Hidden</span>'}
                </td>
                <td class="align-middle">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" 
                               id="visibility_${table.name}" 
                               ${table.visible ? 'checked' : ''}>
                        <label class="form-check-label" for="visibility_${table.name}">
                            Show this table
                        </label>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    function saveTableVisibility() {
        // Collect form data
        const formData = new FormData();
        formData.append('action', 'update_visibility');
        
        // Get all checkboxes
        const checkboxes = document.querySelectorAll('#tableVisibilityBody input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            const tableName = checkbox.id.replace('visibility_', '');
            formData.append(`visibility_${tableName}`, checkbox.checked ? '1' : '0');
        });
        
        // Send update request
        fetch('api/table_visibility.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                document.getElementById('tableVisibilityAlert').innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Reload table data to reflect changes
                loadTableVisibilityData();
            } else {
                // Show error message
                document.getElementById('tableVisibilityAlert').innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> Error: ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            }
        })
        .catch(error => {
            // Show error message
            document.getElementById('tableVisibilityAlert').innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> Error: ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        });
    }
    </script>
</body>
</html>