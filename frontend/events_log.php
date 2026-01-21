<?php
// frontend/events_log.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../services/EventService.php';

session_start();
if (!isset($_SESSION['registrado']) || $_SESSION['registrado'] != 'true') {
    die("Acceso Denegado");
}

$eventService = new EventService();
$events = $eventService->getRecentEvents(100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Eventos - VeriFactu Audit</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .event-row { font-size: 0.9em; }
        .hash-cell { font-family: monospace; font-size: 0.8em; color: #555; }
        .type-BOOT { color: green; font-weight: bold; }
        .type-ERROR { color: red; font-weight: bold; }
        .type-WARNING { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1>Registro de Eventos</h1>
                    <span style="color:var(--text-secondary);">Auditoría del Sistema (Veri*Factu)</span>
                </div>
                <a href="index.php" class="btn">Volver a Facturas</a>
            </div>
        </header>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha (UTC)</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Usuario</th>
                        <th>Hash (Encadenado)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="6" style="text-align:center">No hay eventos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $evt): ?>
                            <tr class="event-row">
                                <td><?= $evt['id'] ?></td>
                                <td><?= $evt['timestamp'] ?></td>
                                <td class="type-<?= $evt['event_type'] ?>"><?= $evt['event_type'] ?></td>
                                <td>
                                    <?= htmlspecialchars($evt['description']) ?>
                                    <?php if(!empty($evt['details']) && $evt['details'] != '[]'): ?>
                                        <br><small style="color:#777"><?= htmlspecialchars($evt['details']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($evt['user_id']) ?></td>
                                <td class="hash-cell" title="<?= $evt['current_hash'] ?>">
                                    <?= substr($evt['current_hash'], 0, 16) ?>...
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
