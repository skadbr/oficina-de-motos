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

    public function parts($search = '')
    {
        if ($search === '') {
            return $this->db->query('SELECT * FROM parts ORDER BY name')->fetchAll();
        }
        $statement = $this->db->prepare('SELECT * FROM parts WHERE name LIKE ? OR description LIKE ? ORDER BY name');
        $term = '%' . $search . '%';
        $statement->execute(array($term, $term));
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

    public function findPart($id)
    {
        $statement = $this->db->prepare('SELECT * FROM parts WHERE id = ?');
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
            $statement = $this->db->prepare($sql . ' WHERE CAST(work_orders.id AS TEXT) LIKE ? OR clients.name LIKE ? OR motorcycles.brand LIKE ? OR motorcycles.model LIKE ? OR motorcycles.plate LIKE ? OR work_orders.parts_description LIKE ? OR work_orders.services_description LIKE ? OR work_orders.status LIKE ? OR work_orders.created_at LIKE ? OR work_orders.completed_at LIKE ? OR EXISTS (SELECT 1 FROM work_order_parts WHERE work_order_parts.work_order_id = work_orders.id AND work_order_parts.part_name LIKE ?) ORDER BY work_orders.id DESC');
            $term = '%' . $search . '%';
            $statement->execute(array_fill(0, 11, $term));
            $orders = $statement->fetchAll();
        }
        $partRows = $this->db->query('SELECT work_order_id, part_id, part_name, quantity, unit_price_cents, line_total_cents FROM work_order_parts ORDER BY id')->fetchAll();
        $partsByOrder = array();
        foreach ($partRows as $partRow) {
            $partsByOrder[$partRow['work_order_id']][] = $partRow;
        }
        foreach ($orders as &$order) {
            $order['part_items'] = isset($partsByOrder[$order['id']]) ? $partsByOrder[$order['id']] : array();
        }
        unset($order);
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
        $parts = $this->db->prepare('SELECT part_id, part_name, quantity, unit_price_cents, line_total_cents FROM work_order_parts WHERE work_order_id = ? ORDER BY id');
        $parts->execute(array($id));
        $order['part_items'] = $parts->fetchAll();
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

    public function addPart($name, $description, $stockQuantity, $salePriceCents)
    {
        $statement = $this->db->prepare('INSERT INTO parts (name, description, stock_quantity, sale_price_cents) VALUES (?, ?, ?, ?)');
        $statement->execute(array($name, $description, $stockQuantity, $salePriceCents));
        return $this->db->lastInsertId();
    }

    public function updatePart($id, $name, $description, $stockQuantity, $salePriceCents)
    {
        $statement = $this->db->prepare('UPDATE parts SET name = ?, description = ?, stock_quantity = ?, sale_price_cents = ? WHERE id = ?');
        $statement->execute(array($name, $description, $stockQuantity, $salePriceCents, $id));
        return $statement->rowCount() > 0 || $this->findPart($id);
    }

    public function deletePart($id)
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM work_order_parts WHERE part_id = ?');
        $statement->execute(array($id));
        if ((int) $statement->fetchColumn() > 0) {
            throw new \RuntimeException('Não é possível excluir: a peça está registrada no histórico de uma OS.');
        }
        $statement = $this->db->prepare('DELETE FROM parts WHERE id = ?');
        $statement->execute(array($id));
        return $statement->rowCount() > 0;
    }

    public function addOrder($clientId, $motorcycleId, $createdAt, $servicesDescription, $partItems, $laborCents)
    {
        $checkBike = $this->db->prepare('SELECT id FROM motorcycles WHERE id = ? AND client_id = ?');
        $checkBike->execute(array($motorcycleId, $clientId));
        if (!$checkBike->fetch()) {
            throw new \InvalidArgumentException('A moto precisa pertencer ao cliente selecionado.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO work_orders (client_id, motorcycle_id, created_at, services_description, parts_description, parts_cents, labor_cents, total_cents, stock_deducted, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)');
            $statement->execute(array(
                $clientId,
                $motorcycleId,
                str_replace('T', ' ', $createdAt),
                $servicesDescription,
                '',
                0,
                $laborCents,
                $laborCents,
                'Aberta'
            ));
            $orderId = $this->db->lastInsertId();
            $parts = $this->reserveOrderParts($orderId, $partItems);
            $update = $this->db->prepare('UPDATE work_orders SET parts_description = ?, parts_cents = ?, total_cents = ?, stock_deducted = ? WHERE id = ?');
            $update->execute(array($parts['description'], $parts['total_cents'], $parts['total_cents'] + $laborCents, $parts['deducted'] ? 1 : 0, $orderId));
            $this->db->commit();
            return $orderId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateOrder($id, $clientId, $motorcycleId, $createdAt, $servicesDescription, $partItems, $laborCents)
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

        $hasLegacyParts = empty($current['part_items']) && (trim((string) $current['parts_description']) !== '' || (int) $current['parts_cents'] > 0);
        $stockDeducted = (int) $current['stock_deducted'];
        $this->db->beginTransaction();
        try {
            if ($current['status'] === 'Cancelada') {
                $partsCents = (int) $current['parts_cents'];
                $partsDescription = $current['parts_description'];
            } else {
                $requestedQuantities = array();
                foreach ($partItems as $item) {
                    $partId = isset($item['id']) ? (int) $item['id'] : 0;
                    $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;
                    if ($partId > 0 && $quantity > 0) {
                        $requestedQuantities[$partId] = isset($requestedQuantities[$partId]) ? $requestedQuantities[$partId] + $quantity : $quantity;
                    }
                }
                $priceOverrides = array();
                foreach ($current['part_items'] as $oldItem) {
                    $partId = (int) $oldItem['part_id'];
                    if (isset($requestedQuantities[$partId]) && $requestedQuantities[$partId] === (int) $oldItem['quantity']) {
                        $priceOverrides[$partId] = (int) $oldItem['unit_price_cents'];
                    }
                }
                if ((int) $current['stock_deducted'] === 1) {
                    $this->restoreOrderStock($id, true);
                } else {
                    $this->db->prepare('DELETE FROM work_order_parts WHERE work_order_id = ?')->execute(array($id));
                }
                $parts = $this->reserveOrderParts($id, $partItems, $priceOverrides);
                $partsCents = $parts['total_cents'] + ($hasLegacyParts ? (int) $current['parts_cents'] : 0);
                $partsDescription = $hasLegacyParts
                    ? trim($current['parts_description'] . ($parts['description'] !== '' ? ', ' . $parts['description'] : ''))
                    : $parts['description'];
                $stockDeducted = $parts['deducted'] ? 1 : 0;
            }
            $statement = $this->db->prepare('UPDATE work_orders SET client_id = ?, motorcycle_id = ?, created_at = ?, services_description = ?, parts_description = ?, parts_cents = ?, labor_cents = ?, total_cents = ?' . ($current['status'] === 'Cancelada' ? '' : ', stock_deducted = ?') . ' WHERE id = ?');
            $values = array(
                $clientId,
                $motorcycleId,
                str_replace('T', ' ', $createdAt),
                $servicesDescription,
                $partsDescription,
                $partsCents,
                $laborCents,
                $partsCents + $laborCents
            );
            if ($current['status'] !== 'Cancelada') {
                $values[] = $stockDeducted;
            }
            $values[] = $id;
            $statement->execute($values);
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
        $this->db->beginTransaction();
        try {
            $current = $this->db->prepare("SELECT stock_deducted FROM work_orders WHERE id = ? AND status = 'Aberta'");
            $current->execute(array($orderId));
            $order = $current->fetch();
            if (!$order) {
                $this->db->rollBack();
                return false;
            }
            if ((int) $order['stock_deducted'] === 1) {
                $this->restoreOrderStock($orderId, false);
            }
            $statement = $this->db->prepare("UPDATE work_orders SET status = 'Cancelada', cancelled_at = ?, stock_deducted = 0 WHERE id = ? AND status = 'Aberta'");
            $statement->execute(array(date('Y-m-d H:i:s'), $orderId));
            $this->db->commit();
            return $statement->rowCount() > 0;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function reserveOrderParts($orderId, $partItems, $priceOverrides = array())
    {
        if (!is_array($partItems)) {
            throw new \InvalidArgumentException('A lista de peças da OS é inválida.');
        }
        $requested = array();
        foreach ($partItems as $item) {
            $partId = isset($item['id']) ? (int) $item['id'] : 0;
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;
            if ($partId < 1 || $quantity < 1) {
                throw new \InvalidArgumentException('Selecione uma peça e informe uma quantidade válida.');
            }
            $requested[$partId] = isset($requested[$partId]) ? $requested[$partId] + $quantity : $quantity;
        }

        $lookup = $this->db->prepare('SELECT id, name, stock_quantity, sale_price_cents FROM parts WHERE id = ?');
        $decrement = $this->db->prepare('UPDATE parts SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
        $insert = $this->db->prepare('INSERT INTO work_order_parts (work_order_id, part_id, part_name, quantity, unit_price_cents, line_total_cents) VALUES (?, ?, ?, ?, ?, ?)');
        $totalCents = 0;
        $description = array();
        foreach ($requested as $partId => $quantity) {
            $lookup->execute(array($partId));
            $part = $lookup->fetch();
            if (!$part) {
                throw new \InvalidArgumentException('Uma das peças selecionadas não existe mais no estoque.');
            }
            if ((int) $part['stock_quantity'] < $quantity) {
                throw new \RuntimeException('Estoque insuficiente para ' . $part['name'] . '. Disponível: ' . (int) $part['stock_quantity'] . '.');
            }
            $decrement->execute(array($quantity, $partId, $quantity));
            if ($decrement->rowCount() !== 1) {
                throw new \RuntimeException('O estoque de ' . $part['name'] . ' foi alterado. Atualize a OS e tente novamente.');
            }
            $unitPrice = isset($priceOverrides[$partId]) ? $priceOverrides[$partId] : (int) $part['sale_price_cents'];
            $lineTotal = $unitPrice * $quantity;
            $insert->execute(array($orderId, $partId, $part['name'], $quantity, $unitPrice, $lineTotal));
            $totalCents += $lineTotal;
            $description[] = $part['name'] . ' x ' . $quantity;
        }
        return array(
            'total_cents' => $totalCents,
            'description' => implode(', ', $description),
            'deducted' => count($requested) > 0
        );
    }

    private function restoreOrderStock($orderId, $deleteLines)
    {
        $statement = $this->db->prepare('SELECT part_id, quantity FROM work_order_parts WHERE work_order_id = ?');
        $statement->execute(array($orderId));
        $restore = $this->db->prepare('UPDATE parts SET stock_quantity = stock_quantity + ? WHERE id = ?');
        foreach ($statement->fetchAll() as $item) {
            $restore->execute(array((int) $item['quantity'], (int) $item['part_id']));
            if ($restore->rowCount() !== 1) {
                throw new \RuntimeException('Não foi possível devolver uma peça ao estoque.');
            }
        }
        if ($deleteLines) {
            $this->db->prepare('DELETE FROM work_order_parts WHERE work_order_id = ?')->execute(array($orderId));
        }
    }
}