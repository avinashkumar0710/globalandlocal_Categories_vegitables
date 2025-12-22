<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ExportInsight'; ?> - Export Intelligence Platform</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom Styles -->
    <style>
        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* max-width: 1280px; */
            margin: 0 auto;
            padding: 0.5rem 1rem;
        }
        
        /* Logo Section - LEFT */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-section img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        
        .company-name {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            white-space: nowrap;
        }
        
        /* Navigation Section - RIGHT */
        .nav-section {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .nav-links {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .nav-link {
            color: #4b5563;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #667eea;
            cursor: pointer;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(10px);
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                gap: 10px;
            }
            
            .nav-links.mobile-open {
                display: flex;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .company-name {
                font-size: 1.2rem;
            }
            
            .logo-section img {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <!-- Logo & Company Name - LEFT SIDE -->
        <div class="logo-section">
            <img src="assets/logo/export-logo.png" alt="ExportInsight Logo">
            <h1 class="company-name">ExportInsight</h1>
        </div>
        
        <!-- Navigation - RIGHT SIDE -->
        <nav class="nav-section">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="nav-links" id="navLinks">
                <a href="home.php" class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>">
                    <i class="bi bi-house-door"></i> Home
                </a>
                <a href="chapters.php" class="nav-link <?php echo $currentPage === 'chapters' ? 'active' : ''; ?>">
                    <i class="bi bi-book"></i> Chapters
                </a>
                <a href="user_dashboard.php" class="nav-link <?php echo $currentPage === 'user_dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up"></i> User Dashboard
                </a>
                
                <a href="about.php" class="nav-link <?php echo $currentPage === 'about' ? 'active' : ''; ?>">
                    <i class="bi bi-info-circle"></i> About
                </a>
                <a href="contact.php" class="nav-link <?php echo $currentPage === 'contact' ? 'active' : ''; ?>">
                    <i class="bi bi-envelope"></i> Contact
                </a>
            </div>
        </nav>
    </div>
</header>

<!-- Mobile Menu Toggle Script -->
<script>
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    
    mobileMenuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('mobile-open');
        const icon = mobileMenuToggle.querySelector('i');
        if (navLinks.classList.contains('mobile-open')) {
            icon.classList.remove('bi-list');
            icon.classList.add('bi-x');
        } else {
            icon.classList.remove('bi-x');
            icon.classList.add('bi-list');
        }
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-section')) {
            navLinks.classList.remove('mobile-open');
            const icon = mobileMenuToggle.querySelector('i');
            icon.classList.remove('bi-x');
            icon.classList.add('bi-list');
        }
    });
</script>