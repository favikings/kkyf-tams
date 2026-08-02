<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

logout();

redirect('login.php');
