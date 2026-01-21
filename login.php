<?php
session_start();
include("config/bd.php"); // conexión a la base de datos clothtrack_bd

// --- Función para normalizar el nombre de la BD de la tienda ---
function dbNameFromStore(string $tienda): string {
    if (class_exists('Transliterator')) {
        $tienda = Transliterator::create('Any-Latin; Latin-ASCII;')->transliterate($tienda);
    } else {
        $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT', $tienda);
        if ($tmp !== false) $tienda = $tmp;
    }
    $tienda = str_replace(['ñ','Ñ'], ['n','N'], $tienda);
    $tienda = preg_replace('/[^A-Za-z0-9_]+/', '_', $tienda);
    $tienda = preg_replace('/_+/', '_', $tienda);
    $tienda = trim($tienda, '_');
    if ($tienda === '') $tienda = 'tienda';
    return 'clothtrack_' . strtolower($tienda);
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if ($correo === '' || $password === '') {
        $mensaje = '<div class="alert alert-danger text-center">Por favor, completa todos los campos.</div>';
    } else {
        //Verifica si existe el correo
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario["contraseña"])) {
            $_SESSION["correo"] = $usuario["correo"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["tienda"] = $usuario["tienda"];

            //Nombre de BD de tienda
            $_SESSION["tienda_db"] = dbNameFromStore($usuario["tienda"]);

            //Dirige al nventario
            header("Location: paginas/inventario.php");
            exit;
        } else {
            $mensaje = '<div class="alert alert-danger text-center">Correo o contraseña incorrectos.</div>';
        }
    }
}
?>

<?php if (isset($_GET['registro']) && $_GET['registro'] === 'ok'): ?>
    <div class="alert alert-success text-center">
      ¡Te has registrado con éxito! Ahora puedes iniciar sesión.
    </div>
  <?php endif; ?>

  <?= $mensaje ?>

 <?php include("template/header.php"); ?>

<body class="d-flex flex-column justify-content-center align-items-center vh-100" style="background-color: #F0F0E9;">
  
  <div class="card shadow p-4 border-0" 
      style="max-width: 520px; width: 100%; background-color: #F0F0E9; border-radius: 15px;">
    <h2 class="text-center display-5 fw-bold">Iniciar Sesión</h2>

    <form method="POST">
      <div class="mb-4">
        <label for="correo" class="form-label">Correo electrónico</label>
        <input type="email" name="correo" id="correo" class="form-control" required>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" name="password" id="password" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-marron w-100">Entrar</button>
    </form>

    <!--Enlace de registro-->
    <div class="text-center mt-3">
      <a href="registro.php" class="text-decoration-none text-secondary">
        ¿No tienes cuenta? <strong>Regístrate</strong>
      </a>
    </div>
  </div>

</body>

<?php include("template/footer.php"); ?>
