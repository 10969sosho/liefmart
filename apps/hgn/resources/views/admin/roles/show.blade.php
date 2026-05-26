@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Detail Role: {{ $role->display_name }}
                    </h4>
                    <div>
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    
                    <div class="row">
                        <!-- Role Information -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Informasi Role
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Nama:</strong></td>
                                            <td><span class="badge bg-secondary">{{ $role->name }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Display Name:</strong></td>
                                            <td>{{ $role->display_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Deskripsi:</strong></td>
                                            <td>{{ $role->description ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                @if($role->is_active)
                                                    <span class="badge bg-success">Aktif</span>
                                                @else
                                                    <span class="badge bg-danger">Tidak Aktif</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Permissions:</strong></td>
                                            <td><span class="badge bg-primary">{{ $role->permissions->count() }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Users:</strong></td>
                                            <td><span class="badge bg-info">{{ $role->users->count() }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dibuat:</strong></td>
                                            <td>{{ $role->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Diupdate:</strong></td>
                                            <td>{{ $role->updated_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Users dengan Role ini -->
                            @if($role->users->count() > 0)
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-users me-2"></i>
                                            Users dengan Role ini
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach($role->users as $user)
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-user-circle me-2 text-muted"></i>
                                                <div>
                                                    <strong>{{ $user->name }}</strong><br>
                                                    <small class="text-muted">
                                                        {{ $user->email ?: $user->username }}
                                                    </small>
                                                </div>
                                                <div class="ms-auto">
                                                    @if($user->is_active)
                                                        <span class="badge bg-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-danger">Tidak Aktif</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(!$loop->last)<hr>@endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Permissions -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-key me-2"></i>
                                        Permissions yang Diberikan
                                    </h6>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    @if($role->permissions->count() > 0)
                                        @php
                                            $groupedPermissions = $role->permissions->groupBy('category');
                                        @endphp
                                        
                                        @foreach($groupedPermissions as $category => $categoryPermissions)
                                            <div class="mb-4">
                                                <h6 class="text-primary border-bottom pb-2">
                                                    <i class="fas fa-folder me-2"></i>
                                                    {{ ucfirst(str_replace('-', ' ', $category)) }}
                                                    <span class="badge bg-primary">{{ $categoryPermissions->count() }}</span>
                                                </h6>
                                                
                                                <div class="row">
                                                    @foreach($categoryPermissions as $permission)
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="card border-success">
                                                                <div class="card-body p-3">
                                                                    <div class="d-flex align-items-start">
                                                                        <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                                        <div>
                                                                            <strong>{{ $permission->display_name }}</strong>
                                                                            <br>
                                                                            <small class="text-muted">{{ $permission->description }}</small>
                                                                            <br>
                                                                            <span class="badge bg-secondary mt-1">{{ $permission->name }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-ban fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Tidak Ada Permissions</h5>
                                            <p class="text-muted">Role ini belum memiliki permission apapun</p>
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i>Tambah Permissions
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
