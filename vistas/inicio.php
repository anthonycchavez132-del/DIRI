<?php

declare(strict_types=1);

if (!defined('DIRI_ENTRADA')) {
    http_response_code(404);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($nombreProyecto, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="publico/estilos.css?v=3">
    <link rel="stylesheet" href="publico/inicio.css?v=1">
</head>
<body class="pagina-inicio vista-inicio">
    <a class="saltar-contenido" href="#contenido">Saltar al contenido</a>
    <header>
        <a href="index.php" class="marca" aria-label="DIRI, inicio">diri</a>
        <nav aria-label="Navegación principal">
            <a href="index.php?pagina=tramites">Consultar trámites</a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a class="boton" href="index.php?pagina=panel">Mi cuenta</a>
            <?php else: ?>
                <a href="#como-funciona">Cómo funciona</a>
                <a class="boton" href="index.php?pagina=login">Iniciar sesión <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
        </nav>
    </header>

    <main id="contenido">
        <section class="portada" aria-labelledby="titulo-portada">
            <div>
                <span class="etiqueta">Tu guía de trámites en <?= htmlspecialchars($ciudad, ENT_QUOTES, 'UTF-8') ?></span>
                <h1 id="titulo-portada">Tu trámite,<br>un paso <em>más cerca.</em></h1>
                <p class="descripcion">Descubre qué necesitas, consigue los documentos que te faltan y encuentra un recorrido a pie pensado para ti.</p>
                <div class="acciones"><a class="boton" href="index.php?pagina=preparar_recorrido">Organizar mi trámite <span aria-hidden="true">→</span></a><a class="boton secundario" href="index.php?pagina=tramites">Explorar trámites</a></div>
                <p class="nota">Puedes preparar tu recorrido sin crear una cuenta.</p>
            </div>
            <div class="vista-ruta" aria-label="Así organiza DIRI tu recorrido: ubicación, requisitos y entidad final">
                <p>Un camino más claro</p>
                <div class="ruta-ilustrada" aria-hidden="true"><svg viewBox="0 0 400 135" fill="none"><path d="M0 30H400M0 100H400M65 0V135M210 0V135M345 0V135" stroke="#d5e4dd" stroke-width="12"/><path d="M65 100V60Q65 30 95 30H180Q210 30 210 60V75Q210 100 240 100H315Q345 100 345 70V30" stroke="white" stroke-width="13" stroke-linecap="round"/><path d="M65 100V60Q65 30 95 30H180Q210 30 210 60V75Q210 100 240 100H315Q345 100 345 70V30" stroke="#087b66" stroke-width="4" stroke-linecap="round" stroke-dasharray="6 7"/><circle cx="65" cy="100" r="10" fill="#087b66" stroke="white" stroke-width="5"/><circle cx="210" cy="65" r="10" fill="#dfab4b" stroke="white" stroke-width="5"/><path d="M345 43s-16-17-16-26a16 16 0 0 1 32 0c0 9-16 26-16 26Z" fill="#153f49"/><circle cx="345" cy="17" r="5" fill="white"/></svg></div>
                <div class="ruta-tarjeta"><span class="numero">01</span><div><strong>Tu punto de partida</strong><small>Comenzamos desde tu ubicación</small></div></div>
                <div class="ruta-tarjeta"><span class="numero">02</span><div><strong>Los requisitos que te faltan</strong><small>Organizamos las visitas necesarias</small></div></div>
                <div class="ruta-tarjeta"><span class="numero">03</span><div><strong>La entidad de tu trámite</strong><small>Llega con tu documentación preparada</small></div></div>
            </div>
        </section>
        <div class="franja-confianza"><span><strong>Hecho para La Paz</strong></span><span>Requisitos con fuentes oficiales</span><span>Recorridos a pie</span><span>Abrir ruta en Google Maps</span></div>
        <section aria-labelledby="titulo-categorias">
            <div class="fila-titulo"><div><span class="etiqueta">¿Qué necesitas hacer?</span><h2 id="titulo-categorias">Encuentra tu siguiente paso.</h2><p>Comienza por el trámite que tienes pendiente.</p></div><a href="index.php?pagina=tramites">Ver todos los trámites <span aria-hidden="true">↗</span></a></div>
            <div class="tarjetas">
                <article class="tarjeta"><span class="icono" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="8" cy="11" r="2"/><path d="M5 16c0-3 6-3 6 0m3-6h4m-4 4h4"/></svg></span><h3>Tu identidad</h3><p>Consulta cómo renovar o reponer tu cédula de identidad.</p><a href="index.php?pagina=tramites&amp;buscar=c%C3%A9dula%20de%20identidad">Ver trámites de identidad →</a></article>
                <article class="tarjeta"><span class="icono" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 9 2-5h10l2 5M3 10h18v8H3zM6 18v3m12-3v3M6 14h2m8 0h2"/></svg></span><h3>Tu licencia de conducir</h3><p>Prepara tu primera licencia, una renovación o un duplicado.</p><a href="index.php?pagina=tramites&amp;buscar=licencia%20de%20conducir">Ver trámites de licencia →</a></article>
                <article class="tarjeta"><span class="icono" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 12h7m-7 4h4"/></svg></span><h3>Tus certificados</h3><p>Encuentra los pasos para solicitar antecedentes y certificaciones.</p><a href="index.php?pagina=tramites&amp;buscar=certific">Ver certificados →</a></article>
            </div>
        </section>
        <section class="como-funciona" id="como-funciona" aria-labelledby="titulo-como"><h2 id="titulo-como">Menos dudas. Un recorrido claro.</h2><ol><li><strong>Elige tu trámite</strong>Consulta qué se necesita y para qué casos está preparado.</li><li><strong>Cuéntanos qué tienes</strong>Marca tus documentos vigentes y comparte tu punto de partida.</li><li><strong>Sigue tu recorrido</strong>Consigue lo que te falta y revisa los horarios antes de salir.</li></ol></section>
    </main>
    <footer class="pie-pagina"><span><strong>DIRI</strong> · Tu guía de trámites en La Paz.</span><a href="index.php?pagina=tramites">Explorar trámites</a></footer>
</body>
</html>
