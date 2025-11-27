@extends('layouts.app')

@push('styles')
@include('master.mapping.styles')
@endpush

@section('content')
<div class="container-fluid">
    <div class="mapping-card">
        <div class="card-header">
            @php
                $platformName = \App\Models\Platform::whereRaw('LOWER(name) = ?', [strtolower($platform)])->first()->name 
                    ?? \App\Models\Platform::where('name', 'like', '%' . $platform . '%')->first()->name 
                    ?? ucfirst($platform);
            @endphp
            <h5 class="mb-0">Mapping Produk Platform {{ $platformName }}</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <div>
                        <h6 class="mb-1">Produk Belum Dimapping</h6>
                        <p class="mb-0">Beberapa produk perlu dilakukan mapping sebelum dapat diimpor.</p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table mapping-table">
                    <thead>
                        <tr>
                            <th>Nama Produk Platform</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unmappedProducts as $productName)
                            <tr>
                                <td>{{ $productName }}</td>
                                <td>
                                    <a href="{{ route('master.mapping.show', [
                                        'platform' => $platform, 
                                        'productName' => $productName
                                    ]) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-link"></i> Mapping
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('master.mapping.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>
@endsection