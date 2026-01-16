// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Charts
    initializeCharts();
    
    // Initialize DataTables
    initializeDataTables();
    
    // Initialize Modals
    initializeModals();
    
    // Initialize Real-time Updates
    initializeRealTimeUpdates();
    
    // Initialize Sidebar Toggle
    initializeSidebar();
    
    // Initialize Form Validations
    initializeFormValidations();
    
    console.log('Dashboard initialized successfully');
});

// Chart Initialization
function initializeCharts() {
    // Controls Evolution Chart
    const controlsCtx = document.getElementById('controlsChart');
    if (controlsCtx) {
        new Chart(controlsCtx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Contrôles',
                    data: [65, 78, 90, 81, 56, 55, 40],
                    borderColor: '#003366',
                    backgroundColor: 'rgba(0, 51, 102, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Regional Distribution Chart
    const regionCtx = document.getElementById('regionChart');
    if (regionCtx) {
        new Chart(regionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Conakry', 'Kindia', 'Labé', 'Faranah', 'N\'Zérékoré'],
                datasets: [{
                    data: [35, 25, 20, 12, 8],
                    backgroundColor: [
                        '#003366',
                        '#FF6B35',
                        '#00A652',
                        '#17a2b8',
                        '#ffc107'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Brigade Performance Chart
    const brigadeCtx = document.getElementById('brigadePerformanceChart');
    if (brigadeCtx) {
        new Chart(brigadeCtx, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Brigade Centre',
                    data: [45, 52, 48, 58, 42, 38, 25],
                    backgroundColor: '#003366'
                }, {
                    label: 'Brigade Nord',
                    data: [32, 38, 35, 42, 28, 25, 18],
                    backgroundColor: '#FF6B35'
                }, {
                    label: 'Brigade Sud',
                    data: [28, 32, 30, 35, 22, 20, 15],
                    backgroundColor: '#00A652'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Infraction Types Chart
    const infractionCtx = document.getElementById('infractionTypesChart');
    if (infractionCtx) {
        new Chart(infractionCtx, {
            type: 'pie',
            data: {
                labels: ['Vitesse', 'Documentation', 'Alcoolémie', 'Équipement', 'Autres'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: [
                        '#dc3545',
                        '#ffc107',
                        '#fd7e14',
                        '#20c997',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Team Performance Chart
    const teamCtx = document.getElementById('teamPerformanceChart');
    if (teamCtx) {
        new Chart(teamCtx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
                datasets: [{
                    label: 'Mamadou Diallo',
                    data: [45, 52, 48, 58],
                    borderColor: '#003366',
                    backgroundColor: 'rgba(0, 51, 102, 0.1)',
                    borderWidth: 2
                }, {
                    label: 'Oumar Touré',
                    data: [38, 42, 40, 45],
                    borderColor: '#FF6B35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 2
                }, {
                    label: 'Sékou Condé',
                    data: [32, 35, 33, 38],
                    borderColor: '#00A652',
                    backgroundColor: 'rgba(0, 166, 82, 0.1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Agent Performance Chart
    const agentCtx = document.getElementById('agentPerformanceChart');
    if (agentCtx) {
        new Chart(agentCtx, {
            type: 'bar',
            data: {
                labels: ['01/01', '02/01', '03/01', '04/01', '05/01', '06/01', '07/01', '08/01'],
                datasets: [{
                    label: 'Contrôles',
                    data: [8, 12, 10, 15, 9, 11, 13, 12],
                    backgroundColor: '#003366'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Control Types Chart
    const controlTypesCtx = document.getElementById('controlTypesChart');
    if (controlTypesCtx) {
        new Chart(controlTypesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Vitesse', 'Documentation', 'Alcoolémie', 'Équipement', 'Chargement'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        '#003366',
                        '#17a2b8',
                        '#ffc107',
                        '#20c997',
                        '#fd7e14'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Regional Performance Chart
    const regionalPerfCtx = document.getElementById('regionalPerformanceChart');
    if (regionalPerfCtx) {
        new Chart(regionalPerfCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
                datasets: [{
                    label: 'Conakry',
                    data: [1200, 1350, 1100, 1450, 1300, 1500],
                    borderColor: '#003366',
                    backgroundColor: 'rgba(0, 51, 102, 0.1)',
                    borderWidth: 3
                }, {
                    label: 'Kindia',
                    data: [800, 900, 750, 950, 850, 1000],
                    borderColor: '#FF6B35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 3
                }, {
                    label: 'Labé',
                    data: [600, 700, 650, 750, 680, 720],
                    borderColor: '#00A652',
                    backgroundColor: 'rgba(0, 166, 82, 0.1)',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Top Regions Chart
    const topRegionsCtx = document.getElementById('topRegionsChart');
    if (topRegionsCtx) {
        new Chart(topRegionsCtx, {
            type: 'polarArea',
            data: {
                labels: ['Conakry', 'Kindia', 'Labé', 'Faranah', 'N\'Zérékoré'],
                datasets: [{
                    data: [1500, 1000, 750, 450, 300],
                    backgroundColor: [
                        'rgba(0, 51, 102, 0.8)',
                        'rgba(255, 107, 53, 0.8)',
                        'rgba(0, 166, 82, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// DataTable Initialization
function initializeDataTables() {
    // Add sorting functionality to all tables
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        if (!table.classList.contains('no-sort')) {
            // Simple sorting implementation
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    sortTable(table, index);
                });
            });
        }
    });
}

// Table Sorting
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.getAttribute('data-sort-order') !== 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (isAscending) {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
    
    table.setAttribute('data-sort-order', isAscending ? 'asc' : 'desc');
}

// Modal Initialization
function initializeModals() {
    // Handle modal show events
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            // Reset form when modal opens
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        });
    });
    
    // Handle form submissions
    const forms = document.querySelectorAll('.modal form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleModalSubmit(form);
        });
    });
}

// Modal Form Submission
function handleModalSubmit(form) {
    const modal = form.closest('.modal');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Add loading state
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        // Remove loading state
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        
        // Close modal
        const modalInstance = bootstrap.Modal.getInstance(modal);
        modalInstance.hide();
        
        // Show success notification
        showNotification('Succès', 'Opération effectuée avec succès', 'success');
    }, 1500);
}

// Real-time Updates
function initializeRealTimeUpdates() {
    // Simulate real-time updates
    setInterval(() => {
        updateStatistics();
        checkForNewActivities();
    }, 30000); // Update every 30 seconds
}

// Update Statistics
function updateStatistics() {
    // Update random statistics
    const statNumbers = document.querySelectorAll('.stat-number, .h5');
    statNumbers.forEach(element => {
        if (Math.random() > 0.7) {
            const currentValue = parseInt(element.textContent.replace(/[^0-9]/g, ''));
            const change = Math.floor(Math.random() * 5) - 2;
            const newValue = Math.max(0, currentValue + change);
            
            // Animate the change
            animateValue(element, currentValue, newValue, 500);
        }
    });
}

// Check for New Activities
function checkForNewActivities() {
    // Simulate new activity
    if (Math.random() > 0.8) {
        const tables = document.querySelectorAll('.table tbody');
        tables.forEach(tbody => {
            const newRow = createNewActivityRow();
            tbody.insertBefore(newRow, tbody.firstChild);
            
            // Highlight new row
            newRow.classList.add('table-warning', 'fade-in');
            setTimeout(() => {
                newRow.classList.remove('table-warning', 'fade-in');
            }, 3000);
        });
    }
}

// Create New Activity Row
function createNewActivityRow() {
    const row = document.createElement('tr');
    const now = new Date();
    const timeString = now.toLocaleString('fr-GN', { 
        hour: '2-digit', 
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit'
    });
    
    row.innerHTML = `
        <td>${timeString}</td>
        <td>Agent ${Math.floor(Math.random() * 100)}</td>
        <td>${getRandomActivity()}</td>
        <td>${getRandomLocation()}</td>
        <td><span class="badge bg-warning">En cours</span></td>
    `;
    
    return row;
}

// Get Random Activity
function getRandomActivity() {
    const activities = [
        'Contrôle vitesse',
        'Contrôle documentation',
        'Contrôle alcoolémie',
        'Rapport journalier',
        'Validation rapport'
    ];
    return activities[Math.floor(Math.random() * activities.length)];
}

// Get Random Location
function getRandomLocation() {
    const locations = [
        'Avenue du Général De Gaulle',
        'Carrefour du 8 Novembre',
        'Pont du 8 Novembre',
        'Route de Friguiagbé',
        'Avenue du Niger'
    ];
    return locations[Math.floor(Math.random() * locations.length)];
}

// Sidebar Toggle
function initializeSidebar() {
    // Add mobile sidebar toggle
    const navbar = document.querySelector('.navbar');
    const sidebar = document.querySelector('.sidebar');
    
    if (navbar && sidebar) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-outline-secondary d-md-none';
        toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
        toggleBtn.style.position = 'fixed';
        toggleBtn.style.top = '70px';
        toggleBtn.style.left = '10px';
        toggleBtn.style.zIndex = '1040';
        
        document.body.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
}

// Form Validations
function initializeFormValidations() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                validateField(input);
            });
        });
        
        form.addEventListener('submit', (e) => {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
}

// Field Validation
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name || field.id;
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Ce champ est obligatoire';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Veuillez entrer une adresse email valide';
        }
    }
    
    // Number validation
    if (field.type === 'number' && value) {
        const num = parseFloat(value);
        const min = parseFloat(field.getAttribute('min'));
        const max = parseFloat(field.getAttribute('max'));
        
        if (min !== null && num < min) {
            isValid = false;
            errorMessage = `La valeur minimale est ${min}`;
        }
        
        if (max !== null && num > max) {
            isValid = false;
            errorMessage = `La valeur maximale est ${max}`;
        }
    }
    
    // Update field appearance
    updateFieldValidation(field, isValid, errorMessage);
}

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            updateFieldValidation(input, false, 'Ce champ est obligatoire');
        }
    });
    
    return isValid;
}

// Update Field Validation
function updateFieldValidation(field, isValid, errorMessage) {
    // Remove existing validation
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    field.classList.remove('is-invalid', 'is-valid');
    
    if (!isValid) {
        field.classList.add('is-invalid');
        
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = errorMessage;
        field.parentNode.appendChild(feedback);
    } else if (field.value.trim()) {
        field.classList.add('is-valid');
    }
}

// Animate Value Change
function animateValue(element, start, end, duration) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        
        // Format the display value
        if (element.textContent.includes('GNF')) {
            element.textContent = Math.floor(current).toLocaleString() + ' GNF';
        } else {
            element.textContent = Math.floor(current).toLocaleString();
        }
    }, 16);
}

// Show Notification
function showNotification(title, message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-${getNotificationIcon(type)} me-2"></i>
            <div>
                <strong>${title}</strong><br>
                <small>${message}</small>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Get Notification Icon
function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle-fill',
        error: 'exclamation-triangle-fill',
        warning: 'exclamation-triangle-fill',
        info: 'info-circle-fill'
    };
    return icons[type] || icons.info;
}

// Export Functions
function exportData(format) {
    showNotification('Export', `Exportation des données en format ${format.toUpperCase()}`, 'info');
    
    // Simulate export
    setTimeout(() => {
        showNotification('Succès', `Export ${format.toUpperCase()} terminé avec succès`, 'success');
    }, 2000);
}

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl+N: New Control
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const newControlModal = document.getElementById('newControlModal');
        if (newControlModal) {
            const modal = new bootstrap.Modal(newControlModal);
            modal.show();
        }
    }
    
    // Ctrl+E: Export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportData('excel');
    }
    
    // Escape: Close modals
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }
});

// Print Function
function printReport() {
    window.print();
    showNotification('Impression', 'Le rapport est en cours d\'impression', 'info');
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .is-valid {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .position-fixed {
        position: fixed !important;
    }
`;
document.head.appendChild(style);
