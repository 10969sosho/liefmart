{{-- 
    Export script partial - Include this in any analytics view.
    Usage: @include('analytics.partials.export_script', ['exportType' => 'gross_profit_offline'])
    Automatically includes the floating export widget if not already present.
--}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

{{-- Floating Export Widget (auto-included, skips if already present) --}}
@if(!isset($_exportWidgetIncluded))
    @php $_exportWidgetIncluded = true; @endphp
    <div id="export-widget" class="export-widget" style="display: none;">
        <button class="export-widget-toggle" onclick="toggleExportWidget()" title="Status Export">
            <i class="fas fa-file-export"></i>
            <span class="export-widget-badge" id="widget-badge" style="display: none;">0</span>
        </button>
        
        <div class="export-widget-panel" id="export-widget-panel">
            <div class="export-widget-header">
                <h6 class="mb-0">Status Export</h6>
                <a href="{{ route('analytics.exports.list') }}" class="text-white" style="font-size: 0.7rem;" title="Lihat Semua">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <div class="export-widget-body" id="export-widget-list">
                <div class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <br><small>Loading...</small>
                </div>
            </div>
        </div>
    </div>

    <style>
    .export-widget {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        font-family: 'Poppins', sans-serif;
    }

    .export-widget-toggle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: none;
        background: linear-gradient(135deg, #6366F1, #4F46E5);
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .export-widget-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.6);
    }

    .export-widget-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #EF4444;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.6rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.4);
    }

    .export-widget-panel {
        position: absolute;
        bottom: 56px;
        right: 0;
        width: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        display: none;
        overflow: hidden;
    }

    .export-widget-panel.show {
        display: block;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .export-widget-header {
        background: linear-gradient(135deg, #6366F1, #4F46E5);
        color: white;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .export-widget-body {
        max-height: 300px;
        overflow-y: auto;
        padding: 8px 0;
    }

    .export-widget-body::-webkit-scrollbar { width: 4px; }
    .export-widget-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    
    /* Bell pulse animation */
    @keyframes bellPulse {
        0% { transform: scale(1) rotate(0deg); }
        20% { transform: scale(1.3) rotate(15deg); }
        40% { transform: scale(1.1) rotate(-15deg); }
        60% { transform: scale(1.25) rotate(10deg); }
        80% { transform: scale(1.05) rotate(-5deg); }
        100% { transform: scale(1) rotate(0deg); }
    }
    .pulse-bell {
        animation: bellPulse 0.6s ease-in-out;
        display: inline-block;
    }
    </style>

    <script>
    function toggleExportWidget() {
        const panel = document.getElementById('export-widget-panel');
        panel.classList.toggle('show');
        if (panel.classList.contains('show')) {
            loadExportWidget();
            setTimeout(() => {
                document.addEventListener('click', closeWidgetOutside, { once: true });
            }, 100);
        }
    }
    
    function closeWidgetOutside(e) {
        const widget = document.getElementById('export-widget');
        if (widget && !widget.contains(e.target)) {
            document.getElementById('export-widget-panel').classList.remove('show');
        }
    }

    // Show widget
    document.addEventListener('DOMContentLoaded', function() {
        const w = document.getElementById('export-widget');
        if (w) w.style.display = 'block';
    });
    </script>
@endif

<script>
    // ====== NOTIFICATION TOAST ======
    var exportNotyf = null;
    document.addEventListener('DOMContentLoaded', function() {
        exportNotyf = new Notyf({
            duration: 4000,
            position: { x: 'right', y: 'top' },
            ripple: true,
            dismissible: true,
            types: [
                {
                    type: 'export-progress',
                    background: '#6366F1',
                    icon: {
                        className: 'fas fa-spinner fa-spin',
                        tagName: 'i',
                        color: 'white'
                    }
                },
                {
                    type: 'export-ready',
                    background: '#10B981',
                    icon: {
                        className: 'fas fa-check-circle',
                        tagName: 'i',
                        color: 'white'
                    },
                    duration: 10000
                },
                {
                    type: 'export-error',
                    background: '#EF4444',
                    icon: {
                        className: 'fas fa-exclamation-circle',
                        tagName: 'i',
                        color: 'white'
                    },
                    duration: 10000
                }
            ]
        });

        // Load export status widget data
        loadExportWidget();
    });

    // ====== DISPATCH EXPORT ======
    function dispatchExport(exportType, filters) {
        // Null-safe toast helper
        function showToast(type, msg) {
            if (exportNotyf) {
                return exportNotyf.open({ type: type, message: msg });
            }
            return null;
        }
        function dismissToast(ref) {
            if (exportNotyf && ref) exportNotyf.dismiss(ref);
        }

        // Animate bell icon
        notifyBell();

        // Show sending toast
        const sendingToast = showToast('export-progress', 'Mengirim permintaan export...');

        fetch('{{ route("analytics.exports.dispatch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                type: exportType,
                filters: filters
            })
        })
        .then(response => response.json())
        .then(data => {
            dismissToast(sendingToast);
            
            if (data.success) {
                showToast('export-ready', 
                    '<div class="d-flex align-items-center gap-2">' +
                    '<span>Export dimulai! 🎉</span>' +
                    '<a href="{{ route("analytics.exports.list") }}" class="btn btn-sm btn-light ms-2" style="border-radius: 4px; padding: 2px 8px; font-size: 12px;">Lihat Status</a>' +
                    '</div>'
                );

                // Refresh widget
                loadExportWidget();
                // Refresh bell count
                refreshBellCount();

                // Start tracking this export
                if (data.export_log_id) {
                    trackExportStatus(data.export_log_id);
                }
            } else {
                showToast('export-error', data.message || 'Gagal memulai export');
            }
        })
        .catch(error => {
            dismissToast(sendingToast);
            showToast('export-error', 'Gagal terhubung ke server');
            console.error('Export dispatch failed:', error);
        });
    }
    window.dispatchExport = dispatchExport;

    // ====== BELL ANIMATION ======
    function notifyBell() {
        var bellIcon = document.querySelector('#exportNotificationsDropdown .fa-bell');
        if (bellIcon) {
            bellIcon.classList.add('pulse-bell');
            setTimeout(function() { bellIcon.classList.remove('pulse-bell'); }, 600);
        }
    }

    function refreshBellCount() {
        // Trigger the same fetch used by navbar to update badge
        if (typeof loadNotifications === 'function') {
            loadNotifications();
        } else {
            // Direct fetch as fallback
            fetch('{{ route("analytics.exports.notifications") }}')
                .then(res => res.json())
                .then(data => {
                    var badge = document.getElementById('notificationBadge');
                    if (badge) {
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                            badge.style.display = 'inline';
                        }
                    }
                })
                .catch(function() {});
        }
    }

    // ====== TRACK EXPORT STATUS ======
    var trackedExports = {};
    
    function trackExportStatus(exportLogId) {
        trackedExports[exportLogId] = {
            checked: false,
            interval: setInterval(function() {
                fetch('{{ route("analytics.exports.status", "") }}/' + exportLogId)
                    .then(res => res.json())
                    .then(data => {
                        const widget = document.getElementById('export-widget-list');
                        
                        if (data.status === 'completed' && !trackedExports[exportLogId].checked) {
                            trackedExports[exportLogId].checked = true;
                            clearInterval(trackedExports[exportLogId].interval);
                            delete trackedExports[exportLogId];

                            // Notify user
                            exportNotyf.open({
                                type: 'export-ready',
                                message: '<div class="d-flex align-items-center gap-2">' +
                                    '<span><strong>' + data.type_label + '</strong> siap diunduh!</span>' +
                                    '<a href="{{ route("analytics.exports.download", "") }}/' + exportLogId + '" class="btn btn-sm btn-light ms-2" style="border-radius: 4px; padding: 2px 8px; font-size: 12px;">Download</a>' +
                                '</div>'
                            });

                            loadExportWidget();
                        }
                        
                        if (data.status === 'failed' && !trackedExports[exportLogId].checked) {
                            trackedExports[exportLogId].checked = true;
                            clearInterval(trackedExports[exportLogId].interval);
                            delete trackedExports[exportLogId];

                            exportNotyf.open({
                                type: 'export-error',
                                message: '<strong>' + data.type_label + '</strong> gagal: ' + (data.error_message || 'Unknown error')
                            });
                            
                            loadExportWidget();
                        }
                    })
                    .catch(() => {
                        clearInterval(trackedExports[exportLogId].interval);
                        delete trackedExports[exportLogId];
                    });
            }, 3000);
        };
    }

    // ====== EXPORT WIDGET ======
    function loadExportWidget() {
        const widget = document.getElementById('export-widget-list');
        if (!widget) return;

        fetch('{{ route("analytics.exports.notifications") }}')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }

                // Build widget content
                if (data.notifications.length === 0) {
                    widget.innerHTML = '<div class="text-center text-muted py-2"><small>Tidak ada export</small></div>';
                } else {
                    let html = '';
                    data.notifications.slice(0, 3).forEach(function(notif) {
                        const statusIcon = notif.status === 'completed' ? 'fa-check-circle text-success' : 
                                          notif.status === 'failed' ? 'fa-exclamation-circle text-danger' : 
                                          'fa-spinner fa-spin text-primary';
                        
                        html += '<div class="d-flex align-items-center gap-2 px-2 py-1 border-bottom">' +
                            '<i class="fas ' + statusIcon + '" style="font-size: 0.8rem;"></i>' +
                            '<div class="flex-grow-1" style="min-width: 0;">' +
                                '<div class="text-truncate" style="font-size: 0.75rem;">' + notif.type_label + '</div>' +
                                '<small class="text-muted" style="font-size: 0.65rem;">' + notif.created_at + '</small>' +
                            '</div>' +
                            (notif.status === 'completed' ? 
                                '<a href="{{ route("analytics.exports.download", "") }}/' + notif.export_log_id + '" class="btn btn-sm btn-success" style="padding: 0 6px; font-size: 0.7rem;"><i class="fas fa-download"></i></a>' : 
                                '') +
                        '</div>';
                    });
                    widget.innerHTML = html;
                }
            })
            .catch(() => {});
    }

    // ====== UTILITY FUNCTIONS ======
    function exportWithFilters(exportType, formElement) {
        const formData = new FormData(formElement);
        const filters = {};
        for (let [key, value] of formData.entries()) {
            if (value) filters[key] = value;
        }
        dispatchExport(exportType, filters);
    }
</script>
