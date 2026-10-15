<?php
namespace App\Models;

class WorkshopModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function clients()
    {
        return $this->db->query('SELECT * FROM clients ORDER BY name')->fetchAll();
    }

    public function motorcycles()
    {
        return $this->db->query('SELECT motorcycles.*, clients.name AS client_name FROM motorcycles JOIN clients ON clients.id = motorcycles.client_id ORDER BY motorcycles.plate')->fetchAll();
    }

    public function services()
    {
        return $this->db->query('SELECT * FROM services ORDER BY name')->fetchAll();
    }

    public function orders()
    {
        $orders = $this->db->query("SELECT work_orders.*, clients.name AS client_name, motorcycles.brand, motorcycles.model, motorcycles.plate
            FROM work_orders
            JOIN clients ON clients.id = work_orders.client_id
            JOIN motorcycles ON motorcycles.id = work_orders.motorcycle_id
            ORDER BY work_orders.id DESC")->fetchAll();
        $serviceRows = $this->db->query('SELECT work_order_id, service_name FROM work_order_services ORDER BY id')->fetchAll();
        $servicesByOrder = array();
        foreach ($serviceRows as $service) {
            $servicesByOrder[$service['work_order_id']][] = $service['service_name'];
        }

        foreach ($orders as &$order) {
            $order['services'] = isset($servicesByOrder[$order['id']]) ? $servicesByOrder[$order['id']] : array();
        }
        unset($order);
        return $orders;
    }

    public function addClient($name, $phone, $email)
    {
        $statement = $this->db->prepare('INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)');
        $statement->execute(array($name, $phone, $email));
    }

    public function addMotorcycle($clientId, $brand, $model, $year, $plate, $mileage)
    {
        $statement = $this->db->prepare('INSERT INTO motorcycles (client_id, brand, model, year, plate, mileage) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute(array($clientId, $brand, $model, $year, $plate, $mileage));
    }

    public function addService($name, $description, $priceCents)
    {
        $statement = $this->db->prepare('INSERT INTO services (name, description, price_cents) VALUES (?, ?, ?)');
        $statement->execute(array($name, $description, $priceCents));
    }

    public function addOrder($clientId, $motorcycleId, $createdAt, $completedAt, $partsDescription, $partsCents, $laborCents, $serviceIds)
    {
        $checkBike = $this->db->prepare('SELECT id FROM motorcycles WHERE id = ? AND client_id = ?');
        $checkBike->execute(array($motorcycleId, $clientId));
        if (!$checkBike->fetch()) {
            throw new \InvalidArgumentException('A moto precisa pertencer ao cliente selecionado.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO work_orders (client_id, motorcycle_id, created_at, completed_at, parts_description, parts_cents, labor_cents, total_cents, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute(array(
                $clientId,
                $motorcycleId,
                str_replace('T', ' ', $createdAt),
                $completedAt === '' ? null : str_replace('T', ' ', $completedAt),
                $partsDescription,
                $partsCents,
                $laborCents,
                $partsCents + $laborCents,
                $completedAt === '' ? 'Aberta' : 'Concluída'
            ));
            $orderId = $this->db->lastInsertId();
            $serviceLookup = $this->db->prepare('SELECT id, name FROM services WHERE id = ?');
            $insertService = $this->db->prepare('INSERT INTO work_order_services (work_order_id, service_id, service_name) VALUES (?, ?, ?)');
            foreach ($serviceIds as $serviceId) {
                $serviceLookup->execute(array($serviceId));
                $service = $serviceLookup->fetch();
                if ($service) {
                    $insertService->execute(array($orderId, $service['id'], $service['name']));
                }
            }
            $this->db->commit();
            return $orderId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function completeOrder($orderId)
    {
        $statement = $this->db->prepare("UPDATE work_orders SET status = 'Concluída', completed_at = ? WHERE id = ? AND status = 'Aberta'");
        $statement->execute(array(date('Y-m-d H:i:s'), $orderId));
        return $statement->rowCount() > 0;
    }
}