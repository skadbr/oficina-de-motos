<?php
namespace App\Models;

class WorkshopModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createBackupSnapshot()
    {
        $backupPath = tempnam(sys_get_temp_dir(), 'oficina-backup-');
        if ($backupPath === false) {
            throw new \RuntimeException('Não foi possível criar o arquivo temporário do backup.');
        }

        $source = null;
        $destination = null;
        try {
            $source = new \SQLite3($this->databasePath(), \SQLITE3_OPEN_READONLY);
            $destination = new \SQLite3($backupPath);
            $source->enableExceptions(true);
            $destination->enableExceptions(true);
            if (!$source->backup($destination)) {
                throw new \RuntimeException('O SQLite não conseguiu copiar o banco para o backup.');
            }
            if ($destination->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new \RuntimeException('A cópia do banco não passou na verificação de integridade.');
            }
            $destination->close();
            $source->close();
            return $backupPath;
        } catch (\Throwable $exception) {
            if ($destination instanceof \SQLite3) {
                $destination->close();
            }
            if ($source instanceof \SQLite3) {
                $source->close();
            }
            if (is_file($backupPath)) {
                unlink($backupPath);
            }
            throw $exception;
        }
    }

    public function restoreBackup($uploadedPath)
    {
        $databasePath = $this->databasePath();
        $stagingPath = $databasePath . '.restore-' . bin2hex(random_bytes(6));
        if (!copy($uploadedPath, $stagingPath)) {
            throw new \RuntimeException('Não foi possível copiar o backup para validação.');
        }

        $staging = null;
        try {
            $staging = new \SQLite3($stagingPath, \SQLITE3_OPEN_READWRITE);
            $staging->enableExceptions(true);
            if ($staging->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new \RuntimeException('O arquivo selecionado não é um backup SQLite íntegro.');
            }
            $tables = $staging->query("SELECT name FROM sqlite_master WHERE type = 'table'");
            $tableNames = array();
            while ($row = $tables->fetchArray(SQLITE3_ASSOC)) {
                $tableNames[] = $row['name'];
            }
            foreach (array('clients', 'motorcycles', 'work_orders') as $requiredTable) {
                if (!in_array($requiredTable, $tableNames, true)) {
                    throw new \RuntimeException('O arquivo selecionado não parece ser um backup desta oficina.');
                }
            }
            $staging->close();
            $staging = null;
        } catch (\Throwable $exception) {
            if ($staging instanceof \SQLite3) {
                $staging->close();
            }
            if (is_file($stagingPath)) {
                unlink($stagingPath);
            }
            if ($exception instanceof \SQLite3Exception) {
                throw new \RuntimeException('O arquivo selecionado não é um backup SQLite válido.', 0, $exception);
            }
            throw $exception;
        }

        $this->db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        $this->db = null;
        $safetyPath = $databasePath . '.before-restore-' . bin2hex(random_bytes(6));
        if (!rename($databasePath, $safetyPath)) {
            $this->db = new \PDO('sqlite:' . $databasePath);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA foreign_keys = ON');
            unlink($stagingPath);
            throw new \RuntimeException('Não foi possível preparar o banco atual para restauração.');
        }
        if (!rename($stagingPath, $databasePath)) {
            rename($safetyPath, $databasePath);
            $this->db = new \PDO('sqlite:' . $databasePath);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->exec('PRAGMA foreign_keys = ON');
            throw new \RuntimeException('Não foi possível instalar o backup. O banco anterior foi mantido.');
        }

        if (is_file($safetyPath)) {
            unlink($safetyPath);
        }
    }

    private function databasePath()
    {
        $databases = $this->db->query('PRAGMA database_list')->fetchAll();
        foreach ($databases as $database) {
            if ($database['name'] === 'main') {
                return $database['file'];
            }
        }
        throw new \RuntimeException('Não foi possível localizar o arquivo SQLite da oficina.');
    }

    public function clients($search = '')
    {
        if ($search === '') {
            return $this->db->query('SELECT * FROM clients ORDER BY name')->fetchAll();
        }
        $statement = $this->db->prepare('SELECT * FROM clients WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY name');
        $term = '%' . $search . '%';
        $statement->execute(array($term, $term, $term));
        return $statement->fetchAll();
    }

    public function motorcycles($search = '')
    {
        $sql = 'SELECT motorcycles.*, clients.name AS client_name FROM motorcycles JOIN clients ON clients.id = motorcycles.client_id';
        if ($search === '') {
            return $this->db->query($sql . ' ORDER BY motorcycles.plate')->fetchAll();
        }
        $statement = $this->db->prepare($sql . ' WHERE motorcycles.brand LIKE ? OR motorcycles.model LIKE ? OR motorcycles.plate LIKE ? OR CAST(motorcycles.year AS TEXT) LIKE ? OR clients.name LIKE ? ORDER BY motorcycles.plate');
        $term = '%' . $search . '%';
        $statement->execute(array($term, $term, $term, $term, $term));
        return $statement->fetchAll();
    }

    public function findClient($id)
    {
        $statement = $this->db->prepare('SELECT * FROM clients WHERE id = ?');
        $statement->execute(array($id));
        return $statement->fetch();
    }

    public function findMotorcycle($id)
    {
        $statement = $this->db->prepare('SELECT * FROM motorcycles WHERE id = ?');
        $statement->execute(array($id));
        return $statement->fetch();
    }

    public function orders($search = '')
    {
        $sql = "SELECT work_orders.*, clients.name AS client_name, motorcycles.brand, motorcycles.model, motorcycles.plate
            FROM work_orders
            JOIN clients ON clients.id = work_orders.client_id
            JOIN motorcycles ON motorcycles.id = work_orders.motorcycle_id";
        if ($search === '') {
            $orders = $this->db->query($sql . ' ORDER BY work_orders.id DESC')->fetchAll();
        } else {
            $statement = $this->db->prepare($sql . ' WHERE CAST(work_orders.id AS TEXT) LIKE ? OR clients.name LIKE ? OR motorcycles.brand LIKE ? OR motorcycles.model LIKE ? OR motorcycles.plate LIKE ? OR work_orders.parts_description LIKE ? OR work_orders.services_description LIKE ? OR work_orders.status LIKE ? OR work_orders.created_at LIKE ? OR work_orders.completed_at LIKE ? ORDER BY work_orders.id DESC');
            $term = '%' . $search . '%';
            $statement->execute(array_fill(0, 10, $term));
            $orders = $statement->fetchAll();
        }
        return $orders;
    }

    public function financialReport($from, $to)
    {
        $statusDate = "CASE work_orders.status
            WHEN 'Aberta' THEN work_orders.created_at
            WHEN 'Faturada' THEN COALESCE(work_orders.completed_at, work_orders.created_at)
            WHEN 'Cancelada' THEN COALESCE(work_orders.cancelled_at, work_orders.created_at)
        END";
        $statement = $this->db->prepare("SELECT work_orders.id, work_orders.status, work_orders.total_cents, clients.name AS client_name, motorcycles.brand, motorcycles.model, motorcycles.plate, $statusDate AS status_date
            FROM work_orders
            JOIN clients ON clients.id = work_orders.client_id
            JOIN motorcycles ON motorcycles.id = work_orders.motorcycle_id
            WHERE $statusDate >= ? AND $statusDate <= ?
            ORDER BY status_date DESC, work_orders.id DESC");
        $statement->execute(array($from . ' 00:00:00', $to . ' 23:59:59'));
        $orders = $statement->fetchAll();
        $summary = array(
            'Aberta' => array('count' => 0, 'total_cents' => 0),
            'Faturada' => array('count' => 0, 'total_cents' => 0),
            'Cancelada' => array('count' => 0, 'total_cents' => 0)
        );
        foreach ($orders as $order) {
            $summary[$order['status']]['count']++;
            $summary[$order['status']]['total_cents'] += (int) $order['total_cents'];
        }
        return array('summary' => $summary, 'orders' => $orders);
    }

    public function findOrder($id)
    {
        $statement = $this->db->prepare('SELECT * FROM work_orders WHERE id = ?');
        $statement->execute(array($id));
        $order = $statement->fetch();
        if (!$order) {
            return false;
        }
        return $order;
    }

    public function addClient($name, $phone, $email)
    {
        $statement = $this->db->prepare('INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)');
        $statement->execute(array($name, $phone, $email));
        return $this->db->lastInsertId();
    }

    public function addClientWithMotorcycle($name, $phone, $email, $motorcycle)
    {
        $this->db->beginTransaction();
        try {
            $clientId = $this->addClient($name, $phone, $email);
            $motorcycleId = $this->addMotorcycle(
                $clientId,
                $motorcycle['brand'],
                $motorcycle['model'],
                $motorcycle['year'],
                $motorcycle['plate'],
                $motorcycle['mileage']
            );
            $this->db->commit();
            return $motorcycleId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateClient($id, $name, $phone, $email)
    {
        $statement = $this->db->prepare('UPDATE clients SET name = ?, phone = ?, email = ? WHERE id = ?');
        $statement->execute(array($name, $phone, $email, $id));
        return $statement->rowCount() > 0 || $this->findClient($id);
    }

    public function deleteClient($id)
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM motorcycles WHERE client_id = ?');
        $statement->execute(array($id));
        if ((int) $statement->fetchColumn() > 0) {
            throw new \RuntimeException('Não é possível excluir: este cliente ainda possui motocicleta cadastrada.');
        }
        $statement = $this->db->prepare('SELECT COUNT(*) FROM work_orders WHERE client_id = ?');
        $statement->execute(array($id));
        if ((int) $statement->fetchColumn() > 0) {
            throw new \RuntimeException('Não é possível excluir: este cliente possui ordem de serviço no histórico.');
        }
        $statement = $this->db->prepare('DELETE FROM clients WHERE id = ?');
        $statement->execute(array($id));
        return $statement->rowCount() > 0;
    }

    public function addMotorcycle($clientId, $brand, $model, $year, $plate, $mileage)
    {
        $statement = $this->db->prepare('INSERT INTO motorcycles (client_id, brand, model, year, plate, mileage) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute(array($clientId, $brand, $model, $year, $plate, $mileage));
        return $this->db->lastInsertId();
    }

    public function updateMotorcycle($id, $clientId, $brand, $model, $year, $plate, $mileage)
    {
        $current = $this->findMotorcycle($id);
        if (!$current) {
            throw new \RuntimeException('Motocicleta não encontrada.');
        }
        if ((int) $current['client_id'] !== (int) $clientId) {
            $statement = $this->db->prepare('SELECT COUNT(*) FROM work_orders WHERE motorcycle_id = ?');
            $statement->execute(array($id));
            if ((int) $statement->fetchColumn() > 0) {
                throw new \RuntimeException('Não é possível trocar o proprietário: esta moto possui OS no histórico.');
            }
        }
        $statement = $this->db->prepare('UPDATE motorcycles SET client_id = ?, brand = ?, model = ?, year = ?, plate = ?, mileage = ? WHERE id = ?');
        $statement->execute(array($clientId, $brand, $model, $year, $plate, $mileage, $id));
        return true;
    }

    public function deleteMotorcycle($id)
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM work_orders WHERE motorcycle_id = ?');
        $statement->execute(array($id));
        if ((int) $statement->fetchColumn() > 0) {
            throw new \RuntimeException('Não é possível excluir: esta motocicleta possui ordem de serviço no histórico.');
        }
        $statement = $this->db->prepare('DELETE FROM motorcycles WHERE id = ?');
        $statement->execute(array($id));
        return $statement->rowCount() > 0;
    }

    public function addOrder($clientId, $motorcycleId, $createdAt, $servicesDescription, $partsDescription, $partsCents, $laborCents)
    {
        $checkBike = $this->db->prepare('SELECT id FROM motorcycles WHERE id = ? AND client_id = ?');
        $checkBike->execute(array($motorcycleId, $clientId));
        if (!$checkBike->fetch()) {
            throw new \InvalidArgumentException('A moto precisa pertencer ao cliente selecionado.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO work_orders (client_id, motorcycle_id, created_at, services_description, parts_description, parts_cents, labor_cents, total_cents, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute(array(
                $clientId,
                $motorcycleId,
                str_replace('T', ' ', $createdAt),
                $servicesDescription,
                $partsDescription,
                $partsCents,
                $laborCents,
                $partsCents + $laborCents,
                'Aberta'
            ));
            $orderId = $this->db->lastInsertId();
            $this->db->commit();
            return $orderId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateOrder($id, $clientId, $motorcycleId, $createdAt, $servicesDescription, $partsDescription, $partsCents, $laborCents)
    {
        $current = $this->findOrder($id);
        if (!$current) {
            throw new \RuntimeException('Ordem de serviço não encontrada.');
        }
        $checkBike = $this->db->prepare('SELECT id FROM motorcycles WHERE id = ? AND client_id = ?');
        $checkBike->execute(array($motorcycleId, $clientId));
        if (!$checkBike->fetch()) {
            throw new \InvalidArgumentException('A moto precisa pertencer ao cliente selecionado.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('UPDATE work_orders SET client_id = ?, motorcycle_id = ?, created_at = ?, services_description = ?, parts_description = ?, parts_cents = ?, labor_cents = ?, total_cents = ? WHERE id = ?');
            $statement->execute(array(
                $clientId,
                $motorcycleId,
                str_replace('T', ' ', $createdAt),
                $servicesDescription,
                $partsDescription,
                $partsCents,
                $laborCents,
                $partsCents + $laborCents,
                $id
            ));
            $this->db->commit();
            return true;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function billOrder($orderId)
    {
        $statement = $this->db->prepare("UPDATE work_orders SET status = 'Faturada', completed_at = ? WHERE id = ? AND status = 'Aberta'");
            $statement->execute(array(date('Y-m-d H:i:s'), $orderId));
        return $statement->rowCount() > 0;
    }

    public function cancelOrder($orderId)
    {
        $statement = $this->db->prepare("UPDATE work_orders SET status = 'Cancelada', cancelled_at = ? WHERE id = ? AND status = 'Aberta'");
        $statement->execute(array(date('Y-m-d H:i:s'), $orderId));
        return $statement->rowCount() > 0;
    }
}