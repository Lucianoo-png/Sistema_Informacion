<?php
// control/navbar.php — Sidebar con iconos Font Awesome 5
$paginaActual = $paginaActual ?? 'panel';
$base = defined('BASE_URL') ? BASE_URL : './';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-store" style="font-size:26px;color:#e87722"></i>
        </div>
        <div class="brand-text">
            <strong>Abarrotes Angy</strong>
            <span>Sistema de Información</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= $base ?>panel"
           class="nav-item <?= $paginaActual==='panel'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-table-cells-large"></i></span>
            Panel Principal
        </a>
        <a href="<?= $base ?>ventas"
           class="nav-item <?= $paginaActual==='ventas'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-cart-shopping"></i></span>
            Ventas
        </a>
        <a href="<?= $base ?>compras"
           class="nav-item <?= $paginaActual==='compras'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-box-open"></i></span>
            Compras
        </a>
        <a href="<?= $base ?>inventario"
           class="nav-item <?= $paginaActual==='inventario'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-tags"></i></span>
            Inventario
        </a>
        <a href="<?= $base ?>proveedores"
           class="nav-item <?= $paginaActual==='proveedores'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-truck"></i></span>
            Proveedores
        </a>
        <a href="<?= $base ?>transferencias"
           class="nav-item <?= $paginaActual==='transferencias'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-right-left"></i></span>
            Transferencias
        </a>
        <a href="<?= $base ?>reporte"
           class="nav-item <?= $paginaActual==='reporte'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
            Reporte Diario
        </a>
        <a href="<?= $base ?>corte"
           class="nav-item <?= $paginaActual==='corte'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-cash-register"></i></span>
            Corte de Caja
        </a>

        <div class="nav-divider"></div>

        <a href="<?= $base ?>bitacora"
           class="nav-item <?= $paginaActual==='bitacora'?'active':'' ?>">
            <span class="nav-icon"><i class="fa-solid fa-book"></i></span>
            Bitácora
        </a>
    </nav>

    <!-- ── Usuario + Logout al fondo del sidebar ── -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="fa-solid fa-circle-user"></i>
        </div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= htmlspecialchars(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? '')) ?></span>
            <span class="sidebar-user-clave"><?= htmlspecialchars($_SESSION['usuario'] ?? '') ?></span>
        </div>
    </div>
    <a href="<?= $base ?>logout" class="sidebar-logout">
        <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
    </a>
    <div class="sidebar-footer">v1.0 — 2026</div>
</aside>
