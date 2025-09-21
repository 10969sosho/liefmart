@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-tools text-warning" style="font-size: 5rem;"></i>
                    </div>
                    <h1 class="fw-bold mb-3">Halaman Dalam Perbaikan</h1>
                    <p class="text-muted mb-4 lead">
                        Mohon maaf atas ketidaknyamanan ini. Halaman yang Anda cari sedang dalam perbaikan untuk meningkatkan layanan kami. Silakan kembali lagi nanti.
                    </p>
                    <div class="mb-4">
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="text-muted mt-2">Perkiraan waktu selesai: Segera</p>
                    </div>
                    <a href="{{ route('finance.choose') }}" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-home me-2"></i> Kembali ke Pilihan Platform
                    </a>
                </div>
            </div>
            
            <div class="mt-4 text-muted">
                <p>Jika Anda memiliki pertanyaan, silakan hubungi tim support kami.</p>
                <p><i class="fas fa-envelope me-2"></i> support@example.com</p>
            </div>
        </div>
    </div>
</div>
@endsection 