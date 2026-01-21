<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_destroy();

// Redirigir al index en la raíz del proyecto
header("Location: ../index.php?logout=ok");
exit;
?>