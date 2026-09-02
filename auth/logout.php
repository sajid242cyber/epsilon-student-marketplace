<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/index.php');
exit;
