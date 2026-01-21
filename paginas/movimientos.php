<?php
session_start();

// 🧩 Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION["correo"])) {
    header("Location: ../login.php");
    exit();
}

// 🕒 Zona horaria y fecha actual
date_default_timezone_set('Europe/Madrid');
$ahora = new DateTime('now', new DateTimeZone('Europe/Madrid'));
$fecha = $ahora->format('d/m/Y H:i');

// 🗄️ Conectarse a la base de datos específica de la tienda
$nombre_db = $_SESSION["tienda_db"];
try {
    $conexion = new PDO("mysql:host=localhost;dbname=$nombre_db;charset=utf8mb4", "root", "");
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='alert alert-danger text-center mt-5'>
            Error al conectar con la base de datos de la tienda: " . $e->getMessage() . "
        </div>");
}

// 🔎 Obtener los movimientos con nombre del producto
$sql = "SELECT m.id, p.nombre AS producto, m.talla, m.tipo, m.cantidad, m.fecha
        FROM movimientos m
        INNER JOIN productos p ON m.producto_id = p.id
        ORDER BY m.fecha DESC";
$stmt = $conexion->prepare($sql);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="inventario.php" class="btn btn-outline-primary">
          <i class="bi bi-box-seam me-1"></i>Inventario
        </a>
        <a href="cerrarSesion.php" class="btn btn-outline-danger"id="cerrarSesionBtn">
          <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
        </a>
      </div>
    </div>
  </nav>

  <!-- Contenido principal -->
  <div class="container" style="margin-top: 90px;">
    <h2 class="mb-4 text-center text-primary">
      📜 Historial de Movimientos
    </h2>
    <p class="text-center text-muted mb-4">
      Tienda: <strong><?= htmlspecialchars($_SESSION["tienda"]) ?></strong>
    </p>

    <table class="table table-striped table-hover align-middle text-center shadow-sm">
      <thead class="table-secondary">
        <tr>
          <th>ID</th>
          <th>Producto</th>
          <th>Talla</th>
          <th>Tipo</th>
          <th>Cantidad</th>
          <th>Fecha y Hora</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($movimientos) > 0): ?>
          <?php foreach ($movimientos as $mov): ?>
            <tr class="<?= $mov['tipo'] == 'salida' ? 'table-danger' : 'table-success' ?>">
              <td><?= $mov['id'] ?></td>
              <td><?= htmlspecialchars($mov['producto']) ?></td>
              <td><?= htmlspecialchars($mov['talla']) ?></td>
              <td class="fw-bold text-uppercase"><?= htmlspecialchars($mov['tipo']) ?></td>
              <td><?= htmlspecialchars($mov['cantidad']) ?></td>
              <td>
                <?php
                  $fecha_mov = new DateTime($mov['fecha'], new DateTimeZone('Europe/Madrid'));
                  echo $fecha_mov->format('d/m/Y H:i:s');
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center text-muted">No hay movimientos registrados</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

<?php include("../template/footer.php"); ?>