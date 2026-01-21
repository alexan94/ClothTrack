<?php include("template/header.php"); ?>

<!-- Vincular CSS -->
<link rel="stylesheet" href="css/style.css">

<body class="d-flex flex-column justify-content-center align-items-center vh-100">

  <!-- Botón arriba a la derecha identificate -->
  <div class="position-absolute top-0 end-0 p-3">
    <a href="login.php" class="btn btn-marron px-4 py-2">Identifícate</a>
  </div>

  <!-- texto centrado -->
  <div class="text-center">
    <!-- Logo ClothTrack -->
    <img src="img/LogoClothTrack.png" 
         class="img-fluid mb-4 rounded logo-clothtrack" 
         alt="Logo ClothTrack">

    <!-- Texto de bienvenida -->
    <h1 class="display-3 fw-bold">¡Bienvenido a ClothTrack!</h1>
    <h4 class="text-secondary mt-3">Tu web de gestión de inventario</h4>
  </div>

<?php include("template/footer.php"); ?>
