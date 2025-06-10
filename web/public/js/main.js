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
 * Fonction pour créer un graphique ECG de base
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
 * Fonction pour créer un graphique ECG avancé avec marqueurs d'ondes
 * @param {string} canvasId - L'ID du canvas
 * @param {Array} ecgData - Données du signal ECG
 * @param {Array} timeData - Données temporelles
 * @param {Object} wavePositions - Marqueurs de position des ondes
 */
function createAdvancedEcgChart(canvasId, ecgData, timeData, wavePositions) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    
    const annotations = {};
    const waveColors = {
        p: 'rgba(255, 99, 132, 0.8)',
        q: 'rgba(54, 162, 235, 0.8)',
        r: 'rgba(255, 206, 86, 0.8)',
        s: 'rgba(75, 192, 192, 0.8)',
        t: 'rgba(153, 102, 255, 0.8)'
    };
    
    // Création des annotations pour les marqueurs d'ondes
    for (const [wave, position] of Object.entries(wavePositions)) {
        if (position < timeData.length) {
            annotations[`${wave}Wave`] = {
                type: 'line',
                xMin: timeData[position],
                xMax: timeData[position],
                borderColor: waveColors[wave],
                borderWidth: 2,
                label: {
                    content: `Onde ${wave.toUpperCase()}`,
                    enabled: true,
                    position: 'top'
                }
            };
        }
    }
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: timeData,
            datasets: [{
                label: 'Signal ECG',
                data: ecgData,
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
                    },
                    type: 'linear'
                },
                y: {
                    title: {
                        display: true,
                        text: 'Amplitude (mV)'
                    }
                }
            },
            plugins: {
                annotation: {
                    annotations: annotations
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
 * JavaScript principal pour le Système de Monitoring ECG
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le toggle de la navbar
    initNavbarToggle();
    
    // Initialiser les dropdowns
    initDropdowns();
    
    // Initialiser la visualisation ECG si présente
    initEcgVisualization();
});

/**
 * Initialiser le toggle de la navbar pour la vue mobile
 */
function initNavbarToggle() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', () => {
            navbarCollapse.classList.toggle('show');
        });
        
        // Fermer le menu en cliquant à l'extérieur
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
 * Initialiser les menus déroulants
 */
function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    for (const toggle of dropdownToggles) {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const parent = toggle.closest('.dropdown');
            const menu = parent.querySelector('.dropdown-menu');
            
            // Fermer tous les autres dropdowns
            for (const openMenu of document.querySelectorAll('.dropdown-menu.show')) {
                if (openMenu !== menu) {
                    openMenu.classList.remove('show');
                }
            }
            
            // Basculer le dropdown actuel
            menu.classList.toggle('show');
        });
    }
    
    // Fermer les dropdowns en cliquant à l'extérieur
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
 * Initialiser la visualisation ECG
 */
function initEcgVisualization() {
    const ecgCanvas = document.getElementById('ecg-chart');
    if (ecgCanvas && typeof window.ecgData !== 'undefined') {
        createAdvancedEcgChart('ecg-chart', window.ecgData, window.timeData, window.wavePositions);
    }
}

/**
 * Afficher un message d'alerte
 * @param {string} message - Texte du message d'alerte
 * @param {string} type - Type d'alerte (success, danger, warning, info)
 * @param {number} duration - Durée en millisecondes avant masquage automatique
 */
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = message;
    
    alertContainer.appendChild(alert);
    
    // Suppression automatique après la durée spécifiée
    if (duration > 0) {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alertContainer.removeChild(alert);
                
                // Supprimer le conteneur s'il est vide
                if (alertContainer.children.length === 0) {
                    alertContainer.remove();
                }
            }, 300);
        }, duration);
    }
}

/**
 * Créer un conteneur d'alerte s'il n'existe pas
 * @returns {HTMLElement} Le conteneur d'alerte
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