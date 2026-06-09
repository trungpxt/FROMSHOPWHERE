<?php
require_once __DIR__ . '/config.php';
startSession();
session_destroy();
redirect(SITE_URL . '/index.php');
