<?php
require_once __DIR__ . '/config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

$host_name = $_ENV['HOST_NAME'] ?? 'localhost';
$database = $_ENV['DATABASE'] ?? 'everest2026';
$user_name = $_ENV['USER_NAME'] ?? 'root';
$password = $_ENV['PASSWORD'] ?? '';

$link = new mysqli($host_name, $user_name, $password, $database);

if ($link->connect_error) {
	  echo "<h1>Bievenido error</h1>";
    die('<p>Error al conectar con servidor MySQL: '. $link->connect_error .'</p>');
  } else {
    echo '<p>Se ha establecido la conexión al servidor MySQL con éxito.</p>';
  }
?>
