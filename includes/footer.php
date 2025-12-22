<style>
    /* Footer Styles */
    footer {
        background: rgba(31, 41, 55, 0.95);
        backdrop-filter: blur(10px);
        color: #d1d5db;
        margin-top: 80px;
        position: relative;
        z-index: 1000;
    }
    
    .footer-container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 60px 40px 30px;
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        margin-bottom: 40px;
    }
    
    .footer-section h3 {
        color: white;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .footer-section p {
        line-height: 1.8;
        margin-bottom: 15px;
        color: #d1d5db;
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 12px;
    }
    
    .footer-links a {
        color: #d1d5db;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .footer-links a:hover {
        color: #667eea;
        transform: translateX(5px);
    }
    
    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .social-link {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(102, 126, 234, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 1.2rem;
    }
    
    .social-link:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-3px);
    }
    
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 30px;
        text-align: center;
        color: #9ca3af;
        font-size: 0.9rem;
    }
    
    .footer-bottom p {
        margin: 5px 0;
    }
    
    .footer-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .footer-logo img {
        width: 45px;
        height: 45px;
    }
    
    .footer-logo span {
        font-size: 1.4rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .footer-container {
            padding: 40px 20px 20px;
        }
    }
</style>

<footer>
    <div class="footer-container">
        <div class="footer-grid">
            <!-- About Section -->
            <div class="footer-section">
                <div class="footer-logo">
                    <img src="assets/logo/export-logo.png" alt="ExportInsight">
                    <span>ExportInsight</span>
                </div>
                <p>
                    Your comprehensive export intelligence platform providing chapter-wise insights for global trade.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="#" class="social-link" title="Twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="#" class="social-link" title="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="social-link" title="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="bi bi-chevron-right"></i> Home</a></li>
                    <li><a href="chapters.php"><i class="bi bi-chevron-right"></i> Export Insight Chapters</a></li>
                    <li><a href="user_dashboard.php"><i class="bi bi-chevron-right"></i> User Dashboard</a></li>
                    
                    <li><a href="about.php"><i class="bi bi-chevron-right"></i> About Us</a></li>
                    <li><a href="compliance.php"><i class="bi bi-chevron-right"></i> Contact Us </a></li>
                </ul>
            </div>
            
            <!-- Resources -->
            <!-- <div class="footer-section">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Documentation</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> API Access</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Export Guides</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Case Studies</a></li>
                    <li><a href="#"><i class="bi bi-chevron-right"></i> Blog</a></li>
                </ul>
            </div> -->
            
            <!-- Contact Info -->
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul class="footer-links">
                    <li>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>123 Export Plaza, New Delhi, India</span>
                    </li>
                    <li>
                        <i class="bi bi-envelope-fill"></i>
                        <a href="mailto:info@exportinsight.com">info@exportinsight.com</a>
                    </li>
                    <li>
                        <i class="bi bi-telephone-fill"></i>
                        <a href="tel:+911234567890">+91 123 456 7890</a>
                    </li>
                    <li>
                        <i class="bi bi-clock-fill"></i>
                        <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> ExportInsight. All rights reserved.</p>
            <p>
                <span id="visitorCount">Total Visitors: Loading...</span> |
                <a href="privacy.php" style="color: #9ca3af; text-decoration: none;">Privacy Policy</a>  
                <!-- <a href="terms.php" style="color: #9ca3af; text-decoration: none;">Terms of Service</a> |  -->
                <!-- <a href="sitemap.php" style="color: #9ca3af; text-decoration: none;">Sitemap</a> -->
            </p>
        </div>
    </div>
</footer>

<script>
// Fetch visitor count
fetch('get_visitor_count.php')
    .then(response => response.text())
    .then(count => {
        const visitorCountElement = document.getElementById('visitorCount');
        if (visitorCountElement) {
            // Trim whitespace and parse the count
            const trimmedCount = count.trim();
            const parsedCount = parseInt(trimmedCount);
            if (!isNaN(parsedCount)) {
                visitorCountElement.textContent = 'Total Visitors: ' + parsedCount.toLocaleString();
            } else {
                visitorCountElement.textContent = 'Total Visitors: ' + trimmedCount;
            }
        }
    })
    .catch(error => {
        console.error('Error fetching visitor count:', error);
        const visitorCountElement = document.getElementById('visitorCount');
        if (visitorCountElement) {
            visitorCountElement.textContent = 'Total Visitors: --';
        }
    });
</script>

</body>
</html>