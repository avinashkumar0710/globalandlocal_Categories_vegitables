<?php
$currentPage = 'user_dashboard';
$pageTitle = 'Dashboard';
require "config/database.php";
require "get_ip.php";
require "visitor_counter.php";
require "includes/header.php";

$db = new Database();
$pdo = $db->getConnection();

// Fetch all tables
$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch visibility settings
$visibilityStmt = $pdo->query("SELECT table_name, is_visible FROM table_visibility");
$visibilitySettings = [];
while ($row = $visibilityStmt->fetch(PDO::FETCH_ASSOC)) {
    $visibilitySettings[$row['table_name']] = (bool)$row['is_visible'];
}

// Filter out hidden tables and sensitive tables
$sensitiveTables = ['activity_log', 'admin_login'];
$tables = array_filter($allTables, function($table) use ($sensitiveTables, $visibilitySettings) {
    // Check if table is sensitive
    if (in_array($table, $sensitiveTables)) {
        return false;
    }
    
    // Check if table visibility is set to false
    if (isset($visibilitySettings[$table]) && !$visibilitySettings[$table]) {
        return false;
    }
    
    return true;
});

// Re-index array
$tables = array_values($tables);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ExportInsight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        'primary-dark': '#4f46e5',
                        secondary: '#8b5cf6',
                        accent: '#06b6d4',
                    }
                }
            }
        }
    </script>
    
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        .page-section {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .page-section.active {
            display: block;
        }

        /* Dashboard active styling */
        .dashboard-active {
            display: block;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f8fafc;
            overflow: hidden;
            z-index: 100;
        }



        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Excel table sticky header */
        .excel-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Ensure table container has proper height for scrolling */
        #tableContainer {
            max-height: calc(100vh - 236px);
            height: 100%;
            flex: 1;
        }
        
        /* Responsive table container heights */
        @media (max-height: 600px) {
            #tableContainer {
                min-height: 250px;
            }
        }
        
        @media (min-height: 601px) and (max-height: 900px) {
            #tableContainer {
                min-height: 350px;
            }
        }
        
        @media (min-height: 901px) {
            #tableContainer {
                min-height: 500px;
            }
        }



        /* Gradient Brand Glow */
/* nav {
    box-shadow: 0 2px 15px rgba(99, 102, 241, 0.22);
} */

/* Nav link active effect */
.nav-link.bg-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
    color: white !important;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
}

/* Sidebar Tabs – colorful hover */
.tab-sidebar-btn {
    transition: 0.25s ease;
}
.tab-sidebar-btn:hover {
    background: linear-gradient(90deg, #4f46e5, #06b6d4) !important;
    border-color: transparent !important;
    color: white !important;
    transform: translateX(3px);
    box-shadow: 0 0 10px rgba(6, 182, 212, 0.4);
}

/* Active sidebar tab */
.tab-sidebar-btn.bg-gray-800 {
    background: linear-gradient(90deg, #6366f1, #8b5cf6) !important;
    border-color: transparent !important;
    box-shadow: 0 0 12px rgba(124, 58, 237, 0.35);
}

/* Feature cards hover boost */
#home .bg-white:hover {
    border-color: #6366f1 !important;
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.15) !important;
    transform: translateY(-3px);
}

