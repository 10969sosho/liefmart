/**
 * Fixed Table Scroll - Creates fixed scrollbars at the bottom of the screen for tables
 */
document.addEventListener('DOMContentLoaded', function() {
    // Wait for DataTables to initialize if it's being used
    setTimeout(function() {
        initFixedTableScrollbars();
    }, 300);
    
    // Initialize observer to handle dynamic content changes
    const contentObserver = new MutationObserver(function(mutations) {
        const shouldReinit = mutations.some(function(mutation) {
            return mutation.type === 'childList' ||
                   mutation.type === 'attributes' && 
                   (mutation.target.classList.contains('table-responsive') || 
                    mutation.target.querySelector && mutation.target.querySelector('.table-responsive'));
        });
        
        if (shouldReinit) {
            setTimeout(function() {
                initFixedTableScrollbars();
            }, 100);
        }
    });
    
    // Observe content changes
    const contentContainer = document.querySelector('.content');
    if (contentContainer) {
        contentObserver.observe(contentContainer, { 
            childList: true, 
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    // Handle tab switching and accordion toggling
    document.addEventListener('click', function(e) {
        if (e.target && (
            e.target.getAttribute('data-bs-toggle') === 'tab' || 
            e.target.getAttribute('data-bs-toggle') === 'pill' ||
            e.target.getAttribute('data-bs-toggle') === 'collapse'
        )) {
            setTimeout(function() {
                initFixedTableScrollbars();
            }, 350);
        }
    });
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            initFixedTableScrollbars();
        }, 150);
    });
    
    // Special handling for DataTables
    if (typeof $.fn !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
        $(document).on('length.dt', function() {
            setTimeout(function() {
                initFixedTableScrollbars();
            }, 300);
        });
        
        $(document).on('page.dt', function() {
            setTimeout(function() {
                initFixedTableScrollbars();
            }, 300);
        });
        
        $(document).on('search.dt', function() {
            setTimeout(function() {
                initFixedTableScrollbars();
            }, 300);
        });
    }
});

/**
 * Initialize fixed scrollbars for all table-responsive elements
 */
function initFixedTableScrollbars() {
    // Clean up any existing fixed scrollbars
    document.querySelectorAll('.fixed-table-scrollbar').forEach(el => el.remove());
    document.querySelectorAll('.fixed-table-container').forEach(el => {
        // Unwrap the container but keep the content
        const parent = el.parentNode;
        while (el.firstChild) {
            parent.insertBefore(el.firstChild, el);
        }
        parent.removeChild(el);
    });
    
    // Process all table-responsive elements
    document.querySelectorAll('.table-responsive').forEach(function(tableContainer, index) {
        // Skip if this table should not have fixed scrollbar
        if (tableContainer.classList.contains('disable-fixed-scrollbar')) return;
        
        // Skip if this table already has a fixed-table-container parent
        if (tableContainer.closest('.fixed-table-container')) return;
        
        // Check if table is in a hidden element
        const isVisible = isElementVisible(tableContainer);
        if (!isVisible) return;
        
        const table = tableContainer.querySelector('table');
        if (!table) return;
        
        // Create our container structure
        const containerId = 'fixed-table-container-' + index;
        const scrollbarId = 'fixed-table-scrollbar-' + index;
        
        // Create the wrapper
        const wrapper = document.createElement('div');
        wrapper.classList.add('fixed-table-container');
        wrapper.id = containerId;
        
        // Create the content container
        const content = document.createElement('div');
        content.classList.add('fixed-table-content');
        
        // Create the scrollbar container
        const scrollbar = document.createElement('div');
        scrollbar.classList.add('fixed-table-scrollbar');
        scrollbar.id = scrollbarId;
        
        // Create the scroll content (same width as table)
        const scrollContent = document.createElement('div');
        scrollContent.classList.add('table-scroll-content');
        
        // Wrap the table responsive element
        tableContainer.parentNode.insertBefore(wrapper, tableContainer);
        content.appendChild(tableContainer);
        wrapper.appendChild(content);
        scrollbar.appendChild(scrollContent);
        document.body.appendChild(scrollbar);
        
        // Set up the scroll synchronization
        syncScroll(tableContainer, scrollbar, scrollContent, table);
    });
}

/**
 * Synchronize scroll between the table content and the fixed scrollbar
 */
function syncScroll(tableContainer, scrollbar, scrollContent, table) {
    // Set the width of the scroll content to match the table
    function updateScrollWidth() {
        const tableWidth = table.offsetWidth;
        const containerWidth = tableContainer.offsetWidth;
        
        if (tableWidth > containerWidth) {
            scrollContent.style.width = tableWidth + 'px';
            scrollbar.style.display = 'block';
            
            // Add indicator class
            tableContainer.classList.add('has-overflow');
            
            // Position the scrollbar below the visible viewport
            const rect = tableContainer.getBoundingClientRect();
            const isInViewport = rect.top < window.innerHeight && rect.bottom > 0;
            
            if (isInViewport) {
                scrollbar.style.display = 'block';
                
                // Check if table is at the bottom of the page
                if (rect.bottom > window.innerHeight - 100) {
                    scrollbar.style.bottom = '0';
                } else {
                    scrollbar.style.bottom = '20px';
                }
            } else {
                scrollbar.style.display = 'none';
                
                // Remove indicator class
                tableContainer.classList.remove('has-overflow');
            }
        } else {
            scrollbar.style.display = 'none';
        }
    }
    
    // Sync scroll positions
    scrollbar.addEventListener('scroll', function() {
        if (tableContainer.scrollLeft !== scrollbar.scrollLeft) {
            tableContainer.scrollLeft = scrollbar.scrollLeft;
        }
    });
    
    tableContainer.addEventListener('scroll', function() {
        if (scrollbar.scrollLeft !== tableContainer.scrollLeft) {
            scrollbar.scrollLeft = tableContainer.scrollLeft;
        }
    });
    
    // Update positions when window is resized
    window.addEventListener('resize', updateScrollWidth);
    
    // Watch for scrolling to show/hide the scrollbar
    document.addEventListener('scroll', function() {
        updateScrollWidth();
    }, { passive: true });
    
    // Initial setup
    updateScrollWidth();
    
    // Handle table container position changes
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                // Table is visible, show scrollbar and update position
                updateScrollWidth();
            } else {
                // Table is not visible, hide scrollbar
                scrollbar.style.display = 'none';
            }
        });
    }, { threshold: [0, 0.1, 0.5, 1.0] });
    
    observer.observe(tableContainer);
}

/**
 * Check if an element is visible
 */
function isElementVisible(element) {
    // Check for element or any parent with display: none or visibility: hidden
    let current = element;
    while (current) {
        if (current === document) break;
        const style = window.getComputedStyle(current);
        if (style.display === 'none' || style.visibility === 'hidden') {
            return false;
        }
        current = current.parentElement;
    }
    
    // Check if element is in a hidden tab or accordion
    const hiddenTab = element.closest('.tab-pane:not(.active)');
    if (hiddenTab) return false;
    
    const hiddenCollapse = element.closest('.collapse:not(.show)');
    if (hiddenCollapse) return false;
    
    return true;
} 