<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprintCheck - BVN & NIN Verification SDK</title>
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        header {
            background-color: var(--dark);
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .logo span {
            color: var(--primary);
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 2rem;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 4rem 1rem;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            background-color: var(--secondary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0da271;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2rem;
            color: var(--dark);
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .feature-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 350px;
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .pricing {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .pricing-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 300px;
            text-align: center;
        }

        .pricing-card h3 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .price span {
            font-size: 1rem;
            color: var(--gray);
        }

        .pricing-card ul {
            list-style: none;
            margin: 2rem 0;
            text-align: left;
        }

        .pricing-card ul li {
            margin-bottom: 0.5rem;
            position: relative;
            padding-left: 1.5rem;
        }

        .pricing-card ul li::before {
            content: "✓";
            color: var(--secondary);
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .how-it-works {
            margin-bottom: 4rem;
        }

        .steps {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
        }

        .step {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 250px;
            text-align: center;
            position: relative;
        }

        .step-number {
            background-color: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 1rem;
        }

        .contact {
            background-color: var(--dark);
            color: white;
            padding: 4rem 1rem;
            text-align: center;
        }

        .contact-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .contact h2 {
            margin-bottom: 2rem;
        }

        .contact p {
            margin-bottom: 2rem;
        }

        footer {
            background-color: var(--dark);
            color: var(--gray);
            text-align: center;
            padding: 2rem 1rem;
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                text-align: center;
            }

            nav ul {
                margin-top: 1rem;
                justify-content: center;
            }

            nav ul li {
                margin: 0 0.5rem;
            }

            .hero h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
<!-- Header & Navigation -->
<header>
    <div class="nav-container">
        <a href="#" class="logo">Sprint<span>Check</span></a>
        <nav>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Secure Identity Verification SDKs</h1>
        <p>Fast and reliable BVN & NIN verification with facial recognition technology for your applications.</p>
        <a href="https://pub.dev/packages/sprint_check" class="btn">Get Started Today</a>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="container">
    <h2 class="section-title">Our SDK Features</h2>
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon">🔍</div>
            <h3>BVN Verification</h3>
            <p>Quickly verify Bank Verification Numbers (BVN) with our easy-to-integrate SDK, ensuring customers are who they claim to be.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🆔</div>
            <h3>NIN Verification</h3>
            <p>Validate National Identification Numbers (NIN) in real-time, providing secure identity confirmation for your users.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">👤</div>
            <h3>Facial Recognition</h3>
            <p>Advanced facial recognition technology that matches users' faces with their registered identity for additional security.</p>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="container">
    <h2 class="section-title">Simple, Transparent Pricing</h2>
    <div class="pricing">
        <div class="pricing-card">
            <h3>BVN Verification</h3>
            <div class="price">₦60 <span>per verification</span></div>
            <ul>
                <li>Real-time verification</li>
                <li>Detailed BVN data</li>
                <li>Facial matching available</li>
                <li>API documentation</li>
                <li>Technical support</li>
            </ul>
            <a href="#contact" class="btn">Get Access</a>
        </div>
        <div class="pricing-card">
            <h3>NIN Verification</h3>
            <div class="price">₦65 <span>per verification</span></div>
            <ul>
                <li>Instant NIN validation</li>
                <li>Complete identity details</li>
                <li>Facial matching available</li>
                <li>API documentation</li>
                <li>Technical support</li>
            </ul>
            <a href="#contact" class="btn">Get Access</a>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="container how-it-works">
    <h2 class="section-title">How It Works</h2>
    <div class="steps">
        <div class="step">
            <div class="step-number">1</div>
            <h3>Sign Up</h3>
            <p>Contact us to create your developer account and get API credentials.</p>
        </div>
        <div class="step">
            <div class="step-number">2</div>
            <h3>Integrate SDK</h3>
            <p>Follow our simple documentation to integrate our SDK into your application.</p>
        </div>
        <div class="step">
            <div class="step-number">3</div>
            <h3>Verify Users</h3>
            <p>Start verifying users' identities with BVN, NIN, and facial recognition.</p>
        </div>
        <div class="step">
            <div class="step-number">4</div>
            <h3>Scale Securely</h3>
            <p>Grow your business with confidence, backed by our reliable verification system.</p>
        </div>
    </div>
</section>

<!-- Use Cases Section -->
<section class="container">
    <h2 class="section-title">Perfect For</h2>
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon">🏦</div>
            <h3>Financial Services</h3>
            <p>Banks, fintech, and payment platforms needing to verify customer identities for KYC compliance.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3>Mobile Applications</h3>
            <p>Apps requiring secure user verification for sensitive services or transactions.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🛒</div>
            <h3>E-commerce</h3>
            <p>Online marketplaces looking to reduce fraud and ensure secure transactions.</p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact">
    <div class="contact-content">
        <h2>Ready to Get Started?</h2>
        <p>Reach out to our team today for pricing details, documentation, and to start your integration.</p>
        <p><strong>Email:</strong> info@megasprintlimited.com.ng</p>
        <a href="mailto:info@megasprintlimited.com.ng" class="btn">Contact Us</a>
    </div>
</section>

<!-- Footer -->
<footer>
    <p>&copy; 2025 SprintCheck. All rights reserved.</p>
</footer>

</body>
</html>
