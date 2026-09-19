</main>
<?php if (!empty($_SESSION['uid'])):
  $__self = basename($_SERVER['PHP_SELF'] ?? '');
  if (!function_exists('__nav')) { function __nav($file,$cur){return $file===$cur?'active':'';} }
?>
<nav class="bottom-nav">
  <a href="dashboard.php" class="<?=__nav('dashboard.php',$__self)?>">
    <span class="nav-ico"><i class="bi bi-house-door-fill"></i></span><span>Home</span>
  </a>
  <a href="plans.php" class="<?=__nav('plans.php',$__self)?>">
    <span class="nav-ico"><i class="bi bi-gem"></i></span><span>Plans</span>
  </a>
  <a href="checkin.php" class="<?=__nav('checkin.php',$__self)?>">
    <span class="nav-ico"><i class="bi bi-check2-circle"></i></span><span>Check-in</span>
  </a>
  <a href="profile.php" class="<?=__nav('profile.php',$__self)?>">
    <span class="nav-ico"><i class="bi bi-person-circle"></i></span><span>Profile</span>
  </a>
</nav>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>window.addEventListener('load',()=>{const s=document.getElementById('splash');if(s){s.style.transition='opacity .3s';s.style.opacity='0';setTimeout(()=>s.style.display='none',350);}});</script>
</body></html>
