<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angie — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:       #007A5E;
            --brand-dark:  #005C47;
            --brand-light: #009972;
            --brand-pale:  #e6f4f1;
            --brand-muted: #b2d8d0;
        }

        body {
            min-height: 100vh;
            display: flex;
            font-family: 'Inter', sans-serif;
            background: #f5faf8;
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            flex: 1;
            background: var(--brand);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            width: 480px; height: 480px;
            border-radius: 50%;
            border: 60px solid rgba(255,255,255,0.05);
            top: -160px; left: -160px;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            width: 360px; height: 360px;
            border-radius: 50%;
            border: 50px solid rgba(255,255,255,0.05);
            bottom: -120px; right: -120px;
        }

        .left-inner {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 400px;
        }

        .brand-script {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 6rem;
            color: #fff;
            line-height: 1;
            margin-bottom: 6px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.15);
        }

        .brand-system {
            font-size: 0.78rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.65);
            font-weight: 600;
            margin-bottom: 40px;
        }

        .divider-line {
            width: 60px;
            height: 2px;
            background: rgba(255,255,255,0.3);
            margin: 0 auto 40px;
            border-radius: 2px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            text-align: left;
            width: 100%;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.85);
            font-size: 0.88rem;
        }

        .feature-list li:last-child { border-bottom: none; }

        .feature-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon i {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.9);
        }

        .feature-text strong {
            display: block;
            font-weight: 600;
            color: #fff;
            font-size: 0.9rem;
        }

        .feature-text span {
            font-size: 0.76rem;
            color: rgba(255,255,255,0.5);
        }

        /* ── RIGHT PANEL ── */
        .right-panel {
            width: 560px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 64px 56px;
            background: #fff;
            box-shadow: -4px 0 40px rgba(0,0,0,0.06);
        }

        .login-box { width: 100%; }

        .login-subtitle {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 36px;
        }

        .login-heading {
            font-size: 1.9rem;
            font-weight: 800;
            color: #0f2d24;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .login-heading span {
            color: var(--brand);
        }

        /* Form elements */
        .field-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 22px;
        }

        .input-wrap i.field-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px 14px 44px;
            font-size: 0.95rem;
            color: #111827;
            background: #fafafa;
            outline: none;
            transition: all 0.2s;
        }

        .input-wrap input:focus {
            border-color: var(--brand);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 122, 94, 0.1);
        }

        .input-wrap input::placeholder { color: #c4c4c4; }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: #6b7280;
            cursor: pointer;
            margin-bottom: 28px;
        }

        .remember-label input[type=checkbox] {
            accent-color: var(--brand);
            width: 16px; height: 16px;
        }

        .btn-signin {
            width: 100%;
            padding: 15px;
            background: var(--brand);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 16px rgba(0, 122, 94, 0.3);
        }

        .btn-signin:hover {
            background: var(--brand-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0, 122, 94, 0.4);
        }

        .btn-signin:active { transform: translateY(0); }

        /* Alert */
        .alert-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.86rem;
            color: #dc2626;
            margin-bottom: 22px;
        }

        .alert-ok {
            background: var(--brand-pale);
            border: 1px solid var(--brand-muted);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.86rem;
            color: var(--brand-dark);
            margin-bottom: 22px;
        }

        /* Footer */
        .login-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .login-footer strong {
            color: var(--brand);
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 48px 28px; }
        }
    </style>
</head>
<body>

{{-- LEFT PANEL --}}
<div class="left-panel">
    <div class="left-inner">

        <div class="brand-script">Angie</div>
        <div class="brand-system">Inventory · Sales · Finance</div>
        <div class="divider-line"></div>

        <ul class="feature-list">
            <li>
                <div class="feature-icon">
                    <i class="bi bi-boxes"></i>
                </div>
                <div class="feature-text">
                    <strong>Inventory Management</strong>
                    <span>Raw materials & finished products</span>
                </div>
            </li>
            <li>
                <div class="feature-icon">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <div class="feature-text">
                    <strong>Production Tracking</strong>
                    <span>Batch costing & expiry monitoring</span>
                </div>
            </li>
            <li>
                <div class="feature-icon">
                    <i class="bi bi-shop"></i>
                </div>
                <div class="feature-text">
                    <strong>Branch Distribution</strong>
                    <span>Multi-branch transfers & sales</span>
                </div>
            </li>
            <li>
                <div class="feature-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="feature-text">
                    <strong>Financial Management</strong>
                    <span>Cash, bank, expenses & P&L</span>
                </div>
            </li>
            <li>
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                </div>
                <div class="feature-text">
                    <strong>Reports & Remittance</strong>
                    <span>Branch delivery & remittance reports</span>
                </div>
            </li>
        </ul>

    </div>
</div>

{{-- RIGHT PANEL --}}
<div class="right-panel">
    <div class="login-box">

        <div class="login-heading">
            Sign in to <span>Angie ERP</span>
        </div>
        <div class="login-subtitle">
            Enter your credentials to access your account
        </div>

        @if(session('status'))
            <div class="alert-ok">
                <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-err">
                <i class="bi bi-exclamation-circle me-1"></i>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <label class="field-label">Email Address</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope field-icon"></i>
                    <input type="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="your@email.com"
                           required autofocus>
                </div>
            </div>

            <div>
                <label class="field-label">Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" name="password"
                           placeholder="••••••••" required>
                </div>
            </div>

            <label class="remember-label">
                <input type="checkbox" name="remember">
                Keep me signed in
            </label>

            <button type="submit" class="btn-signin">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="login-footer">
            © {{ date('Y') }} <strong>Powered by: Trinity Software</strong>
            · All rights reserved
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>