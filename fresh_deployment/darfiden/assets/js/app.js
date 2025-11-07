// Darfiden Management System - Main JavaScript

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Close alert manually
function closeAlert(element) {
    const alert = element.closest('.alert');
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 300);
}

// Confirm delete action
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// File upload preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger-color)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--border-color)';
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields');
    }
    
    return isValid;
}

// Number formatting
function formatMoney(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Date formatting
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Active link highlighting
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage) {
            link.classList.add('active');
        }
    });
});

// Calculate totals in forms
function calculateDailyTotal() {
    const opening = parseFloat(document.getElementById('opening_balance')?.value || 0);
    const transfer = parseFloat(document.getElementById('transfer_to_staff')?.value || 0);
    const winnings = parseFloat(document.getElementById('total_winnings')?.value || 0);
    const expenses = parseFloat(document.getElementById('total_expenses')?.value || 0);
    const dailyDebt = parseFloat(document.getElementById('daily_debt')?.value || 0);
    const closing = parseFloat(document.getElementById('closing_balance')?.value || 0);
    const tips = parseFloat(document.getElementById('tips')?.value || 0);
    
    // Cash Balance = Opening + Transfer - Winnings - Expenses - Daily Debt - Closing
    const cashBalance = opening + transfer - winnings - expenses - dailyDebt - closing;
    const cashBalanceField = document.getElementById('cash_balance');
    if (cashBalanceField) {
        cashBalanceField.value = cashBalance.toFixed(2);
    }
    
    // Tips Calculation = Cash Balance + Tips
    const tipsCalculation = cashBalance + tips;
    const tipsCalculationField = document.getElementById('tips_calculation');
    if (tipsCalculationField) {
        tipsCalculationField.value = tipsCalculation.toFixed(2);
    }
}

// Search/Filter table
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let txtValue = tr[i].textContent || tr[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = '';
        } else {
            tr[i].style.display = 'none';
        }
    }
}

// Print report
function printReport() {
    window.print();
}

// Export to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => rowData.push(col.textContent));
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Session timeout warning
let sessionTimeout;
function resetSessionTimeout() {
    clearTimeout(sessionTimeout);
    sessionTimeout = setTimeout(() => {
        alert('Your session is about to expire. Please save your work.');
    }, 55 * 60 * 1000); // 55 minutes
}

document.addEventListener('mousemove', resetSessionTimeout);
document.addEventListener('keypress', resetSessionTimeout);
resetSessionTimeout();