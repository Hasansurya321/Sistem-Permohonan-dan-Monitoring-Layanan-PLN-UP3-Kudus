<!-- ==========================================================================
   PLN UP3 KUDUS - CORPORATE FOOTER COMPONENT (REVISED)
   ========================================================================== -->
<footer class="pln-footer-wrapper">
    <div class="pln-footer-container">
        <div class="pln-footer-grid">
            
            <!-- KOLOM 1: BRAND / COMPANY -->
            <div class="pln-footer-col">
                <div class="pln-footer-logo-container">
                    <!-- PNG Logo from public/images/ -->
                    <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="pln-footer-logo-img">
                    <div class="pln-footer-brand-text">
                        <span class="pln-footer-brand-title">PLN</span>
                        <span class="pln-footer-brand-subtitle">UP3 KUDUS</span>
                    </div>
                </div>
                <p class="pln-footer-brand-desc">
                    PLN UP3 Kudus berkomitmen memberikan layanan kelistrikan terbaik berbasis teknologi digital untuk kemudahan masyarakat.
                </p>
                <div class="pln-footer-socials">
                    <a href="https://www.instagram.com/info.plnkudus/" target="_blank" rel="noopener noreferrer" class="pln-footer-social-icon" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://x.com/plnareakudus" target="_blank" rel="noopener noreferrer" class="pln-footer-social-icon" aria-label="X (Twitter)">
                        <svg class="pln-footer-social-svg" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- KOLOM 2: INFORMASI KANTOR -->
            <div class="pln-footer-col">
                <h4 class="pln-footer-col-title">Informasi Kantor</h4>
                <ul class="pln-footer-info-list">
                    <li>
                        <div class="pln-footer-info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <span class="pln-footer-info-text">
                            Jl. R. Agil Kusumadya No.102, Jati Wetan, Kec. Jati, Kabupaten Kudus, Jawa Tengah 59346
                        </span>
                    </li>
                    <li>
                        <div class="pln-footer-info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <span class="pln-footer-info-text">
                            <a href="tel:0291431982" class="pln-footer-link-hover">0291431982</a>
                        </span>
                    </li>
                </ul>
            </div>
            
            <!-- KOLOM 3: QUICK NAVIGATION -->
            <div class="pln-footer-col">
                <h4 class="pln-footer-col-title">Layanan</h4>
                <ul class="pln-footer-links-list">
                    <li><a href="{{ route('landing') }}" class="pln-footer-link">Dashboard</a></li>
                    <li><a href="{{ route('monitoring') }}" class="pln-footer-link">Monitoring</a></li>
                    <li><a href="{{ route('pembayaran') }}" class="pln-footer-link">Pembayaran</a></li>
                    <li><a href="#" class="pln-footer-link">Bantuan</a></li>
                </ul>
            </div>
            
            <!-- KOLOM 4: JAM OPERASIONAL & PETA -->
            <div class="pln-footer-col">
                <h4 class="pln-footer-col-title">Jam Layanan</h4>
                <div class="pln-footer-hours">
                    <div class="pln-footer-hours-row">
                        <span class="pln-footer-hours-day">Senin - Jumat</span>
                        <span class="pln-footer-hours-time">07.30 – 16.00 WIB</span>
                    </div>
                    <div class="pln-footer-hours-row">
                        <span class="pln-footer-hours-day">Sabtu - Minggu</span>
                        <span class="pln-footer-hours-time pln-status-closed">Tutup</span>
                    </div>
                </div>
                
                <!-- Google Maps Direction Button -->
                <a href="https://maps.app.goo.gl/SHZ6d5xuvjBfumgT6" target="_blank" rel="noopener noreferrer" class="pln-footer-maps-btn">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Petunjuk Lokasi Kantor</span>
                </a>
            </div>
            
        </div>
        
        <!-- SEPARATOR LINE -->
        <hr class="pln-footer-separator">
        
        <!-- COPYRIGHT -->
        <div class="pln-footer-bottom">
            <p class="pln-footer-copyright">
                &copy; 2024 PT PLN (Persero) UP3 Kudus. All rights reserved.
            </p>
        </div>
    </div>
</footer>
