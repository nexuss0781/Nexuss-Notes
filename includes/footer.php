<?php
/**
 * Nexus Notes - Footer Include
 * Common footer for all pages
 */

defined('APP_INIT') or exit('Direct access not allowed');
?>
    </main>
    
    <!-- Footer -->
    <footer class="border-t border-gray-200 dark:border-dark-border bg-white dark:bg-dark-surface mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <i data-lucide="notebook-tabs" class="w-4 h-4"></i>
                    <span>&copy; <?php echo date('Y'); ?> Nexus Notes. All rights reserved.</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                    <span>v<?php echo APP_VERSION; ?></span>
                    <span class="hidden sm:inline">Built with Pure PHP & Vanilla JS</span>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <!-- Modal Container -->
    <div id="modalContainer" class="fixed inset-0 z-50 hidden"></div>
    
    <!-- Scripts -->
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/calendar.js"></script>
    <script src="assets/js/editor.js"></script>
    <script src="assets/js/app.js"></script>
    
    <?php if (FEATURE_PWA): ?>
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered:', reg.scope))
                    .catch(err => console.log('SW registration failed:', err));
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
