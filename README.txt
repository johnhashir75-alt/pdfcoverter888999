REWARDS APP — MODERN UI EDITION

This package preserves the PHP/MySQL reward app logic while replacing the visual layer with a modern responsive glass/dark interface.

Structure:
- /admin admin panel
- /assets/css/style.css modern shared theme
- /includes core PHP
- /lang English + Urdu
- /database schema + backups
- /uploads uploaded assets

Security cleanup:
- Removed the hard-coded Telegram bot credential and the code that transmitted the admin password on successful login.
- Existing CSRF, prepared statements, session regeneration and rate limiting remain.

Install:
1. Upload the contents to public_html.
2. Visit your domain and complete install.php.
3. User login: /login.php
4. Admin login: /admin/login.php
