<?php

// función para conectar con la base de datos 'events_db'
function conexionBD(){
   // Database configuration
  $host = 'localhost';
  $dbname = 'events_db';
  $user = 'root';
  $pass = 'root'; // Asegúrate que esta es tu contraseña correcta


  // Establish database connection using MySQLi procedural
  $conn = mysqli_connect($host, $user, $pass, $dbname);


  // Check connection
  if (mysqli_connect_errno()) {
      echo "Database Connection Error: " . mysqli_connect_error();
      die();
  }

  return $conn;

}