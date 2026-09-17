<?php
require_once __DIR__ . '/_common.php';
require_method('POST');
verifyCsrf();
logout_admin();
redirect_to('admin/login.php');
