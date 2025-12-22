<?php
$currentPage = 'chapters';
$pageTitle = "HS Chapters";
require "config/database.php";

// Read chapters from JSON file
$chaptersFile = __DIR__ . '/data/chapters.json';
$chapters = [];

if (file_exists($chaptersFile)) {
    $chapters = json_decode(file_get_contents($chaptersFile), true) ?: [];
}
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
        .btn-gradient {
            background: linear-gradient(135deg, #6366f1, #06b6d4);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #4f46e5, #0891b2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }

        /* Back to Top Button */
        #backToTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            border: none;
            outline: none;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        #backToTop.visible {
            opacity: 1;
            transform: translateY(0);
        }

        #backToTop:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.5);
        }

        /* Floating Food Items (Global) */
        .floating-food {
            position: fixed;
            opacity: 0.15;
            animation: floatFood 8s ease-in-out infinite;
            pointer-events: none;
            z-index: 1;
        }

        @keyframes floatFood {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(15deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        .floating-food:nth-child(1) { animation-duration: 7s; animation-delay: 0s; }
        .floating-food:nth-child(2) { animation-duration: 9s; animation-delay: 1s; }
        .floating-food:nth-child(3) { animation-duration: 6s; animation-delay: 2s; }
        .floating-food:nth-child(4) { animation-duration: 8s; animation-delay: 1.5s; }
        .floating-food:nth-child(5) { animation-duration: 10s; animation-delay: 2.5s; }
        .floating-food:nth-child(6) { animation-duration: 7.5s; animation-delay: 3s; }
        .floating-food:nth-child(7) { animation-duration: 9.5s; animation-delay: 0.5s; }
        .floating-food:nth-child(8) { animation-duration: 8.5s; animation-delay: 4s; }

        /* Fade-in Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1.2s ease forwards;
        }
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Wavy Divider */
        .wave-divider {
            width: 100%;
            opacity: 0.9;
            margin: 40px 0;
        }

        /* Feature Card Hover */
        .feature-card {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 1);
        }

        /* HS Chapter Cards */
        .chapter-card {
            transition: 0.3s;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        .chapter-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.18);
            background: rgba(255, 255, 255, 1);
        }

        /* Ensure content always stays above floating icons */
        .page-content {
            position: relative;
            z-index: 10;
        }

        /* Section Backgrounds */
        section {
            position: relative;
            z-index: 10;
        }

        /* Footer Fix */
        footer {
            position: relative;
            z-index: 10;
            background: rgba(31, 41, 55, 0.95);
            backdrop-filter: blur(10px);
            margin-top: 60px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-cyan-50 min-h-screen">
    <?php require "includes/header.php"; ?>
    
    <!-- ==================== GLOBAL FLOATING ICONS ===================== -->
    <div id="global-floating-icons">
        <img src="assets/food/onion.png" class="floating-food" style="top:8%; left:5%; width:65px;">
        <img src="assets/food/mango.png" class="floating-food" style="top:25%; left:12%; width:70px;">
        <img src="assets/food/grapes.png" class="floating-food" style="top:50%; left:7%; width:68px;">
        <img src="assets/food/fish.png" class="floating-food" style="top:70%; left:10%; width:75px;">
        <img src="assets/food/spices.png" class="floating-food" style="top:15%; right:8%; width:72px;">
        <img src="assets/food/wheat.png" class="floating-food" style="top:40%; right:12%; width:80px;">
        <img src="assets/food/honey.png" class="floating-food" style="top:65%; right:6%; width:70px;">
        <img src="assets/food/onion.png" class="floating-food" style="top:85%; right:15%; width:65px;">
        <img src="assets/food/mango.png" class="floating-food" style="top:30%; left:50%; width:70px;">
        <img src="assets/food/grapes.png" class="floating-food" style="top:75%; left:45%; width:68px;">
    </div>

    <!-- ====================== PAGE WRAPPER ======================= -->
    <div class="page-content max-w-7xl mx-auto px-6 py-10">
        <!-- ====================== HERO SECTION ======================= -->
        <section id="chapters-hero" class="fade-in text-center mb-16">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-6">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-cyan-500">
                    📚 HS Chapters Explorer
                </span>
            </h1>
            <p class="text-xl text-gray-700 max-w-3xl mx-auto leading-relaxed">
                Browse through all Harmonized System (HS) chapters to explore detailed trade intelligence, 
                market analysis, and export opportunities for each commodity category.
            </p>
        </section>

        <!-- ====================== CHAPTERS GRID ======================= -->
        <section id="all-chapters" class="fade-in">
            <h2 class="text-4xl font-bold text-gray-900 text-center mb-12">
                All HS Chapters
            </h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                // Icons for different chapters
                $icons = ["🥩", "🐟", "🥛", "🥚", "🌿", "🌾", "🥕", "🍎", "🌶️", "🛢️", "🍪", "📦"];
                
                foreach ($chapters as $index => $chapter) {
                    $icon = $icons[$index % count($icons)];
                    // Add icon to chapter data for modal
                    $chapter['icon'] = $icon;
                    echo "
                    <div class='chapter-card p-8 rounded-xl shadow-lg cursor-pointer hover:shadow-xl transition-all duration-300' onclick='openChapterModal(" . json_encode($chapter) . ")'>
                        <div class='text-5xl mb-4 text-center'>{$icon}</div>
                        <h3 class='font-bold text-gray-800 mb-3 text-xl text-center'>" . htmlspecialchars($chapter['name']) . "</h3>
                        <p class='text-gray-600 text-center'>
                            Explore detailed trade intelligence, market analysis, and export opportunities for this chapter.
                        </p>
                        <div class='mt-6 text-center'>
                            <button class='bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-full font-medium hover:from-purple-700 hover:to-indigo-700 transition-all duration-300'>
                                Explore Chapter
                            </button>
                        </div>
                    </div>";
                }
                
                // If no chapters found, show a message
                if (empty($chapters)) {
                    echo "
                    <div class='col-span-full text-center py-12'>
                        <i class='bi bi-file-earmark-text text-5xl text-gray-400 mb-4'></i>
                        <h3 class='text-2xl font-bold text-gray-700 mb-2'>No Chapters Available</h3>
                        <p class='text-gray-600'>Chapters will be added soon. Please check back later.</p>
                    </div>";
                }
                ?>
            </div>
        </section>

        <!-- ====================== ACCESS DASHBOARD ======================= -->
        <section id="access-dashboard" class="fade-in mt-20 text-center">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8 max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Explore Trade Data</h2>
                <p class="text-gray-700 mb-6">
                    Access detailed trade intelligence, market analysis, and export opportunities through our dashboard.
                </p>
                <a href="user_dashboard.php" class="inline-flex items-center bg-gradient-to-r from-cyan-500 to-blue-500 text-white px-6 py-3 rounded-full font-medium hover:from-cyan-600 hover:to-blue-600 transition-all duration-300 shadow-lg">
                    <i class="bi bi-speedometer2 mr-2"></i>
                    Access Dashboard
                </a>
            </div>
        </section>
    </div>

    <!-- Chapter Modal -->
    <div id="chapter-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeChapterModal()"></div>
        <div id="chapter-modal-content" class="absolute right-0 top-0 h-full w-full md:w-1/2 bg-white shadow-2xl transform transition-transform duration-300 translate-x-full">
            <div class="h-full overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Chapter Details</h3>
                    <button onclick="closeChapterModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="bi bi-x-lg text-2xl"></i>
                    </button>
                </div>
                <div id="chapter-modal-body" class="p-6">
                    <!-- Chapter content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php require "includes/footer.php"; ?>
    
    <!-- Back to Top Button -->
    <button id="backToTop" title="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script>
        // Open chapter modal with chapter details
        function openChapterModal(chapter) {
            const modal = document.getElementById('chapter-modal');
            const modalContent = document.getElementById('chapter-modal-content');
            const modalBody = document.getElementById('chapter-modal-body');
            
            // Show loading state
            modalBody.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-600 mb-4"></div>
                    <p class="text-gray-600">Loading chapter details...</p>
                </div>
            `;
            
            // Show modal
            modal.classList.remove('hidden');
            
            // Trigger slide-in animation
            setTimeout(() => {
                modalContent.classList.remove('translate-x-full');
            }, 10);
            
            // Fetch chapter documents data
            fetch(`api/chapter_data.php?chapter_id=${chapter.id}`)
                .then(response => response.json())
                .then(data => {
                    // Populate modal with chapter details
                    let documentsHtml = '';
                    if (data.success && data.documents.length > 0) {
                        documentsHtml = `
                            <div class="mb-6">
                                <h3 class="text-lg font-bold text-gray-800 mb-3">Available Documents</h3>
                                <div class="space-y-3">
                        `;
                        
                        // Load document content immediately for each document
                        data.documents.forEach(doc => {
                            documentsHtml += `
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                    <div class="flex items-start">
                                        <i class="bi bi-file-earmark-text text-purple-600 text-xl mr-3 mt-1"></i>
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-800">${doc.title}</h4>
                                            <p class="text-sm text-gray-600 mt-1">Uploaded on ${doc.uploaded_at}</p>
                                            <div class="document-content mt-3 border-t border-gray-100 pt-3">
                                                <div class="text-center py-2">
                                                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-purple-600"></div>
                                                    <p class="text-gray-600 text-sm mt-2">Loading document content...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        // After adding documents to DOM, load their content
                        setTimeout(() => {
                            loadAllDocumentContents(data.documents);
                        }, 100);
                        
                        documentsHtml += `</div></div>`;
                    } else {
                        documentsHtml = `
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start">
                                    <i class="bi bi-info-circle text-yellow-600 text-xl mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-medium text-yellow-800">No documents available</h4>
                                        <p class="text-sm text-yellow-700 mt-1">Documents for this chapter will be added soon.</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    modalBody.innerHTML = `
                        <div class="text-center mb-6">
                            <div class="text-6xl mb-4">${chapter.icon || getChapterIcon(chapter.id)}</div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">${chapter.name}</h2>
                            <p class="text-gray-600">Created on ${chapter.created_at}</p>
                        </div>
                        
                        ${documentsHtml}
                        
                        <div class="text-center mt-8">
                            <a href="user_dashboard.php" class="inline-flex items-center bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-3 rounded-full font-medium hover:from-purple-700 hover:to-indigo-700 transition-all duration-300 shadow-lg">
                                <i class="bi bi-speedometer2 mr-2"></i>
                                Access Full Dashboard
                            </a>
                            <p class="text-gray-600 mt-3 text-sm">View detailed analytics and trade data for this chapter</p>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error fetching chapter data:', error);
                    modalBody.innerHTML = `
                        <div class="text-center py-12">
                            <i class="bi bi-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Error Loading Data</h3>
                            <p class="text-gray-600 mb-4">Failed to load chapter details. Please try again.</p>
                            <button onclick="closeChapterModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-medium">
                                Close
                            </button>
                        </div>
                    `;
                });
        }
        
        // Load all document contents
        function loadAllDocumentContents(documents) {
            documents.forEach((doc, index) => {
                // Get the content container for this document
                const documentContainers = document.querySelectorAll('.document-content');
                if (documentContainers[index]) {
                    loadSingleDocumentContent(doc.content_file, documentContainers[index]);
                }
            });
        }
        
        // Load single document content
        function loadSingleDocumentContent(filePath, contentContainer) {
            // Fetch document content
            fetch(filePath)
                .then(response => response.text())
                .then(html => {
                    contentContainer.innerHTML = `
                        <div class="prose max-w-none mt-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            ${html}
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error loading document:', error);
                    contentContainer.innerHTML = `
                        <div class="mt-3 p-4 bg-red-50 rounded-lg border border-red-200">
                            <p class="text-red-700">Failed to load document content. Please try again.</p>
                        </div>
                    `;
                });
        }
        
        // Close chapter modal
        function closeChapterModal() {
            const modal = document.getElementById('chapter-modal');
            const modalContent = document.getElementById('chapter-modal-content');
            
            // Trigger slide-out animation
            modalContent.classList.add('translate-x-full');
            
            // Hide modal after animation completes
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
        
        // Get icon for chapter based on ID
        function getChapterIcon(id) {
            const icons = ["🥩", "🐟", "🥛", "🥚", "🌿", "🌾", "🥕", "🍎", "🌶️", "🛢️", "🍪", "📦"];
            return icons[(id - 1) % icons.length];
        }
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeChapterModal();
            }
        });
    </script>
     
    <script>
        // Back to Top Button
        const backToTopButton = document.getElementById("backToTop");
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add("visible");
            } else {
                backToTopButton.classList.remove("visible");
            }
        });
        
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>