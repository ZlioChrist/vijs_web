    <!-- JS Eksternal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script untuk toggle dropdown dan sidebar -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar mobile
            const toggleBtn = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.modern-sidebar');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                });
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 1024 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                });
            }
            
            // Toggle notification dropdown
            const bell = document.getElementById('notificationBell');
            const notifDropdown = document.getElementById('notificationDropdown');
            if (bell && notifDropdown) {
                bell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('show');
                    const profileDropdown = document.getElementById('profileDropdown');
                    if (profileDropdown) profileDropdown.classList.remove('show');
                });
            }
            
            // Toggle profile dropdown
            const profileCard = document.getElementById('profileCard');
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileCard && profileDropdown) {
                profileCard.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                    if (notifDropdown) notifDropdown.classList.remove('show');
                });
            }
            
            // Tutup dropdown ketika klik di luar
            document.addEventListener('click', function() {
                if (notifDropdown) notifDropdown.classList.remove('show');
                if (profileDropdown) profileDropdown.classList.remove('show');
            });
        });
    </script>
</body>
</html>