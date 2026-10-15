<?php
namespace App\Controllers;

use App\Models\WorkshopModel;

class WorkshopController
{
    private $model;

    public function __construct(WorkshopModel $model)
    {
        $this->model = $model;
    }

    public function run()
    {
        $error = '';
        $success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
        unset($_SESSION['success']);
        $pages = array('ordens', 'clientes', 'motos', 'servicos');
        $page = isset($_GET['page']) && in_array($_GET['page'], $pages, true) ? $_GET['page'] : 'ordens';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->handlePost();
            } catch (\Throwable $exception) {
                $error = $exception instanceof \PDOException && strpos($exception->getMessage(), 'UNIQUE constraint failed') !== false
                    ? 'Já existe uma moto cadastrada com essa placa.'
                    : $exception->getMessage();
            }
        }

        $data = array(
            'clients' => $this->model->clients(),
            'motorcycles' => $this->model->motorcycles(),
            'services' => $this->model->services(),
            'orders' => $this->model->orders(),
            'page' => $page,
            'success' => $success,
            'error' => $error,
            'csrf' => csrfToken(),
            'nowForInput' => date('Y-m-d\\TH:i'),
            'pageTitles' => array('ordens' => 'Ordens de serviço', 'clientes' => 'Clientes', 'motos' => 'Motocicletas', 'servicos' => 'Serviços')
        );
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/dashboard.php';
    }

    private function handlePost()
    {
        if (!isset($_POST['csrf_token']) || !hash_equals(csrfToken(), $_POST['csrf_token'])) {
            throw new \RuntimeException('Sua sessão expirou. Atualize a página e tente novamente.');
        }
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'add_client') {
            $this->model->addClient(
                requiredText(isset($_POST['name']) ? $_POST['name'] : '', 'nome'),
                requiredText(isset($_POST['phone']) ? $_POST['phone'] : '', 'telefone'),
                trim(isset($_POST['email']) ? $_POST['email'] : '')
            );
            $this->flashAndRedirect('Cliente cadastrado.', 'clientes');
        }
        if ($action === 'add_motorcycle') {
            $clientId = filter_var(isset($_POST['client_id']) ? $_POST['client_id'] : '', FILTER_VALIDATE_INT);
            if (!$clientId) {
                throw new \InvalidArgumentException('Selecione um cliente cadastrado.');
            }
            $this->model->addMotorcycle(
                $clientId,
                requiredText(isset($_POST['brand']) ? $_POST['brand'] : '', 'marca'),
                requiredText(isset($_POST['model']) ? $_POST['model'] : '', 'modelo'),
                trim(isset($_POST['year']) ? $_POST['year'] : '') ?: null,
                strtoupper(requiredText(isset($_POST['plate']) ? $_POST['plate'] : '', 'placa')),
                max(0, (int) (isset($_POST['mileage']) ? $_POST['mileage'] : 0))
            );
            $this->flashAndRedirect('Moto cadastrada.', 'motos');
        }
        if ($action === 'add_service') {
            $this->model->addService(
                requiredText(isset($_POST['name']) ? $_POST['name'] : '', 'nome do serviço'),
                trim(isset($_POST['description']) ? $_POST['description'] : ''),
                moneyToCents(isset($_POST['price']) ? $_POST['price'] : '0')
            );
            $this->flashAndRedirect('Serviço cadastrado.', 'servicos');
        }
        if ($action === 'add_order') {
            $clientId = filter_var(isset($_POST['client_id']) ? $_POST['client_id'] : '', FILTER_VALIDATE_INT);
            $motorcycleId = filter_var(isset($_POST['motorcycle_id']) ? $_POST['motorcycle_id'] : '', FILTER_VALIDATE_INT);
            if (!$clientId || !$motorcycleId) {
                throw new \InvalidArgumentException('Selecione o cliente e a moto.');
            }
            $createdAt = validateLocalDate(trim(isset($_POST['created_at']) ? $_POST['created_at'] : ''), 'criação da OS');
            $completedAt = trim(isset($_POST['completed_at']) ? $_POST['completed_at'] : '');
            if ($completedAt !== '') {
                $completedAt = validateLocalDate($completedAt, 'término da OS');
                if ($completedAt < $createdAt) {
                    throw new \InvalidArgumentException('A data de término não pode ser anterior à criação.');
                }
            }
            $serviceIds = isset($_POST['services']) && is_array($_POST['services']) ? array_values(array_unique(array_map('intval', $_POST['services']))) : array();
            $orderId = $this->model->addOrder(
                $clientId,
                $motorcycleId,
                $createdAt,
                $completedAt,
                trim(isset($_POST['parts_description']) ? $_POST['parts_description'] : ''),
                moneyToCents(isset($_POST['parts_value']) ? $_POST['parts_value'] : '0'),
                moneyToCents(isset($_POST['labor_value']) ? $_POST['labor_value'] : '0'),
                $serviceIds
            );
            $this->flashAndRedirect('Ordem de serviço #' . $orderId . ' cadastrada.', 'ordens');
        }
        if ($action === 'complete_order') {
            $orderId = filter_var(isset($_POST['order_id']) ? $_POST['order_id'] : '', FILTER_VALIDATE_INT);
            $message = $this->model->completeOrder($orderId) ? 'OS concluída.' : 'A OS não foi alterada.';
            $this->flashAndRedirect($message, 'ordens');
        }
        throw new \InvalidArgumentException('Ação não reconhecida.');
    }

    private function flashAndRedirect($message, $page)
    {
        $_SESSION['success'] = $message;
        header('Location: /?page=' . urlencode($page));
        exit;
    }
}