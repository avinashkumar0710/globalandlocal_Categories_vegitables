<?php
$currentPage = 'privacy';
$pageTitle = 'Privacy Policy';
require "includes/header.php";

// Database connection
require "config/database.php";
$db = new Database();
$conn = $db->getConnection();

// Get the active privacy policy
$activePolicy = null;
try {
    $stmt = $conn->prepare("SELECT * FROM privacy_policy WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $activePolicy = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error silently
    error_log("Failed to get active privacy policy: " . $e->getMessage());
}
?>

<style>
.privacy-policy-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.privacy-policy-header {
    text-align: center;
    margin-bottom: 2rem;
}

.privacy-policy-header h1 {
    color: #333;
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.privacy-policy-header p {
    color: #666;
    font-size: 1.1rem;
}

.policy-meta {
    background: #f8f9fa;
    border-radius: 5px;
    padding: 1rem;
    margin: 1rem 0;
    font-size: 0.9rem;
    color: #555;
}

.policy-meta strong {
    color: #333;
}

.policy-content {
    line-height: 1.6;
    color: #444;
}

.policy-content h2 {
    color: #333;
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

.policy-content h3 {
    color: #444;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}

.policy-content p {
    margin-bottom: 1rem;
}

.policy-content ul, .policy-content ol {
    margin-bottom: 1rem;
    padding-left: 2rem;
}

.policy-content li {
    margin-bottom: 0.5rem;
}

.no-policy {
    text-align: center;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 10px;
    color: #666;
}

.no-policy i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ccc;
}

@media (max-width: 768px) {
    .privacy-policy-container {
        margin: 1rem;
        padding: 1rem;
    }
    
    .privacy-policy-header h1 {
        font-size: 2rem;
    }
}
</style>

<div class="privacy-policy-container">
    <div class="privacy-policy-header">
        <h1><i class="bi bi-shield-lock"></i> Privacy Policy</h1>
        <p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
    </div>
    
    <?php if ($activePolicy): ?>
        <div class="policy-meta">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Effective Date:</strong> <?php echo date('F j, Y', strtotime($activePolicy['created_at'])); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p><strong>Version:</strong> <?php echo htmlspecialchars($activePolicy['version']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="policy-content">
            <?php echo $activePolicy['content']; ?>
        </div>
        
        <div class="policy-meta mt-4">
            <p class="mb-0"><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($activePolicy['updated_at'])); ?></p>
        </div>
    <?php else: ?>
        <div class="no-policy">
            <i class="bi bi-shield-exclamation"></i>
            <h3>No Privacy Policy Available</h3>
            <p>We're working on creating a comprehensive privacy policy for our platform.</p>
            <p>Please check back later or contact us for more information.</p>
        </div>
    <?php endif; ?>
</div>

<?php require "includes/footer.php"; ?>