/**
 * ============================================
 * WARUNG ONLINE SYSTEM - MAIN JAVASCRIPT
 * ============================================
 * Versi: 3.0
 * Update: 2024
 * Fitur: Utility Functions, Toast, Modal, Sidebar, 
 *        Fetch API, Form Validation, Export, Print, Auto Refresh
 * ============================================
 */

// ========== 1. UTILITY FUNCTIONS ==========

/**
 * Format angka ke format Rupiah
 * @param {number} angka - Nilai yang akan diformat
 * @returns {string} Format Rupiah (Rp 1.000.000)
 */
function formatRupiah(angka) {
    if (!angka || angka === 0 || angka === '0') return 'Rp 0';
    const number = typeof angka === 'string' ? parseInt(angka) : angka;
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}

/**
 * Format tanggal ke format Indonesia
 * @param {string} tanggal - Tanggal dalam format YYYY-MM-DD
 * @returns {string} Format tanggal Indonesia (Senin, 1 Januari 2024)
 */
function formatTanggal(tanggal) {
    if (!tanggal || tanggal === '0000-00-00') return '-';
    const date = new Date(tanggal);
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${days[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

/**
 * Format tanggal untuk input date (YYYY-MM-DD)
 * @param {Date} date - Object Date
 * @returns {string} Format YYYY-MM-DD
 */
function formatTanggalInput(date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Escape HTML untuk mencegah XSS
 * @param {string} text - Teks yang akan di-escape
 * @returns {string} Teks yang sudah di-escape
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format angka dengan separator ribuan
 * @param {number} num - Angka yang akan diformat
 * @returns {string} Angka dengan separator (1.000.000)
 */
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Format angka pendek (1K, 1M, 1B)
 * @param {number} num - Angka yang akan diformat
 * @returns {string} Angka pendek
 */
function formatNumberShort(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// ========== 2. TOAST NOTIFICATION ==========

/**
 * Menampilkan toast notification
 * @param {string} message - Pesan yang ditampilkan
 * @param {string} type - Tipe: 'success', 'error', 'warning', 'info'
 */
function showToast(message, type = 'success') {
    // Hapus toast yang sudah ada
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    
    let icon = '✅';
    let bgColor = '#10b981';
    let borderColor = '#059669';
    
    switch(type) {
        case 'error':
            icon = '❌';
            bgColor = '#ef4444';
            borderColor = '#dc2626';
            break;
        case 'warning':
            icon = '⚠️';
            bgColor = '#f59e0b';
            borderColor = '#d97706';
            break;
        case 'info':
            icon = 'ℹ️';
            bgColor = '#3b82f6';
            borderColor = '#2563eb';
            break;
        default:
            icon = '✅';
            bgColor = '#10b981';
            borderColor = '#059669';
    }
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 20px;">${icon}</span>
            <span style="flex: 1; font-size: 14px; font-weight: 500;">${message}</span>
            <span style="cursor: pointer; font-size: 18px;" onclick="this.parentElement.parentElement.remove()">✕</span>
        </div>
    `;
    
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: ${bgColor};
        color: white;
        padding: 14px 20px;
        border-radius: 12px;
        z-index: 9999;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideUp 0.3s ease;
        min-width: 280px;
        max-width: 420px;
        border-left: 4px solid ${borderColor};
        font-family: 'Inter', sans-serif;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast && toast.parentElement) {
            toast.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
}

// ========== 3. LOADING FUNCTIONS ==========

/**
 * Menampilkan loading pada button
 * @param {string} buttonId - ID button
 * @param {string} originalText - Teks asli button (opsional)
 */
function showLoading(buttonId, originalText = null) {
    const button = document.getElementById(buttonId);
    if (button) {
        if (!originalText) {
            button.dataset.originalText = button.innerHTML;
        } else {
            button.dataset.originalText = originalText;
        }
        button.disabled = true;
        button.innerHTML = '<span class="spinner"></span> Memproses...';
    }
}

/**
 * Menyembunyikan loading pada button
 * @param {string} buttonId - ID button
 */
function hideLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (button && button.dataset.originalText) {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText;
        delete button.dataset.originalText;
    }
}

/**
 * Menampilkan loading global (overlay)
 */
function showGlobalLoading() {
    let overlay = document.getElementById('global-loading');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'global-loading';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(4px);
        `;
        overlay.innerHTML = `
            <div style="text-align: center; background: white; padding: 30px 40px; border-radius: 20px;">
                <div class="spinner-large"></div>
                <p style="margin-top: 15px; color: #333; font-weight: 500;">Memuat data...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

/**
 * Menyembunyikan loading global
 */
function hideGlobalLoading() {
    const overlay = document.getElementById('global-loading');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// ========== 4. MODAL FUNCTIONS ==========

/**
 * Membuka modal
 * @param {string} modalId - ID modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Tutup modal saat klik di luar area
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modalId);
            }
        });
    }
}

/**
 * Menutup modal
 * @param {string} modalId - ID modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// ========== 5. SIDEBAR FUNCTIONS ==========

/**
 * Toggle sidebar untuk mobile
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (sidebar) {
        sidebar.classList.toggle('open');
        if (overlay) {
            overlay.classList.toggle('active');
        }
    }
}

/**
 * Menutup sidebar (mobile)
 */
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    if (sidebar) {
        sidebar.classList.remove('open');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }
}

// Tutup sidebar saat klik di luar (mobile)
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.menu-toggle');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar && toggle && overlay && 
            !sidebar.contains(e.target) && 
            !toggle.contains(e.target) && 
            sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    }
});

// Tutup modal pada escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
        closeSidebar();
    }
});

// ========== 6. FETCH API WITH ERROR HANDLING ==========

/**
 * Fetch API dengan error handling
 * @param {string} url - URL endpoint
 * @param {object} options - Options fetch
 * @returns {Promise} Promise response data
 */
async function fetchAPI(url, options = {}) {
    try {
        showGlobalLoading();
        const response = await fetch(url, options);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        showToast(error.message, 'error');
        throw error;
    } finally {
        hideGlobalLoading();
    }
}

// ========== 7. TABLE FUNCTIONS ==========

/**
 * Filter tabel berdasarkan input
 * @param {string} inputId - ID input search
 * @param {string} tableId - ID tabel
 * @param {number} columnIndex - Index kolom yang difilter (default 1)
 */
function filterTable(inputId, tableId, columnIndex = 1) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const table = document.getElementById(tableId);
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            if (cells[columnIndex]) {
                const textValue = cells[columnIndex].textContent || cells[columnIndex].innerText;
                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
    });
}

/**
 * Sort tabel berdasarkan kolom
 * @param {string} tableId - ID tabel
 * @param {number} column - Index kolom
 * @param {string} type - Tipe data ('string', 'number', 'date')
 */
function sortTable(tableId, column, type = 'string') {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    let direction = table.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        let aVal = a.children[column].textContent;
        let bVal = b.children[column].textContent;
        
        if (type === 'number') {
            aVal = parseFloat(aVal.replace(/[^0-9,-]/g, '')) || 0;
            bVal = parseFloat(bVal.replace(/[^0-9,-]/g, '')) || 0;
        } else if (type === 'date') {
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        }
        
        if (direction === 'asc') {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
    table.dataset.sortDirection = direction;
}

// ========== 8. EXPORT & PRINT FUNCTIONS ==========

/**
 * Export data ke CSV
 * @param {Array} data - Array of objects
 * @param {string} filename - Nama file
 */
function exportToCSV(data, filename = 'export.csv') {
    if (!data || data.length === 0) {
        showToast('Tidak ada data untuk diexport', 'warning');
        return;
    }
    
    const headers = Object.keys(data[0]);
    const csvRows = [];
    csvRows.push(headers.join(','));
    
    for (const row of data) {
        const values = headers.map(header => {
            let value = row[header];
            if (value === null || value === undefined) value = '';
            if (typeof value === 'string') value = value.replace(/"/g, '""');
            return `"${value}"`;
        });
        csvRows.push(values.join(','));
    }
    
    const csvContent = csvRows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
    
    showToast('Data berhasil diexport!', 'success');
}

/**
 * Print element
 * @param {string} elementId - ID element yang akan di-print
 */
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
        showToast('Element tidak ditemukan', 'error');
        return;
    }
    
    const originalContent = document.body.innerHTML;
    const printContent = element.cloneNode(true);
    
    document.body.innerHTML = `
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
            th { background: #f0f0f0; }
            @media print {
                body { margin: 0; padding: 0; }
            }
        </style>
        ${printContent.outerHTML}
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// ========== 9. AUTO REFRESH ==========

class AutoRefresh {
    constructor(interval = 30000) {
        this.interval = interval;
        this.refreshFunctions = [];
        this.timer = null;
    }
    
    addRefreshFunction(fn) {
        if (typeof fn === 'function') {
            this.refreshFunctions.push(fn);
        }
    }
    
    start() {
        if (this.timer) clearInterval(this.timer);
        this.timer = setInterval(() => {
            this.refreshFunctions.forEach(fn => {
                try {
                    fn();
                } catch (error) {
                    console.error('Auto refresh error:', error);
                }
            });
        }, this.interval);
    }
    
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
}

let autoRefreshInstance = null;

/**
 * Memulai auto refresh
 * @param {Function} refreshFn - Fungsi refresh
 * @param {number} interval - Interval dalam milidetik
 */
function startAutoRefresh(refreshFn, interval = 30000) {
    if (!autoRefreshInstance) {
        autoRefreshInstance = new AutoRefresh(interval);
    }
    autoRefreshInstance.addRefreshFunction(refreshFn);
    autoRefreshInstance.start();
}

/**
 * Menghentikan auto refresh
 */
function stopAutoRefresh() {
    if (autoRefreshInstance) {
        autoRefreshInstance.stop();
        autoRefreshInstance = null;
    }
}

// ========== 10. FORM VALIDATION ==========

/**
 * Validasi form
 * @param {string} formId - ID form
 * @param {object} rules - Rules validasi
 * @returns {boolean} Valid atau tidak
 */
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const errors = [];
    
    // Reset styling
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.style.borderColor = '';
    });
    
    for (const [field, rule] of Object.entries(rules)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (!input) continue;
        
        const value = input.value.trim();
        
        if (rule.required && !value) {
            errors.push(`${rule.label} wajib diisi`);
            input.style.borderColor = '#ef4444';
            isValid = false;
        }
        
        if (rule.min && Number(value) < rule.min) {
            errors.push(`${rule.label} minimal ${rule.min}`);
            input.style.borderColor = '#ef4444';
            isValid = false;
        }
        
        if (rule.max && Number(value) > rule.max) {
            errors.push(`${rule.label} maksimal ${rule.max}`);
            input.style.borderColor = '#ef4444';
            isValid = false;
        }
        
        if (rule.minLength && value.length < rule.minLength) {
            errors.push(`${rule.label} minimal ${rule.minLength} karakter`);
            input.style.borderColor = '#ef4444';
            isValid = false;
        }
        
        if (rule.maxLength && value.length > rule.maxLength) {
            errors.push(`${rule.label} maksimal ${rule.maxLength} karakter`);
            input.style.borderColor = '#ef4444';
            isValid = false;
        }
        
        if (rule.pattern && !rule.pattern.test(value)) {
            errors.push(`${rule.label} tidak valid`);
            input.style.borderColor = '#ef4444';
            isValid = false;
        }
    }
    
    if (errors.length > 0) {
        showToast(errors.join('\n'), 'error');
    }
    
    return isValid;
}

// ========== 11. DEBOUNCE FUNCTION ==========

/**
 * Debounce function untuk optimasi event
 * @param {Function} func - Fungsi yang akan di-debounce
 * @param {number} delay - Delay dalam milidetik
 * @returns {Function} Fungsi yang sudah di-debounce
 */
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

// ========== 12. STOCK FUNCTIONS ==========

/**
 * Update display stok
 * @param {number} barangId - ID barang
 * @param {number} newStock - Stok baru
 */
function updateStockDisplay(barangId, newStock) {
    const stockElement = document.getElementById(`stock-${barangId}`);
    if (stockElement) {
        stockElement.textContent = newStock;
        if (newStock <= 5) {
            stockElement.style.color = '#ef4444';
            stockElement.style.fontWeight = 'bold';
        } else {
            stockElement.style.color = '';
            stockElement.style.fontWeight = '';
        }
    }
}

/**
 * Hitung margin
 * @param {number} hargaBeli - Harga beli
 * @param {number} hargaJual - Harga jual
 * @returns {number} Persentase margin
 */
function calculateMargin(hargaBeli, hargaJual) {
    if (!hargaBeli || hargaBeli === 0) return 0;
    return ((hargaJual - hargaBeli) / hargaBeli * 100).toFixed(2);
}

// ========== 13. DATE FUNCTIONS ==========

/**
 * Mendapatkan tanggal sekarang
 * @returns {string} Tanggal dalam format YYYY-MM-DD
 */
function getCurrentDate() {
    const today = new Date();
    return formatTanggalInput(today);
}

/**
 * Mendapatkan range tanggal
 * @param {number} days - Jumlah hari kebelakang
 * @returns {object} Object start dan end date
 */
function getDateRange(days) {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);
    return {
        start: formatTanggalInput(startDate),
        end: formatTanggalInput(endDate)
    };
}

// ========== 14. CONFIRM DIALOG ==========

/**
 * Dialog konfirmasi kustom
 * @param {string} message - Pesan konfirmasi
 * @param {Function} onConfirm - Fungsi jika konfirmasi
 * @param {Function} onCancel - Fungsi jika batal
 */
function confirmDialog(message, onConfirm, onCancel = null) {
    const modal = document.createElement('div');
    modal.className = 'confirm-dialog';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        backdrop-filter: blur(4px);
    `;
    
    modal.innerHTML = `
        <div style="background: white; padding: 24px; border-radius: 20px; max-width: 400px; width: 90%;">
            <div style="margin-bottom: 20px;">
                <span style="font-size: 48px; display: block; text-align: center; margin-bottom: 10px;">⚠️</span>
                <h3 style="text-align: center; color: #333; margin-bottom: 10px;">Konfirmasi</h3>
                <p style="text-align: center; color: #666; line-height: 1.5;">${message}</p>
            </div>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button class="btn btn-outline" id="confirm-cancel" style="padding: 10px 24px;">Batal</button>
                <button class="btn btn-danger" id="confirm-ok" style="padding: 10px 24px;">Ya, Lanjutkan</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    document.getElementById('confirm-cancel').onclick = () => {
        modal.remove();
        if (onCancel) onCancel();
    };
    
    document.getElementById('confirm-ok').onclick = () => {
        modal.remove();
        if (onConfirm) onConfirm();
    };
}

// ========== 15. CSS ANIMATIONS ==========

// Tambahkan CSS animations ke document
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideDown {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(30px);
        }
    }
    
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    @keyframes shake {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(-5deg); }
        75% { transform: rotate(5deg); }
    }
    
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }
    
    .spinner-large {
        width: 40px;
        height: 40px;
        border: 3px solid #e2e8f0;
        border-top: 3px solid #4361ee;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Skeleton loading */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 8px;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* Toast Notification */
    .toast-notification {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        animation: slideUp 0.3s ease;
    }
    
    /* Modal */
    .modal {
        display: none;
    }
    
    .modal.show {
        display: flex;
    }
`;
document.head.appendChild(style);

// ========== 16. INITIALIZATION ==========

// Auto close alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    // Auto close alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentElement) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });
    
    // Add CSRF token to all forms (optional)
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (!form.querySelector('input[name="csrf_token"]')) {
            const token = Math.random().toString(36).substring(2);
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = token;
            form.appendChild(input);
        }
    });
    
    // Add number input validation
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('change', function() {
            const min = parseFloat(this.min);
            const max = parseFloat(this.max);
            let value = parseFloat(this.value);
            
            if (!isNaN(min) && value < min) {
                this.value = min;
            }
            if (!isNaN(max) && value > max) {
                this.value = max;
            }
        });
    });
    
    // Set active menu based on current page
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPage) {
            item.classList.add('active');
        } else if (currentPage === 'index.php' && href === 'dashboard.php') {
            item.classList.add('active');
        }
    });
});

// ========== 17. EXPORT ALL FUNCTIONS TO GLOBAL ==========

window.formatRupiah = formatRupiah;
window.formatTanggal = formatTanggal;
window.formatTanggalInput = formatTanggalInput;
window.escapeHtml = escapeHtml;
window.formatNumber = formatNumber;
window.formatNumberShort = formatNumberShort;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.showGlobalLoading = showGlobalLoading;
window.hideGlobalLoading = hideGlobalLoading;
window.openModal = openModal;
window.closeModal = closeModal;
window.toggleSidebar = toggleSidebar;
window.closeSidebar = closeSidebar;
window.fetchAPI = fetchAPI;
window.filterTable = filterTable;
window.sortTable = sortTable;
window.exportToCSV = exportToCSV;
window.printElement = printElement;
window.startAutoRefresh = startAutoRefresh;
window.stopAutoRefresh = stopAutoRefresh;
window.validateForm = validateForm;
window.debounce = debounce;
window.updateStockDisplay = updateStockDisplay;
window.calculateMargin = calculateMargin;
window.getCurrentDate = getCurrentDate;
window.getDateRange = getDateRange;
window.confirmDialog = confirmDialog;

console.log('✅ Main.js loaded successfully - Warung Online System v3.0');