<?php


  $host_name = 'db5010897290.hosting-data.io';
  $database = 'dbs9214508';
  $user_name = 'dbu272359';
  $password = 'Chuchi00@@';

  $link = new mysqli($host_name, $user_name, $password, $database);

  if ($link->connect_error) {
	  echo "<h1>Bievenido error</h1>";
    die('<p>Error al conectar con servidor MySQL: '. $link->connect_error .'</p>');
  } else {
    echo '<p>Se ha establecido la conexión al servidor MySQL con éxito.</p>';
  }
?>