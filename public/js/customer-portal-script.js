// Customer Portal JavaScript
document.addEventListener('DOMContentLoaded', function() {
  
  // Scroll Animation Observer
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-in');
      }
    });
  }, observerOptions);

  // Observe all elements with scroll animation classes
  const animateElements = document.querySelectorAll('.scroll-animate');
  animateElements.forEach(el => {
    observer.observe(el);
  });

  // Navbar background on scroll
  window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });

  // Quick Action Cards
  const actionCards = document.querySelectorAll('.action-card');
  actionCards.forEach(card => {
    card.addEventListener('click', function() {
      const action = this.getAttribute('data-action');
      handleQuickAction(action);
      
      // Add click animation
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 150);
    });
  });

  function handleQuickAction(action) {
    switch(action) {
      case 'book-trip':
        showNotification('Redirecting to travel packages...', 'info');
        setTimeout(() => {
          window.location.href = 'portfolio.html#packages';
        }, 1000);
        break;
      case 'my-bookings':
        showNotification('Loading your bookings...', 'info');
        scrollToSection('.bookings-section');
        break;
      case 'payment-history':
        showNotification('Opening payment history...', 'info');
        // Here you would navigate to payment history page
        break;
      case 'support':
        showNotification('Connecting you to support...', 'success');
        // Here you would open support chat or contact form
        break;
    }
  }

  // Booking Actions
  const bookingButtons = document.querySelectorAll('.booking-actions .btn');
  bookingButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.stopPropagation();
      const action = this.textContent.trim();
      
      if (action.includes('View Details')) {
        showNotification('Opening booking details...', 'info');
      } else if (action.includes('Download Voucher')) {
        showNotification('Downloading your travel voucher...', 'success');
        // Simulate download
        setTimeout(() => {
          showNotification('Voucher downloaded successfully! 📄', 'success');
        }, 2000);
      } else if (action.includes('Complete Payment')) {
        showNotification('Redirecting to payment gateway...', 'warning');
        // Here you would redirect to payment page
      }
    });
  });

  // Package Booking
  const packageButtons = document.querySelectorAll('.btn-package');
  packageButtons.forEach(button => {
    button.addEventListener('click', function() {
      const packageName = this.closest('.package-card').querySelector('h4').textContent;
      showNotification(`Adding ${packageName} to your wishlist...`, 'success');
      
      // Add to wishlist animation
      const icon = this.querySelector('i');
      icon.classList.remove('bi-heart');
      icon.classList.add('bi-heart-fill');
      
      setTimeout(() => {
        showNotification('Package added to wishlist! ❤️', 'success');
      }, 1000);
    });
  });

  // Notification System
  function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `custom-notification alert alert-${type === 'info' ? 'primary' : type} alert-dismissible fade show`;
    notification.style.cssText = `
      position: fixed;
      top: 100px;
      right: 20px;
      z-index: 9999;
      min-width: 300px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      border-radius: 12px;
      border: none;
    `;
    
    notification.innerHTML = `
      <div class="d-flex align-items-center">
        <i class="bi bi-${getNotificationIcon(type)} me-2"></i>
        <span>${message}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
      </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 3000);
  }

  function getNotificationIcon(type) {
    switch(type) {
      case 'success': return 'check-circle';
      case 'warning': return 'exclamation-triangle';
      case 'danger': return 'x-circle';
      default: return 'info-circle';
    }
  }

  // Smooth scroll to section
  function scrollToSection(selector) {
    const element = document.querySelector(selector);
    if (element) {
      element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  }

  // Smooth scrolling for navigation links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Countdown Timer for upcoming trips
  function updateCountdown() {
    const countdownBadge = document.querySelector('.countdown-badge');
    if (countdownBadge) {
      const tripDate = new Date('2024-12-15'); // Palawan trip date
      const now = new Date();
      const timeDiff = tripDate - now;
      const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
      
      if (daysDiff > 0) {
        countdownBadge.textContent = `${daysDiff} days to go!`;
      } else if (daysDiff === 0) {
        countdownBadge.textContent = 'Today is the day! 🎉';
        countdownBadge.style.background = 'rgba(16, 185, 129, 0.9)';
      }
    }
  }

  // Update countdown on load
  updateCountdown();

  // Real-time stats updates
  function updateStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach((stat, index) => {
      if (index === 0) { // Active bookings
        const current = parseInt(stat.textContent);
        // Simulate real-time updates (could be from API)
        if (Math.random() > 0.95) { // 5% chance of update
          stat.textContent = current + (Math.random() > 0.5 ? 1 : -1);
        }
      }
    });
  }

  // Update stats every 30 seconds
  setInterval(updateStats, 30000);

  // Welcome message based on time
  function updateWelcomeMessage() {
    const hour = new Date().getHours();
    const welcomeTitle = document.querySelector('.welcome-title');
    
    if (hour < 12) {
      welcomeTitle.textContent = 'Good morning, Sarah! ☀️';
    } else if (hour < 18) {
      welcomeTitle.textContent = 'Good afternoon, Sarah! 🌤️';
    } else {
      welcomeTitle.textContent = 'Good evening, Sarah! 🌙';
    }
  }

  // Update welcome message
  updateWelcomeMessage();

  // Parallax effect for hero section
  window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const parallax = document.querySelector('.hero-section');
    const speed = scrolled * 0.3;
    
    if (parallax) {
      parallax.style.transform = `translateY(${speed}px)`;
    }
  });

  // Interactive hover effects
  const cards = document.querySelectorAll('.action-card, .booking-card, .package-card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-10px)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });

  // Keyboard shortcuts for power users
  document.addEventListener('keydown', function(e) {
    // Alt + B for new booking
    if (e.altKey && e.key === 'b') {
      e.preventDefault();
      handleQuickAction('book-trip');
    }
    
    // Alt + M for my bookings
    if (e.altKey && e.key === 'm') {
      e.preventDefault();
      handleQuickAction('my-bookings');
    }
    
    // Alt + S for support
    if (e.altKey && e.key === 's') {
      e.preventDefault();
      handleQuickAction('support');
    }
  });

  // Show welcome notification
  setTimeout(() => {
    showNotification('Welcome back, Sarah! Your Palawan adventure awaits! 🏝️', 'success');
  }, 1500);

  // Simulate real-time booking updates
  setTimeout(() => {
    showNotification('Your Boracay booking payment reminder: Due in 3 days', 'warning');
  }, 5000);

  console.log('Customer Portal initialized successfully! 🎉');
});
