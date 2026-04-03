// scripts/user-realtime.js
// NutriDeq User Dashboard Controller - Real-time Macros
(function () {
    'use strict';

    const POLL_INTERVAL = 10000; // 10 seconds

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

        // SVG circumference for r=40 is 2*PI*40 ~= 251.3
        const circumference = 251.3;
        const offset = circumference - (data.pct / 100) * circumference;
        
        // Ensure offset is never less than 0
        const finalOffset = Math.max(0, offset);
        bar.style.strokeDasharray = circumference;
        bar.style.strokeDashoffset = finalOffset;
        
        label.innerText = `${Math.round(data.current)} / ${data.target}g`;
    }

    // PDF Report Generator (Premium Enterprise Feature)
    window.generateClinicalReport = function(selector, filename) {
        const { jsPDF } = window.jspdf;
        const reportArea = document.querySelector(selector);
        if (!reportArea) return;

        console.log('Generating clinical PDF report...');
        
        // Find the button (contextually)
        const btn = event.currentTarget || document.querySelector('button[onclick*="generateClinicalReport"]');
        const originalText = btn.innerHTML;
        
        // Start "Analyzing" State (The WOW Factor)
        btn.innerHTML = '<i class="fas fa-microchip fa-spin"></i> Analyzing Stats...';
        btn.style.opacity = '0.7';
        btn.disabled = true;

        // Visual shimmer effect
        const targetContainer = document.querySelector('.macro-snap-card');
        if (targetContainer) targetContainer.style.filter = 'blur(2px) grayscale(50%)';

        setTimeout(() => {
            html2canvas(document.querySelector('.main-content'), {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgProps = pdf.getImageProperties(imgData);
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save(filename || 'NutriDeq-Clinical-Report.pdf');
                
                // Success Toast
                if (window.showToast) window.showToast('Report generated successfully!', 'check-circle');
                
                // Reset UI
                btn.innerHTML = originalText;
                btn.style.opacity = '1';
                btn.disabled = false;
                if (targetContainer) targetContainer.style.filter = 'none';
                
            }).catch(err => {
                console.error('PDF error:', err);
                btn.innerHTML = 'Error Generating';
                btn.disabled = false;
                if (targetContainer) targetContainer.style.filter = 'none';
            });
        }, 1200); // 1.2s delay for "Analyzing" feel
    };

    document.addEventListener('DOMContentLoaded', init);
})();
