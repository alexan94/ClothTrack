<?php
include("config/bd.php"); // conexión a clothtrack_bd

// ---Función normalizar el nombre de la BD de la tienda ---
function dbNameFromStore(string $tienda): string {
    //Quitar tildes
    if (class_exists('Transliterator')) {
        $tienda = Transliterator::create('Any-Latin; Latin-ASCII;')->transliterate($tienda);
    } else {
        $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $tienda);
        if ($tmp !== false) $tienda = $tmp;
    }
    //Normalizar ñ por si no se transliteró
    $tienda = str_replace(['ñ','Ñ'], ['n','N'], $tienda);
    //Reemplazar todo lo que no sea [A-Za-z0-9_] por "_"
    $tienda = preg_replace('/[^A-Za-z0-9_]+/', '_', $tienda);
    $tienda = preg_replace('/_+/', '_', $tienda);
    $tienda = trim($tienda, '_');
    if ($tienda === '') $tienda = 'tienda';
    return 'clothtrack_' . strtolower($tienda);
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? '');
    $apellidos = trim($_POST["apellidos"] ?? '');
    $correo = trim($_POST["correo"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $password_confirm = trim($_POST["password_confirm"] ?? '');
    $tienda = trim($_POST["tienda"] ?? '');

    //Validaciones
    if ($nombre === '' || $apellidos === '' || $correo === '' || $password === '' || $password_confirm === '' || $tienda === '') {
        $mensaje = '<div class="alert alert-danger text-center">Por favor, completa todos los campos.</div>';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = '<div class="alert alert-warning text-center">El correo electrónico no es válido.</div>';
    } elseif ($password !== $password_confirm) {
        $mensaje = '<div class="alert alert-warning text-center">Las contraseñas no coinciden.</div>';
    } else {
        //¿Existe ya el correo?
        $stmt = $conexion->prepare("SELECT 1 FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario_existente) {
            $mensaje = '<div class="alert alert-warning text-center">El correo ya está registrado.</div>';
        } else {
            try {
                //Cifrar contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                //Insertar usuario en la base global clothtrack_bd
                $stmt = $conexion->prepare("
                    INSERT INTO usuarios (nombre, apellidos, correo, contraseña, tienda)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nombre, $apellidos, $correo, $password_hash, $tienda]);

                //Nombre de la BD de la tienda (normalizado)
                $nombre_db = dbNameFromStore($tienda);

                //Conectarse al servidor MySQL "administrador" (sin bd)
                $pdo_admin = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
                $pdo_admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                //Crear la base de datos si no existe
                $pdo_admin->exec("CREATE DATABASE IF NOT EXISTS `$nombre_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

                //Conectar a la bd recién creada
                $pdo_tienda = new PDO("mysql:host=localhost;dbname=$nombre_db;charset=utf8mb4", "root", "");
                $pdo_tienda->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                //Crear tablas dentro de la bd de la tienda productos, tallas y movimientos
                $pdo_tienda->exec("
                    CREATE TABLE IF NOT EXISTS productos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        codigo VARCHAR(50) NOT NULL,
                        nombre VARCHAR(150) NOT NULL
                    );
                ");

                $pdo_tienda->exec("
                    CREATE TABLE IF NOT EXISTS tallas (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        producto_id INT NOT NULL,
                        talla VARCHAR(10) NOT NULL,
                        cantidad INT DEFAULT 0,
                        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
                    );
                ");

                $pdo_tienda->exec("
                    CREATE TABLE IF NOT EXISTS movimientos (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        producto_id INT NOT NULL,
                        talla VARCHAR(10) NOT NULL,
                        tipo ENUM('entrada','salida') NOT NULL,
                        cantidad INT NOT NULL,
                        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
                    );
                ");

                //Hecho! redirige al login
                header("Location: login.php?registro=ok");
                exit();

            } catch (PDOException $e) {
                $mensaje = '<div class="alert alert-danger text-center">Error en el registro : ' . htmlspecialchars($e->getMessage()) . '</div>';
              }
        }
    }
}
?>

<?php include("template/header.php"); ?>

<body class="d-flex flex-column justify-content-center align-items-center vh-100" style="background-color: #F0F0E9;">
  
  <div class="card shadow p-4 border-0" 
      style="max-width: 620px; width: 100%; background-color: #F0F0E9; border-radius: 15px;">
<div class="container py-5" style="max-width: 600px;">
  <h2 class="text-center fw-bold mb-4">
    <i class="bi bi-person-plus-fill me-2"></i>Registro de Usuario
  </h2>

  <?= $mensaje ?>

  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" name="nombre" id="nombre" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label for="apellidos" class="form-label">Apellidos</label>
        <input type="text" name="apellidos" id="apellidos" class="form-control" required>
      </div>
    </div>

    <div class="mt-3">
      <label for="correo" class="form-label">Correo electrónico</label>
      <input type="email" name="correo" id="correo" class="form-control" required>
    </div>

    <div class="mt-3">
      <label for="tienda" class="form-label">Nombre de la tienda</label>
      <input type="text" name="tienda" id="tienda" class="form-control" required>
    </div>

    <hr class="my-4">

    <div class="mt-3">
      <label for="password" class="form-label">Contraseña</label>
      <input type="password" name="password" id="password" class="form-control" required>
    </div>

    <div class="mt-3">
      <label for="password_confirm" class="form-label">Confirmar contraseña</label>
      <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
    </div>
    <div class="mt-3">
      <button type="submit" class="btn btn-marron w-100">Registrar</button>
    </div>
    <div class="text-center mt-3">
      <a href="login.php" class="link-secondary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
  </form>
</div>

<?php include("template/footer.php"); ?>