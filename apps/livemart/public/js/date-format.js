/**
 * Global Date Format Handler for DD/MM/YYYY format
 * This script handles all date inputs to display in DD/MM/YYYY format
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all date inputs with DD/MM/YYYY formatting
    initializeDateInputs();
    
    // Add event listeners for date format conversion
    addDateInputListeners();
});

/**
 * Initialize all date inputs on the page
 */
function initializeDateInputs() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Convert existing values to DD/MM/YYYY format for display
        if (input.value) {
            input.value = convertToDDMMYYYY(input.value);
        }
        
        // Add placeholder text
        input.setAttribute('placeholder', 'DD/MM/YYYY');
        
        // Add data attribute to track original format
        input.setAttribute('data-original-format', 'DD/MM/YYYY');
    });
}

/**
 * Add event listeners for date input handling
 */
function addDateInputListeners() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Handle focus - show DD/MM/YYYY format
        input.addEventListener('focus', function() {
            if (this.value) {
                this.value = convertToDDMMYYYY(this.value);
            }
        });
        
        // Handle blur - convert back to YYYY-MM-DD for form submission
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = convertToYYYYMMDD(this.value);
            }
        });
        
        // Handle change - ensure proper format
        input.addEventListener('change', function() {
            if (this.value) {
                this.value = convertToYYYYMMDD(this.value);
            }
        });
    });
}

/**
 * Convert DD/MM/YYYY to YYYY-MM-DD (for form submission)
 * @param {string} dateString - Date in DD/MM/YYYY format
 * @returns {string} Date in YYYY-MM-DD format
 */
function convertToYYYYMMDD(dateString) {
    if (!dateString) return '';
    
    // If already in YYYY-MM-DD format, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString;
    }
    
    // If in DD/MM/YYYY format, convert to YYYY-MM-DD
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        const parts = dateString.split('/');
        const day = parts[0];
        const month = parts[1];
        const year = parts[2];
        return `${year}-${month}-${day}`;
    }
    
    // If in DD-MM-YYYY format, convert to YYYY-MM-DD
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateString)) {
        const parts = dateString.split('-');
        const day = parts[0];
        const month = parts[1];
        const year = parts[2];
        return `${year}-${month}-${day}`;
    }
    
    return dateString;
}

/**
 * Convert YYYY-MM-DD to DD/MM/YYYY (for display)
 * @param {string} dateString - Date in YYYY-MM-DD format
 * @returns {string} Date in DD/MM/YYYY format
 */
function convertToDDMMYYYY(dateString) {
    if (!dateString) return '';
    
    // If already in DD/MM/YYYY format, return as is
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        return dateString;
    }
    
    // If in YYYY-MM-DD format, convert to DD/MM/YYYY
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        const parts = dateString.split('-');
        const year = parts[0];
        const month = parts[1];
        const day = parts[2];
        return `${day}/${month}/${year}`;
    }
    
    return dateString;
}

/**
 * Format date to DD/MM/YYYY for display
 * @param {Date|string} date - Date object or date string
 * @returns {string} Formatted date string
 */
function formatDateDDMMYYYY(date) {
    if (!date) return '';
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return '';
    
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    return `${day}/${month}/${year}`;
}

/**
 * Format date to DD/MM/YY for display (short year)
 * @param {Date|string} date - Date object or date string
 * @returns {string} Formatted date string
 */
function formatDateDDMMYY(date) {
    if (!date) return '';
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return '';
    
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = String(d.getFullYear()).slice(-2);
    
    return `${day}/${month}/${year}`;
}

/**
 * Get today's date in DD/MM/YYYY format
 * @returns {string} Today's date in DD/MM/YYYY format
 */
function getTodayDDMMYYYY() {
    const today = new Date();
    return formatDateDDMMYYYY(today);
}

/**
 * Get today's date in YYYY-MM-DD format (for form submission)
 * @returns {string} Today's date in YYYY-MM-DD format
 */
function getTodayYYYYMMDD() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Validate date string in DD/MM/YYYY format
 * @param {string} dateString - Date string to validate
 * @returns {boolean} True if valid, false otherwise
 */
function isValidDDMMYYYY(dateString) {
    if (!dateString) return false;
    
    const regex = /^\d{2}\/\d{2}\/\d{4}$/;
    if (!regex.test(dateString)) return false;
    
    const parts = dateString.split('/');
    const day = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const year = parseInt(parts[2], 10);
    
    // Check if date is valid
    const date = new Date(year, month - 1, day);
    return date.getDate() === day && 
           date.getMonth() === month - 1 && 
           date.getFullYear() === year;
}

// Make functions globally available
window.convertToYYYYMMDD = convertToYYYYMMDD;
window.convertToDDMMYYYY = convertToDDMMYYYY;
window.formatDateDDMMYYYY = formatDateDDMMYYYY;
window.formatDateDDMMYY = formatDateDDMMYY;
window.getTodayDDMMYYYY = getTodayDDMMYYYY;
window.getTodayYYYYMMDD = getTodayYYYYMMDD;
window.isValidDDMMYYYY = isValidDDMMYYYY;

// Signal that date-format.js has loaded
window.dateFormatLoaded = true;
console.log('Date format functions loaded successfully');
