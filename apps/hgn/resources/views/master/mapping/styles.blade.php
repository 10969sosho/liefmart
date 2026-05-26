<style>
    /* Master Mapping Module Styling */
    
    /* Card styling */
    .mapping-card {
        border-radius: var(--card-border-radius);
        box-shadow: 0 4px 15px rgba(181, 198, 224, 0.12);
        border: none;
        overflow: hidden;
        transition: all var(--transition-speed);
        margin-bottom: 2rem;
    }
    
    .mapping-card:hover {
        box-shadow: 0 8px 20px rgba(181, 198, 224, 0.15);
        transform: translateY(-2px);
    }
    
    .mapping-card .card-header {
        background-color: var(--light-color);
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .mapping-card .card-header h3, 
    .mapping-card .card-header h5 {
        margin-bottom: 0;
        font-weight: 600;
        color: var(--text-color);
    }
    
    .mapping-card .card-body {
        padding: 1.5rem;
    }
    
    .mapping-card .card-footer {
        background-color: rgba(181, 198, 224, 0.06);
        border-top: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
    }
    
    /* Table styling */
    .mapping-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 1.5rem;
    }
    
    .mapping-table thead th {
        background-color: rgba(181, 198, 224, 0.08);
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .mapping-table tbody td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.95rem;
        transition: background-color 0.3s ease;
    }
    
    .mapping-table tbody tr:hover {
        background-color: rgba(181, 198, 224, 0.05);
    }
    
    .mapping-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .table-info {
        background-color: transparent !important;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
    }
    
    .table-info th {
        width: 30%;
        background-color: rgba(181, 198, 224, 0.06);
        border-bottom: 1px solid var(--border-color);
    }
    
    .table-info td {
        border-bottom: 1px solid var(--border-color);
        font-weight: 500;
    }
    
    .table-info tr:last-child th,
    .table-info tr:last-child td {
        border-bottom: none;
    }
    
    /* Form elements */
    .form-control {
        border-radius: 12px;
        border: 1.5px solid var(--border-color);
        padding: 0.65rem 1.2rem;
        font-size: 0.95rem;
        transition: all var(--transition-speed);
    }
    
    .form-control-sm {
        border-radius: 10px;
        padding: 0.45rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .form-control:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 0.25rem rgba(181, 198, 224, 0.15);
    }
    
    .form-select {
        border-radius: 12px;
        border: 1.5px solid var(--border-color);
        padding: 0.65rem 2.25rem 0.65rem 1.2rem;
        font-size: 0.95rem;
        background-position: right 1.2rem center;
        transition: all var(--transition-speed);
    }
    
    .form-select:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 0.25rem rgba(181, 198, 224, 0.15);
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
        color: var(--text-color);
    }
    
    .form-text {
        color: var(--text-muted);
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }
    
    /* Button styling */
    .btn {
        border-radius: 12px;
        font-weight: 500;
        padding: 0.65rem 1.5rem;
        font-size: 0.95rem;
        transition: all var(--transition-speed);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.4rem 1rem;
        font-size: 0.85rem;
        border-radius: 10px;
    }
    
    .btn i {
        font-size: 0.85em;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        color: var(--light-color);
        box-shadow: 0 4px 10px rgba(123, 104, 238, 0.2);
    }
    
    .btn-primary:hover {
        background-color: var(--primary-dark);
        box-shadow: 0 6px 15px rgba(123, 104, 238, 0.3);
        transform: translateY(-2px);
    }
    
    .btn-success {
        background-color: var(--success-color);
        color: var(--text-color);
        box-shadow: 0 4px 10px rgba(98, 221, 176, 0.2);
    }
    
    .btn-success:hover {
        background-color: #4fc89a;
        box-shadow: 0 6px 15px rgba(98, 221, 176, 0.3);
        transform: translateY(-2px);
    }
    
    .btn-danger {
        background-color: var(--danger-color);
        color: var(--light-color);
        box-shadow: 0 4px 10px rgba(255, 117, 117, 0.2);
    }
    
    .btn-danger:hover {
        background-color: #ff5e5e;
        box-shadow: 0 6px 15px rgba(255, 117, 117, 0.3);
        transform: translateY(-2px);
    }
    
    .btn-warning {
        background-color: var(--warning-color);
        color: var(--text-color);
        box-shadow: 0 4px 10px rgba(255, 205, 92, 0.2);
    }
    
    .btn-warning:hover {
        background-color: #ffbf3d;
        box-shadow: 0 6px 15px rgba(255, 205, 92, 0.3);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background-color: #e2e8f0;
        color: var(--text-color);
    }
    
    .btn-secondary:hover {
        background-color: #cbd5e0;
        transform: translateY(-2px);
    }
    
    /* Action buttons in tables */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    /* Section headers */
    .section-heading {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: var(--text-color);
    }
    
    .section-subheading {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-bottom: 1.25rem;
    }
    
    /* DataTables custom styling */
    .dataTables_wrapper {
        padding: 1.25rem;
        border-radius: var(--card-border-radius);
    }
    
    .dataTables_filter input {
        border-radius: 12px;
        border: 1.5px solid var(--border-color);
        padding: 0.65rem 1.2rem;
        font-size: 0.9rem;
        margin-left: 0.5rem;
    }
    
    .dataTables_filter input:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 0.25rem rgba(181, 198, 224, 0.15);
    }
    
    .dataTables_length select {
        border-radius: 10px;
        border: 1.5px solid var(--border-color);
        padding: 0.5rem;
        margin: 0 0.5rem;
    }
    
    .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .page-link {
        border-radius: 8px;
        margin: 0 2px;
    }
    
    /* Status badges */
    .badge-mapped {
        background-color: var(--success-color);
        color: var(--text-color);
        font-weight: 500;
        padding: 0.4em 0.8em;
        border-radius: 8px;
    }
    
    .badge-unmapped {
        background-color: var(--warning-color);
        color: var(--text-color);
        font-weight: 500;
        padding: 0.4em 0.8em;
        border-radius: 8px;
    }
    
    /* Highlight effects */
    .bg-success-light {
        background-color: rgba(98, 221, 176, 0.15) !important;
        transition: background-color 0.5s ease;
    }
    
    /* Form sections */
    .form-section {
        background-color: rgba(181, 198, 224, 0.05);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid var(--border-color);
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
        color: var(--text-color);
    }
    
    /* Product mapping item */
    .product-mapping-item {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        background-color: var(--light-color);
        transition: all var(--transition-speed);
    }
    
    .product-mapping-item:hover {
        box-shadow: 0 4px 12px rgba(181, 198, 224, 0.1);
    }
    
    /* Quantity inputs */
    .update-quantity-form {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .update-quantity-form .form-control-sm {
        width: 80px;
    }
    
    /* Responsive tables */
    .table-responsive {
        border-radius: var(--card-border-radius);
        overflow: hidden;
    }
    
    /* Filter section */
    .filter-section {
        background-color: rgba(181, 198, 224, 0.05);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        border: 1px solid var(--border-color);
    }
    
    /* Alert styling */
    .alert {
        border-radius: 12px;
        border: none;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background-color: rgba(98, 221, 176, 0.15);
        color: #2c7a60;
    }
    
    .alert-danger {
        background-color: rgba(255, 117, 117, 0.15);
        color: #a83636;
    }
    
    .alert-warning {
        background-color: rgba(255, 205, 92, 0.15);
        color: #95742a;
    }
    
    .alert-info {
        background-color: rgba(94, 223, 255, 0.15);
        color: #2a7d95;
    }
    
    /* TomSelect Custom Styling */
    .ts-control {
        border: 2px solid var(--border-color) !important;
        border-radius: 12px !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.95rem !important;
        transition: all var(--transition-speed) !important;
        background-color: white !important;
        min-height: 48px !important;
    }
    
    .ts-control:focus {
        border-color: var(--primary-light) !important;
        box-shadow: 0 0 0 3px rgba(181, 198, 224, 0.1) !important;
        outline: none !important;
    }
    
    .ts-control.single .ts-control-input {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .ts-control.single .ts-control-input input {
        padding: 0 !important;
        margin: 0 !important;
        font-size: 0.95rem !important;
        color: var(--text-color) !important;
    }
    
    .ts-dropdown {
        border: 2px solid var(--border-color) !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
        background-color: white !important;
        margin-top: 4px !important;
        z-index: 1000 !important;
    }
    
    .ts-dropdown .ts-option {
        padding: 0.75rem 1rem !important;
        font-size: 0.95rem !important;
        color: var(--text-color) !important;
        transition: all 0.2s ease !important;
        border-radius: 8px !important;
        margin: 2px 4px !important;
    }
    
    .ts-dropdown .ts-option:hover {
        background-color: var(--sidebar-hover) !important;
        color: var(--bs-primary) !important;
    }
    
    .ts-dropdown .ts-option.ts-selected {
        background-color: var(--sidebar-active) !important;
        color: var(--bs-primary) !important;
        font-weight: 500 !important;
    }
    
    .ts-dropdown .ts-option.ts-disabled {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
        cursor: not-allowed !important;
    }
    
    .ts-dropdown .ts-option.ts-disabled:hover {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
    }
    
    .ts-dropdown .ts-option-header {
        background-color: var(--light-color) !important;
        color: var(--text-muted) !important;
        font-weight: 600 !important;
        font-size: 0.8rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        padding: 0.5rem 1rem !important;
        margin: 0 !important;
        border-bottom: 1px solid var(--border-color) !important;
    }
    
    .ts-dropdown .ts-option-empty {
        color: var(--text-muted) !important;
        font-style: italic !important;
        padding: 1rem !important;
        text-align: center !important;
    }
    
    .ts-dropdown .ts-option-loading {
        color: var(--text-muted) !important;
        font-style: italic !important;
        padding: 1rem !important;
        text-align: center !important;
    }
    
    .ts-control.multi .ts-control-input {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .ts-control.multi .ts-control-input input {
        padding: 0 !important;
        margin: 0 !important;
        font-size: 0.95rem !important;
        color: var(--text-color) !important;
    }
    
    .ts-control.multi .ts-control-input > div {
        background-color: var(--bs-primary) !important;
        color: white !important;
        border-radius: 6px !important;
        padding: 0.25rem 0.5rem !important;
        margin: 0.125rem !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
    }
    
    .ts-control.multi .ts-control-input > div .ts-control-remove {
        color: white !important;
        margin-left: 0.5rem !important;
        font-weight: bold !important;
    }
    
    .ts-control.multi .ts-control-input > div .ts-control-remove:hover {
        background-color: rgba(255, 255, 255, 0.2) !important;
        border-radius: 3px !important;
    }
    
    /* Search input styling */
    .ts-control .ts-control-input input {
        background: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        font-family: inherit !important;
    }
    
    .ts-control .ts-control-input input::placeholder {
        color: #6c757d !important;
        font-style: italic !important;
    }
    
    /* Loading state */
    .ts-control.loading .ts-control-input::after {
        content: '' !important;
        position: absolute !important;
        right: 10px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 16px !important;
        height: 16px !important;
        border: 2px solid var(--border-color) !important;
        border-top: 2px solid var(--bs-primary) !important;
        border-radius: 50% !important;
        animation: spin 1s linear infinite !important;
    }
    
    @keyframes spin {
        0% { transform: translateY(-50%) rotate(0deg); }
        100% { transform: translateY(-50%) rotate(360deg); }
    }
    
    /* Focus state improvements */
    .ts-control.focus {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
    }
    
    /* Error state */
    .ts-control.is-invalid {
        border-color: var(--bs-danger) !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    /* Success state */
    .ts-control.is-valid {
        border-color: var(--bs-success) !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .ts-control {
            font-size: 16px !important; /* Prevent zoom on iOS */
        }
        
        .ts-dropdown {
            max-height: 200px !important;
            overflow-y: auto !important;
        }
    }
</style> 