{{-- resources/views/components/navbar.blade.php --}}

<nav class="navbar navbar-expand-lg mb-4" style="background-color: #FFFFFF; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1), 0 2px 4px -1px rgba(99, 102, 241, 0.06); min-height: 60px; height: auto !important;">
    <div class="container-fluid px-3">
        <button class="navbar-toggler" type="button" id="sidebarToggle" style="color: #6366F1;">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Main Category Indicator -->
        @php
            $mainCategoryName = session('main_category_name') ?? 'All Categories';
            $mainCategoryId = session('main_category_id') ?? null;
        @endphp
        <div class="ms-3 d-none d-md-flex align-items-center">
            <span class="badge rounded-pill text-bg-primary px-3 py-2 me-2">
                <i class="fas fa-layer-group me-1"></i> 
                {{ $mainCategoryName }}
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="dropdown" id="exportNotificationsDropdown">
                <button class="btn btn-icon position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
                       style="background-color: #F9FAFB; color: #6366F1;">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                          id="notificationBadge" style="font-size: 0.6rem; display: none;">
                        0
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" id="notificationList" style="width: 350px;">
                    <li class="px-3 py-2 text-center text-muted">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <small class="ms-2">Loading notifications...</small>
                    </li>
                </ul>
            </div>
            
            <div class="dropdown">
                <button class="btn rounded-pill px-3 d-flex align-items-center gap-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                       style="background-color: #F9FAFB; border: 1px solid rgba(99, 102, 241, 0.15);">
                    <div class="avatar" style="width: 32px; height: 32px; background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);">
                        {{ Auth::check() ? substr(Auth::user()->name, 0, 1) : 'G' }}
                    </div>
                    <span class="d-none d-md-inline" style="color: #1F2937;">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown" style="border-radius: 0.75rem; box-shadow: 0 0.5rem 1rem rgba(99, 102, 241, 0.15);">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2" style="color: #6366F1;"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2" style="color: #3B82F6;"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2" style="color: #EF4444;"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

@push('scripts')
<script>
    // Notification polling for export readiness
    document.addEventListener('DOMContentLoaded', function() {
        loadNotifications();
        // Poll every 15 seconds
        setInterval(loadNotifications, 15000);

        // Mark notification as read when dropdown item is clicked
        document.getElementById('notificationList').addEventListener('click', function(e) {
            const markReadBtn = e.target.closest('.mark-read-btn');
            if (markReadBtn) {
                e.preventDefault();
                const notificationId = markReadBtn.dataset.notificationId;
                const exportLogId = markReadBtn.dataset.exportId;

                // Mark as read
                fetch('{{ route("analytics.exports.notifications.read", "") }}/' + notificationId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(() => {
                    // Navigate to download if export is completed
                    if (exportLogId) {
                        window.location.href = '{{ route("analytics.exports.download", "") }}/' + exportLogId;
                    }
                });
            }
            
            // Open exports page
            const viewAllBtn = e.target.closest('.view-all-exports');
            if (viewAllBtn) {
                e.preventDefault();
                window.location.href = '{{ route("analytics.exports.list") }}';
            }
        });

        // Mark all as read
        document.addEventListener('click', function(e) {
            const markAllBtn = e.target.closest('#markAllRead');
            if (markAllBtn) {
                e.preventDefault();
                fetch('{{ route("analytics.exports.notifications.read-all") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(() => {
                    loadNotifications();
                });
            }
        });

        // Reload notifications when dropdown opens
        const dropdownEl = document.getElementById('exportNotificationsDropdown');
        if (dropdownEl) {
            dropdownEl.addEventListener('show.bs.dropdown', function() {
                loadNotifications();
            });
        }
    });

    function loadNotifications() {
        fetch('{{ route("analytics.exports.notifications") }}')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('notificationBadge');
                const list = document.getElementById('notificationList');

                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }

                if (list) {
                    if (data.notifications.length === 0) {
                        list.innerHTML = `
                            <li class="px-3 py-3 text-center text-muted">
                                <i class="fas fa-check-circle mb-2" style="font-size: 1.5rem; color: #10B981;"></i>
                                <br><small>Tidak ada notifikasi</small>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item small text-center view-all-exports" href="{{ route("analytics.exports.list") }}">
                                <i class="fas fa-history me-1"></i> Lihat Riwayat Export
                            </a></li>
                        `;
                        return;
                    }

                    let html = '';
                    data.notifications.forEach(function(notif) {
                        const icon = notif.status === 'completed' 
                            ? '<i class="fas fa-check-circle" style="color: #10B981;"></i>' 
                            : '<i class="fas fa-exclamation-circle" style="color: #EF4444;"></i>';
                        
                        const readClass = notif.read ? 'opacity-50' : 'fw-medium';
                        
                        html += `
                            <li class="${readClass}">
                                <a class="dropdown-item mark-read-btn" href="#" 
                                   data-notification-id="${notif.id}" 
                                   data-export-id="${notif.export_log_id}">
                                    <div class="d-flex align-items-start gap-2">
                                        ${icon}
                                        <div class="flex-grow-1">
                                            <small>${notif.message}</small>
                                            <br>
                                            <small class="text-muted">${notif.created_at}</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        `;
                    });

                    html += `
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <div class="d-flex justify-content-between px-3 py-1">
                                <a class="small text-decoration-none view-all-exports" href="{{ route("analytics.exports.list") }}">
                                    <i class="fas fa-history me-1"></i> Lihat Semua
                                </a>
                                <a class="small text-decoration-none" href="#" id="markAllRead">
                                    <i class="fas fa-check-double me-1"></i> Tandai Dibaca
                                </a>
                            </div>
                        </li>
                    `;

                    list.innerHTML = html;
                }
            })
            .catch(function() {
                console.log('Failed to load notifications');
            });
    }
</script>
@endpush