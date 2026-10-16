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
        $pages = array('ordens', 'clientes', 'motos', 'financeiro', 'backup', 'restaurar');
        $page = isset($_GET['page']) && in_array($_GET['page'], $pages, true) ? $_GET['page'] : 'ordens';
        $search = trim(isset($_GET['q']) ? $_GET['q'] : '');
        if ($page === 'backup' && isset($_GET['download'])) {
            $this->sendBackup();
        }
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-d');
        $rangeFrom = $this->validDateInput(isset($_GET['from']) ? $_GET['from'] : '', $defaultFrom);
        $rangeTo = $this->validDateInput(isset($_GET['to']) ? $_GET['to'] : '', $defaultTo);
        if ($rangeFrom > $rangeTo) {
            $error = 'A data inicial não pode ser posterior à data final.';
            $rangeFrom = $defaultFrom;
            $rangeTo = $defaultTo;
        }
        $pendingMotorcycle = isset($_SESSION['pending_motorcycle']) ? $_SESSION['pending_motorcycle'] : null;
        $clientLinkWizard = $page === 'clientes' && is_array($pendingMotorcycle);
        $motorcycleWizard = $page === 'motos' && isset($_GET['new']) && $_GET['new'] === 'order';

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
            'orders' => $this->model->orders(),
            'financialReport' => $page === 'financeiro' ? $this->model->financialReport($rangeFrom, $rangeTo) : array('summary' => array(), 'orders' => array()),
            'rangeFromDisplay' => date('d/m/Y', strtotime($rangeFrom)),
            'rangeToDisplay' => date('d/m/Y', strtotime($rangeTo)),
            'page' => $page,
            'search' => $search,
            'editing' => $clientLinkWizard ? null : $this->editingRecord($page),
            'pendingMotorcycle' => $pendingMotorcycle,
            'clientLinkWizard' => $clientLinkWizard,
            'newClientWizard' => $clientLinkWizard && isset($_GET['new']) && $_GET['new'] === '1',
            'motorcycleWizard' => $motorcycleWizard,
            'selectedMotorcycleId' => filter_var(isset($_GET['motorcycle_id']) ? $_GET['motorcycle_id'] : '', FILTER_VALIDATE_INT) ?: 0,
            'success' => $success,
            'error' => $error,
            'csrf' => csrfToken(),
            'todayForInput' => date('d/m/Y'),
            'currentTimeForInput' => date('H:i'),
            'pageTitles' => array('motos' => 'Motocicletas', 'clientes' => 'Clientes', 'financeiro' => 'Financeiro', 'ordens' => 'Ordens de serviço', 'backup' => 'Fazer backup', 'restaurar' => 'Restaurar backup')
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
        if ($action === 'restore_backup') {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['backup_file']['tmp_name'])) {
                throw new \InvalidArgumentException('Selecione um arquivo de backup válido.');
            }
            $extension = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, array('sqlite', 'sqlite3', 'db'), true)) {
                throw new \InvalidArgumentException('Selecione um arquivo .sqlite, .sqlite3 ou .db.');
            }
            $this->model->restoreBackup($_FILES['backup_file']['tmp_name']);
            $this->flashAndRedirect('Backup restaurado com sucesso.', 'ordens');
        }

        if ($action === 'add_client') {
            $name = requiredText(isset($_POST['name']) ? $_POST['name'] : '', 'nome');
            $phone = requiredText(isset($_POST['phone']) ? $_POST['phone'] : '', 'telefone');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            if (isset($_SESSION['pending_motorcycle']) && is_array($_SESSION['pending_motorcycle'])) {
                $motorcycleId = $this->model->addClientWithMotorcycle($name, $phone, $email, $_SESSION['pending_motorcycle']);
                unset($_SESSION['pending_motorcycle']);
                $this->flashAndRedirect('Cliente e moto cadastrados. Continue a abertura da OS.', 'ordens', '', array('motorcycle_id' => $motorcycleId));
            }
            $this->model->addClient($name, $phone, $email);
            $this->flashAndRedirect('Cliente cadastrado.', 'clientes');
        }
        if ($action === 'update_client') {
            $updated = $this->model->updateClient(
                $this->postId('id'),
                requiredText(isset($_POST['name']) ? $_POST['name'] : '', 'nome'),
                requiredText(isset($_POST['phone']) ? $_POST['phone'] : '', 'telefone'),
                trim(isset($_POST['email']) ? $_POST['email'] : '')
            );
            if (!$updated) {
                throw new \RuntimeException('Cliente não encontrado.');
            }
            $this->flashAndRedirect('Cliente atualizado.', 'clientes', $this->submittedSearch());
        }
        if ($action === 'delete_client') {
            if (!$this->model->deleteClient($this->postId('id'))) {
                throw new \RuntimeException('Cliente não encontrado.');
            }
            $this->flashAndRedirect('Cliente excluído.', 'clientes', $this->submittedSearch());
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
        if ($action === 'stage_motorcycle_for_order') {
            $_SESSION['pending_motorcycle'] = array(
                'brand' => requiredText(isset($_POST['brand']) ? $_POST['brand'] : '', 'marca'),
                'model' => requiredText(isset($_POST['model']) ? $_POST['model'] : '', 'modelo'),
                'year' => trim(isset($_POST['year']) ? $_POST['year'] : '') ?: null,
                'plate' => strtoupper(requiredText(isset($_POST['plate']) ? $_POST['plate'] : '', 'placa')),
                'mileage' => max(0, (int) (isset($_POST['mileage']) ? $_POST['mileage'] : 0))
            );
            $this->flashAndRedirect('Moto informada. Agora escolha ou cadastre o cliente.', 'clientes');
        }
        if ($action === 'link_pending_motorcycle') {
            if (!isset($_SESSION['pending_motorcycle']) || !is_array($_SESSION['pending_motorcycle'])) {
                throw new \RuntimeException('Não há moto aguardando vínculo.');
            }
            $motorcycleId = $this->savePendingMotorcycle($this->postId('client_id'));
            $this->flashAndRedirect('Moto vinculada ao cliente. Continue a abertura da OS.', 'ordens', '', array('motorcycle_id' => $motorcycleId));
        }
        if ($action === 'cancel_pending_motorcycle') {
            unset($_SESSION['pending_motorcycle']);
            $this->flashAndRedirect('Cadastro da moto cancelado.', 'ordens');
        }
        if ($action === 'update_motorcycle') {
            $clientId = filter_var(isset($_POST['client_id']) ? $_POST['client_id'] : '', FILTER_VALIDATE_INT);
            if (!$clientId) {
                throw new \InvalidArgumentException('Selecione um cliente cadastrado.');
            }
            $this->model->updateMotorcycle(
                $this->postId('id'),
                $clientId,
                requiredText(isset($_POST['brand']) ? $_POST['brand'] : '', 'marca'),
                requiredText(isset($_POST['model']) ? $_POST['model'] : '', 'modelo'),
                trim(isset($_POST['year']) ? $_POST['year'] : '') ?: null,
                strtoupper(requiredText(isset($_POST['plate']) ? $_POST['plate'] : '', 'placa')),
                max(0, (int) (isset($_POST['mileage']) ? $_POST['mileage'] : 0))
            );
            $this->flashAndRedirect('Motocicleta atualizada.', 'motos', $this->submittedSearch());
        }
        if ($action === 'delete_motorcycle') {
            if (!$this->model->deleteMotorcycle($this->postId('id'))) {
                throw new \RuntimeException('Motocicleta não encontrada.');
            }
            $this->flashAndRedirect('Motocicleta excluída.', 'motos', $this->submittedSearch());
        }
        if ($action === 'add_order') {
            $clientId = filter_var(isset($_POST['client_id']) ? $_POST['client_id'] : '', FILTER_VALIDATE_INT);
            $motorcycleId = filter_var(isset($_POST['motorcycle_id']) ? $_POST['motorcycle_id'] : '', FILTER_VALIDATE_INT);
            if (!$clientId || !$motorcycleId) {
                throw new \InvalidArgumentException('Selecione o cliente e a moto.');
            }
            $createdAt = validateBrazilianDateTime(isset($_POST['created_date']) ? $_POST['created_date'] : '', isset($_POST['created_time']) ? $_POST['created_time'] : '', 'criação da OS');
            $orderId = $this->model->addOrder(
                $clientId,
                $motorcycleId,
                $createdAt,
                trim(isset($_POST['services_description']) ? $_POST['services_description'] : ''),
                trim(isset($_POST['parts_description']) ? $_POST['parts_description'] : ''),
                moneyToCents(isset($_POST['parts_value']) ? $_POST['parts_value'] : '0'),
                moneyToCents(isset($_POST['labor_value']) ? $_POST['labor_value'] : '0')
            );
            $this->flashAndRedirect('Ordem de serviço #' . $orderId . ' cadastrada.', 'ordens');
        }
        if ($action === 'update_order') {
            $clientId = filter_var(isset($_POST['client_id']) ? $_POST['client_id'] : '', FILTER_VALIDATE_INT);
            $motorcycleId = filter_var(isset($_POST['motorcycle_id']) ? $_POST['motorcycle_id'] : '', FILTER_VALIDATE_INT);
            if (!$clientId || !$motorcycleId) {
                throw new \InvalidArgumentException('Selecione o cliente e a moto.');
            }
            $createdAt = validateBrazilianDateTime(isset($_POST['created_date']) ? $_POST['created_date'] : '', isset($_POST['created_time']) ? $_POST['created_time'] : '', 'criação da OS');
            $this->model->updateOrder(
                $this->postId('id'),
                $clientId,
                $motorcycleId,
                $createdAt,
                trim(isset($_POST['services_description']) ? $_POST['services_description'] : ''),
                trim(isset($_POST['parts_description']) ? $_POST['parts_description'] : ''),
                moneyToCents(isset($_POST['parts_value']) ? $_POST['parts_value'] : '0'),
                moneyToCents(isset($_POST['labor_value']) ? $_POST['labor_value'] : '0')
            );
            $this->flashAndRedirect('Ordem de serviço atualizada.', 'ordens', $this->submittedSearch());
        }
        if ($action === 'bill_order') {
            $billed = $this->model->billOrder($this->postId('order_id'));
            $message = $billed ? 'OS faturada e recebimento registrado.' : 'Somente uma OS aberta pode ser faturada.';
            $this->flashAndRedirect($message, 'ordens', $this->submittedSearch());
        }
        if ($action === 'cancel_order') {
            $cancelled = $this->model->cancelOrder($this->postId('order_id'));
            $message = $cancelled ? 'OS cancelada.' : 'Somente uma OS aberta pode ser cancelada.';
            $this->flashAndRedirect($message, 'ordens', $this->submittedSearch());
        }
        throw new \InvalidArgumentException('Ação não reconhecida.');
    }

    private function editingRecord($page)
    {
        $id = filter_var(isset($_GET['edit']) ? $_GET['edit'] : '', FILTER_VALIDATE_INT);
        if (!$id) {
            return null;
        }
        if ($page === 'clientes') {
            return $this->model->findClient($id);
        }
        if ($page === 'motos') {
            return $this->model->findMotorcycle($id);
        }
        if ($page === 'ordens') {
            return $this->model->findOrder($id);
        }
        return null;
    }

    private function validDateInput($value, $fallback)
    {
        if (!is_string($value)) {
            return $fallback;
        }
        $parts = explode('-', $value);
        if (count($parts) === 3 && ctype_digit($parts[0]) && ctype_digit($parts[1]) && ctype_digit($parts[2])) {
            $year = (int) $parts[0];
            $month = (int) $parts[1];
            $day = (int) $parts[2];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        $parts = explode('/', $value);
        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1]) || !ctype_digit($parts[2])) {
            return $fallback;
        }
        $day = (int) $parts[0];
        $month = (int) $parts[1];
        $year = (int) $parts[2];
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : $fallback;
    }

    private function postId($field)
    {
        $id = filter_var(isset($_POST[$field]) ? $_POST[$field] : '', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new \InvalidArgumentException('Registro inválido.');
        }
        return $id;
    }

    private function submittedSearch()
    {
        return trim(isset($_POST['return_q']) ? $_POST['return_q'] : '');
    }

    private function savePendingMotorcycle($clientId)
    {
        $motorcycle = $_SESSION['pending_motorcycle'];
        $motorcycleId = $this->model->addMotorcycle(
            $clientId,
            $motorcycle['brand'],
            $motorcycle['model'],
            $motorcycle['year'],
            $motorcycle['plate'],
            $motorcycle['mileage']
        );
        unset($_SESSION['pending_motorcycle']);
        return $motorcycleId;
    }

    private function sendBackup()
    {
        $backupPath = $this->model->createBackupSnapshot();
        $filename = 'oficina-' . date('Y-m-d-H-i-s') . '.sqlite';
        header('Content-Type: application/vnd.sqlite3');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($backupPath));
        header('Cache-Control: no-store');
        readfile($backupPath);
        unlink($backupPath);
        exit;
    }

    private function flashAndRedirect($message, $page, $search = '', $extra = array())
    {
        $_SESSION['success'] = $message;
        $query = array_merge(array('page' => $page), $extra);
        if ($search !== '') {
            $query['q'] = $search;
        }
        header('Location: /?' . http_build_query($query));
        exit;
    }
}