<?php
$currentPage = 'home';
$pageTitle = 'Home';
require "includes/header.php";
?>

<!-- ==================== GLOBAL STYLES ===================== -->
<style>

/* ----------------------------------------------------------
   Animated Full Page Gradient Background
----------------------------------------------------------- */
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    background-size: 400% 400%;
    animation: gradientFlow 18s ease infinite;
    min-height: 100vh;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ----------------------------------------------------------
   Header Layout Fix
----------------------------------------------------------- */
header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header .logo-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

header .nav-section {
    display: flex;
    gap: 20px;
    align-items: center;
}

/* ----------------------------------------------------------
   Floating Food Items (Global)
----------------------------------------------------------- */
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

/* ----------------------------------------------------------
   Hero Section (Static Content)
----------------------------------------------------------- */
.hero-section {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    padding: 60px 80px;
    margin: 40px auto;
    max-width: 100%;
    position: relative;
    z-index: 10;
}

.hero-text {
    font-size: 1.1rem;
    line-height: 1.9;
    color: #374151;
    font-weight: 400;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 30px;
    text-align: center;
}

/* ----------------------------------------------------------
   Fade-in Animation
----------------------------------------------------------- */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 1.2s ease forwards;
}
@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
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

/* Pulse animation for map points */
.animate-pulse {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

</style>

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

    <!-- ====================== HERO SECTION WITH STATIC CONTENT ======================= -->
    <section id="summary" class="fade-in">
        <div class="hero-section">
            <h1 class="hero-title">
                🌍 Welcome to ExportInsight
            </h1>
            <div class="hero-text">
                <p>
                    ExportInsight is a comprehensive export–intelligence platform built to provide chapter-wise insights 
                    for global trade. The platform consolidates global demand, India's export performance, market risks, 
                    import thresholds, and production advantages into actionable intelligence.
                </p>
                <p class="mt-4">
                    Whether you're an exporter, manufacturer, processor, trader, or government agency, the platform 
                    helps you analyze profitable HS codes, avoid compliance risks, identify top markets, and 
                    scale global exports confidently.
                </p>
                <p class="mt-4">
                    With structured analytics across chapters such as meat, seafood, spices, cereals, fruits, vegetables, oils, 
                    processed foods, and industrial goods, ExportInsight simplifies global trade intelligence like never before.
                </p>
            </div>
        </div>
    </section>

    <!-- Divider -->
    

    <!-- ====================== WHY EXPORTINSIGHT ======================= -->
    <section id="why" class="fade-in">
        <h2 class="text-4xl font-bold text-white text-center mb-10 drop-shadow-lg">
            ✨ Why ExportInsight?
        </h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="feature-card p-8 rounded-xl shadow-lg">
                <i class="bi bi-graph-up text-purple-600 text-4xl mb-4 block"></i>
                <h3 class="font-bold text-xl mb-3 text-gray-800">Chapter-Wise Insights</h3>
                <p class="text-gray-600">
                    From HS-01 to HS-97, get structured export intelligence with market size, demand patterns, and forecast potential.
                </p>
            </div>

            <div class="feature-card p-8 rounded-xl shadow-lg">
                <i class="bi bi-shield-check text-purple-600 text-4xl mb-4 block"></i>
                <h3 class="font-bold text-xl mb-3 text-gray-800">Compliance Intelligence</h3>
                <p class="text-gray-600">
                    Avoid rejections with country-specific MRLs, microbiological limits, pesticide thresholds, and banned substances.
                </p>
            </div>

            <div class="feature-card p-8 rounded-xl shadow-lg">
                <i class="bi bi-globe2 text-purple-600 text-4xl mb-4 block"></i>
                <h3 class="font-bold text-xl mb-3 text-gray-800">Top Importing Markets</h3>
                <p class="text-gray-600">
                    Identify high-demand destinations for every commodity and export category.
                </p>
            </div>
        </div>
    </section>

    <!-- Divider -->
  
<br><br>
    <!-- ====================== WHO IS THIS FOR? ======================= -->
    <section id="who" class="fade-in">
        <h2 class="text-4xl font-bold text-white text-center mb-10 drop-shadow-lg">
            👥 Who Is This Platform For?
        </h2>
        <div class="grid md:grid-cols-4 gap-6">
            <div class="feature-card p-8 rounded-xl shadow-lg text-center">
                <i class="bi bi-truck text-purple-600 text-4xl mb-4 block"></i>
                <h4 class="font-bold text-lg text-gray-800">Exporters</h4>
            </div>
            <div class="feature-card p-8 rounded-xl shadow-lg text-center">
                <i class="bi bi-people-fill text-purple-600 text-4xl mb-4 block"></i>
                <h4 class="font-bold text-lg text-gray-800">FPOs & Farmers</h4>
            </div>
            <div class="feature-card p-8 rounded-xl shadow-lg text-center">
                <i class="bi bi-building text-purple-600 text-4xl mb-4 block"></i>
                <h4 class="font-bold text-lg text-gray-800">Manufacturers</h4>
            </div>
            <div class="feature-card p-8 rounded-xl shadow-lg text-center">
                <i class="bi bi-bank text-purple-600 text-4xl mb-4 block"></i>
                <h4 class="font-bold text-lg text-gray-800">Govt & EPC Bodies</h4>
            </div>
        </div>
    </section>

    <!-- Divider -->
   
<br><br>
    <!-- ====================== SEARCH HS CHAPTERS ======================= -->
    <section id="chapter-search" class="fade-in">
        <h2 class="text-4xl font-bold text-white text-center mb-8 drop-shadow-lg">
            🔍 HS Chapters
        </h2>
        
    </section>

    <!-- ====================== TOP CHAPTERS GRID ======================= -->
    <div class="grid md:grid-cols-3 gap-6 my-12 fade-in">
        <?php
        // Read chapters from JSON file
        $chaptersFile = __DIR__ . '/data/chapters.json';
        $allChapters = [];
        
        if (file_exists($chaptersFile)) {
            $allChapters = json_decode(file_get_contents($chaptersFile), true) ?: [];
        }
        
        // Limit to first 5 chapters
        $chapters = array_slice($allChapters, 0, 5);
        
        // Icons for different chapters
        $icons = ["🥩", "🐟", "🥛", "🥚", "🌿", "🌾", "🥕", "🍎", "🌶️", "🛢️", "🍪", "📦"];
        
        // Show first 5 chapters
        foreach ($chapters as $index => $chapter) {
            $icon = $icons[$index % count($icons)];
            echo "
            <a href='chapters.php#chapter-" . htmlspecialchars($chapter['id']) . "' class='chapter-card p-8 rounded-xl shadow-lg cursor-pointer block'>
                <div class='text-5xl mb-4'>{$icon}</div>
                <h4 class='font-bold text-gray-800 mb-3 text-lg'>" . htmlspecialchars($chapter['name']) . "</h4>
                <p class='text-gray-600 text-sm'>
                    Explore global market size, India's exports, rejection risks & production edge.
                </p>
                <div class='mt-4 text-center'>
                    <button class='bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-4 py-2 rounded-full text-sm font-medium hover:from-purple-700 hover:to-indigo-700 transition-all duration-300'>
                        Explore Chapter
                    </button>
                </div>
            </a>";
        }
        
        // Add the "Show More" card as the 6th card
        echo "
        <a href='chapters.php' class='chapter-card p-8 rounded-xl shadow-lg cursor-pointer flex flex-col items-center justify-center text-center hover:bg-purple-50 transition duration-300'>
            <div class='text-5xl mb-4'>📚</div>
            <h4 class='font-bold text-gray-800 mb-3 text-lg'>View All Chapters</h4>
            <p class='text-gray-600 text-sm'>
                Explore all HS chapters and detailed trade intelligence.
            </p>
            <div class='mt-4 text-purple-600 font-semibold'>Click to View All →</div>
        </a>";
        ?>
    </div>

    <!-- ====================== DYNAMIC WORLD MAP ======================= -->
    <section id="world-map" class="fade-in text-center my-12">
        <h2 class="text-4xl font-bold text-white mb-8 drop-shadow-lg">
            🗺️ Global Import Hotspots
        </h2>
        <div class="flex justify-center">
            <div class="bg-white/90 backdrop-blur-sm p-8 rounded-xl shadow-xl w-full max-w-4xl relative overflow-hidden">
                <div id="map-container" class="relative w-full" style="padding-top: 50%; min-height: 400px;">
                    <!-- World map with highlighted countries -->
                    <div id="world-map-highlighted" class="absolute inset-0 hidden">
                        <img src="assets/images/world-map-high-res.png" alt="World Map" class="w-full h-full object-contain">
                        <svg id="country-overlays" class="absolute inset-0 w-full h-full pointer-events-none"></svg>
                    </div>
                    
                    <!-- Loading indicator -->
                    <div id="map-loading" class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full text-purple-600 border-t-transparent mb-2"></div>
                            <p class="text-gray-600">Loading trade data...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="country-data-info" class="mt-6 text-center text-white bg-black/30 backdrop-blur-sm rounded-lg p-4 max-w-4xl mx-auto hidden">
            <h3 class="text-xl font-bold mb-2">Trade Data Insights</h3>
            <p id="country-count">Analyzing data from <span id="total-countries">0</span> countries</p>
        </div>
        <div id="country-list" class="mt-6 text-center text-white bg-black/30 backdrop-blur-sm rounded-lg p-4 max-w-4xl mx-auto hidden">
            <h3 class="text-xl font-bold mb-2">Countries with Trade Data</h3>
            <div id="country-tags" class="flex flex-wrap justify-center gap-2"></div>
        </div>
    </section>

    <script>
        // Fetch country data and render dynamic world map with highlighted countries
        async function loadDynamicWorldMap() {
            try {
                // Try to fetch real data first
                const response = await fetch('api/country_data.php');
                const data = await response.json();
                
                if (data.success && data.countries.length > 0) {
                    // Hide loading indicator
                    document.getElementById('map-loading').classList.add('hidden');
                    
                    // Show map with highlighted countries
                    document.getElementById('world-map-highlighted').classList.remove('hidden');
                    
                    // Update country count
                    document.getElementById('total-countries').textContent = data.total_countries;
                    document.getElementById('country-data-info').classList.remove('hidden');
                    document.getElementById('country-list').classList.remove('hidden');
                    
                    // Render highlighted countries
                    renderHighlightedCountries(data.countries);
                } else {
                    // If no real data, show demo data
                    showDemoMapWithHighlights();
                }
            } catch (error) {
                console.error('Error loading dynamic world map:', error);
                // Show demo data as fallback
                showDemoMapWithHighlights();
            }
        }
        
        // Show demo map with sample data
        function showDemoMapWithHighlights() {
            // Hide loading indicator
            document.getElementById('map-loading').classList.add('hidden');
            
            // Show map with highlighted countries
            document.getElementById('world-map-highlighted').classList.remove('hidden');
            
            // Demo data
            const demoCountries = [
                {name: 'United States', iso_code: 'US', table_count: 12},
                {name: 'Germany', iso_code: 'DE', table_count: 8},
                {name: 'China', iso_code: 'CN', table_count: 15},
                {name: 'United Kingdom', iso_code: 'GB', table_count: 7},
                {name: 'Japan', iso_code: 'JP', table_count: 6},
                {name: 'Brazil', iso_code: 'BR', table_count: 4},
                {name: 'India', iso_code: 'IN', table_count: 9},
                {name: 'Canada', iso_code: 'CA', table_count: 5},
                {name: 'Australia', iso_code: 'AU', table_count: 4},
                {name: 'France', iso_code: 'FR', table_count: 6},
                {name: 'South Korea', iso_code: 'KR', table_count: 5},
                {name: 'Mexico', iso_code: 'MX', table_count: 3}
            ];
            
            // Update country count
            document.getElementById('total-countries').textContent = demoCountries.length;
            document.getElementById('country-data-info').classList.remove('hidden');
            document.getElementById('country-list').classList.remove('hidden');
            
            // Render highlighted countries with demo data
            renderHighlightedCountries(demoCountries);
        }
        
        // Render highlighted countries on the world map
        function renderHighlightedCountries(countries) {
            const countryTagsContainer = document.getElementById('country-tags');
            const countryOverlays = document.getElementById('country-overlays');
            countryTagsContainer.innerHTML = '';
            countryOverlays.innerHTML = '';
            
            // Approximate bounding boxes for country highlights (based on world map dimensions)
            const countryBoxes = {
                'US': {x: 200, y: 180, width: 100, height: 80},
                'CA': {x: 180, y: 120, width: 80, height: 80},
                'MX': {x: 150, y: 250, width: 70, height: 70},
                'BR': {x: 280, y: 380, width: 90, height: 100},
                'AR': {x: 270, y: 450, width: 60, height: 90},
                'GB': {x: 560, y: 140, width: 30, height: 30},
                'DE': {x: 580, y: 160, width: 30, height: 30},
                'FR': {x: 570, y: 170, width: 30, height: 30},
                'IT': {x: 590, y: 180, width: 30, height: 40},
                'ES': {x: 550, y: 190, width: 30, height: 40},
                'CN': {x: 850, y: 200, width: 120, height: 100},
                'IN': {x: 780, y: 250, width: 80, height: 90},
                'JP': {x: 950, y: 220, width: 50, height: 50},
                'KR': {x: 920, y: 220, width: 40, height: 40},
                'AU': {x: 950, y: 450, width: 80, height: 70},
                'SG': {x: 850, y: 320, width: 20, height: 20},
                'AE': {x: 670, y: 280, width: 20, height: 20},
                'SA': {x: 640, y: 270, width: 40, height: 40},
                'ZA': {x: 600, y: 480, width: 50, height: 70},
                'EG': {x: 610, y: 230, width: 40, height: 50},
                'RU': {x: 700, y: 100, width: 150, height: 120}
            };
            
            // Add country tags
            countries.forEach(country => {
                const tag = document.createElement('span');
                tag.className = 'bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium flex items-center';
                tag.innerHTML = `
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                    ${country.name} (${country.table_count})
                `;
                countryTagsContainer.appendChild(tag);
                
                // Add highlight overlay for country (red point)
                if (country.iso_code && countryBoxes[country.iso_code]) {
                    const box = countryBoxes[country.iso_code];
                    // Calculate center point of the country
                    const centerX = box.x + (box.width / 2);
                    const centerY = box.y + (box.height / 2);
                    
                    // Create a red point (circle)
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', centerX);
                    circle.setAttribute('cy', centerY);
                    circle.setAttribute('r', '8');
                    circle.setAttribute('fill', '#ef4444');
                    circle.setAttribute('stroke', '#ffffff');
                    circle.setAttribute('stroke-width', '2');
                    
                    // Add pulse animation
                    circle.setAttribute('class', 'animate-pulse');
                    
                    countryOverlays.appendChild(circle);
                    
                    // Add a second larger circle for a glow effect
                    const glow = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    glow.setAttribute('cx', centerX);
                    glow.setAttribute('cy', centerY);
                    glow.setAttribute('r', '12');
                    glow.setAttribute('fill', 'rgba(239, 68, 68, 0.3)');
                    countryOverlays.appendChild(glow);
                }
            });
        }
        
        // Load the dynamic world map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadDynamicWorldMap();
        });
    </script>

</div>

    <?php require "includes/footer.php"; ?>