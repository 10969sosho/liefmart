<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Construction - Siap Play</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b35;
            --primary-dark: #e55a2b;
            --accent-color: #ffc107;
            --text-color: #2c3e50;
            --light-color: #ffffff;
            --bg-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        
        .construction-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
        }
        
        .construction-icon {
            font-size: 6rem;
            color: var(--accent-color);
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }
        
        .construction-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .construction-subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .feature-list {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .feature-list h4 {
            color: white;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.9);
            margin-bottom: 1rem;
            padding: 0.5rem;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-item i {
            font-size: 1.2rem;
            margin-right: 1rem;
            color: var(--accent-color);
            width: 25px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            border: 2px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            margin-top: 2rem;
        }
        
        .back-button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .back-button i {
            margin-right: 0.5rem;
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
        }
        
        .shape-1 {
            top: 10%;
            left: 10%;
            width: 100px;
            height: 100px;
            background: var(--accent-color);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-2 {
            top: 20%;
            right: 15%;
            width: 150px;
            height: 150px;
            background: var(--primary-color);
            border-radius: 30%;
            animation: float 8s ease-in-out infinite 1s;
        }
        
        .shape-3 {
            bottom: 20%;
            left: 20%;
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20%;
            animation: float 7s ease-in-out infinite 2s;
        }
        
        .shape-4 {
            bottom: 30%;
            right: 25%;
            width: 60px;
            height: 60px;
            background: var(--accent-color);
            border-radius: 50%;
            animation: float 5s ease-in-out infinite 0.5s;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
            100% {
                transform: translateY(0px) rotate(0deg);
            }
        }
        
        @media (max-width: 768px) {
            .construction-title {
                font-size: 2rem;
            }
            
            .construction-subtitle {
                font-size: 1rem;
            }
            
            .construction-icon {
                font-size: 4rem;
            }
            
            .feature-list {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="construction-container">
        <div class="construction-icon">
            <i class="fas fa-hard-hat"></i>
        </div>
        
        <h1 class="construction-title">Under Construction</h1>
        <p class="construction-subtitle">
            Fitur yang Anda cari sedang dalam tahap pengembangan dan akan segera tersedia.
        </p>
        
        <div class="feature-list">
            <h4><i class="fas fa-tools"></i> Fitur yang Sedang Dikembangkan</h4>
            @if(isset($featureType))
                @if($featureType === 'offline')
                    <div class="feature-item">
                        <i class="fas fa-store"></i>
                        <span>Sistem Penjualan Offline</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-calculator"></i>
                        <span>Laporan Keuangan Offline</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analitik Penjualan Offline</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-undo"></i>
                        <span>Sistem Retur Offline</span>
                    </div>
                @elseif($featureType === 'coffee')
                    <div class="feature-item">
                        <i class="fas fa-coffee"></i>
                        <span>Sistem Manajemen Produk Kopi</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-warehouse"></i>
                        <span>Inventori Khusus Kopi</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Analitik Penjualan Kopi</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-receipt"></i>
                        <span>Invoice Template Kopi</span>
                    </div>
                @else
                    <div class="feature-item">
                        <i class="fas fa-cogs"></i>
                        <span>Sistem sedang dalam pengembangan</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Peningkatan keamanan</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-rocket"></i>
                        <span>Optimasi performa</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Interface yang lebih responsif</span>
                    </div>
                @endif
            @else
                <div class="feature-item">
                    <i class="fas fa-cogs"></i>
                    <span>Sistem sedang dalam pengembangan</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Peningkatan keamanan</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-rocket"></i>
                    <span>Optimasi performa</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Interface yang lebih responsif</span>
                </div>
            @endif
        </div>
        
        <a href="{{ route('dashboard') }}" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
