<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title ?? SITE_NAME; ?></title>
<meta name="description" content="<?php echo SITE_TAGLINE; ?>">
<meta name="keywords" content="slime, handmade, kpop, photocard, vij slimee, aprpiejise">
<meta name="author" content="Vij Slimee & Aprpiejise">
<meta name="robots" content="index, follow">

<!-- Open Graph / Social Media -->
<meta property="og:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
<meta property="og:description" content="<?php echo SITE_TAGLINE; ?>">
<meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
<meta property="og:url" content="<?php echo SITE_URL . ($_SERVER['REQUEST_URI'] ?? ''); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo SITE_NAME; ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
<meta name="twitter:description" content="<?php echo SITE_TAGLINE; ?>">
<meta name="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">

<!-- Favicon -->
<link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
<link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/images/apple-touch-icon.png">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- AOS Animation -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">

<!-- Color Palette Script -->
<script>
window.VijSlimeeColors = {
    pinkCoral: '#FFB6C1',
    pinkTua: '#FF69B4',
    tosca: '#40E0D0',
    toscaMuda: '#7FFFD4',
    kuning: '#FFD700',
    kuningMuda: '#FFE4B5',
    biruDongker: '#000080',
    abuAbu: '#808080'
};

// Preload critical resources
if ('link' in document.createElement('link')) {
    const preload = document.createElement('link');
    preload.rel = 'preload';
    preload.as = 'font';
    preload.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap';
    preload.crossOrigin = 'anonymous';
    document.head.appendChild(preload);
}
</script>

<!-- Schema.org Structured Data -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "<?php echo SITE_NAME; ?>",
    "url": "<?php echo SITE_URL; ?>",
    "description": "<?php echo SITE_TAGLINE; ?>",
    "potentialAction": {
        "@type": "SearchAction",
        "target": "<?php echo SITE_URL; ?>/products.php?search={search_term_string}",
        "query-input": "required name=search_term_string"
    }
}
</script>