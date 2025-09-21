<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .error-icon {
            font-size: 5rem;
            color: #ffc107;
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }
        
        .error-code {
            font-size: 4rem;
            font-weight: 700;
            color: #ffc107;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .error-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .error-details h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .error-details p {
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }
        
        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d, #545b62);
            color: white;
        }
        
        .btn-secondary-custom:hover {
            background: linear-gradient(135deg, #545b62, #3d4449);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
        }
        
        .btn-warning-custom {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .btn-warning-custom:hover {
            background: linear-gradient(135deg, #e0a800, #c69500);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
        }
        
        .search-suggestions {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .search-suggestions h6 {
            color: #1565c0;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .search-suggestions p {
            color: #1565c0;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .error-code {
                font-size: 3rem;
            }
            
            .error-icon {
                font-size: 4rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-custom {
                width: 100%;
            }
        }
        
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="error-container">
        <!-- Error Icon -->
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        
        <!-- Error Code -->
        <div class="error-code">404</div>
        
        <!-- Error Title -->
        <div class="error-title">Halaman Tidak Ditemukan</div>
        
        <!-- Error Message -->
        <div class="error-message">
            Maaf, halaman yang Anda cari tidak dapat ditemukan. 
            Halaman mungkin telah dipindahkan, dihapus, atau URL yang Anda masukkan salah.
        </div>
        
        <!-- Search Suggestions -->
        <div class="search-suggestions">
            <h6><i class="fas fa-lightbulb me-2"></i>Saran Pencarian</h6>
            <p>
                Periksa kembali URL yang Anda masukkan atau gunakan menu navigasi 
                untuk menemukan halaman yang Anda cari.
            </p>
        </div>
        

        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="{{ url()->previous() }}" class="btn btn-custom btn-secondary-custom">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            
            <a href="{{ route('dashboard') }}" class="btn btn-custom btn-primary-custom">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            

        </div>
        
        <!-- Footer -->
        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                {{ now()->format('d/m/Y H:i:s') }} | 
                <i class="fas fa-user me-1"></i>
                {{ auth()->user()->name ?? 'Guest' }}
            </small>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add typing effect to error message
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                const text = errorMessage.textContent;
                errorMessage.textContent = '';
                let i = 0;
                
                function typeWriter() {
                    if (i < text.length) {
                        errorMessage.textContent += text.charAt(i);
                        i++;
                        setTimeout(typeWriter, 50);
                    }
                }
                
                // Start typing effect after a short delay
                setTimeout(typeWriter, 1000);
            }
        });
    </script>
</body>
</html>
