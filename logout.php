<?php
session_start();
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$auth = new AuthController($pdo);
$auth->logout(); 