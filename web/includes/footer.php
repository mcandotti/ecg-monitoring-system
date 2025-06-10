    </div> <!-- Close .container -->
    </main> <!-- Close .main-content -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-container">
            <div class="footer-brand">
                <span>Système de Monitoring ECG - Raspberry Pi</span>
            </div>
            <div class="footer-links">
                <a href="/pages/index.php" class="footer-link">
                    <i class="fas fa-home"></i> Accueil
                </a>
            </div>
            <div class="footer-copyright">
                <span>&copy; <?php echo date('Y'); ?> ECG Monitoring</span>
            </div>
        </div>
    </footer>
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- JS personnalisé commun -->
    <script src="/js/main.js"></script>
    
    <?php if (isset($extraJs)): ?>
        <!-- JS supplémentaire spécifique à la page -->
        <script src="<?php echo $extraJs; ?>"></script>
    <?php endif; ?>
    
    <?php if (isset($inlineJs)): ?>
        <!-- JS inline spécifique à la page -->
        <script>
            <?php echo $inlineJs; ?>
        </script>
    <?php endif; ?>
</body>
</html> 