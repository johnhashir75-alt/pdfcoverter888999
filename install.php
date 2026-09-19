<?php
// One-time installer. Locks itself after successful install.
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors', '1');

$lockFile = __DIR__ . '/includes/.installed';
if (file_exists($lockFile)) {
    exit('Installation already completed. Delete /includes/.installed to reinstall.');
}

$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '');
    $dbn  = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = (string)($_POST['db_pass'] ?? '');
    $site = trim($_POST['site_name'] ?? '');
    $url  = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $auser= trim($_POST['admin_user'] ?? '');
    $amail= trim($_POST['admin_email'] ?? '');
    $apass= (string)($_POST['admin_pass'] ?? '');

    if (!$host||!$dbn||!$user||!$site||!$url||!$auser||!$amail||!$apass) $errors[]='All fields except DB password are required.';
    if (!filter_var($amail, FILTER_VALIDATE_EMAIL)) $errors[]='Invalid admin email.';
    if (strlen($apass)<6) $errors[]='Admin password must be 6+ chars.';

    if (!$errors) {
        $mysqli = @new mysqli($host, $user, $pass, $dbn);
        if ($mysqli->connect_errno) {
            $errors[] = 'DB connection failed: ' . $mysqli->connect_error;
        } else {
            $mysqli->set_charset('utf8mb4');
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            if (!$mysqli->multi_query($schema)) {
                $errors[] = 'Schema error: ' . $mysqli->error;
            } else {
                while ($mysqli->more_results() && $mysqli->next_result()) {;}

                $hash = password_hash($apass, PASSWORD_BCRYPT);
                $stmt = $mysqli->prepare("INSERT INTO admins(username,email,password_hash,role) VALUES(?,?,?, 'super')");
                $stmt->bind_param('sss', $auser, $amail, $hash);
                $stmt->execute();

                $settings = [
                    'site_name' => $site,
                    'site_url'  => $url,
                    'lang_default' => 'en',
                    'referral_bonus' => '50',
                    'referral_required' => '0',
                    'checkin_points' => '10',
                    'min_payout' => '500',
                    'max_payout' => '20000',
                    'payout_fee_percent' => '0',
                    'daily_payout_limit' => '5000',
                    'maintenance_mode' => '0',
                    'logo' => '',
                    'favicon' => '',
                    'splash_logo' => '',
                    'primary_color' => '#0d6efd',
                ];
                $ss = $mysqli->prepare("INSERT INTO settings(k,v) VALUES(?,?)");
                foreach ($settings as $k=>$v) { $ss->bind_param('ss',$k,$v); $ss->execute(); }

                $cfg = "<?php\n"
                    ."// AUTO-GENERATED. Do not edit unless you know what you are doing.\n"
                    ."define('DB_HOST', ".var_export($host,true).");\n"
                    ."define('DB_NAME', ".var_export($dbn,true).");\n"
                    ."define('DB_USER', ".var_export($user,true).");\n"
                    ."define('DB_PASS', ".var_export($pass,true).");\n"
                    ."define('SITE_URL', ".var_export($url,true).");\n"
                    ."define('APP_KEY', ".var_export(bin2hex(random_bytes(32)),true).");\n";
                file_put_contents(__DIR__.'/config.php', $cfg);
                file_put_contents($lockFile, date('c'));
                $done = true;
            }
            $mysqli->close();
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Install</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head><body>
<div class="container py-5" style="max-width:680px">
  <div class="card"><div class="card-body p-4">
    <h3 class="mb-3 text-primary">Rewards App Installer</h3>
    <?php if ($done): ?>
      <div class="alert alert-success">Installed successfully.</div>
      <a class="btn btn-primary" href="login.php">Go to Login</a>
      <a class="btn btn-outline-primary" href="admin/login.php">Admin Login</a>
    <?php else: ?>
      <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?=htmlspecialchars($e)?></div><?php endforeach; ?>
      <form method="post" class="row g-3">
        <div class="col-6"><label class="form-label">DB Host</label><input class="form-control" name="db_host" value="localhost" required></div>
        <div class="col-6"><label class="form-label">DB Name</label><input class="form-control" name="db_name" required></div>
        <div class="col-6"><label class="form-label">DB User</label><input class="form-control" name="db_user" required></div>
        <div class="col-6"><label class="form-label">DB Password</label><input class="form-control" name="db_pass" type="password"></div>
        <div class="col-6"><label class="form-label">Site Name</label><input class="form-control" name="site_name" required></div>
        <div class="col-6"><label class="form-label">Site URL</label><input class="form-control" name="site_url" value="https://" required></div>
        <hr class="mt-4">
        <div class="col-6"><label class="form-label">Admin Username</label><input class="form-control" name="admin_user" required></div>
        <div class="col-6"><label class="form-label">Admin Email</label><input class="form-control" name="admin_email" type="email" required></div>
        <div class="col-12"><label class="form-label">Admin Password</label><input class="form-control" name="admin_pass" type="password" required></div>
        <div class="col-12"><button class="btn btn-primary rounded-3 w-100 py-2">Install</button></div>
      </form>
    <?php endif; ?>
  </div></div>
</div></body></html>
