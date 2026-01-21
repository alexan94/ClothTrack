<?php
$host = "localhost";
$bd = "clothtrack_bd"; // base de datos global donde está la tabla "usuarios"
$usuario = "root";
$contraseña = "";


try {
    // 1️⃣ Conexión al servidor, sin base de datos de momento
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $usuario, $contraseña);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2️⃣ Crear la base de datos clothtrack_bd si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$bd` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_general_ci");

    // 3️⃣ Conectar ahora a la base de datos clothtrack_bd
    $conexion = new PDO("mysql:host=$host;dbname=$bd;charset=utf8mb4", $usuario, $contraseña);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4️⃣ Crear la tabla usuarios si no existe
    $sqlUsuarios = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            apellidos VARCHAR(150) NOT NULL,
            correo VARCHAR(100) NOT NULL UNIQUE,
            contraseña VARCHAR(255) NOT NULL,
            tienda VARCHAR(150) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conexion->exec($sqlUsuarios);

    // Opcional: mensaje solo para depurar (mejor comentar en producción)
    // echo 'Base de datos y tabla usuarios listas';

} catch (Exception $e) {
    die("Error al conectar o preparar la base de datos: " . $e->getMessage());
}
?>