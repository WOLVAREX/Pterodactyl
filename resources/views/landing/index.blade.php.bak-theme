<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IronHost — Game Server Hosting</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
    <style>
        :root {
            --gray-900: hsl(210, 24%, 16%);
            --gray-800: hsl(209, 20%, 25%);
            --gray-700: hsl(209, 18%, 30%);
            --gray-600: hsl(209, 14%, 37%);
            --gray-500: hsl(211, 12%, 43%);
            --gray-400: hsl(211, 10%, 53%);
            --gray-300: hsl(211, 13%, 65%);
            --gray-200: hsl(210, 16%, 82%);
            --gray-100: hsl(214, 15%, 91%);
            --black: #131a20;
            --cyan-600: #0891b2;
            --cyan-500: #06b6d4;
            --cyan-400: #22d3ee;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--gray-800);
            color: var(--gray-100);
            font-family: 'IBM Plex Sans', 'Roboto', system-ui, sans-serif;
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        nav {
            width: 100%;
            background: var(--gray-900);
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 6%;
            height: 64px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
            color: #fff;
        }
        .brand img { height: 28px; width: auto; }
        .nav-links { display: flex; align-items: center; height: 100%; }
        .nav-links a {
            display: flex;
            align-items: center;
            height: 100%;
            padding: 0 20px;
            color: var(--gray-300);
            font-size: 0.9rem;
            transition: all 0.15s;
        }
        .nav-links a:hover { color: #fff; background: var(--black); }
        .nav-links a.cta {
            background: var(--cyan-600);
            color: #fff;
            border-radius: 4px;
            padding: 8px 18px;
            height: auto;
            margin-left: 12px;
            font-weight: 600;
        }
        .nav-links a.cta:hover { background: var(--cyan-500); }
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            margin: -8px;
        }
        .nav-toggle .bar {
            display: block;
            width: 22px;
            height: 2px;
            background: #fff;
            margin: 5px 0;
            transition: transform 0.2s, opacity 0.2s;
        }
        .nav-toggle.open .bar:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .nav-toggle.open .bar:nth-child(2) { opacity: 0; }
        .nav-toggle.open .bar:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
        .hero {
            text-align: center;
            padding: 90px 6% 70px;
            max-width: 780px;
            margin: 0 auto;
        }
        .hero .tag {
            display: inline-block;
            padding: 5px 14px;
            background: var(--gray-700);
            border-radius: 4px;
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: var(--cyan-400);
            margin-bottom: 22px;
            font-weight: 600;
        }
        .hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.25;
            color: #fff;
            margin-bottom: 18px;
        }
        .hero p {
            color: var(--gray-300);
            font-size: 1rem;
            max-width: 560px;
            margin: 0 auto 32px;
        }
        .hero-actions { display: flex; gap: 14px; justify-content: center; }
        .btn {
            display: inline-block;
            padding: 11px 24px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid var(--gray-600);
            color: var(--gray-100);
            transition: all 0.15s;
        }
        .btn:hover { background: var(--gray-700); border-color: var(--gray-500); }
        .btn-solid { background: var(--cyan-600); border-color: var(--cyan-600); color: #fff; }
        .btn-solid:hover { background: var(--cyan-500); border-color: var(--cyan-500); }
        .section-label {
            text-align: center;
            color: var(--cyan-400);
            font-size: 0.8rem;
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .section-title {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 44px;
            color: #fff;
        }
        .features { padding: 70px 6%; background: var(--gray-800); }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .feature-card {
            background: var(--gray-700);
            border-radius: 6px;
            padding: 26px;
            border: 1px solid var(--gray-600);
        }
        .feature-card .icon {
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            background: var(--gray-900);
            border-radius: 6px;
            font-size: 1.1rem;
            margin-bottom: 16px;
            color: var(--cyan-400);
        }
        .feature-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 8px; color: #fff; }
        .feature-card p { color: var(--gray-300); font-size: 0.88rem; }
        .pricing { padding: 70px 6% 100px; background: var(--gray-900); }
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            max-width: 780px;
            margin: 0 auto;
        }
        @media (max-width: 640px) {
            .plan-grid { gap: 10px; }
        }
        .plan-card {
            background: var(--gray-700);
            border: 1px solid var(--gray-600);
            border-radius: 8px;
            padding: 16px 14px;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 640px) {
            .plan-card { padding: 12px 10px; border-radius: 6px; }
        }
        .plan-card.featured { border-color: var(--cyan-500); box-shadow: 0 0 0 1px var(--cyan-500); }
        .plan-card.featured::before {
            content: 'POPULAR';
            position: absolute;
            top: -9px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--cyan-600);
            color: #fff;
            font-size: 0.55rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 3px 9px;
            border-radius: 20px;
        }
        .plan-name { font-size: 0.9rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .plan-desc { color: var(--gray-300); font-size: 0.72rem; margin-bottom: 10px; min-height: 16px; }
        .plan-price { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
        .plan-price span { font-size: 0.7rem; color: var(--gray-400); font-weight: 400; display: block; }
        .plan-specs { list-style: none; margin: 10px 0; padding-top: 10px; border-top: 1px solid var(--gray-600); }
        .plan-specs li { display: flex; justify-content: space-between; font-size: 0.72rem; padding: 2px 0; color: var(--gray-400); }
        .plan-specs li b { color: var(--gray-100); font-weight: 500; }
        .plan-features { list-style: none; margin: 4px 0 12px; }
        .plan-features li { font-size: 0.72rem; padding: 2px 0; color: var(--gray-200); }
        .plan-features li::before { content: '✓ '; color: var(--cyan-400); font-weight: 700; }
        .plan-card .btn { margin-top: auto; text-align: center; }
        .no-plans {
            text-align: center;
            color: var(--gray-400);
            padding: 36px;
            border: 1px dashed var(--gray-600);
            border-radius: 8px;
            max-width: 480px;
            margin: 0 auto;
            font-size: 0.9rem;
        }
        footer {
            background: var(--gray-900);
            padding: 26px 6%;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.8rem;
        }
        @media (max-width: 768px) {
            nav { position: relative; }
            .nav-toggle { display: block; }
            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--gray-900);
                flex-direction: column;
                align-items: stretch;
                height: auto;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.25s ease;
            }
            .nav-links.open {
                max-height: 400px;
                border-top: 1px solid var(--gray-700);
                box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            }
            .nav-links a {
                height: auto;
                padding: 16px 6%;
                border-bottom: 1px solid var(--gray-800);
            }
            .nav-links a.cta {
                margin: 14px 6%;
                text-align: center;
                padding: 11px 18px;
            }
        }
        @media (max-width: 640px) {
            .hero h1 { font-size: 1.9rem; }
            .hero-actions { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="nav-inner">
            <div class="brand">
                <img src="/assets/svgs/pterodactyl.svg" alt="" onerror="this.style.display='none'">
                IronHost
            </div>
            <div class="nav-links" id="nav-links">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="{{ route('auth.login') }}">Login</a>
                <a href="{{ route('auth.register') }}" class="cta">Get Started</a>
            </div>
            <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="nav-links">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </nav>

    <section class="hero">
        <div class="tag">HIGH-PERFORMANCE GAME SERVER HOSTING</div>
        <h1>Deploy your game server in seconds, not hours.</h1>
        <p>Instant setup, DDoS-protected nodes, and full control over your server — powered by IronHost infrastructure.</p>
        <div class="hero-actions">
            <a href="{{ route('auth.register') }}" class="btn btn-solid">Create Account</a>
            <a href="#pricing" class="btn">View Plans</a>
        </div>
    </section>

    <section class="features" id="features">
        <div class="section-label">Capabilities</div>
        <h2 class="section-title">Built for performance</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon">⚡</div>
                <h3>Instant Deployment</h3>
                <p>Your server spins up automatically the moment your plan is provisioned — no waiting on manual setup.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🛡</div>
                <h3>DDoS Protected</h3>
                <p>All nodes run behind protected infrastructure to keep your server online when it matters most.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🗄</div>
                <h3>Full File Access</h3>
                <p>Manage files, run backups, and configure your server directly from a fast, modern control panel.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🧩</div>
                <h3>Any Game, Any Engine</h3>
                <p>Minecraft, Source engine titles, Rust, voice servers, and more — deploy whatever you need.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📡</div>
                <h3>Live Console</h3>
                <p>Real-time server console and resource monitoring, right in your browser.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🕓</div>
                <h3>24/7 Uptime Monitoring</h3>
                <p>Your infrastructure is watched around the clock so issues get caught before they become downtime.</p>
            </div>
        </div>
    </section>

    <section class="pricing" id="pricing">
        <div class="section-label">Plans</div>
        <h2 class="section-title">Choose your plan</h2>

        @if($plans->isEmpty())
            <div class="no-plans">
                No plans are published yet. Add one from <b>Admin → Plans</b> in the panel.
            </div>
        @else
            <div class="plan-grid">
                @foreach($plans as $plan)
                    <div class="plan-card {{ $plan->is_featured ? 'featured' : '' }}">
                        <div class="plan-name">{{ $plan->name }}</div>
                        <div class="plan-desc">{{ $plan->description }}</div>
                        <div class="plan-price">
                            {{ $plan->currency }} {{ number_format($plan->price, 2) }}
                            <span>/ {{ $plan->billing_period }}</span>
                        </div>
                        <ul class="plan-specs">
                            <li>Memory <b>{{ $plan->memory }} MB</b></li>
                            <li>Disk <b>{{ $plan->disk }} MB</b></li>
                            <li>CPU <b>{{ $plan->cpu }}%</b></li>
                            <li>Databases <b>{{ $plan->databases }}</b></li>
                            <li>Backups <b>{{ $plan->backups }}</b></li>
                            <li>Allocations <b>{{ $plan->allocations }}</b></li>
                        </ul>
                        @if($plan->features_list)
                            <ul class="plan-features">
                                @foreach($plan->features_list as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('auth.register') }}" class="btn {{ $plan->is_featured ? 'btn-solid' : '' }}">Get Started</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="pricing" id="resources" style="background: var(--gray-800);">
        <div class="section-label">Build Your Own</div>
        <h2 class="section-title">À la carte pricing</h2>

        @if($resourcePrices->isEmpty())
            <div class="no-plans">
                No resource pricing published yet. Add items from <b>Admin → Resource Prices</b>.
            </div>
        @else
            <div class="plan-grid" style="grid-template-columns: repeat(2, 1fr); max-width: 780px;">
                @foreach($resourcePrices as $item)
                    <div class="plan-card" style="text-align: center;">
                        <div class="plan-name">{{ $item->name }}</div>
                        <div class="plan-price" style="font-size: 1.5rem;">
                            KSh {{ number_format($item->price_kes, 2) }}
                            <span style="display: block; margin-top: 4px;">{{ $item->unit_label }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <footer>
        &copy; {{ date('Y') }} IronHost. All rights reserved.
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('nav-toggle');
            var links = document.getElementById('nav-links');

            function closeMenu() {
                toggle.classList.remove('open');
                links.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function () {
                var isOpen = links.classList.toggle('open');
                toggle.classList.toggle('open', isOpen);
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            links.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', closeMenu);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeMenu();
            });
        })();
    </script>

</body>
</html>
