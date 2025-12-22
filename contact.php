<?php
$currentPage = 'contact';
$pageTitle = 'Contact Us';
require "includes/header.php";
?>

        <!-- Contact Section -->
        <section id="contact">
            <div class="max-w-3xl mx-auto px-5 py-8">
                <div class="bg-white rounded-lg shadow p-8">
                    <h2 class="flex items-center gap-2 text-3xl font-bold text-gray-800 mb-3">
                        <i class="bi bi-envelope-fill text-primary"></i>
                        Contact Us
                    </h2>
                    <p class="text-gray-600 text-sm mb-6">Have questions or feedback? Reach out to us using the form below and we'll get back to you as soon as possible.</p>

                    <form id="contactForm">
                        <!-- Name Field -->
                        <div class="mb-5">
                            <label for="name" class="block text-xs font-medium text-gray-800 mb-1.5">Full Name</label>
                            <input 
                                type="text" 
                                class="w-full px-3 py-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent" 
                                id="name" 
                                placeholder="Enter your name" 
                                required
                            >
                        </div>

                        <!-- Email Field -->
                        <div class="mb-5">
                            <label for="email" class="block text-xs font-medium text-gray-800 mb-1.5">Email Address</label>
                            <input 
                                type="email" 
                                class="w-full px-3 py-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent" 
                                id="email" 
                                placeholder="Enter your email" 
                                required
                            >
                        </div>

                        <!-- Subject Field -->
                        <div class="mb-5">
                            <label for="subject" class="block text-xs font-medium text-gray-800 mb-1.5">Subject</label>
                            <input 
                                type="text" 
                                class="w-full px-3 py-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent" 
                                id="subject" 
                                placeholder="What is this regarding?" 
                                required
                            >
                        </div>

                        <!-- Message Field -->
                        <div class="mb-5">
                            <label for="message" class="block text-xs font-medium text-gray-800 mb-1.5">Message</label>
                            <textarea 
                                class="w-full px-3 py-2.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent resize-vertical min-h-[100px]" 
                                id="message" 
                                placeholder="Enter your message here..." 
                                required
                            ></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="btn-gradient inline-flex items-center px-5 py-2.5 rounded text-sm font-semibold"
                        >
                            <i class="bi bi-send-fill mr-2"></i>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </section>

    <script>
        // Contact form submission
        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Disable submit button and show loading
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="bi bi-arrow-repeat spin mr-2"></i> Sending...';
            submitButton.disabled = true;
            
            // Create FormData object
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('subject', subject);
            formData.append('message', message);
            
            // Send data via fetch
            fetch('save_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(data.message);
                    // Reset form
                    document.getElementById('contactForm').reset();
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending your message. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        });
    </script>

<?php require "includes/footer.php"; ?>
