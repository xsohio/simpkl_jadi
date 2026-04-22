  </main>
</div>

<script>
// ── Sidebar collapse ──────────────────────────────────────────────
const sidebar   = document.getElementById('sidebar');
const mainEl    = document.getElementById('main-content');
const toggleBtn = document.getElementById('sidebar-toggle');

function toggleSidebar() {
  sidebar.classList.toggle('collapsed');
  mainEl.classList.toggle('sidebar-collapsed');
  const collapsed = sidebar.classList.contains('collapsed');
  toggleBtn.classList.toggle('collapsed', collapsed);
  toggleBtn.querySelector('i').className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
  localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
}

function toggleMobileSidebar() {
  sidebar.classList.toggle('mobile-open');
  document.getElementById('sidebar-overlay').classList.toggle('active');
}

// Restore sidebar state on load
(function() {
  if (window.innerWidth > 768 && localStorage.getItem('sidebarCollapsed') === '1') {
    sidebar.classList.add('collapsed');
    mainEl.classList.add('sidebar-collapsed');
    toggleBtn.classList.add('collapsed');
    if (toggleBtn.querySelector('i')) toggleBtn.querySelector('i').className = 'fas fa-chevron-right';
  }
})();
</script>
</body>
</html>
