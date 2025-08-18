// Agent Portal JavaScript
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

  // Sidebar Navigation
  const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Remove active class from all links
      navLinks.forEach(l => l.classList.remove('active'));
      
      // Add active class to clicked link
      this.classList.add('active');
      
      // Get the target section
      const target = this.getAttribute('href').substring(1);
      
      // Simulate content loading
      loadContent(target);
    });
  });

  // Quick Action Buttons
  const actionButtons = document.querySelectorAll('.action-btn');
  actionButtons.forEach(button => {
    button.addEventListener('click', function() {
      const action = this.querySelector('span').textContent;
      showNotification(`Opening ${action}...`, 'info');
      
      // Add loading animation
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 150);
    });
  });

  // New Booking Button
  const newBookingBtn = document.querySelector('.btn-new-booking');
  if (newBookingBtn) {
    newBookingBtn.addEventListener('click', function() {
      showNotification('Opening new booking form...', 'success');
      // Here you would open a modal or navigate to booking form
    });
  }

  // Booking Items Click
  const bookingItems = document.querySelectorAll('.booking-item');
  bookingItems.forEach(item => {
    item.addEventListener('click', function() {
      const customerName = this.querySelector('h6').textContent;
      showNotification(`Opening booking details for ${customerName}`, 'info');
      
      // Add click animation
      this.style.transform = 'scale(0.98)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 150);
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

  // Content Loading Simulation
  function loadContent(section) {
    const mainContent = document.querySelector('.main-content');
    
    // Add loading animation
    mainContent.style.opacity = '0.7';
    mainContent.style.transform = 'translateY(10px)';
    
    setTimeout(() => {
      mainContent.style.opacity = '1';
      mainContent.style.transform = 'translateY(0)';
      
      // Update content based on section
      updatePageContent(section);
    }, 300);
  }

  function updatePageContent(section) {
    const welcomeTitle = document.querySelector('.welcome-title');
    const welcomeSubtitle = document.querySelector('.welcome-subtitle');
    
    switch(section) {
      case 'dashboard':
        welcomeTitle.textContent = 'Good morning, Maria! ☀️';
        welcomeSubtitle.textContent = 'You have 5 new booking requests and 3 pending payments to review today.';
        break;
      case 'bookings':
        welcomeTitle.textContent = 'My Bookings 📅';
        welcomeSubtitle.textContent = 'Manage all your customer bookings and reservations.';
        break;
      case 'customers':
        welcomeTitle.textContent = 'Customer Management 👥';
        welcomeSubtitle.textContent = 'View and manage your customer database.';
        break;
      case 'packages':
        welcomeTitle.textContent = 'Travel Packages 🎒';
        welcomeSubtitle.textContent = 'Browse and manage available travel packages.';
        break;
      case 'commissions':
        welcomeTitle.textContent = 'Commission Tracking 💰';
        welcomeSubtitle.textContent = 'Track your earnings and commission payments.';
        break;
      case 'leads':
        welcomeTitle.textContent = 'Lead Management 🎯';
        welcomeSubtitle.textContent = 'Follow up on potential customers and inquiries.';
        break;
      case 'reports':
        welcomeTitle.textContent = 'Reports & Analytics 📊';
        welcomeSubtitle.textContent = 'View detailed reports and performance analytics.';
        break;
    }
  }

  // Real-time Updates Simulation
  function simulateRealTimeUpdates() {
    const stats = [
      { selector: '.stat-card:nth-child(1) h3', baseValue: 24 },
      { selector: '.stat-card:nth-child(2) h3', baseValue: 45230, prefix: '₱' },
      { selector: '.stat-card:nth-child(3) h3', baseValue: 156 },
      { selector: '.stat-card:nth-child(4) h3', baseValue: 4.9 }
    ];

    setInterval(() => {
      stats.forEach(stat => {
        const element = document.querySelector(stat.selector);
        if (element) {
          let newValue = stat.baseValue + Math.floor(Math.random() * 5) - 2;
          if (stat.prefix) {
            element.textContent = stat.prefix + newValue.toLocaleString();
          } else {
            element.textContent = newValue;
          }
        }
      });
    }, 30000); // Update every 30 seconds
  }

  // Initialize real-time updates
  simulateRealTimeUpdates();

  // Smooth hover effects for cards
  const cards = document.querySelectorAll('.stat-card, .activity-card, .quick-actions-card, .commission-card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-5px)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
    });
  });

  // Progress bar animations
  function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.transition = 'width 1s ease-in-out';
        bar.style.width = width;
      }, 500);
    });
  }

  // Animate progress bars on load
  setTimeout(animateProgressBars, 1000);

  // Mobile sidebar toggle (if needed)
  const mobileToggle = document.querySelector('.mobile-toggle');
  const sidebar = document.querySelector('.sidebar');
  
  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    }
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N for new booking
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
      e.preventDefault();
      showNotification('Opening new booking form... (Ctrl+N)', 'info');
    }
    
    // Ctrl/Cmd + S for search
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      showNotification('Opening search... (Ctrl+S)', 'info');
    }
  });

  // Welcome message based on time
  function updateWelcomeMessage() {
    const hour = new Date().getHours();
    const welcomeTitle = document.querySelector('.welcome-title');
    
    if (hour < 12) {
      welcomeTitle.textContent = 'Good morning, Maria! ☀️';
    } else if (hour < 18) {
      welcomeTitle.textContent = 'Good afternoon, Maria! 🌤️';
    } else {
      welcomeTitle.textContent = 'Good evening, Maria! 🌙';
    }
  }

  // Update welcome message
  updateWelcomeMessage();

  // Show initial welcome notification
  setTimeout(() => {
    showNotification('Welcome to your Agent Portal! 🎉', 'success');
  }, 1000);

  console.log('Agent Portal initialized successfully! 🚀');
});
