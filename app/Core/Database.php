<?php
namespace App\Core;

class Database
{
    public static function connect()
    {
        $root = dirname(__DIR__, 2);
        $directory = $root . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível criar a pasta data do pendrive.');
        }

        $pdo = new \PDO('sqlite:' . $directory . DIRECTORY_SEPARATOR . 'oficina.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS motorcycles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
            brand TEXT NOT NULL,
            model TEXT NOT NULL,
            year INTEGER,
            plate TEXT NOT NULL UNIQUE,
            mileage INTEGER DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS work_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
            motorcycle_id INTEGER NOT NULL REFERENCES motorcycles(id) ON DELETE RESTRICT,
            created_at TEXT NOT NULL,
            completed_at TEXT,
            cancelled_at TEXT,
            services_description TEXT NOT NULL DEFAULT '',
            parts_description TEXT,
            parts_cents INTEGER NOT NULL DEFAULT 0,
            labor_cents INTEGER NOT NULL DEFAULT 0,
            total_cents INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'Aberta'
        )");
        $hasLegacyWorkOrderServices = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'work_order_services'")->fetchColumn() > 0;
        $workOrderColumns = $pdo->query('PRAGMA table_info(work_orders)')->fetchAll();
        $hasCancelledAt = false;
        foreach ($workOrderColumns as $column) {
            if ($column['name'] === 'cancelled_at') {
                $hasCancelledAt = true;
                break;
            }
        }
        if (!$hasCancelledAt) {
            $pdo->exec('ALTER TABLE work_orders ADD COLUMN cancelled_at TEXT');
        }
        $pdo->exec("UPDATE work_orders SET status = 'Faturada' WHERE status = 'Concluída'");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS validate_work_order_status_insert
            BEFORE INSERT ON work_orders
            WHEN NEW.status NOT IN ('Aberta', 'Faturada', 'Cancelada')
            BEGIN
                SELECT RAISE(ABORT, 'INVALID_WORK_ORDER_STATUS');
            END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS validate_work_order_status_update
            BEFORE UPDATE OF status ON work_orders
            WHEN NEW.status NOT IN ('Aberta', 'Faturada', 'Cancelada')
            BEGIN
                SELECT RAISE(ABORT, 'INVALID_WORK_ORDER_STATUS');
            END");
        $workOrderColumns = $pdo->query('PRAGMA table_info(work_orders)')->fetchAll();
        $hasServicesDescription = false;
        foreach ($workOrderColumns as $column) {
            if ($column['name'] === 'services_description') {
                $hasServicesDescription = true;
                break;
            }
        }
        if (!$hasServicesDescription) {
            $pdo->exec("ALTER TABLE work_orders ADD COLUMN services_description TEXT NOT NULL DEFAULT ''");
            $legacyRows = $hasLegacyWorkOrderServices
                ? $pdo->query('SELECT work_order_id, service_name FROM work_order_services ORDER BY id')->fetchAll()
                : array();
            $legacyServices = array();
            foreach ($legacyRows as $legacyRow) {
                $legacyServices[$legacyRow['work_order_id']][] = $legacyRow['service_name'];
            }
            $updateServices = $pdo->prepare('UPDATE work_orders SET services_description = ? WHERE id = ?');
            foreach ($legacyServices as $workOrderId => $serviceNames) {
                $updateServices->execute(array(implode(', ', $serviceNames), $workOrderId));
            }
        }

        return $pdo;
    }
}