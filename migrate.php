<?php
// Auto-migrations: create new tables + insert default settings if missing.
// Runs once per request from bootstrap.php. Cheap when everything exists.
// Wrapped in try/catch so a partial migration cannot 500 the whole site.

function column_exists(string $table, string $column): bool {
    try {
        $c = db();
        $dbn = defined('DB_NAME') ? DB_NAME : '';
        $st = $c->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
        if (!$st) return true; // fail-open: don't try to ALTER blindly
        $st->bind_param('sss', $dbn, $table, $column);
        $st->execute();
        $res = $st->get_result();
        return (bool)$res->fetch_row();
    } catch (Throwable $e) {
        error_log('column_exists check failed: '.$e->getMessage());
        return true;
    }
}

function table_exists(string $table): bool {
    try {
        $c = db();
        $dbn = defined('DB_NAME') ? DB_NAME : '';
        $st = $c->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1");
        if (!$st) return true;
        $st->bind_param('ss', $dbn, $table);
        $st->execute();
        return (bool)$st->get_result()->fetch_row();
    } catch (Throwable $e) {
        return true;
    }
}

function run_migrations(): void {
    try {
        $c = db();

        if (table_exists('users')) {
            if (!column_exists('users', 'password_reset_code')) {
                @$c->query("ALTER TABLE users ADD COLUMN password_reset_code VARCHAR(8) NULL");
            }
            if (!column_exists('users', 'password_reset_expires')) {
                @$c->query("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_code");
            }
        }

        @$c->query("CREATE TABLE IF NOT EXISTS plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            price_points INT NOT NULL DEFAULT 0,
            daily_return_points INT NOT NULL DEFAULT 0,
            duration_days INT NOT NULL DEFAULT 30,
            image VARCHAR(255) NULL,
            status TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @$c->query("CREATE TABLE IF NOT EXISTS user_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            name VARCHAR(120) NOT NULL,
            price_points INT NOT NULL,
            daily_return_points INT NOT NULL,
            duration_days INT NOT NULL,
            days_paid INT NOT NULL DEFAULT 0,
            last_paid_date DATE NULL,
            status ENUM('active','completed','cancelled') DEFAULT 'active',
            expires_at DATE NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id), INDEX(status)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @$c->query("CREATE TABLE IF NOT EXISTS deposits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            method ENUM('easypaisa','jazzcash','bank','other') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            points INT NOT NULL DEFAULT 0,
            sender_number VARCHAR(64) NULL,
            txn_id VARCHAR(120) NULL,
            note VARCHAR(255) NULL,
            proof VARCHAR(255) NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            admin_note VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            INDEX(user_id), INDEX(status)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure new settings exist with sane defaults (do not overwrite existing values)
        if (table_exists('settings')) {
            $defaults = [
                'min_withdraw'          => setting('min_payout','500'),
                'min_deposit'           => '100',
                'deposit_easypaisa'     => '',
                'deposit_easypaisa_name'=> '',
                'deposit_jazzcash'      => '',
                'deposit_jazzcash_name' => '',
                'deposit_bank_details'  => '',
                'plans_enabled'         => '1',
                'points_rate'           => '10',
                'currency_symbol'       => 'PKR',
                'referral_bonus'        => '0',
                'referral_required'     => '0',
                'referral_deposit_enabled' => '0',
                'referral_deposit_percent' => '0',
                'checkin_points'        => '10',
            ];
            // SMTP/OTP removed: clean up legacy settings and verify all users
            @$c->query("DELETE FROM settings WHERE k LIKE 'smtp\\_%' OR k IN ('otp_enabled','otp_show_on_fail')");
            @$c->query("UPDATE users SET is_verified=1 WHERE is_verified=0");
            $sel = $c->prepare("SELECT 1 FROM settings WHERE k=? LIMIT 1");
            $ins = $c->prepare("INSERT INTO settings(k,v) VALUES(?,?)");
            if ($sel && $ins) {
                foreach ($defaults as $k=>$v) {
                    $vv = (string)($v ?? '');
                    $sel->bind_param('s', $k);
                    $sel->execute();
                    if (!$sel->get_result()->fetch_row()) {
                        $ins->bind_param('ss', $k, $vv);
                        @$ins->execute();
                    }
                }
            }
            // bust cache so freshly-inserted defaults are visible this request
            $GLOBALS['_settings_cache'] = null;
        }
    } catch (Throwable $e) {
        error_log('run_migrations failed: '.$e->getMessage());
    }
}
run_migrations();
