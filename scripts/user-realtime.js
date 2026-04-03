// scripts/user-realtime.js
// NutriDeq User Dashboard Controller - Real-time Macros and Weekly Pulse Charts
(function () {
    'use strict';

    const POLL_INTERVAL = 10000; // 10 seconds
    let trendChart = null;

    function init() {
        console.log('User Real-time Engine Started');
        updateDashboardData();
        setInterval(updateDashboardData, POLL_INTERVAL);
    }

    function updateDashboardData() {
        fetch('api/user_progress_data.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateRings(data.macros);
                    renderTrendChart(data.trends);
                }
            })
            .catch(err => console.error('Dashboard polling error:', err));
    }

    function updateRings(macros) {
        // Protein
        updateRing('p_bar', 'p_val', macros.protein);
        // Carbs
        updateRing('c_bar', 'c_val', macros.carbs);
        // Fats
        updateRing('f_bar', 'f_val', macros.fats);
    }

    function updateRing(id, val_id, data) {
        const bar = document.getElementById(id);
        const label = document.getElementById(val_id);
        if (!bar || !label) return;

        // SVG circumference for r=34 is 2*PI*34 ~= 213.6
        const circumference = 213.6;
        const offset = circumference - (data.pct / 100) * circumference;
        bar.style.strokeDashoffset = offset;
        
        label.innerText = `${Math.round(data.current)} / ${data.target}g`;
    }

    function renderTrendChart(trends) {
        const ctx = document.getElementById('userTrendChart');
        if (!ctx) return;

        if (trendChart) {
            trendChart.data.labels = trends.labels;
            trendChart.data.datasets[0].data = trends.calories;
            trendChart.update();
            return;
        }

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trends.labels,
                datasets: [{
                    label: 'Daily Calories',
                    data: trends.calories,
                    borderColor: '#2e8b57',
                    backgroundColor: 'rgba(46, 139, 87, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#2e8b57'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0,0,0,0.03)' },
                        ticks: { font: { size: 10 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // PDF Report Generator (Enterprise Feature)
    window.printReport = function() {
        const { jsPDF } = window.jspdf;
        const reportArea = document.getElementById('reportableSection');
        if (!reportArea) return;

        console.log('Generating Enterprise PDF Report...');
        
        const btn = document.querySelector('button[onclick="window.printReport()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;

        html2canvas(document.querySelector('.page-container')).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('NutriDeq-Clinical-Report.pdf');
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        }).catch(err => {
            console.error('PDF error:', err);
            btn.innerHTML = 'Error';
            btn.disabled = false;
        });
    };

    document.addEventListener('DOMContentLoaded', init);
})();
