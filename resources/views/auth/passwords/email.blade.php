<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - {{ config('app.name') }}</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .forgot-card {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            max-width: 150px;
            height: auto;
        }
        
        .alert {
            border-radius: 12px;
        }
        
        .form-control {
            padding: 15px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
        
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #198754 0%, #4ba262ff 100%);
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            opacity: 0.95;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="forgot-card">
        <div class="logo">
            <img src="{{ $appLogo }}" alt="Logo">
        </div>
        
        <h2 class="text-center mb-4">Mot de passe oublié</h2>
        
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        
        <p class="text-center text-muted mb-4">
            Entrez votre adresse email pour recevoir un lien de réinitialisation.
        </p>
        
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            
            <div class="mb-3">
                <label for="email" class="form-label">Adresse Email</label>
                <input type="email" 
                       class="form-control @error('email') is-invalid @enderror" 
                       id="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus
                       placeholder="votre@email.com">
                
                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                Envoyer le lien de réinitialisation
            </button>
            
            <div class="back-link">
                <a href="{{ route('login') }}" class="text-decoration-none">
                    ← Retour à la connexion
                </a>
            </div>
        </form>
    </div>
</body>
</html>