/* Dashboard table header gradient */
.excel-table thead th {
    background: linear-gradient(135deg, #6366f1, #06b6d4) !important;
    color: white !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

/* Table hover effect */
.excel-table tbody tr:hover {
    background: rgba(99, 102, 241, 0.08) !important;
}

/* Buttons - gradient style */
.btn-gradient,
.inline-flex.bg-primary {
    background: linear-gradient(135deg, #6366f1, #06b6d4) !important;
    color: white !important;
    border: none !important;
}
.btn-gradient:hover,
.inline-flex.bg-primary:hover {
    background: linear-gradient(135deg, #4f46e5, #0ea5e9) !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
    transform: translateY(-1px);
}

/* Contact form button improvement */
#contact button[type="submit"] {
    background: linear-gradient(130deg, #6366f1, #8b5cf6) !important;
    border-radius: 8px;
}
#contact button[type="submit"]:hover {
    background: linear-gradient(130deg, #4f46e5, #7c3aed) !important;
    transform: translateY(-2px);
}

/* Neon focus for inputs */
input:focus, textarea:focus, select:focus {
    border-color: #06b6d4 !important;
    box-shadow: 0 0 8px rgba(6, 182, 212, 0.5) !important;
}

/* Sidebar search glow */
#tableSearch:focus {
    box-shadow: 0 0 10px rgba(124, 58, 237, 0.6) !important;
}

/* 'Load more' indicator glow */
#loadingIndicator span {
    color: #6366f1 !important;
    font-weight: bold;
}

/* Dark Mode */
.dark body {
    background: #0f172a !important;
    color: #e2e8f0 !important;
}
.dark nav {
    background: #1e293b !important;
    border-bottom-color: #334155 !important;
}

.dark .page-section {
    background: #0f172a !important;
}
.dark .bg-white {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}
.dark .text-gray-800 { color: #e2e8f0 !important; }
.dark .text-gray-700 { color: #cbd5e1 !important; }
.dark .text-gray-600 { color: #94a3b8 !important; }
.dark .border-gray-300 { border-color: #475569 !important; }
.dark .border-gray-200 { border-color: #334155 !important; }
.dark .bg-gray-100 { background: #1e293b !important; }
.dark .bg-gray-50 { background: #1e293b !important; }
.dark input, .dark textarea {
    background: #1e293b !important;
    color: #e2e8f0 !important;
}

/* Icon animations */
.icon-bounce:hover {
    animation: iconBounce 0.4s ease;
}
@keyframes iconBounce {
    0% { transform: translateY(0); }
    40% { transform: translateY(-5px); }
    60% { transform: translateY(2px); }
    100% { transform: translateY(0); }
}

.icon-rotate:hover {
    animation: iconRotate 0.5s linear;
}
@keyframes iconRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Slow pulse for logo */
.animate-pulse-slow {
    animation: pulseSlow 3s infinite;
}
@keyframes pulseSlow {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.07); }
}
/* ---------------------------
   UI: Skeleton, Sidebar, BackTop, Borders
   Paste at END of <style> tag
   --------------------------- */

/* --- Multi-color animated skeleton --- */
.skeleton {
  display: grid;
  gap: 12px;
  padding: 12px;
}
.skeleton .line {
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(90deg, #e6edf8, #f7f2ff, #e6f8f6);
  background-size: 300% 100%;
  animation: skeletonshine 2.2s linear infinite;
}
.skeleton .line.short { width: 20%; }
.skeleton .line.mid   { width: 55%; }
.skeleton .line.full  { width: 100%; min-height: 14px; }
@keyframes skeletonshine {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* --- Sidebar collapse --- */
#leftSidebar {
  width: 18rem; /* default */
  transition: width 0.28s ease, transform 0.28s ease;
  overflow: hidden;
}
#leftSidebar.collapsed {
  width: 4.25rem;
}
#leftSidebar .tab-sidebar-btn span { transition: opacity 0.18s; }
#leftSidebar.collapsed .tab-sidebar-btn span { opacity: 0; pointer-events: none; }

/* collapse icon style */
#sidebarCollapseBtn {
  width: 36px;
  height: 36px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:8px;
}

/* small labels under logo when collapsed */
#leftSidebar.collapsed .sidebar-brand-text { display:none; }

/* --- Gradient animated borders for cards --- */
.gradient-border {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
}
.gradient-border::before {
  content: '';
  position: absolute;
  inset: -2px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4, #f97316);
  z-index: -1;
  filter: blur(10px);
  opacity: 0.9;
  transform: scale(1.02);
  animation: borderShift 5s linear infinite;
}
.gradient-border > * { position: relative; z-index: 1; }
@keyframes borderShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* --- Back to top bubble --- */
#backToTop {
  position: fixed;
  bottom: 92px;
  right: 24px;
  width: 48px;
  height: 48px;
  display: none;
  align-items:center;
  justify-content:center;
  border-radius: 999px;
  background: linear-gradient(135deg, #06b6d4, #6366f1);
  color: #fff;
  box-shadow: 0 8px 20px rgba(99,102,241,0.2);
  cursor:pointer;
  z-index: 9999;
  transition: transform 0.18s ease, opacity 0.18s;
}
#backToTop.show { display:flex; transform: translateY(0); opacity:1; }

/* --- Advanced search controls layout --- */
.advanced-filters {
  display:flex;
  gap:8px;
  align-items:center;
  flex-wrap:wrap;
}
.advanced-filters .filter {
  min-width: 140px;
  max-width: 240px;
}

/* responsive smaller filter inputs */
@media (max-width: 768px) {
  .advanced-filters { gap:6px; }
  #leftSidebar { display: none; } /* keep mobile simple — optional */
}


    </style>
</head>
<body class="bg-gray-100">

    <!-- Main Content -->
    <div class="mt-20">
        
        <?php if (true): // Placeholder for admin check ?>
        <!-- Admin Controls -->
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="bg-white rounded-lg shadow-sm p-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Table Management</h2>
                <a href="admin_tables.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="bi bi-gear mr-2"></i> Manage Table Visibility
                </a>
            </div>
        </div>
        <?php endif; ?>
        <!-- Dashboard Section -->
        <section id="dashboard" class="dashboard-active">
            <div class="h-full">
                <?php if (!empty($tables)): ?>
                    <div class="flex h-full">
                        <!-- Left Sidebar with Tabs -->
                        <div id="leftSidebar" class="w-72 bg-gray-900 border-r border-gray-700 flex flex-col">
                            <!-- Sidebar Header -->
                            <div class="bg-gray-800 text-white px-4 py-3 flex items-center gap-2 border-b border-gray-700">
                                <i class="bi bi-folder2-open text-base"></i>
                                <span class="font-bold text-xs">Tables (<?php echo count($tables); ?>)</span>
                            </div>

                            <!-- Search Box -->
                            <div class="p-2 border-b border-gray-700">
                                <div class="relative">
                                    <i class="bi bi-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                    <input 
                                        type="text" 
                                        id="tableSearch" 
                                        placeholder="Search tables..." 
                                        class="w-full pl-7 pr-3 py-1.5 text-xs border border-gray-600 rounded focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent bg-gray-800 text-white"
                                        onkeyup="filterTables()"
                                    >
                                </div>
                            </div>

                            <!-- Tabs List -->
                            <div class="flex-1 overflow-y-auto p-2 custom-scrollbar">
                                <?php foreach ($tables as $index => $table): ?>
                                    <button 
                                        class="tab-sidebar-btn w-full flex items-center gap-2 px-3 py-2 mb-1.5 bg-gray-800 border border-gray-700 rounded text-gray-200 font-normal text-xs hover:border-primary hover:bg-gray-700 hover:text-white transition-all <?php echo $index == 0 ? 'bg-primary text-white border-primary hover:bg-primary-dark' : ''; ?>" 
                                        onclick="loadTableExcel('<?php echo htmlspecialchars($table); ?>')">
                                        <i class="bi bi-table text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($table); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="flex-1 bg-gray-100 flex flex-col overflow-hidden">
                            <div id="tableOutputExcel" class="h-full">
                                <!-- Empty State -->
                                <div class="flex flex-col items-center justify-center h-full text-gray-500">
                                    <i class="bi bi-table text-8xl mb-5"></i>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Select a table to view</h3>
                                    <p class="text-sm">Click on any table from the left sidebar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="max-w-7xl mx-auto px-6 py-10">
                        <div class="bg-white rounded-xl shadow-sm p-20">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="bi bi-database-x text-8xl mb-5"></i>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">No tables found</h3>
                                <p class="text-sm">Your database doesn't have any tables yet</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>



    <script>
        // Global variables for infinite scrolling
        let currentTableData = [];
        let currentTableName = '';
        let currentOffset = 0;
        let rowsLimit = 50;
        let hasMoreData = true;
        let isLoading = false;
        let filteredData = [];

        // Toggle mobile menu
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('flex');
        }

        // Filter tables in sidebar
        function filterTables() {
            const searchInput = document.getElementById('tableSearch');
            const filter = searchInput.value.toLowerCase();
            const tabButtons = document.querySelectorAll('.tab-sidebar-btn');
            
            tabButtons.forEach(button => {
                const tableName = button.querySelector('span').textContent.toLowerCase();
                if (tableName.indexOf(filter) > -1) {
                    button.style.display = '';
                } else {
                    button.style.display = 'none';
                }
            });
        }

        // Filter table data
        function filterTableData() {
            const searchInput = document.getElementById('tableDataSearch');
            if (!searchInput) return;
            
            const filter = searchInput.value.toLowerCase();
            
            // If search is empty, show all data
            if (filter === '') {
                filteredData = currentTableData;
            } else {
                filteredData = currentTableData.filter(row => {
                    return Object.values(row).some(val => 
                        String(val).toLowerCase().includes(filter)
                    );
                });
            }
            
            // Update table with filtered data
            const tableBody = document.getElementById('tableBody');
            tableBody.innerHTML = renderTableRows(filteredData);
            
            // Update row count
            document.getElementById('rowCount').textContent = filteredData.length;
        }

        // Handle table scroll for infinite loading
        function handleTableScroll() {
            const tableContainer = document.getElementById('tableContainer');
            
            // Check if we're near the bottom (within 100px)
            if (tableContainer && tableContainer.scrollTop + tableContainer.clientHeight >= tableContainer.scrollHeight - 100) {
                console.log('Near bottom - loading more data');
                loadMoreData();
            }
        }

        // Load more data for infinite scrolling
        function loadMoreData() {
            // Don't load if already loading or no more data
            if (isLoading || !hasMoreData) return;
            
            console.log('Loading more data - offset:', currentOffset, 'limit:', rowsLimit, 'table:', currentTableName);
            
            isLoading = true;
            // currentOffset is already set to the correct value, don't increment before the fetch
            const nextOffset = currentOffset + rowsLimit;
            
            // Show loading indicator
            const loadingIndicator = document.getElementById('loadingIndicator');
            if (loadingIndicator) {
                loadingIndicator.classList.remove('hidden');
            }
            
            // Fetch more data
            fetch(`load_table.php?table=${encodeURIComponent(currentTableName)}&format=json&offset=${nextOffset}&limit=${rowsLimit}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success && data.data) {
                        // Append new data to existing data
                        currentTableData = [...currentTableData, ...data.data];
                        
                        // Update table body with new rows
                        const tableBody = document.getElementById('tableBody');
                        if (tableBody) {
                            tableBody.innerHTML += renderTableRows(data.data);
                        }
                        
                        // Update row count
                        const rowCountElement = document.getElementById('rowCount');
                        if (rowCountElement) {
                            rowCountElement.textContent = currentTableData.length;
                        }
                        
                        // Update hasMore flag
                        hasMoreData = data.hasMore;
                        
                        // Update currentOffset for next load
                        currentOffset = nextOffset;
                        
                        // Hide loading indicator
                        if (loadingIndicator) {
                            loadingIndicator.classList.add('hidden');
                        }
                        
                        // Show "no more data" message if applicable
                        if (!hasMoreData) {
                            const noMoreDataElement = document.getElementById('noMoreData');
                            if (noMoreDataElement) {
                                noMoreDataElement.classList.remove('hidden');
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading more data:', error);
                    if (loadingIndicator) {
                        loadingIndicator.classList.add('hidden');
                    }
                    // Show error message
                    const tableContainer = document.getElementById('tableContainer');
                    if (tableContainer) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'text-center py-4 text-red-500';
                        errorMsg.innerHTML = '<i class="bi bi-exclamation-triangle-fill mr-2"></i>Error loading more data';
                        tableContainer.appendChild(errorMsg);
                    }
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        // Load table with Excel-like view
        function loadTableExcel(tableName) {
            const output = document.getElementById("tableOutputExcel");
            
            // Update active tab
            document.querySelectorAll('.tab-sidebar-btn').forEach(btn => {
                btn.classList.remove('bg-gray-800', 'text-white', 'border-gray-800', 'hover:bg-gray-900', 'hover:text-white');
                btn.classList.add('bg-white', 'text-gray-700', 'hover:border-primary', 'hover:bg-gray-50', 'hover:text-primary');
            });
            event.target.closest('.tab-sidebar-btn').classList.remove('bg-white', 'text-gray-700', 'hover:border-primary', 'hover:bg-gray-50', 'hover:text-primary');
            event.target.closest('.tab-sidebar-btn').classList.add('bg-gray-800', 'text-white', 'border-gray-800', 'hover:bg-gray-900', 'hover:text-white');

            // Reset infinite scroll variables
            currentTableName = tableName;
            currentOffset = 0;
            hasMoreData = true;
            isLoading = false;

            // Show loading state
            output.innerHTML = `
                <div class='flex flex-col items-center justify-center h-full'>
                    <div class='spinner'></div>
                    <div class='mt-4 text-sm text-gray-600'>Loading ${tableName}...</div>
                </div>
            `;

            // Fetch table data
            fetch(`load_table.php?table=${encodeURIComponent(tableName)}&format=json&offset=0&limit=${rowsLimit}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (data.success && data.data) {
                        currentTableData = data.data;
                        filteredData = data.data;
                        displayTableWithInfiniteScroll(tableName, data.headers, data.data, data.total, data.hasMore);
                    } else {
                        throw new Error('No data received');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to HTML parsing if JSON not available
                    fetch(`load_table.php?table=${encodeURIComponent(tableName)}`)
                        .then(res => res.text())
                        .then(html => {
                            parseAndDisplayTable(tableName, html);
                        })
                        .catch(err => {
                            output.innerHTML = `
                                <div class='flex items-center justify-center h-full'>
                                    <div class='bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg'>
                                        <i class='bi bi-exclamation-triangle-fill mr-2'></i>
                                        Error loading table data. Please try again.
                                    </div>
                                </div>
                            `;
                        });
                });
        }

        // Parse HTML table and display
        function parseAndDisplayTable(tableName, html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const table = tempDiv.querySelector('table');
            
            if (table) {
                // Extract headers
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
                
                // Extract data
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                const data = rows.map(row => {
                    const cells = Array.from(row.querySelectorAll('td'));
                    const rowObj = {};
                    headers.forEach((header, index) => {
                        rowObj[header] = cells[index] ? cells[index].textContent.trim() : '';
                    });
                    return rowObj;
                });
                
                currentTableData = data;
                filteredData = data;
                // For HTML fallback, show all data at once
                displayTableWithInfiniteScroll(tableName, headers, data, data.length, false);
            }
        }

        // Display table with infinite scroll controls
        function displayTableWithInfiniteScroll(tableName, headers, data, totalRows, hasMore) {
            const output = document.getElementById("tableOutputExcel");
            const colCount = headers.length;
            
            output.innerHTML = `
                <!-- Table Info Bar -->
                <div class='bg-gray-200 px-4 py-2 border-b border-gray-300 flex items-center justify-between'>
                    <div class='flex items-center gap-2 font-bold text-gray-800 text-xs'>
                        <i class='bi bi-table text-xs'></i>
                        ${tableName}
                    </div>
                    <div class='flex items-center gap-3 text-xs text-gray-600'>
                        <span class='bg-white px-2 py-1 rounded-full border border-gray-300 font-semibold text-xs'>
                            <i class='bi bi-list-ol text-xs'></i> <span id='rowCount'>${data.length}</span> of ${totalRows} rows
                        </span>
                        <span class='bg-white px-2 py-1 rounded-full border border-gray-300 font-semibold text-xs'>
                            <i class='bi bi-layout-three-columns text-xs'></i> ${colCount} columns
                        </span>
                    </div>
                </div>

                <!-- Search and Controls Bar -->
                <div class='bg-white px-4 py-2 border-b border-gray-300 flex items-center justify-between gap-3'>
                    <!-- Search -->
                    <div class='relative flex-1 max-w-md'>
                        <i class='bi bi-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs'></i>
                        <input 
                            type='text' 
                            id='tableDataSearch' 
                            placeholder='Search in table data...' 
                            class='w-full pl-7 pr-3 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent'
                            onkeyup='filterTableData()'
                        >
                    </div>
                </div>

                <!-- Table Container -->
                <div class='flex-1 overflow-auto custom-scrollbar' id='tableContainer' style='max-height: calc(100vh - 236px);'>
                    <table class='w-full border-collapse text-xs excel-table'>
                        <thead>
                            <tr>
                                ${headers.map(h => `<th class='bg-gradient-to-r from-primary to-secondary text-white font-semibold px-2 py-2 text-left border border-white/20 text-xs'>${h}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody id='tableBody'>
                            ${renderTableRows(data)}
                        </tbody>
                    </table>
                    <div id='loadingIndicator' class='hidden text-center py-3 text-xs'>
                        <div class='spinner inline-block mr-2' style='width: 20px; height: 20px; border-width: 2px;'></div>
                        <span>Loading more data...</span>
                    </div>
                    <div id='noMoreData' class='hidden text-center py-3 text-gray-500 text-xs'>
                        <i class='bi bi-check-circle-fill text-green-500 mr-1 text-xs'></i>
                        All data loaded
                    </div>
                </div>
            `;
            
            // Add scroll event listener for infinite scrolling
            // Use setTimeout to ensure the DOM is fully updated
            setTimeout(() => {
                const tableContainer = document.getElementById('tableContainer');
                if (tableContainer) {
                    // Remove any existing event listeners to prevent duplicates
                    tableContainer.removeEventListener('scroll', handleTableScroll);
                    // Add the scroll event listener
                    tableContainer.addEventListener('scroll', handleTableScroll);
                }
            }, 0);
        }

        // Render table rows
        function renderTableRows(data) {
            if (data.length === 0) {
                return `
                    <tr>
                        <td colspan="100" class="text-center py-6 text-gray-500 text-xs">
                            <i class="bi bi-search text-3xl block mb-1"></i>
                            No matching records found
                        </td>
                    </tr>
                `;
            }
            
            return data.map((row, index) => {
                const bgColor = index % 2 === 0 ? 'bg-white' : 'bg-gray-100';
                const cells = Object.values(row).map(val => 
                    `<td class="px-2 py-1.5 border border-gray-300 text-xs text-gray-700">${val !== null ? val : 'NULL'}</td>`
                ).join('');
                return `<tr class="${bgColor} hover:bg-blue-50">${cells}</tr>`;
            }).join('');
        }

        // Auto-load first table on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($tables)): ?>
            // Auto-load first table
            const firstTabBtn = document.querySelector('.tab-sidebar-btn');
            if (firstTabBtn) {
                firstTabBtn.click();
            }
            <?php endif; ?>
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobileMenu');
            const menuToggle = document.getElementById('menuToggle');
            
            if (mobileMenu && menuToggle && 
                !mobileMenu.contains(event.target) && 
                !menuToggle.contains(event.target) &&
                mobileMenu.classList.contains('flex')) {
                toggleMenu();
            }
        });

        // DARK MODE TOGGLE
const darkToggle = document.getElementById("darkModeToggle");
const html = document.documentElement;

// Load preference
if (localStorage.getItem("darkMode") === "enabled") {
    html.classList.add("dark");
    darkToggle.innerHTML = `<i class="bi bi-brightness-high-fill text-base"></i>`;
}

// Toggle theme
darkToggle.addEventListener("click", () => {
    html.classList.toggle("dark");

    if (html.classList.contains("dark")) {
        localStorage.setItem("darkMode", "enabled");
        darkToggle.innerHTML = `<i class="bi bi-brightness-high-fill text-base"></i>`;
    } else {
        localStorage.removeItem("darkMode");
        darkToggle.innerHTML = `<i class="bi bi-moon-fill text-base"></i>`;
    }
});

/* ---------- BACK TO TOP ---------- */
const backToTop = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
        backToTop?.classList.add('show');
    } else {
        backToTop?.classList.remove('show');
    }
});
backToTop?.addEventListener('click', () => {
    window.scrollTo({top: 0, behavior: 'smooth'});
});

    </script>
    <!-- Back to top bubble -->
<div id="backToTop" title="Back to top">
    <i class="bi bi-arrow-up-short text-2xl"></i>
</div>

</body>
</html>