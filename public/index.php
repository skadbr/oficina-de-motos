<?php
use App\Controllers\WorkshopController;
use App\Core\Database;
use App\Models\WorkshopModel;

session_start();
require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Models/WorkshopModel.php';
require dirname(__DIR__) . '/app/Controllers/WorkshopController.php';

try {
    $controller = new WorkshopController(new WorkshopModel(Database::connect()));
    $controller->run();
} catch (\Throwable $exception) {
    http_response_code(500);
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Erro ao iniciar</title><body><h1>Não foi possível iniciar a oficina</h1><p>' . e($exception->getMessage()) . '</p><p>Verifique o PHP com PDO SQLite e a permissão de escrita no pendrive.</p></body></html>';
}