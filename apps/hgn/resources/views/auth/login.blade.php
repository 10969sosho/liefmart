<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard App</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8799ff;
            --primary-dark: #7e76ce;
            --accent-color: #ff7eb9;
            --text-color: #4a5568;
            --light-color: #ffffff;
            --border-color: #edf2f7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            overflow: hidden;
            background-color: #f8f9fa;
            position: relative;
        }
        
        .login-container {
            height: 100vh;
            width: 100%;
            display: flex;
        }
        
        .login-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.8;
            animation: pulse 10s ease-in-out infinite alternate;
        }
        
        .waves-bg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: -1;
            animation: wave 8s ease-in-out infinite;
        }
        
        .left-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: white;
            padding: 3rem;
        }
        
        .left-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
        }
        
        .welcome-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .welcome-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .features-list {
            margin-top: 2rem;
        }
        
        .features-list .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .features-list .feature-icon {
            width: 30px;
            height: 30px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .features-list .feature-text {
            font-size: 0.95rem;
        }
        
        .right-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        
        .login-form-container {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 2;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .login-header p {
            color: #94a3b8;
            font-size: 0.95rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            padding: 0.8rem 1.2rem;
            height: 55px;
            font-size: 0.95rem;
        }
        
        .form-floating label {
            padding: 0.8rem 1.2rem;
            color: #94a3b8;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(135, 153, 255, 0.1);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 500;
            width: 100%;
            font-size: 1rem;
            letter-spacing: 0.02em;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(135, 153, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 8px 25px rgba(135, 153, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .form-check-input {
            width: 1.1em;
            height: 1.1em;
            margin-right: 0.5rem;
            cursor: pointer;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .circle-1, .circle-2, .circle-3 {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255,126,185,0.7), rgba(255,126,185,0.3));
        }
        
        .circle-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -100px;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, rgba(94,223,255,0.5), rgba(94,223,255,0.2));
        }
        
        .circle-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: -75px;
            background: linear-gradient(135deg, rgba(178,102,255,0.6), rgba(178,102,255,0.2));
        }
        
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
            }
            
            .left-section {
                display: none;
            }
            
            .right-section {
                flex: 1;
                padding: 2rem 1.5rem;
            }
            
            .login-form-container {
                padding: 2rem;
                max-width: 100%;
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1) rotate(-1deg);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.08) rotate(1deg);
                opacity: 0.8;
            }
            100% {
                transform: scale(1) rotate(-1deg);
                opacity: 0.5;
            }
        }
        
        @keyframes wave {
            0% {
                transform: translateY(0) translateX(0);
            }
            25% {
                transform: translateY(-25px) translateX(-25px);
            }
            50% {
                transform: translateY(15px) translateX(20px);
            }
            75% {
                transform: translateY(-15px) translateX(-10px);
            }
            100% {
                transform: translateY(0) translateX(0);
            }
        }
        
        .floating-shape {
            position: absolute;
            z-index: -1;
            opacity: 0.75;
            filter: blur(1px);
        }
        
        .particle {
            position: absolute;
            z-index: -1;
            background-color: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- SVG Backgrounds -->
    <svg class="login-bg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
        <path fill="#8799ff" fill-opacity="0.1" d="M0,64L48,80C96,96,192,128,288,138.7C384,149,480,139,576,122.7C672,107,768,85,864,90.7C960,96,1056,128,1152,138.7C1248,149,1344,139,1392,133.3L1440,128L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path>
    </svg>
    
    <svg class="waves-bg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
        <path fill="#8799ff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,160C1248,139,1344,149,1392,154.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
    </svg>

    <div class="login-container">
        <div class="left-section">
            <div class="left-content">
                <div class="welcome-text">
                    <h1>Welcome back!</h1>
                    <p>Log in to access your dashboard and manage your business operations smoothly and efficiently.</p>
                </div>
                
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-text">Real-time business analytics</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="feature-text">Inventory management system</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="feature-text">Sales tracking and reporting</div>
                    </div>
                </div>
            </div>
            
            <!-- Decorative elements -->
            <div class="bubble" style="width: 40px; height: 40px; top: 20%; left: 20%; animation: float 6s ease-in-out infinite;"></div>
            <div class="bubble" style="width: 25px; height: 25px; top: 30%; right: 20%; animation: float 8s ease-in-out infinite 1s;"></div>
            <div class="bubble" style="width: 35px; height: 35px; bottom: 30%; left: 30%; animation: float 7s ease-in-out infinite 2s;"></div>
        </div>
        
        <div class="right-section">
            <!-- Decorative circles -->
            <div class="circle-1"></div>
            <div class="circle-2"></div>
            <div class="circle-3"></div>
            
            <div class="login-form-container">
                <div class="login-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to access your account</p>
                </div>
                
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="form-floating">
                        <input type="text" class="form-control @error('login') is-invalid @enderror" 
                               id="login" name="login" placeholder="Username atau Email" 
                               value="{{ old('login') }}" required autocomplete="username" autofocus>
                        <label for="login">Username atau Email</label>
                        @error('login')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Password" 
                               required autocomplete="current-password">
                        <label for="password">Password</label>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="form-floating">
                        <select class="form-control @error('main_category_id') is-invalid @enderror" 
                               id="main_category_id" name="main_category_id" required>
                            <option value="">Pilih Kategori Utama</option>
                            @foreach($mainCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <label for="main_category_id">Pilih Kategori Utama</label>
                        @error('main_category_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        @if (Route::has('password.request'))
                            <a class="forgot-password" href="{{ route('password.request') }}">
                                Forgot password?
                            </a>
                        @endif
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>&copy; {{ date('Y') }} Dashboard App. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Animation styles -->
    <style>
        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(5deg);
            }
            100% {
                transform: translateY(0px) rotate(0deg);
            }
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.2);
            }
            to {
                transform: rotate(360deg) scale(1);
            }
        }
        
        @keyframes morph {
            0% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
                transform: rotate(0deg);
            }
            33% {
                border-radius: 70% 30% 50% 50% / 30% 40% 60% 70%;
                transform: rotate(2deg);
            }
            66% {
                border-radius: 40% 60% 70% 30% / 50% 60% 40% 50%;
                transform: rotate(-2deg);
            }
            100% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
                transform: rotate(0deg);
            }
        }
    </style>

    <!-- Add more floating shapes with enhanced animations -->
    <div class="floating-shape" style="top: 15%; left: 15%; width: 100px; height: 100px; background: linear-gradient(45deg, rgba(135, 153, 255, 0.5), rgba(126, 118, 206, 0.3)); border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; animation: float 6s ease-in-out infinite, morph 8s ease-in-out infinite;"></div>
    
    <div class="floating-shape" style="top: 70%; right: 10%; width: 150px; height: 150px; background: linear-gradient(45deg, rgba(255, 126, 185, 0.5), rgba(94, 223, 255, 0.3)); border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%; animation: float 7s ease-in-out infinite 1s, morph 10s ease-in-out infinite 1s;"></div>
    
    <div class="floating-shape" style="bottom: 10%; left: 20%; width: 80px; height: 80px; background: linear-gradient(45deg, rgba(94, 223, 255, 0.5), rgba(178, 102, 255, 0.3)); border-radius: 50%; animation: float 5s ease-in-out infinite 2s, spin 8s linear infinite;"></div>
    
    <div class="floating-shape" style="top: 40%; right: 25%; width: 60px; height: 60px; background: linear-gradient(45deg, rgba(255, 207, 115, 0.5), rgba(255, 126, 185, 0.3)); border-radius: 40% 60% 70% 30% / 50% 60% 40% 50%; animation: float 9s ease-in-out infinite 0.5s, morph 12s ease-in-out infinite 0.5s;"></div>
    
    <div class="floating-shape" style="bottom: 35%; left: 35%; width: 45px; height: 45px; background: linear-gradient(45deg, rgba(98, 221, 176, 0.5), rgba(94, 223, 255, 0.3)); border-radius: 50%; animation: spin 10s linear infinite 1s;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced script for more dynamic SVG animations -->
    <script>
        // Function to animate SVG paths
        const animateSVGPath = () => {
            // Get the SVG paths
            const topPath = document.querySelector('.login-bg path');
            const bottomPath = document.querySelector('.waves-bg path');
            
            // Create animation for top path - more frequent updates
            setInterval(() => {
                // Generate significantly different path data for more noticeable changes
                const d1 = `M0,64L48,${65 + Math.random() * 30}C96,${85 + Math.random() * 25},192,128,288,${125 + Math.random() * 25}C384,${140 + Math.random() * 20},480,139,576,${115 + Math.random() * 20}C672,${95 + Math.random() * 25},768,85,864,${80 + Math.random() * 25}C960,${85 + Math.random() * 25},1056,128,1152,${130 + Math.random() * 20}C1248,${140 + Math.random() * 20},1344,139,1392,${125 + Math.random() * 20}L1440,128L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z`;
                
                // Smoothly transition to new path
                topPath.setAttribute('d', d1);
            }, 3000); // More frequent updates (3 seconds)
            
            // Create animation for bottom path - more frequent updates
            setInterval(() => {
                // Generate significantly different path data for more noticeable changes
                const d2 = `M0,${215 + Math.random() * 20}L48,${205 + Math.random() * 20}C96,${195 + Math.random() * 20},192,181,288,${175 + Math.random() * 20}C384,${170 + Math.random() * 25},480,203,576,${215 + Math.random() * 20}C672,${235 + Math.random() * 25},768,267,864,${240 + Math.random() * 25}C960,${225 + Math.random() * 25},1056,181,1152,${150 + Math.random() * 25}C1248,${130 + Math.random() * 20},1344,149,1392,${145 + Math.random() * 20}L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z`;
                
                // Smoothly transition to new path
                bottomPath.setAttribute('d', d2);
            }, 2500); // More frequent updates (2.5 seconds)
        };
        
        // Create particle effect
        const createParticles = () => {
            const container = document.body;
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size between 3 and 8px
                const size = Math.random() * 5 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Random animation
                const duration = Math.random() * 20 + 10;
                const delay = Math.random() * 5;
                
                particle.style.animation = `float ${duration}s ease-in-out infinite ${delay}s`;
                
                container.appendChild(particle);
            }
        };
        
        // Start the animations after the DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            animateSVGPath();
            createParticles();
            
            // Add a transition to make the path changes smooth but faster
            document.querySelectorAll('.login-bg path, .waves-bg path').forEach(path => {
                path.style.transition = 'all 2s ease-in-out'; // Faster transition
            });
        });
    </script>
</body>
</html>
