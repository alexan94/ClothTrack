<?php
session_start();

//Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION["correo"]) || !isset($_SESSION["tienda_db"])) {
    header("Location: ../login.php");
    exit();
}

//Configurar la hora y fecha
date_default_timezone_set('Europe/Madrid');
$ahora = new DateTime('now', new DateTimeZone('Europe/Madrid'));
$fecha = $ahora->format('d/m/Y H:i');

//Conectarse a la base de datos específica de la tienda
$nombre_db = $_SESSION["tienda_db"]; //viene de login.php
try {
    $conexion = new PDO("mysql:host=localhost;dbname=$nombre_db;charset=utf8mb4", "root", "");
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='alert alert-danger text-center mt-5'>
            ❌ Error al conectar con la base de datos de la tienda: " . htmlspecialchars($e->getMessage()) . "
        </div>");
}

//Manejar inserción de nuevo producto
if (isset($_POST['agregar_producto'])) {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $cantidades = $_POST['cantidad'] ?? [];

    if ($codigo !== '' && $nombre !== '') {
        $stmt = $conexion->prepare("INSERT INTO productos (codigo, nombre) VALUES (?, ?)");
        $stmt->execute([$codigo, $nombre]);
        $producto_id = $conexion->lastInsertId();

        foreach ($cantidades as $talla => $cantidad) {
            $cantidad = max(0, intval($cantidad)); //evitar negativos

            //Insertar la talla
            $stmt = $conexion->prepare("INSERT INTO tallas (producto_id, talla, cantidad) VALUES (?, ?, ?)");
            $stmt->execute([$producto_id, $talla, $cantidad]);

            //Registrar movimiento de entrada si hay stock inicial
            if ($cantidad > 0) {
                $stmt = $conexion->prepare("
                    INSERT INTO movimientos (producto_id, talla, tipo, cantidad)
                    VALUES (?, ?, 'entrada', ?)
                ");
                $stmt->execute([$producto_id, $talla, $cantidad]);
            }
        }
    }
}

//Actualizar cantidades y registrar movimientos
if (isset($_POST['actualizar'])) {
    foreach ($_POST['cantidad'] as $talla_id => $cantidad_nueva) {
        $stmt = $conexion->prepare("SELECT * FROM tallas WHERE id = ?");
        $stmt->execute([$talla_id]);
        $talla_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($talla_data) {
            $cantidad_anterior = intval($talla_data['cantidad']);
            $producto_id = $talla_data['producto_id'];
            $talla = $talla_data['talla'];
            $cantidad_nueva = max(0, intval($cantidad_nueva));

            if ($cantidad_nueva !== $cantidad_anterior) {
                $tipo = ($cantidad_nueva > $cantidad_anterior) ? 'entrada' : 'salida';
                $diferencia = abs($cantidad_nueva - $cantidad_anterior);

                //Actualizar cantidad
                $stmt = $conexion->prepare("UPDATE tallas SET cantidad = ? WHERE id = ?");
                $stmt->execute([$cantidad_nueva, $talla_id]);

                //Registrar movimiento
                $stmt = $conexion->prepare("
                    INSERT INTO movimientos (producto_id, talla, tipo, cantidad)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$producto_id, $talla, $tipo, $diferencia]);
            }
        }
    }
}

//Consultar productos con tallas
$sql = "SELECT t.id AS talla_id, p.codigo, p.nombre, t.talla, t.cantidad
        FROM productos p
        INNER JOIN tallas t ON p.id = t.producto_id
        ORDER BY p.codigo, t.talla";
$stmt = $conexion->prepare($sql);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../template/header.php"); ?>


<body style="background-color: #F0F0E9;">
  <!-- Navbar -->
  <nav class="navbar navbar-light bg-white shadow-sm fixed-top">
    
    <div class="container-fluid">
      <span class="text-muted small">Hoy: <?= htmlspecialchars($fecha) ?></span>
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="../img/LogoClothTrack.png" alt="ClothTrack" 
       style="height: 50px; width: auto; margin-right: 8px;">
      </a>

      <div class="d-flex gap-2">
        <a href="movimientos.php" class="btn btn-warning">
          <i class="bi bi-clock-history me-1"></i>Historial
        </a>
        <a href="cerrarSesion.php" class="btn btn-danger" id="cerrarSesionBtn">
          <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
        </a>
      </div>
    </div>
  </nav>

  <!-- Contenido principal -->
  <div class="container" style="margin-top: 90px; background-color: #F0F0E9;">

    <h2 class="mb-2">Hola, <?= htmlspecialchars($_SESSION["nombre"]) ?> 👋</h2>
    <p class="text-muted mb-4">Tienda: <strong><?= htmlspecialchars($_SESSION["tienda"]) ?></strong></p>

    <!-- Tabla de inventario -->
    <form method="POST">
      <table class="table table-bordered shadow table-hover align-middle text-center">
        <thead class="table-secondary">
          <tr>
            <th>Código</th>
            <th>Producto</th>
            <th>Talla</th>
            <th>Cantidad</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($productos): ?>
            <?php foreach ($productos as $row): ?>
              <?php
                if ($row['cantidad'] <= 1) {
                    $claseFila = 'table-danger';
                    $mensajeStock = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Stock muy bajo</span>';
                } elseif ($row['cantidad'] <= 2) {
                    $claseFila = 'table-warning';
                    $mensajeStock = '<span class="text-warning fw-bold"><i class="bi bi-exclamation-circle me-1"></i>Stock bajo</span>';
                } else {
                    $claseFila = '';
                    $mensajeStock = '';
                }
              ?>
              <tr class="<?= $claseFila ?>">
                <td><?= htmlspecialchars($row['codigo']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['talla']) ?></td>
                <td class="text-center">
                  <input type="number" name="cantidad[<?= $row['talla_id'] ?>]" 
                         value="<?= $row['cantidad'] ?>" 
                         min="0" class="form-control text-center mb-1">
                  <?= $mensajeStock ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center text-muted">No hay productos registrados</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="d-flex justify-content-end mb-5">
        <button type="submit" name="actualizar" class="btn btn-primary">
          <i class="bi bi-arrow-repeat me-1"></i>Actualizar Cantidades
        </button>
      </div>
    </form>

    <!-- Formulario para agregar nuevo producto -->
    <div class="card shadow p-4 border-0"style="background-color: #F0F0E9;">
      <h4 class="mb-3 text-primary">
        <i class="bi bi-handbag text-warning me-2"></i>Añadir nuevas prendas
      </h4>
      <form method="POST" class="row g-3">
        <div class="col-md-3">
          <label for="codigo" class="form-label">Código</label>
          <input type="text" name="codigo" id="codigo" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" name="nombre" id="nombre" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Talla S</label>
          <input type="number" name="cantidad[S]" class="form-control" min="0" value="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Talla M</label>
          <input type="number" name="cantidad[M]" class="form-control" min="0" value="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Talla L</label>
          <input type="number" name="cantidad[L]" class="form-control" min="0" value="0">
        </div>
        <div class="col-md-12 d-flex justify-content-end mt-2">
          <button type="submit" name="agregar_producto" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>Agregar Producto
          </button>
        </div>
      </form>
    </div>
  </div>

<?php include("../template/footer.php"); ?>