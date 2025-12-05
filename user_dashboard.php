<?php
require "config/database.php";
require "get_ip.php";
require "visitor_counter.php";

$db = new Database();
$pdo = $db->getConnection();

// Fetch all tables
$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filter out sensitive tables
$sensitiveTables = ['activity_log', 'admin_login'];
$tables = array_filter($allTables, function($table) use ($sensitiveTables) {
    return !in_array($table, $sensitiveTables);
});

// Re-index array
$tables = array_values($tables);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - Modern Dashboard</title>
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

        .page-section {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .page-section.active {
            display: block;
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

        /* Dashboard full page */
        #dashboard.active {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            bottom: 70px;
            background: #f8fafc;
            overflow: hidden;
            z-index: 100;
        }

        /* Footer nav link hover effect */
        .footer-nav-link {
            position: relative;
        }

        .footer-nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #06b6d4;
            transition: width 0.3s;
        }

        .footer-nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 bg-white shadow-md z-50 h-16">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-full">
            <a href="#" class="flex items-center gap-3 text-primary font-bold text-lg">
                <i class="bi bi-database-fill text-2xl"></i>
                <span>DB Manager</span>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="lg:hidden flex flex-col gap-1 w-8 h-8 justify-center items-center" onclick="toggleMenu()" id="menuToggle">
                <span class="block w-6 h-0.5 bg-gray-800 transition-all"></span>
                <span class="block w-6 h-0.5 bg-gray-800 transition-all"></span>
                <span class="block w-6 h-0.5 bg-gray-800 transition-all"></span>
            </button>

            <!-- Desktop Menu -->
            <ul class="hidden lg:flex items-center gap-2" id="navbarMenu">
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-primary transition-all hover:bg-primary-dark" onclick="showSection('home')">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all" onclick="showSection('dashboard')">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all" onclick="showSection('about')">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>About</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-all" onclick="showSection('contact')">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Contact</span>
                    </a>
                </li>
            </ul>

            <!-- Mobile Menu -->
            <ul class="absolute top-16 left-0 right-0 bg-white shadow-lg p-4 flex-col gap-2 hidden" id="mobileMenu">
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium text-white bg-primary" onclick="showSection('home'); toggleMenu();">
                        <i class="bi bi-house-door-fill"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100" onclick="showSection('dashboard'); toggleMenu();">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100" onclick="showSection('about'); toggleMenu();">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>About</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100" onclick="showSection('contact'); toggleMenu();">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Contact</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="mt-16 mb-[70px]">
        <!-- Home Section -->
        <section id="home" class="page-section active">
            <div class="max-w-7xl mx-auto px-6 py-10">
                <!-- Hero Section -->
                <div class="bg-gradient-to-br from-primary to-secondary rounded-2xl p-20 text-center text-white mb-12 shadow-xl">
                    <h1 class="text-5xl font-extrabold mb-4">Welcome to Database Manager</h1>
                    <p class="text-lg mb-8 opacity-95">Efficiently manage and visualize your database tables with our modern, intuitive platform</p>
                    <button class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary rounded-lg font-semibold text-sm hover:shadow-lg transform hover:-translate-y-1 transition-all" onclick="showSection('dashboard')">
                        <i class="bi bi-speedometer2"></i>
                        Go to Dashboard
                    </button>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                    <!-- Feature Card 1 -->
                    <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transform hover:-translate-y-2 transition-all">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center mb-5">
                            <i class="bi bi-table text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Easy Table Management</h3>
                        <p class="text-gray-600 leading-relaxed">Browse and manage all your database tables with a clean, intuitive interface designed for efficiency.</p>
                    </div>

                    <!-- Feature Card 2 -->
                    <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transform hover:-translate-y-2 transition-all">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center mb-5">
                            <i class="bi bi-lightning-charge-fill text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Fast Performance</h3>
                        <p class="text-gray-600 leading-relaxed">Quickly load and view large datasets with our optimized data loading system and smooth scrolling.</p>
                    </div>

                    <!-- Feature Card 3 -->
                    <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transform hover:-translate-y-2 transition-all">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center mb-5">
                            <i class="bi bi-shield-lock-fill text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Secure Access</h3>
                        <p class="text-gray-600 leading-relaxed">Your data is protected with secure authentication and access controls to keep information safe.</p>
                    </div>

                    <!-- Feature Card 4 -->
                    <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transform hover:-translate-y-2 transition-all">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center mb-5">
                            <i class="bi bi-gear-fill text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Flexible Configuration</h3>
                        <p class="text-gray-600 leading-relaxed">Customize your workspace and viewing preferences to match your workflow requirements.</p>
                    </div>

                    <!-- Feature Card 5 -->
                    <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transform hover:-translate-y-2 transition-all">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center mb-5">
                            <i class="bi bi-graph-up-arrow text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Real-time Updates</h3>
                        <p class="text-gray-600 leading-relaxed">Get instant feedback and live data updates as you work with your database tables.</p>
                    </div>

                    <!-- Feature Card 6 -->
                    <div class="bg-white rounded-xl p-8 shadow-sm hover:shadow-xl transform hover:-translate-y-2 transition-all">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary to-secondary rounded-xl flex items-center justify-center mb-5">
                            <i class="bi bi-people-fill text-white text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Team Collaboration</h3>
                        <p class="text-gray-600 leading-relaxed">Work together seamlessly with your team on database management and data operations.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dashboard Section -->
        <section id="dashboard" class="page-section">
            <div class="h-full">
                <?php if (!empty($tables)): ?>
                    <div class="flex h-full">
                        <!-- Left Sidebar with Tabs -->
                        <div class="w-72 bg-white border-r border-gray-200 flex flex-col">
                            <!-- Sidebar Header -->
                            <div class="bg-gradient-to-r from-primary to-secondary text-white p-5 flex items-center gap-3 border-b border-white/20">
                                <i class="bi bi-folder2-open text-lg"></i>
                                <span class="font-bold text-sm">Tables (<?php echo count($tables); ?>)</span>
                            </div>

                            <!-- Search Box -->
                            <div class="p-3 border-b border-gray-200">
                                <div class="relative">
                                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input 
                                        type="text" 
                                        id="tableSearch" 
                                        placeholder="Search tables..." 
                                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        onkeyup="filterTables()"
                                    >
                                </div>
                            </div>

                            <!-- Tabs List -->
                            <div class="flex-1 overflow-y-auto p-3 custom-scrollbar">
                                <?php foreach ($tables as $index => $table): ?>
                                    <button 
                                        class="tab-sidebar-btn w-full flex items-center gap-3 px-4 py-3 mb-2 bg-white border border-gray-200 rounded-lg text-gray-700 font-medium text-sm hover:border-primary hover:bg-gray-50 hover:text-primary transition-all <?php echo $index == 0 ? 'bg-gray-800 text-white border-gray-800 hover:bg-gray-900 hover:text-white' : ''; ?>" 
                                        onclick="loadTableExcel('<?php echo htmlspecialchars($table); ?>')">
                                        <i class="bi bi-table"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($table); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="flex-1 bg-white flex flex-col overflow-hidden">
                            <div id="tableOutputExcel" class="h-full">
                                <!-- Empty State -->
                                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                    <i class="bi bi-table text-8xl mb-5"></i>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Select a table to view</h3>
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

        <!-- About Section -->
        <section id="about" class="page-section">
            <div class="max-w-5xl mx-auto px-6 py-10">
                <div class="bg-white rounded-xl shadow-sm p-12">
                    <h2 class="flex items-center gap-3 text-4xl font-bold text-gray-800 mb-6">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        About Database Manager
                    </h2>
                    <p class="text-gray-700 text-base leading-relaxed mb-4">
                        Database Manager is a powerful yet simple tool designed to help you easily view and manage 
                        your database tables. Our platform provides an intuitive interface for database administrators 
                        and developers to interact with their data efficiently.
                    </p>
                    <p class="text-gray-700 text-base leading-relaxed mb-4">
                        Built with modern web technologies, Database Manager offers a seamless experience across all 
                        devices, allowing you to access your databases anytime, anywhere. We focus on simplicity, 
                        speed, and reliability to ensure your data management tasks are as smooth as possible.
                    </p>
                    <p class="text-gray-700 text-base leading-relaxed mb-12">
                        Our mission is to democratize database management by making it accessible to everyone, 
                        from beginners to experienced professionals. We believe that managing databases should be 
                        straightforward and enjoyable.
                    </p>

                    <h4 class="text-2xl font-bold text-gray-800 mb-8 mt-12">Our Team</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Team Member 1 -->
                        <div class="text-center">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="John Smith" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-gray-100 shadow-md">
                            <h5 class="text-lg font-bold text-gray-800 mb-1">John Smith</h5>
                            <p class="text-sm text-gray-500">Lead Developer</p>
                        </div>

                        <!-- Team Member 2 -->
                        <div class="text-center">
                            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Sarah Johnson" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-gray-100 shadow-md">
                            <h5 class="text-lg font-bold text-gray-800 mb-1">Sarah Johnson</h5>
                            <p class="text-sm text-gray-500">UI/UX Designer</p>
                        </div>

                        <!-- Team Member 3 -->
                        <div class="text-center">
                            <img src="https://randomuser.me/api/portraits/men/67.jpg" alt="Michael Brown" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-gray-100 shadow-md">
                            <h5 class="text-lg font-bold text-gray-800 mb-1">Michael Brown</h5>
                            <p class="text-sm text-gray-500">Database Specialist</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="page-section">
            <div class="max-w-3xl mx-auto px-6 py-10">
                <div class="bg-white rounded-xl shadow-sm p-12">
                    <h2 class="flex items-center gap-3 text-4xl font-bold text-gray-800 mb-4">
                        <i class="bi bi-envelope-fill text-primary"></i>
                        Contact Us
                    </h2>
                    <p class="text-gray-600 mb-8">Have questions or feedback? Reach out to us using the form below and we'll get back to you as soon as possible.</p>

                    <form id="contactForm">
                        <!-- Name Field -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-semibold text-gray-800 mb-2">Full Name</label>
                            <input 
                                type="text" 
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                                id="name" 
                                placeholder="Enter your name" 
                                required
                            >
                        </div>

                        <!-- Email Field -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-semibold text-gray-800 mb-2">Email Address</label>
                            <input 
                                type="email" 
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                                id="email" 
                                placeholder="Enter your email" 
                                required
                            >
                        </div>

                        <!-- Subject Field -->
                        <div class="mb-6">
                            <label for="subject" class="block text-sm font-semibold text-gray-800 mb-2">Subject</label>
                            <input 
                                type="text" 
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" 
                                id="subject" 
                                placeholder="What is this regarding?" 
                                required
                            >
                        </div>

                        <!-- Message Field -->
                        <div class="mb-6">
                            <label for="message" class="block text-sm font-semibold text-gray-800 mb-2">Message</label>
                            <textarea 
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-vertical min-h-[120px]" 
                                id="message" 
                                placeholder="Enter your message here..." 
                                required
                            ></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-primary to-secondary text-white rounded-lg font-semibold text-sm hover:shadow-lg transform hover:-translate-y-1 transition-all"
                        >
                            <i class="bi bi-send-fill"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-gradient-to-r from-gray-800 to-gray-900 text-white h-[70px] flex items-center z-50 shadow-2xl border-t border-white/10">
        <div class="max-w-7xl mx-auto px-6 w-full flex items-center justify-between">
            <!-- Left: Brand -->
            <div class="flex items-center gap-3">
                <i class="bi bi-database-fill text-accent text-xl"></i>
                <span class="font-bold text-base text-accent">Database Manager v2.0</span>
            </div>

            <!-- Center: Navigation -->
            <ul class="hidden md:flex items-center gap-6">
                <li>
                    <a href="#" onclick="showSection('home')" class="footer-nav-link flex items-center gap-2 text-sm font-medium text-white/70 hover:text-white transition-all pb-1">
                        <i class="bi bi-house-door-fill"></i>
                        Home
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showSection('dashboard')" class="footer-nav-link flex items-center gap-2 text-sm font-medium text-white/70 hover:text-white transition-all pb-1">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showSection('about')" class="footer-nav-link flex items-center gap-2 text-sm font-medium text-white/70 hover:text-white transition-all pb-1">
                        <i class="bi bi-info-circle-fill"></i>
                        About
                    </a>
                </li>
                <li>
                    <a href="#" onclick="showSection('contact')" class="footer-nav-link flex items-center gap-2 text-sm font-medium text-white/70 hover:text-white transition-all pb-1">
                        <i class="bi bi-envelope-fill"></i>
                        Contact
                    </a>
                </li>
            </ul>

            <!-- Right: Social Links -->
            <div class="flex items-center gap-5">
                <a href="#" class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:text-accent hover:bg-white/10 transition-all transform hover:-translate-y-1">
                    <i class="bi bi-facebook text-lg"></i>
                </a>
                <a href="#" class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:text-accent hover:bg-white/10 transition-all transform hover:-translate-y-1">
                    <i class="bi bi-twitter text-lg"></i>
                </a>
                <a href="#" class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:text-accent hover:bg-white/10 transition-all transform hover:-translate-y-1">
                    <i class="bi bi-linkedin text-lg"></i>
                </a>
                <a href="#" class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-white/70 hover:text-accent hover:bg-white/10 transition-all transform hover:-translate-y-1">
                    <i class="bi bi-github text-lg"></i>
                </a>
            </div>

            <!-- Copyright (Mobile) -->
            <div class="md:hidden text-xs text-white/60">
                &copy; <?php echo date('Y'); ?>
            </div>
        </div>
    </footer>

    <script>
        // Global variables for pagination
        let currentTableData = [];
        let currentPage = 1;
        let rowsPerPage = 10;
        let filteredData = [];

        // Show section function
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.page-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update active nav link (desktop)
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('bg-primary', 'text-white');
                link.classList.add('text-gray-700', 'hover:bg-gray-100');
            });
            
            // Set active class to clicked link
            const clickedLink = event?.target?.closest('.nav-link');
            if (clickedLink && !clickedLink.href?.includes('logout.php')) {
                clickedLink.classList.remove('text-gray-700', 'hover:bg-gray-100');
                clickedLink.classList.add('bg-primary', 'text-white');
            }
        }

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
            filteredData = currentTableData.filter(row => {
                return Object.values(row).some(val => 
                    String(val).toLowerCase().includes(filter)
                );
            });
            
            currentPage = 1;
            renderTable();
        }

        // Change rows per page
        function changeRowsPerPage() {
            const select = document.getElementById('rowsPerPageSelect');
            rowsPerPage = parseInt(select.value);
            currentPage = 1;
            renderTable();
        }

        // Go to specific page
        function goToPage(page) {
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderTable();
        }

        // Render table with pagination
        function renderTable() {
            const dataToShow = filteredData.length > 0 ? filteredData : currentTableData;
            const totalPages = Math.ceil(dataToShow.length / rowsPerPage);
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const pageData = dataToShow.slice(startIndex, endIndex);

            if (dataToShow.length === 0) {
                document.getElementById('tableBody').innerHTML = `
                    <tr>
                        <td colspan="100" class="text-center py-8 text-gray-400">
                            <i class="bi bi-search text-4xl block mb-2"></i>
                            No matching records found
                        </td>
                    </tr>
                `;
                updatePaginationInfo(0, 0, 0);
                return;
            }

            // Render table rows
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = pageData.map(row => {
                const cells = Object.values(row).map(val => 
                    `<td class="px-3 py-2 border border-gray-200 text-xs text-gray-700">${val !== null ? val : 'NULL'}</td>`
                ).join('');
                return `<tr class="hover:bg-blue-50">${cells}</tr>`;
            }).join('');

            // Update pagination info
            updatePaginationInfo(startIndex + 1, Math.min(endIndex, dataToShow.length), dataToShow.length);
        }

        // Update pagination info
        function updatePaginationInfo(start, end, total) {
            const totalPages = Math.ceil(total / rowsPerPage);
            document.getElementById('paginationInfo').textContent = 
                `Showing ${start} to ${end} of ${total} rows`;
            
            // Update pagination buttons
            const paginationButtons = document.getElementById('paginationButtons');
            let buttonsHTML = '';

            // Previous button
            buttonsHTML += `
                <button onclick="goToPage(${currentPage - 1})" 
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                        ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i>
                </button>
            `;

            // Page numbers
            const maxButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxButtons - 1);
            
            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                buttonsHTML += `
                    <button onclick="goToPage(${i})" 
                            class="px-3 py-1.5 text-xs font-medium rounded-lg ${i === currentPage ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'} border">
                        ${i}
                    </button>
                `;
            }

            // Next button
            buttonsHTML += `
                <button onclick="goToPage(${currentPage + 1})" 
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'}"
                        ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="bi bi-chevron-right"></i>
                </button>
            `;

            paginationButtons.innerHTML = buttonsHTML;
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

            // Show loading state
            output.innerHTML = `
                <div class='flex flex-col items-center justify-center h-full'>
                    <div class='spinner'></div>
                    <div class='mt-4 text-sm text-gray-600'>Loading ${tableName}...</div>
                </div>
            `;

            // Fetch table data
            fetch("load_table.php?table=" + encodeURIComponent(tableName) + "&format=json")
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (data.success && data.data) {
                        currentTableData = data.data;
                        filteredData = data.data;
                        currentPage = 1;
                        displayTable(tableName, data.headers, data.data);
                    } else {
                        throw new Error('No data received');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to HTML parsing if JSON not available
                    fetch("load_table.php?table=" + encodeURIComponent(tableName))
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
                currentPage = 1;
                displayTable(tableName, headers, data);
            }
        }

        // Display table with controls
        function displayTable(tableName, headers, data) {
            const output = document.getElementById("tableOutputExcel");
            const rowCount = data.length;
            const colCount = headers.length;
            
            output.innerHTML = `
                <!-- Table Info Bar -->
                <div class='bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center justify-between'>
                    <div class='flex items-center gap-2 font-bold text-gray-800 text-sm'>
                        <i class='bi bi-table'></i>
                        ${tableName}
                    </div>
                    <div class='flex items-center gap-4 text-xs text-gray-600'>
                        <span class='bg-white px-3 py-1 rounded-full border border-gray-200 font-semibold'>
                            <i class='bi bi-list-ol'></i> ${rowCount} rows
                        </span>
                        <span class='bg-white px-3 py-1 rounded-full border border-gray-200 font-semibold'>
                            <i class='bi bi-layout-three-columns'></i> ${colCount} columns
                        </span>
                    </div>
                </div>

                <!-- Search and Controls Bar -->
                <div class='bg-white px-5 py-3 border-b border-gray-200 flex items-center justify-between gap-4'>
                    <!-- Search -->
                    <div class='relative flex-1 max-w-md'>
                        <i class='bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm'></i>
                        <input 
                            type='text' 
                            id='tableDataSearch' 
                            placeholder='Search in table data...' 
                            class='w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent'
                            onkeyup='filterTableData()'
                        >
                    </div>

                    <!-- Rows per page -->
                    <div class='flex items-center gap-2 text-sm'>
                        <label class='text-gray-600 font-medium'>Show:</label>
                        <select id='rowsPerPageSelect' onchange='changeRowsPerPage()' 
                                class='px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary'>
                            <option value='10'>10</option>
                            <option value='25'>25</option>
                            <option value='50'>50</option>
                            <option value='100'>100</option>
                            <option value='${rowCount}'>All</option>
                        </select>
                        <span class='text-gray-600'>entries</span>
                    </div>
                </div>

                <!-- Table Container -->
                <div class='flex-1 overflow-auto custom-scrollbar'>
                    <table class='w-full border-collapse text-xs excel-table'>
                        <thead>
                            <tr>
                                ${headers.map(h => `<th class='bg-gradient-to-r from-primary to-secondary text-white font-semibold px-3 py-3 text-left border border-white/20 text-xs'>${h}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody id='tableBody'>
                            <!-- Populated by renderTable() -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar -->
                <div class='bg-gray-50 px-5 py-3 border-t border-gray-200 flex items-center justify-between'>
                    <div id='paginationInfo' class='text-sm text-gray-600 font-medium'>
                        <!-- Updated by renderTable() -->
                    </div>
                    <div id='paginationButtons' class='flex items-center gap-2'>
                        <!-- Updated by renderTable() -->
                    </div>
                </div>
            `;
            
            renderTable();
        }

        // Contact form submission
        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            alert('Thank you for your message, ' + name + '! We will get back to you at ' + email + ' soon.');
            this.reset();
        });

        // Auto-load first table when dashboard becomes active
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($tables)): ?>
            const firstTable = "<?php echo htmlspecialchars($tables[0]); ?>";
            
            const dashboardObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.target.classList.contains('active') && 
                        mutation.target.id === 'dashboard') {
                        const tableOutput = document.getElementById('tableOutputExcel');
                        if (tableOutput && tableOutput.querySelector('.text-8xl')) {
                            const firstTabBtn = document.querySelector('.tab-sidebar-btn');
                            if (firstTabBtn) {
                                firstTabBtn.click();
                            }
                        }
                    }
                });
            });

            const dashboardSection = document.getElementById('dashboard');
            if (dashboardSection) {
                dashboardObserver.observe(dashboardSection, {
                    attributes: true,
                    attributeFilter: ['class']
                });
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
    </script>
</body>
</html>