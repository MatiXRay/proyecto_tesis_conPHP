<?php
/**
 * BIALYSTOK BREWING CO — Sidebar / Menú
 * Parcial incluido en todas las páginas protegidas.
 * Requiere que auth.php ya haya sido cargado (sesión activa).
 *
 * Uso: <?php require 'menu.php'; ?>
 * Se pasa $menu_activo desde la página padre para marcar el ítem activo.
 * Ej: $menu_activo = 'lotes';
 */

$menu_activo = $menu_activo ?? '';

$items_menu = [
    ['id' => 'inicio',         'label' => 'Inicio',         'url' => 'inicio',         'icono' => 'grid'],
    ['id' => 'lotes',          'label' => 'Lotes',          'url' => 'lotes',          'icono' => 'layers'],
    ['id' => 'recetas',        'label' => 'Recetas',        'url' => 'recetas',        'icono' => 'file-text'],
];
$items_ingredientes = [
    ['id' => 'maltas',         'label' => 'Malta',          'url' => 'maltas',         'icono' => 'box'],
    ['id' => 'lupulos',        'label' => 'Lúpulo',         'url' => 'lupulos',        'icono' => 'star'],
    ['id' => 'cepas_levadura', 'label' => 'Levadura',       'url' => 'cepas_levadura', 'icono' => 'circle'],
];
$items_produccion = [
    ['id' => 'fermentadores',  'label' => 'Fermentadores',  'url' => 'fermentadores',  'icono' => 'thermometer'],
    ['id' => 'reportes_agua',  'label' => 'Reportes H₂O',   'url' => 'reportes_agua',  'icono' => 'droplet'],
    ['id' => 'estadisticas',   'label' => 'Estadísticas',   'url' => 'estadisticas',   'icono' => 'bar-chart-2'],
    ['id' => 'panel_sensorial','label' => 'Panel Sensorial','url' => 'panel_sensorial','icono' => 'eye'],
    ['id' => 'planificacion',  'label' => 'Planificación',  'url' => 'planificacion',  'icono' => 'calendar'],
];

function menuIcono(string $name): string {
    $iconos = [
        'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'layers'      => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'file-text'   => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
        'box'         => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'star'        => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'circle'      => '<circle cx="12" cy="12" r="10"/>',
        'thermometer' => '<path d="M14 14.76V3.5a2.5 2.5 0 00-5 0v11.26a4.5 4.5 0 105 0z"/>',
        'droplet'     => '<path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>',
        'bar-chart-2' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'eye'         => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'calendar'    => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>',
    ];
    $path = $iconos[$name] ?? '<circle cx="12" cy="12" r="4"/>';
    return '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;opacity:.7">' . $path . '</svg>';
}

function menuItem(array $item, string $activo): string {
    $clase = $item['id'] === $activo ? ' class="active"' : '';
    $icono = menuIcono($item['icono']);
    return "<li><a href=\"{$item['url']}\"{$clase}>{$icono} {$item['label']}</a></li>\n";
}
?>
<aside id="sidebar" class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name">Bialystok Brewing</div>
    <div class="brand-sub">Sistema de Producción</div>
  </div>

  <nav>
    <ul class="menu" style="list-style:none;padding:0;margin:0">
      <li class="menu-label">Principal</li>
      <?php foreach ($items_menu as $item): echo menuItem($item, $menu_activo); endforeach; ?>

      <li class="menu-label">Ingredientes</li>
      <?php foreach ($items_ingredientes as $item): echo menuItem($item, $menu_activo); endforeach; ?>

      <li class="menu-label">Producción</li>
      <?php foreach ($items_produccion as $item): echo menuItem($item, $menu_activo); endforeach; ?>

      <?php if (isAdmin()): ?>
      <li class="menu-label">Sistema</li>
      <li><a href="configuracion" <?= $menu_activo === 'configuracion' ? 'class="active"' : '' ?>>
        <?= menuIcono('settings') ?> Configuración
      </a></li>
      <?php endif; ?>
    </ul>
  </nav>
</aside>
