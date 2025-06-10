/**
 * Fonction pour formater une date
 * @param {Date} date - L'objet Date à formater
 * @param {boolean} withTime - Inclure l'heure ou non
 * @returns {string} Date formatée
 */
function formatDate(date, withTime = false) {
    let formattedDate = date;
    
    if (!(date instanceof Date)) {
        formattedDate = new Date(date);
    }
    
    const day = String(formattedDate.getDate()).padStart(2, '0');
    const month = String(formattedDate.getMonth() + 1).padStart(2, '0');
    const year = formattedDate.getFullYear();
    
    let formatted = `${day}/${month}/${year}`;
    
    if (withTime) {
        const hours = String(formattedDate.getHours()).padStart(2, '0');
        const minutes = String(formattedDate.getMinutes()).padStart(2, '0');
        formatted += ` ${hours}:${minutes}`;
    }
    
    return formatted;
}

/**
 * Fonction d'impression de la page actuelle
 */
function printPage() {
    window.print();
}

/**
 * Fonction pour créer un grahpique ECG de base
 * @param {string} canvasId - L'ID du canvas
 * @param {Array} data - Données ECG
 * @param {Array} labels - Labels pour l'axe X
 */
function createEcgChart(canvasId, data, labels) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Signal ECG',
                data: data,
                borderColor: 'rgb(13, 110, 253)',
                borderWidth: 1.5,
                tension: 0.1,
                pointRadius: 0,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Temps (s)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Amplitude (mV)'
                    }
                }
            }
        }
    });
}

/**
 * Fonction pour confirmer une action
 * @param {string} message - Message de confirmation
 * @returns {boolean} - True si confirmé, false sinon
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Main JavaScript for the ECG Monitoring System
 */

document.addEventListener('DOMContentLoaded', () => {
  // Navbar toggle functionality
  initNavbarToggle();
  
  // Init dropdowns
  initDropdowns();
  
  // Initialize any existing functionality from the original code
  // ...existing code...
});

/**
 * Initialize navbar toggle for mobile view
 */
function initNavbarToggle() {
  const navbarToggler = document.querySelector('.navbar-toggler');
  const navbarCollapse = document.querySelector('.navbar-collapse');
  
  if (navbarToggler && navbarCollapse) {
    navbarToggler.addEventListener('click', () => {
      navbarCollapse.classList.toggle('show');
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', (event) => {
      const isClickInside = navbarToggler.contains(event.target) || 
                           navbarCollapse.contains(event.target);
      
      if (!isClickInside && navbarCollapse.classList.contains('show')) {
        navbarCollapse.classList.remove('show');
      }
    });
  }
}

/**
 * Initialize dropdown menus
 */
function initDropdowns() {
  const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
  
  for (const toggle of dropdownToggles) {
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      const parent = toggle.closest('.dropdown');
      const menu = parent.querySelector('.dropdown-menu');
      
      // Close all other dropdowns
      for (const openMenu of document.querySelectorAll('.dropdown-menu.show')) {
        if (openMenu !== menu) {
          openMenu.classList.remove('show');
        }
      }
      
      // Toggle current dropdown
      menu.classList.toggle('show');
    });
  }
  
  // Close dropdowns when clicking outside
  document.addEventListener('click', (e) => {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    for (const dropdown of dropdowns) {
      if (!dropdown.contains(e.target)) {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (menu?.classList.contains('show')) {
          menu.classList.remove('show');
        }
      }
    }
  });
}

/**
 * Show an alert message
 * @param {string} message - Alert message text
 * @param {string} type - Alert type (success, danger, warning, info)
 * @param {number} duration - Duration in milliseconds before auto-hiding
 */
function showAlert(message, type = 'info', duration = 5000) {
  const alertContainer = document.getElementById('alert-container') || createAlertContainer();
  
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.innerHTML = message;
  
  alertContainer.appendChild(alert);
  
  // Auto-remove after duration
  if (duration > 0) {
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => {
        alertContainer.removeChild(alert);
        
        // Remove container if empty
        if (alertContainer.children.length === 0) {
          alertContainer.remove();
        }
      }, 300);
    }, duration);
  }
}

/**
 * Create alert container if it doesn't exist
 * @returns {HTMLElement} The alert container
 */
function createAlertContainer() {
  const container = document.createElement('div');
  container.id = 'alert-container';
  container.style.position = 'fixed';
  container.style.top = '20px';
  container.style.right = '20px';
  container.style.zIndex = '9999';
  document.body.appendChild(container);
  
  return container;
}

// Add any other utility functions or handlers as needed
// ...existing code... 