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
        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price_cents INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS work_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
            motorcycle_id INTEGER NOT NULL REFERENCES motorcycles(id) ON DELETE RESTRICT,
            created_at TEXT NOT NULL,
            completed_at TEXT,
            parts_description TEXT,
            parts_cents INTEGER NOT NULL DEFAULT 0,
            labor_cents INTEGER NOT NULL DEFAULT 0,
            total_cents INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'Aberta'
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS work_order_services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            work_order_id INTEGER NOT NULL REFERENCES work_orders(id) ON DELETE CASCADE,
            service_id INTEGER REFERENCES services(id) ON DELETE SET NULL,
            service_name TEXT NOT NULL
        )");

        return $pdo;
    }
}