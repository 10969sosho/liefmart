@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-lightbulb fa-lg mt-1"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="alert-heading mb-1">Panduan Penggunaan Fixed Table Scrollbar</h5>
                        <ol class="mb-0">
                            <li>Jika tabel memiliki konten yang melebar, Anda akan melihat scrollbar di bagian bawah layar.</li>
                            <li>Gunakan scrollbar tersebut untuk menggeser tabel ke kanan dan kiri tanpa perlu scroll ke bawah tabel.</li>
                            <li>Saat Anda scroll ke bagian lain halaman, scrollbar akan muncul/hilang sesuai dengan tabel yang sedang terlihat.</li>
                            <li>Tanda panah di sisi kanan tabel menunjukkan bahwa tabel bisa di-scroll secara horizontal.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Tabel dengan Fixed Scrollbar</h5>
                        <p class="text-sm mb-0">
                            Fitur ini membuat scrollbar tabel selalu berada di bagian bawah layar
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-primary">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fa-lg mt-1"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading mb-1">Cara Menggunakan Fixed Table Scrollbar</h5>
                                <p class="mb-0">
                                    Fitur ini otomatis bekerja untuk semua tabel yang memiliki class <code>table-responsive</code>. Tidak perlu modifikasi tambahan pada kode yang sudah ada.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <h6 class="mb-3">Tabel Contoh 1: Tabel Lebar</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>No</th>
                                        @for ($i = 1; $i <= 20; $i++)
                                            <th>Kolom {{ $i }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($i = 1; $i <= 10; $i++)
                                        <tr>
                                            <td>{{ $i }}</td>
                                            @for ($j = 1; $j <= 20; $j++)
                                                <td>Data {{ $i }}-{{ $j }}</td>
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-5 mt-5">
                        <h6 class="mb-3">Tabel Contoh 2: Tabel Panjang</h6>
                        <p class="text-sm mb-3">Jika tabel terlalu panjang, scrollbar tetap muncul di bagian bawah layar.</p>
                        <div class="table-responsive border rounded">
                            <table class="table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>No</th>
                                        @for ($i = 1; $i <= 15; $i++)
                                            <th>Kolom {{ $i }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($i = 1; $i <= 50; $i++)
                                        <tr>
                                            <td>{{ $i }}</td>
                                            @for ($j = 1; $j <= 15; $j++)
                                                <td>Data {{ $i }}-{{ $j }}</td>
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-5 mb-5">
                        <h6 class="mb-3">Tabel Contoh 3: Multiple Tabel</h6>
                        <p class="text-sm mb-3">Scrollbar akan muncul sesuai dengan tabel yang sedang dilihat.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="table-responsive border rounded mb-4">
                                    <table class="table table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>No</th>
                                                @for ($i = 1; $i <= 10; $i++)
                                                    <th>Kolom {{ $i }}</th>
                                                @endfor
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @for ($i = 1; $i <= 5; $i++)
                                                <tr>
                                                    <td>{{ $i }}</td>
                                                    @for ($j = 1; $j <= 10; $j++)
                                                        <td>Data {{ $i }}-{{ $j }}</td>
                                                    @endfor
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive border rounded">
                                    <table class="table table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>No</th>
                                                @for ($i = 1; $i <= 10; $i++)
                                                    <th>Kolom {{ $i }}</th>
                                                @endfor
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @for ($i = 1; $i <= 5; $i++)
                                                <tr>
                                                    <td>{{ $i }}</td>
                                                    @for ($j = 1; $j <= 10; $j++)
                                                        <td>Data {{ $i }}-{{ $j }}</td>
                                                    @endfor
                                                </tr>
                                            @endfor
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mt-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle fa-lg mt-1"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading mb-1">Kelebihan Fitur Ini</h5>
                                <ol class="mb-0">
                                    <li>Pengguna tidak perlu scroll ke bawah tabel untuk melakukan scroll horizontal</li>
                                    <li>Bekerja otomatis pada semua tabel yang menggunakan class <code>table-responsive</code></li>
                                    <li>Meningkatkan user experience dalam menggunakan aplikasi</li>
                                    <li>Scrollbar hanya muncul jika konten tabel lebih lebar dari container</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 