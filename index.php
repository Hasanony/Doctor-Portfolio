<?php
// 1. INCLUDE THE SEPARATE DB CONNECTION
require_once 'db.php';

// 2. FETCH DYNAMIC CONTENT FROM DATABASE

// A. Fetch Primary User Profile (ID: 1)
$user_data = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM `user` WHERE `user_id` = 1 LIMIT 1");
    $stmt->execute();
    $user_data = $stmt->fetch();
}
// Fallback if user table is empty
if (!$user_data) {
    $user_data = [
        'user_id'     => 1,
        'name'        => " Dr. MD. Golam Mortuza",
        'profession'  => "Dermatology",
        'email'       => "info@yourdomain.com",
        'address'     => "Advanced Skin Care, Jessore, Bangladesh",
        'description' => "Leading skin specialist in Jessore. Science-backed, artful transformations for your unique anatomy.",
        'number'      => "+15550192834",
        'image'       => "so.png" 
    ];
}

// B. About Section (Dynamic Database Fetch)
$about_data = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `about` LIMIT 1");
    $about_data = $stmt->fetch();
}
if (!$about_data) {
    $about_data = [
        'badge_des'   => "Jessore's Trusted Dermatologist",
        'name'        => "Advanced Skin Care in Jessore",
        'description' => " Dr. MD. Golam Mortuza offers elite clinical dermatology in Jessore. We provide specialized care for acne, pigmentation, and structural rejuvenation using advanced medical procedures."
    ];
}

// C. Signature Therapies
$therapies = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `therapies` ORDER BY `therapies_id` ASC");
    $therapies = $stmt->fetchAll();
}

// D. Clinical Results (Carousel Items)
$results = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `results` ORDER BY `result_id` ASC");
    $results = $stmt->fetchAll();
}

// E. Awards & Certificates Section
$awards = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `awards` ORDER BY `award_id` ASC");
    $awards = $stmt->fetchAll(); // Fixed: Added $stmt->
}
// F. Availability Management Engine
$availability = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM `availability` WHERE `user_id` = :uid LIMIT 1");
    $stmt->execute(['uid' => $user_data['user_id']]);
    $availability = $stmt->fetch();
}

// Format availability text format dynamically
if ($availability) {
    $start_time_formatted = date("g:i A", strtotime($availability['start_time']));
    $end_time_formatted = date("g:i A", strtotime($availability['end_time']));
    
    $availability_text = "Available " . htmlspecialchars($availability['start_day']) . " to " . htmlspecialchars($availability['end_day']) . ", " . $start_time_formatted . " to " . $end_time_formatted;
    
    if (!empty($availability['rest_days'])) {
        $availability_text .= " Without " . htmlspecialchars($availability['rest_days']);
    }
} else {
    $availability_text = "Available Saturday to Thursday, 9am to 9am Without Friday";
}

