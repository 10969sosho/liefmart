{{-- resources/views/components/navbar.blade.php --}}

<nav class="navbar navbar-expand-lg mb-4" style="background-color: #FFFFFF; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1), 0 2px 4px -1px rgba(99, 102, 241, 0.06);">
    <div class="container-fluid px-0">
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
            <div class="dropdown">
                <button class="btn btn-icon" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
                       style="background-color: #F9FAFB; color: #6366F1;">
                    <i class="fas fa-bell"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-shopping-cart me-2" style="color: #6366F1;"></i>New order received</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-users me-2" style="color: #10B981;"></i>3 users registered</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-check-circle me-2" style="color: #3B82F6;"></i>System update completed</a></li>
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