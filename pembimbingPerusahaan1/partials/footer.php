</main>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
// ============================================================
// SIDEBAR TOGGLE
// ============================================================
const sidebar    = document.getElementById('sidebar');
const mainContent= document.getElementById('mainContent');
const toggleBtn  = document.getElementById('sidebarToggle');
const toggleIcon = document.getElementById('toggleIcon');
const overlay    = document.getElementById('sidebarOverlay');
const hamburger  = document.getElementById('hamburger');

let collapsed = localStorage.getItem('sidebarCollapsed') === '1';
function applyCollapsed() {
  sidebar.classList.toggle('collapsed', collapsed);
  mainContent.classList.toggle('sidebar-collapsed', collapsed);
  toggleBtn.classList.toggle('collapsed', collapsed);
  toggleIcon.className = collapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
}
applyCollapsed();

toggleBtn?.addEventListener('click', () => {
  collapsed = !collapsed;
  localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
  applyCollapsed();
});

hamburger?.addEventListener('click', () => {
  sidebar.classList.toggle('mobile-open');
  overlay.classList.toggle('active');
});
overlay?.addEventListener('click', () => {
  sidebar.classList.remove('mobile-open');
  overlay.classList.remove('active');
});

// ============================================================
// TOAST
// ============================================================
function showToast(msg, type = 'info') {
  const toast = document.getElementById('toast');
  const icon  = document.getElementById('toast-icon');
  const text  = document.getElementById('toast-msg');
  const icons = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle' };
  const colors= { success:'#4ade80', error:'#ef4444', info:'#818cf8' };
  icon.innerHTML = `<i class="fas ${icons[type]||icons.info}" style="color:${colors[type]||colors.info}"></i>`;
  text.textContent = msg;
  toast.className = `toast ${type}`;
  clearTimeout(toast._t);
  toast._t = setTimeout(() => toast.classList.add('hidden'), 3500);
}

// Check for flash message
<?php if (!empty($_SESSION['toast'])): ?>
  showToast(<?= json_encode($_SESSION['toast']['msg']) ?>, <?= json_encode($_SESSION['toast']['type']) ?>);
<?php unset($_SESSION['toast']); endif; ?>
</script>
</body>
</html>