// Clean phone numbers for click-to-call links
$clean_phone = preg_replace('/[^0-9+]/', '', $user_data['number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title> Dr. MD. Golam Mortuza | Premium Dermatology & Aesthetic Services</title>
    <meta name="description" content="Expert dermatological care by Dr. Samir Patel. Specializing in advanced laser resurfacing, acne scar revision, and non-surgical contouring in a premium clinical setting. Secure administration portal for Dr. MD. Golam Mortuza, the best skin doctor in Jessore. Specialist Dermatologist & Dermato Surgeon.Book your consultation today.">
    <meta name="keywords" content="Dr. MD. Golam Mortuza, Best skin doctor in Jessore, Dermatologist Jessore, Dermato Surgeon, Skin specialist in Jessore">
   
    <link rel="icon" type="image/x-icon" href="admin/<?php echo htmlspecialchars($user_data['image']); ?>" alt="<?php echo htmlspecialchars($user_data['name']); ?>" class="doctor-profile-img">
    <link rel="canonical" href="https://yourdomain.com/">
    
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Dermatologist",
      "name": "Dr. MD. Golam Mortuza",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Jessore",
        "addressCountry": "BD"
      },
      "telephone": "+1-555-019-2834",
      "url": "https://yourdomain.com/"
    }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-light: #ffffff;
            --card-bg: rgba(248, 246, 240, 0.4); 
            --card-border: rgba(0, 0, 0, 0.05);
            --text-main: #1d1d1f; 
            --text-muted: #6e6e73; 
            --accent-glow: #f5ebe6; 
            --accent-blue: #0071e3; 
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --whatsapp-green: #25d366;
            --prescription-purple: #6f42c1;
            --effect-red-ambient: rgba(255, 100, 100, 0.18); 
            
            /* Premium Interaction Highlights */
            --nav-active-bg: rgba(163, 129, 113, 0.12);
            --nav-active-text: #8e6553;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; scroll-behavior: smooth; }
        body { background-color: var(--bg-light); color: var(--text-main); font-family: var(--font); overflow-x: hidden; line-height: 1.6; -webkit-font-smoothing: antialiased; }

        .reveal-element { opacity: 0; transform: translateY(80px) scale(0.98); transition: opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1), transform 1.2s cubic-bezier(0.16, 1, 0.3, 1); will-change: transform, opacity; }
        .reveal-element.active { opacity: 1; transform: translateY(0) scale(1); }

        .glass-card { background: var(--card-bg); border: 1px solid var(--card-border); backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px); border-radius: 28px; padding: 40px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.03); transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.6s, box-shadow 0.6s; position: relative; overflow: hidden; z-index: 1; }
        .glass-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle 250px at var(--mouse-x, 50%) var(--mouse-y, 50%), var(--effect-red-ambient), transparent 100%); opacity: 0; transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1); pointer-events: none; z-index: -1; }
        .glass-card:hover::before { opacity: 1; }
        .glass-card:hover { border-color: rgba(0, 0, 0, 0.1); box-shadow: 0 40px 80px rgba(0, 0, 0, 0.08); }

        /* MIRROR NAV HEADER */
        .glass-nav { 
            position: fixed; 
            top: 0; 
            width: 100%; 
            z-index: 1000; 
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 255, 255, 0.2) 100%); 
            backdrop-filter: blur(5px) saturate(80%); 
            -webkit-backdrop-filter: blur(55px) saturate(80%); 
            border-bottom: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03), inset 0 1px 1px rgba(255, 255, 255, 0.6);
        }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; position: relative; }
        .logo { color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 1.25rem; letter-spacing: -0.5px; white-space: nowrap; z-index: 1010; }
        .logo span { color: var(--text-muted); font-weight: 300; font-size: 0.95rem; text-transform: lowercase; margin-left: 3px; }
        
        nav { display: flex; align-items: center; gap: 10px; z-index: 1005; }
        
        /* PREMIUM NAV LINKS LINK HIGHLIGHTS & HOVER LETTER-SPACING SPECS */
        nav a.nav-link { 
            color: var(--text-main); 
            text-decoration: none; 
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem; 
            font-weight: 400; 
            letter-spacing: 0px;
            background: transparent;
            transition: color 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                        background-color 0.4s cubic-bezier(0.16, 1, 0.3, 1), 
                        letter-spacing 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
            white-space: nowrap; 
        }
        nav a.nav-link:hover { 
            color: var(--text-main); 
            letter-spacing: 1.5px;
            background-color: rgba(0, 0, 0, 0.03);
        }
        nav a.nav-link.active {
            color: var(--nav-active-text);
            background-color: var(--nav-active-bg);
            font-weight: 500;
        }

        .menu-toggle { display: none; flex-direction: column; justify-content: space-between; width: 22px; height: 16px; backdrop-filter: blur(159px) saturate(80%); -webkit-backdrop-filter: blur(55px) saturate(80%); border-bottom: 1px solid rgba(255, 255, 255, 1); border: none; cursor: pointer; z-index: 1010; padding: 0; }
        .menu-toggle span { width: 100%; height: 2px; background-color: var(--text-main); border-radius: 2px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .menu-toggle.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .menu-toggle.active span:nth-child(2) { opacity: 0; }
        .menu-toggle.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        .main-content { max-width: 1140px; margin: 120px auto 60px auto; padding: 0 24px; }
        .section { margin-bottom: 100px; scroll-margin-top: 130px; }
        .section-title { font-size: 2.2rem; font-weight: 600; margin-bottom: 40px; text-align: center; letter-spacing: -0.5px; }

        .hero-card { display: grid; grid-template-columns: 1.1fr 0.9fr; align-items: center; gap: 40px; padding: 50px; overflow: hidden; perspective: 1000px; }
        .hero-info { z-index: 2; }
        
        .hero-image-wrapper { display: flex; justify-content: center; align-items: center; width: 100%; height: 380px; z-index: 1; transform-style: preserve-3d; background: rgba(0,0,0,0.02); border-radius: 24px; overflow: hidden; }
        .doctor-profile-img { width: 100%; height: 100%; display: block; object-fit: contain; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); filter: drop-shadow(0px 10px 15px rgba(0, 0, 0, 0.04)); }
        .glass-card:hover .doctor-profile-img { transform: translateZ(20px) scale(1.02); }

        h1 { font-size: 2.8rem; font-weight: 700; line-height: 1.15; letter-spacing: -1.5px; margin-bottom: 14px; color: var(--text-main); }
        .profession-sub { font-size: 1.25rem; font-weight: 500; color: #a38171; margin-bottom: 20px; letter-spacing: -0.5px; }
        .hero-info p { color: var(--text-muted); font-size: 1.05rem; margin-bottom: 35px; font-weight: 400; }

        .btn-primary, .btn-secondary, .btn-whatsapp, .btn-nav-prescription { display: inline-block; padding: 10px 24px; border-radius: 30px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); white-space: nowrap; }
        .btn-primary { background: var(--text-main); color: var(--bg-light); border: 1px solid transparent; }
        .btn-primary:hover { transform: translateY(-2px); background: #000000; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .btn-secondary { color: var(--text-main); border: 1px solid var(--card-border); background: rgba(0,0,0,0.01); }
        .btn-secondary:hover { background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.15); transform: translateY(-2px); }
        
        /* NEW DEDICATED PRESTIGE BUTTON STYLING FOR NAVBAR */
        .btn-nav-prescription { background: rgba(111, 66, 193, 0.08); color: var(--prescription-purple); border: 1px solid rgba(111, 66, 193, 0.2); }
        .btn-nav-prescription:hover { background: var(--prescription-purple); color: #fff; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(111, 66, 193, 0.2); }

        .btn-whatsapp { background: transparent; color: var(--text-main); border: 1px solid rgba(37, 211, 102, 0.3); }
        .btn-whatsapp:hover { background: rgba(37, 211, 102, 0.05); border-color: var(--whatsapp-green); transform: translateY(-2px); }
        
        .cta-group { display: flex; flex-wrap: wrap; gap: 14px; }

        .grid-2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; align-items: center; }
        .badge { background: rgba(163, 129, 113, 0.08); color: #8e6553; padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; display: inline-block; }
        h2 { font-size: 2.2rem; margin: 15px 0; font-weight: 600; letter-spacing: -1px; color: var(--text-main); }
        .about-text p { color: var(--text-muted); margin-bottom: 20px; font-size: 1.05rem; }

        .treatment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
        .treatment-card .icon { font-size: 2rem; margin-bottom: 15px; display: inline-block; }
        .treatment-card h3 { font-size: 1.2rem; margin-bottom: 10px; font-weight: 500; }
        .treatment-card p { color: var(--text-muted); font-size: 0.95rem; }

        .comparison-wrapper { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 40px; align-items: center; }
        .comparison-container { position: relative; width: 100%; height: 380px; border-radius: 20px; overflow: hidden; border: 1px solid var(--card-border); user-select: none; -webkit-user-select: none; background-color: rgba(0,0,0,0.02); }
        
        .comparison-container .img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: contain; background-repeat: no-repeat; background-position: center; pointer-events: none; }
        .foreground-img { z-index: 2; clip-path: inset(0 50% 0 0); will-change: clip-path; }
        
        .slider { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: transparent; opacity: 0; cursor: ew-resize; z-index: 10; -webkit-appearance: none; appearance: none; }
        .slider-button { position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: rgba(255, 255, 255, 0.8); pointer-events: none; z-index: 5; transform: translateX(-50%); will-change: left; }
        .slider-button::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 38px; height: 38px; background: rgba(255, 255, 255, 0.75); border: 1px solid rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .comparison-text h3 { font-size: 1.5rem; margin-bottom: 12px; font-weight: 500; letter-spacing: -0.5px; }
        .comparison-text p { color: var(--text-muted); font-size: 1rem; line-height: 1.6; }

        .results-carousel-wrapper { position: relative; width: 100%; overflow: hidden; padding: 20px 0; }
        .results-track { display: flex; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); will-change: transform; gap: 30px; }
        .carousel-item { flex: 0 0 100%; width: 100%; }
        .carousel-nav-container { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 30px; }
        
        .nav-arrow { background: rgba(0, 0, 0, 0.03); border: 1px solid var(--card-border); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.1rem; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .nav-arrow:hover { background: var(--text-main); color: var(--bg-light); transform: scale(1.05); }
        .carousel-dots { display: flex; gap: 8px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(0, 0, 0, 0.1); cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .dot.active { background: #a38171; width: 24px; border-radius: 4px; }

        .awards-container { display: flex; flex-direction: column; gap: 40px; }
        .award-row { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; padding: 20px 0; }
        .award-row:nth-child(even) .award-image-box { order: 2; }
        .award-row:nth-child(even) .award-details-box { order: 1; }
        
        .award-image-box { width: 100%; height: 320px; border-radius: 20px; overflow: hidden; border: 1px solid var(--card-border); background: rgba(0,0,0,0.02); display: flex; justify-content: center; align-items: center; }
        .award-img { width: 100%; height: 100%; object-fit: contain; display: block; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
        .glass-card:hover .award-img { transform: scale(1.02); }
        .award-details-box h3 { font-size: 1.5rem; font-weight: 600; color: var(--text-main); letter-spacing: -0.5px; }

        .contact-card { max-width: 650px; margin: 0 auto; text-align: center;}
        .phone-display-container { margin-top: 25px; padding: 30px; background: #ffffff; border: 1px solid var(--card-border); border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); position: relative; }
        .phone-label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 10px; }
        .phone-link-large { display: inline-block; font-size: 2.2rem; font-weight: 700; color: var(--text-main); text-decoration: none; letter-spacing: -1px; transition: color 0.3s, transform 0.3s; }
        .phone-link-large:hover { color: #a38171; transform: scale(1.02); }
        
        .copy-btn { background: rgba(0,0,0,0.03); border: 1px solid var(--card-border); padding: 6px 14px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; color: var(--text-muted); cursor: pointer; margin-top: 10px; display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s; }
        .copy-btn:hover { background: var(--text-main); color: var(--bg-light); }
        .action-grid-dual { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 25px; }
        .action-grid-dual .btn-primary, .action-grid-dual .btn-whatsapp { margin: 0; text-align: center; padding: 14px; }
        .hours-tag { margin-top: 15px; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }

        footer { text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 40px 0; border-top: 1px solid var(--card-border); margin-top: 60px;}
        .footer-address { font-style: normal; margin-bottom: 12px; color: var(--text-main); font-weight: 400; font-size: 0.9rem; letter-spacing: -0.1px; }

        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            nav {
                position: absolute; 
                top: 100%; 
                left: 0; 
                width: 100%;
                backdrop-filter: blur(5px) saturate(80%);
                -webkit-backdrop-filter: blur(55px) saturate(80%);
                background: rgba(255, 255, 255, 0.94);
                border-bottom: 1px solid var(--card-border);
                flex-direction: column; 
                padding: 20px 0 30px 0; 
                gap: 16px;
                opacity: 0; 
                pointer-events: none; 
                transform: translateY(-10px);
                transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }
            nav.active { opacity: 1; pointer-events: auto; transform: translateY(0); }
            nav a.nav-link { margin-left: 0; font-size: 1.05rem; width: 85%; text-align: center; padding: 10px 0; border-radius: 12px; }
            nav a.nav-link:hover { letter-spacing: 1px; }
            nav .btn-primary, nav .btn-nav-prescription { width: 85%; text-align: center; }
            .hero-card { grid-template-columns: 1fr; padding: 40px 24px; text-align: center; gap: 30px; }
            .hero-image-wrapper { grid-row: 1; height: 260px; }
            .hero-info { grid-row: 2; padding: 0; }
            h1 { font-size: 2.1rem; }
            .cta-group { justify-content: center; }
            .grid-2 { grid-template-columns: 1fr; gap: 35px; }
            .results-carousel-wrapper { overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; }
            .results-track { gap: 15px; }
            .carousel-item { scroll-snap-align: start; }
            .carousel-nav-container { display: none; }
            .comparison-wrapper { grid-template-columns: 1fr; gap: 25px; padding: 24px; }
            .comparison-container { height: 240px; width: 100%; }
            .award-row { grid-template-columns: 1fr; gap: 20px; padding: 10px 0; }
            .award-image-box { height: 220px; }
            .phone-link-large { font-size: 1.6rem; }
            .action-grid-dual { grid-template-columns: 1fr; gap: 10px; }
        }
    </style>
</head>
<body>

    <header class="glass-nav">
        <div class="nav-container">
            <a href="#" class="logo">
                <?php echo htmlspecialchars($user_data['name']); ?><span>. <?php echo htmlspecialchars($user_data['profession']); ?></span>
            </a>
            
            <button class="menu-toggle" aria-label="Toggle navigation" id="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav id="nav-menu">
                <a href="#about" class="nav-link">About</a>
                <a href="#treatments" class="nav-link">Treatments</a>
                <a href="#results" class="nav-link">Results</a>
                <?php if (!empty($awards)): ?>
                <a href="#awards" class="nav-link">Awards</a>
                <?php endif; ?>
                <a href="#contact" class="nav-link">Contact</a>
                
                <a href="pres.php" class="btn-nav-prescription">📄 Prescriptions</a>
                <a href="tel:<?php echo $clean_phone; ?>" class="btn-primary">Call Desk</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        
        <section class="hero-section section reveal-element" id="hero">
            <div class="glass-card hero-card">
                <div class="hero-info">
                    <h1><?php echo htmlspecialchars($user_data['name']); ?></h1>
                    <div class="profession-sub"><?php echo htmlspecialchars($user_data['profession']); ?></div>
                    <p><?php echo htmlspecialchars($user_data['description']); ?></p>
                    
                    <div class="cta-group">
                        <a href="#treatments" class="btn-secondary">Explore</a>
                        <a href="tel:<?php echo $clean_phone; ?>" class="btn-primary">Call Now</a>
                    </div>
                </div>
                <div class="hero-image-wrapper">
                    <img src="admin/<?php echo htmlspecialchars($user_data['image']); ?>" alt="<?php echo htmlspecialchars($user_data['name']); ?>" loading="lazy" class="doctor-profile-img">
                </div>
            </div>
        </section>

        <section id="about" class="section reveal-element">
            <div class="glass-card grid-2 about-stats-grid">
                <div class="about-text">
                    <span class="badge"><?php echo htmlspecialchars($about_data['badge_des']); ?></span>
                    <h2><?php echo htmlspecialchars($about_data['name']); ?></h2>
                    <p><?php echo htmlspecialchars($about_data['description']); ?></p>
                </div>
                <div class="about-stats">
                    <div class="stat-box"> 
                        <h3>12+</h3>
                        <p>Years Of Practice</p>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($therapies)): ?>
        <section id="treatments" class="section reveal-element">
            <h2 class="section-title">Signature Therapies</h2>
            <div class="treatment-grid">
                <?php foreach ($therapies as $therapy): ?>
                    <div class="glass-card treatment-card">
                        <div class="icon"><?php echo !empty($therapy['icon']) ? htmlspecialchars($therapy['icon']) : '✨'; ?></div>
                        <h3><?php echo htmlspecialchars($therapy['name']); ?></h3>
                        <p><?php echo htmlspecialchars($therapy['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
        <section id="results" class="section reveal-element">
            <h2 class="section-title">Clinical Results</h2>
            
            <div class="results-carousel-wrapper" id="carousel-wrapper">
                <div class="results-track" id="carousel-track">
                    
                    <?php foreach ($results as $case): ?>
                    <div class="carousel-item">
                        <div class="glass-card comparison-wrapper">
                            <div class="comparison-container">
                                <div class="img background-img" style="background-image: url('admin/<?php echo htmlspecialchars($case['bef_img']); ?>');"></div>
                                <div class="img foreground-img" style="background-image: url('admin/<?php echo htmlspecialchars($case['after_img']); ?>');"></div>
                                <input type="range" min="0" max="100" value="50" class="slider">
                                <div class="slider-button"></div>
                            </div>
                            <div class="comparison-text">
                                <span class="badge" style="margin-bottom: 10px; display: inline-block;">
                                    Case Study <?php echo htmlspecialchars($case['result_id']); ?>
                                </span>
                                <h3><?php echo htmlspecialchars($case['name']); ?></h3>
                                <p><?php echo htmlspecialchars($case['description']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <div class="carousel-nav-container">
                    <button class="nav-arrow" id="prev-case-btn" aria-label="Previous case">←</button>
                    <div class="carousel-dots" id="carousel-dots-group"></div>
                    <button class="nav-arrow" id="next-case-btn" aria-label="Next case">→</button>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($awards)): ?>
        <section id="awards" class="section reveal-element">
            <h2 class="section-title">Awards & Certificates</h2>
            <div class="glass-card awards-container">
                
                <?php foreach ($awards as $award): ?>
                <div class="award-row">
                    <div class="award-image-box">
                        <img src="admin/<?php echo htmlspecialchars($award['image_path']); ?>" alt="<?php echo htmlspecialchars($award['name']); ?>" loading="lazy" class="award-img">
                    </div>
                    <div class="award-details-box">
                        <span class="badge" style="margin-bottom: 10px;"><?php echo htmlspecialchars($award['badge']); ?></span>
                        <h3><?php echo htmlspecialchars($award['name']); ?></h3>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </section>
        <?php endif; ?>
        
        <section id="contact" class="section reveal-element">
            <div class="glass-card contact-card">
                <h2>Begin Your Skin Journey</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 10px;">Connect instantly with our private practice clinical desk.</p>
                
                <div class="phone-display-container">
                    <div class="phone-label">Direct Clinical Line</div>
                    <a href="tel:<?php echo $clean_phone; ?>" class="phone-link-large" id="clinic-phone"><?php echo htmlspecialchars($user_data['number']); ?></a>
                    
                    <div>
                        <button class="copy-btn" id="copy-number-btn" data-phone="<?php echo htmlspecialchars($user_data['number']); ?>">
                            📋 Copy Number
                        </button>
                    </div>

                    <div class="action-grid-dual">
                        <a href="tel:<?php echo $clean_phone; ?>" class="btn-primary">📞 Call Now</a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user_data['number']); ?>" target="_blank" class="btn-whatsapp">💬 WhatsApp</a>
                    </div>

                    <div class="hours-tag"><?php echo htmlspecialchars($availability_text); ?></div>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <address class="footer-address">📍 <?php echo htmlspecialchars($user_data['address']); ?></address>
        <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($user_data['name']); ?>. All Rights Reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Scroll reveal tracking
            const revealElements = document.querySelectorAll('.reveal-element');
            const revealObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, { threshold: 0.05, rootMargin: '0px 0px -100px 0px' });

            revealElements.forEach(element => { revealObserver.observe(element); });

            // AUTOMATED ACTIVE VIEWPORT SECTION TRACKING FOR REFLECTIVE LINK STATES
            const sections = document.querySelectorAll('.section[id]');
            const navLinks = document.querySelectorAll('nav a.nav-link');

            const activeSectionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const currentId = entry.target.getAttribute('id');
                        navLinks.forEach(link => {
                            if (link.getAttribute('href') === `#${currentId}`) {
                                link.classList.add('active');
                            } else {
                                link.classList.remove('active');
                            }
                        });
                    }
                });
            }, { threshold: 0.35, rootMargin: '-10% 0px -40% 0px' });

            sections.forEach(section => activeSectionObserver.observe(section));

            // Card mouse track alignment
            const cards = document.querySelectorAll('.glass-card');
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                    const tiltX = x - (rect.width / 2);
                    const tiltY = y - (rect.height / 2);
                    card.style.transform = `perspective(1200px) rotateX(${-tiltY * 0.005}deg) rotateY(${tiltX * 0.005}deg) translateY(-2px)`;
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg) translateY(0)';
                });
            });

            // Clipboard Action Management
            const copyBtn = document.getElementById('copy-number-btn');
            if(copyBtn) {
                copyBtn.addEventListener('click', () => {
                    const phoneNum = copyBtn.getAttribute('data-phone');
                    navigator.clipboard.writeText(phoneNum).then(() => {
                        copyBtn.innerText = '✓ Copied';
                        setTimeout(() => { copyBtn.innerText = '📋 Copy Number'; }, 2000);
                    });
                });
            }

            // Mobile Navigation Toggle
            const menuBtn = document.getElementById('mobile-menu-btn');
            const navMenu = document.getElementById('nav-menu');
            if (menuBtn && navMenu) {
                menuBtn.addEventListener('click', () => {
                    menuBtn.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });
                // Close menu if links are clicked on mobile devices
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        menuBtn.classList.remove('active');
                        navMenu.classList.remove('active');
                    });
                });
            }

            // Before/After Image Sliders
            const initializationSliders = document.querySelectorAll('.slider');
            initializationSliders.forEach(slider => {
                const container = slider.closest('.comparison-container');
                const foregroundImg = container.querySelector('.foreground-img');
                const sliderButton = container.querySelector('.slider-button');
                slider.addEventListener('input', (e) => {
                    const value = e.target.value;
                    sliderButton.style.left = `${value}%`;
                    foregroundImg.style.clipPath = `inset(0 ${100 - value}% 0 0)`;
                });
            });

            // Carousel Slider Setup
            const track = document.getElementById('carousel-track');
            const items = document.querySelectorAll('.carousel-item');
            const prevBtn = document.getElementById('prev-case-btn');
            const nextBtn = document.getElementById('next-case-btn');
            const dotsGroup = document.getElementById('carousel-dots-group');
            let currentIdx = 0;

            if (dotsGroup && items.length > 0) {
                items.forEach((_, index) => {
                    const dot = document.createElement('div');
                    dot.classList.add('dot');
                    if (index === 0) dot.classList.add('active');
                    dot.addEventListener('click', () => changeCaseIdx(index));
                    dotsGroup.appendChild(dot);
                });
            }

            const dots = document.querySelectorAll('.dot');
            function changeCaseIdx(index) {
                if (!track || items.length === 0) return;
                currentIdx = index;
                track.style.transform = `translateX(-${currentIdx * 100}%)`;
                dots.forEach(dot => dot.classList.remove('active'));
                if(dots[currentIdx]) dots[currentIdx].classList.add('active');
            }

            if(nextBtn && prevBtn && items.length > 0) {
                nextBtn.addEventListener('click', () => {
                    currentIdx = (currentIdx + 1) % items.length;
                    changeCaseIdx(currentIdx);
                });
                prevBtn.addEventListener('click', () => {
                    currentIdx = (currentIdx - 1 + items.length) % items.length;
                    changeCaseIdx(currentIdx);
                });
            }
        });
    </script>
</body>
</html>