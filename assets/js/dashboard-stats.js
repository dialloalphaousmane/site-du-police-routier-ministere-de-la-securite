// Dashboard Stats JavaScript
class DashboardStats {
    constructor() {
        this.apiBaseUrl = '/api/stats';
        this.refreshInterval = 30000; // 30 secondes
        this.init();
    }

    init() {
        this.loadDashboardStats();
        this.setupAutoRefresh();
        this.setupEventListeners();
    }

    async loadDashboardStats() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/dashboard`);
            const data = await response.json();
            
            this.updateAdminStats(data.users);
            this.updateDirectionGeneraleStats(data.controls);
            this.updateDirectionRegionaleStats(data.regions);
            this.updateChefBrigadeStats(data.brigades);
            this.updateAgentStats(data.controls);
            
            this.updateCharts(data);
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    updateAdminStats(userData) {
        const stats = userData;
        
        // Update admin dashboard cards
        this.updateCard('total-users', stats.total_users);
        this.updateCard('daily-controls', stats.today_controls);
        this.updateCard('infractions', stats.infractions);
        this.updateCard('revenue', this.formatCurrency(stats.revenue));
    }

    updateDirectionGeneraleStats(controlData) {
        const stats = controlData;
        
        this.updateCard('total-controls', stats.total_controls);
        this.updateCard('revenue', this.formatCurrency(stats.revenue));
        this.updateCard('active-agents', stats.active_users);
        this.updateCard('compliance-rate', `${stats.compliance_rate}%`);
    }

    updateDirectionRegionaleStats(regionData) {
        const stats = regionData.regions[0] || {};
        
        this.updateCard('region-agents', stats.agents || 0);
        this.updateCard('monthly-controls', stats.controls || 0);
        this.updateCard('infractions', stats.infractions || 0);
        this.updateCard('revenue', this.formatCurrency(stats.revenue || 0));
    }

    updateChefBrigadeStats(brigadeData) {
        const stats = brigadeData.brigades[0] || {};
        
        this.updateCard('brigade-size', stats.agents || 0);
        this.updateCard('daily-controls', stats.controls || 0);
        this.updateCard('infractions', stats.infractions || 0);
        this.updateCard('revenue', this.formatCurrency(stats.revenue || 0));
    }

    updateAgentStats(controlData) {
        const stats = controlData;
        
        this.updateCard('daily-controls', stats.today_controls);
        this.updateCard('monthly-controls', stats.this_month_controls);
        this.updateCard('infractions', stats.today_infractions);
        this.updateCard('revenue', this.formatCurrency(stats.today_revenue));
    }

    updateCard(cardId, value) {
        const card = document.querySelector(`[data-stats="${cardId}"]`);
        if (card) {
            card.textContent = value;
            // Add animation
            card.classList.add('animate-pulse');
            setTimeout(() => card.classList.remove('animate-pulse'), 1000);
        }
    }

    updateCharts(data) {
        // Update controls chart
        this.updateControlsChart(data.controls);
        
        // Update revenue chart
        this.updateRevenueChart(data.revenue);
        
        // Update regions chart
        this.updateRegionsChart(data.regions);
    }

    updateControlsChart(controlData) {
        const ctx = document.getElementById('controlsChart');
        if (!ctx) return;

        const evolution = controlData.evolution_7_days || [];
        const labels = evolution.map(item => this.formatDate(item.date));
        const values = evolution.map(item => item.count);

        if (window.controlsChart) {
            window.controlsChart.data.labels = labels;
            window.controlsChart.data.datasets[0].data = values;
            window.controlsChart.update();
        } else {
            window.controlsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Contrôles par jour',
                        data: values,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    updateRevenueChart(revenueData) {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        const monthly = revenueData.revenue_by_month || [];
        const labels = monthly.map(item => this.formatMonth(item.month));
        const values = monthly.map(item => item.revenue);

        if (window.revenueChart) {
            window.revenueChart.data.labels = labels;
            window.revenueChart.data.datasets[0].data = values;
            window.revenueChart.update();
        } else {
            window.revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenus mensuels',
                        data: values,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return this.formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    updateRegionsChart(regionsData) {
        const ctx = document.getElementById('regionChart');
        if (!ctx) return;

        const regions = regionsData.regions || [];
        const labels = regions.map(item => item.name);
        const values = regions.map(item => item.controls);

        if (window.regionChart) {
            window.regionChart.data.labels = labels;
            window.regionChart.data.datasets[0].data = values;
            window.regionChart.update();
        } else {
            window.regionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    setupAutoRefresh() {
        setInterval(() => {
            this.loadDashboardStats();
        }, this.refreshInterval);
    }

    setupEventListeners() {
        // Add refresh button listener
        const refreshBtn = document.querySelector('[data-action="refresh-stats"]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadDashboardStats();
            });
        }

        // Add period filter listeners
        const periodFilters = document.querySelectorAll('[data-period]');
        periodFilters.forEach(filter => {
            filter.addEventListener('change', (e) => {
                this.updatePeriod(e.target.value);
            });
        });
    }

    async updatePeriod(period) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/controls?period=${period}`);
            const data = await response.json();
            this.updateControlsChart(data);
        } catch (error) {
            console.error('Error updating period:', error);
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('fr-GN', {
            style: 'currency',
            currency: 'GNF',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-GN', { 
            day: 'numeric', 
            month: 'short' 
        });
    }

    formatMonth(monthString) {
        const [year, month] = monthString.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('fr-GN', { 
            year: 'numeric', 
            month: 'long' 
        });
    }
}

// Initialize dashboard stats when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DashboardStats();
});

// Export for external use
window.DashboardStats = DashboardStats;
