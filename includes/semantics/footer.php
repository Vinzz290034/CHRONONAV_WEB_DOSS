<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ChronoNav Footer</title>

  <!-- Inter font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">

  <!-- Font Awesome (for social icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    integrity="sha512-5a6b4ht5bJz8b6EzR/AvHmsZmF6y/5JSRt3TDhLroYVvL1oKkCGM58HjYpRBnmhxR1ZT9vC8rZ5YFC9lXlcbXg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
  <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
    href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

  <style>
    /* ===== CHRONONAV FOOTER STYLES ===== */
    .chrononav-footer {
      font-family: 'space grotesk', 'noto sans', sans-serif;
      background: linear-gradient(180deg, #fff 0%, whitesmoke 30%);
      color: rgba(0, 0, 0, .5) !important;
      text-align: center;
      padding: 20px 40px;
      border-top: 1px solid #dbe2e6;
    }

    .chrononav-footer .footer-top {
      margin-bottom: 10px;
    }

    .chrononav-footer .footer-logo {
      height: 28px;
      object-fit: contain;
    }

    .chrononav-footer .footer-links {
      font-size: 14px;
      margin-bottom: 8px;
    }

    .chrononav-footer .footer-links span {
      margin-right: 12px;
    }

    .chrononav-footer .footer-links a {
      margin: 0 8px;
      font-weight: 500;
      color: rgba(0, 0, 0, .5) !important;
      text-decoration: none;
      transition: opacity 0.2s ease, text-decoration 0.2s ease;
    }

    .chrononav-footer .footer-links a:hover {
      opacity: 0.85;
      text-decoration: underline;
    }

    .chrononav-footer .footer-bottom {
      font-size: 12px;
      color: rgba(0, 0, 0, .5) !important;
      display: flex;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .chrononav-footer .footer-bottom a {
      color: rgba(0, 0, 0, .5) !important;
      text-decoration: none;
    }

    .chrononav-footer .footer-bottom a:hover {
      opacity: 0.85;
      text-decoration: underline;
    }

    /* ===== Social Links ===== */
    .chrononav-footer .footer-socials {
      margin-top: 10px;
      display: flex;
      justify-content: center;
      gap: 16px;
    }

    .chrononav-footer .footer-socials a {
      font-size: 20px;
      color: rgba(0, 0, 0, .5) !important;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .chrononav-footer .footer-socials a:hover {
      opacity: 0.85;
      transform: translateY(-2px);
    }

    /* ===== Responsive ===== */
    @media (max-width: 600px) {
      .chrononav-footer {
        padding: 20px;
      }

      .chrononav-footer .footer-links {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }

      .chrononav-footer .footer-bottom {
        flex-direction: column;
        gap: 4px;
      }

      .chrononav-footer .footer-socials {
        margin-top: 14px;
      }
    }



    /* ====================================================================== */
    /* Dark Mode Overrides for Footer - Custom Colors                        */
    /* ====================================================================== */
    body.dark-mode .chrononav-footer {
      background: linear-gradient(180deg, #121A21 0%, #263645 30%) !important;
      /* Primary to secondary gradient */
      color: #94ADC7 !important;
      /* Secondary text color */
      border-top: 1px solid #121A21 !important;
      /* Primary dark border */
    }

    /* Logo adjustments for dark mode */
    body.dark-mode .chrononav-footer .footer-logo {
      filter: brightness(1.1);
      /* Slightly brighten logo for dark mode */
    }

    /* Links in dark mode */
    body.dark-mode .chrononav-footer .footer-links {
      color: #94ADC7 !important;
      /* Secondary text color */
    }

    body.dark-mode .chrononav-footer .footer-links span {
      color: #94ADC7 !important;
      /* Secondary text color */
    }

    body.dark-mode .chrononav-footer .footer-links a {
      color: #94ADC7 !important;
      /* Secondary text color */
      text-decoration: none;
      transition: color 0.2s ease, text-decoration 0.2s ease;
    }

    body.dark-mode .chrononav-footer .footer-links a:hover {
      color: #E5E8EB !important;
      /* Light text on hover */
      text-decoration: underline;
    }

    /* Footer bottom section */
    body.dark-mode .chrononav-footer .footer-bottom {
      color: #94ADC7 !important;
      /* Secondary text color */
    }

    body.dark-mode .chrononav-footer .footer-bottom a {
      color: #94ADC7 !important;
      /* Secondary text color */
      text-decoration: none;
    }

    body.dark-mode .chrononav-footer .footer-bottom a:hover {
      color: #E5E8EB !important;
      /* Light text on hover */
      text-decoration: underline;
    }

    /* Social icons in dark mode */
    body.dark-mode .chrononav-footer .footer-socials a {
      color: #94ADC7 !important;
      /* Secondary text color */
      transition: color 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
    }

    body.dark-mode .chrononav-footer .footer-socials a:hover {
      color: #1C7DD6 !important;
      /* Active blue on hover */
      transform: translateY(-2px);
      opacity: 1 !important;
    }

    /* Specific hover colors for social icons */
    body.dark-mode .chrononav-footer .footer-socials a:hover i.fa-facebook-f {
      color: #1877F2 !important;
      /* Facebook blue */
    }

    body.dark-mode .chrononav-footer .footer-socials a:hover i.fa-twitter {
      color: #1DA1F2 !important;
      /* Twitter blue */
    }

    body.dark-mode .chrononav-footer .footer-socials a:hover i.fa-linkedin-in {
      color: #0A66C2 !important;
      /* LinkedIn blue */
    }

    body.dark-mode .chrononav-footer .footer-socials a:hover i.fa-github {
      color: #E5E8EB !important;
      /* Light color for GitHub */
    }

    /* Text muted class adjustments for dark mode */
    body.dark-mode .text-muted {
      color: #94ADC7 !important;
      /* Secondary text color */
    }

    body.dark-mode .text-muted:hover {
      color: #E5E8EB !important;
      /* Light text on hover */
    }

    /* Contact email specific styling */
    body.dark-mode .chrononav-footer .footer-bottom .contact a {
      color: #94ADC7 !important;
      /* Secondary text color */
    }

    body.dark-mode .chrononav-footer .footer-bottom .contact a:hover {
      color: #1C7DD6 !important;
      /* Active blue on hover */
    }

    /* Responsive adjustments for dark mode */
    @media (max-width: 600px) {
      body.dark-mode .chrononav-footer {
        background: linear-gradient(180deg, #121A21 0%, #263645 40%) !important;
      }

      body.dark-mode .chrononav-footer .footer-links {
        gap: 8px !important;
      }

      body.dark-mode .chrononav-footer .footer-bottom {
        gap: 6px !important;
      }
    }

    /* Version text in dark mode */
    body.dark-mode .chrononav-footer .footer-bottom .version {
      color: #94ADC7 !important;
      opacity: 0.8;
    }

    /* Privacy and terms links specific hover */
    body.dark-mode .chrononav-footer .footer-links a.text-decoration-none:hover {
      text-decoration: underline !important;
      color: #1C7DD6 !important;
      /* Active blue on hover */
    }
  </style>
</head>

<body>

  <!-- ===== CHRONONAV FOOTER ===== -->
  <footer class="chrononav-footer">
    <div class="footer-top">
      <img src="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
        alt="ChronoNav Logo" class="footer-logo" />
    </div>

    <div class="footer-links">
      <span>CHRONONAV © 2025</span>
      <a class="text-muted text-decoration-none" href="../../includes/semantics/privacy.html">Privacy Policy</a>
      <a class="text-muted text-decoration-none" href="../../includes/semantics/terms.html">Terms of Service</a>
    </div>

    <div class="footer-bottom">
      <span class="contact">
        Contact: <a href="mailto:chrononav.support@domain.com">chrononav.support@domain.com</a>
      </span>
      <span class="version">Version 1.0.0</span>
    </div>

    <div class="footer-socials">
      <a href="https://facebook.com" target="_blank" aria-label="Facebook">
        <i class="fab fa-facebook-f"></i>
      </a>
      <a href="https://x.com" target="_blank" aria-label="Twitter / X">
        <i class="fa-brands fa-twitter"></i> <!-- fallback bird -->
      </a>
      <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn">
        <i class="fab fa-linkedin-in"></i>
      </a>
      <a href="https://github.com" target="_blank" aria-label="GitHub">
        <i class="fab fa-github"></i>
      </a>
    </div>
</body>

</html>