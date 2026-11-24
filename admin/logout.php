<?php
require_once dirname(__DIR__) . '/includes/init.php';
init_brochure_session();
logout_admin();
redirect('admin/login.php');
