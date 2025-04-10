<?php require "inc/config.php"?>


<?php
// Conectar al servidor MySQL (sin especificar una base de datos)
$host = "localhost";
$user = "root";
$pass = "root";

$conexion = mysqli_connect($host, $user, $pass);

// Comprobar la conexión al servidor
if (!$conexion) {
    die("Error de conexión al servidor MySQL: " . mysqli_connect_error());
}

// Leer el archivo .sql
$sql = file_get_contents('crearBBDD.sql');

// Separar las instrucciones SQL
$queries = explode(";", $sql);

$correctas=0;
// Ejecutar cada instrucción
foreach ($queries as $query) {
    $query = trim($query); // Eliminar espacios en blanco
    if (!empty($query)) {
        if (mysqli_query($conexion, $query)) {
            // Si la consulta se ejecuta correctamente
            $correctas++;
        } else {
            // Si hay un error en la consulta
            echo "<p>❌ Error al ejecutar la consulta: " . mysqli_error($mysqli) . "</p><br>";
        }
    }
}
echo "<p> ✔ ".$correctas." Consultas ejecutadas correctamente</p><br>";
    
//Cerramos la conexión con la BD
mysqli_close($conexion);
?>

<a href="index.php">Volver a inicio</a>
<?php include "inc/footer.php"?>