<!-- ============ /KONTEN HALAMAN ============ -->
</main>
</div>

<script>
(function(){
  const sidebar   = document.getElementById('sidebar');
  const main      = document.getElementById('mainContent');
  const toggleBtn = document.getElementById('sidebarToggle');
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const overlay   = document.getElementById('sidebarOverlay');
  if (!sidebar) return;
  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('expanded');
    toggleBtn.classList.toggle('collapsed');
  });
  mobileBtn.addEventListener('click', () => {
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
  });
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
  });
})();
</script>
</body>
</html>
