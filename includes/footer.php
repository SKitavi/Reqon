<!-- includes/footer.php -->
</main>

<script>
  // ── User dropdown toggle ─────────────────────────────────
  function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    const btn      = document.getElementById('user-menu-btn');
    const isOpen   = dropdown.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen);
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const menu = document.getElementById('user-menu-btn');
    if (menu && !menu.contains(e.target)) {
      document.getElementById('user-dropdown').classList.remove('open');
      menu.setAttribute('aria-expanded', false);
    }
  });

  // ── Auto-dismiss alerts after 5 s ───────────────────────
  document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() {
      el.style.transition = 'opacity .4s';
      el.style.opacity    = '0';
      setTimeout(function() { el.remove(); }, 400);
    }, 5000);
  });
</script>

</body>
</html>