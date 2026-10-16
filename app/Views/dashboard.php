<?php
/** @var array $pageTitles */
/** @var string $page */
/** @var array $clients */
/** @var array $motorcycles */
/** @var array $parts */
/** @var array $orders */
/** @var array|false $printOrder */
/** @var array $financialReport */
/** @var array|null $editing */
/** @var string $success */
/** @var string $error */
/** @var string $csrf */
/** @var string $search */
/** @var string $rangeFromDisplay */
/** @var string $rangeToDisplay */
/** @var string $todayForInput */
/** @var string $currentTimeForInput */
/** @var array|null $pendingMotorcycle */
/** @var bool $clientLinkWizard */
/** @var bool $newClientWizard */
/** @var bool $motorcycleWizard */
/** @var int $selectedMotorcycleId */
/** @var string $rangeFrom */
/** @var string $rangeTo */
$title = $pageTitles[$page];
$activeMotorcycleId = $page === 'ordens' && $editing ? (int) $editing['motorcycle_id'] : (int) $selectedMotorcycleId;
$activeMotorcycle = null;
foreach ($motorcycles as $motorcycle) {
    if ((int) $motorcycle['id'] === $activeMotorcycleId) {
        $activeMotorcycle = $motorcycle;
        break;
    }
}
$activePartItems = $page === 'ordens' && $editing && isset($editing['part_items']) ? $editing['part_items'] : array();
$editingOrder = $page === 'ordens' && is_array($editing);
$partItemsForView = array();
$partsForPicker = array();
foreach ($activePartItems as $item) {
    $availableQuantity = 0;
    $catalogPriceCents = (int) $item['unit_price_cents'];
    foreach ($parts as $part) {
        if ((int) $part['id'] === (int) $item['part_id']) {
            $availableQuantity = (int) $part['stock_quantity'];
            $catalogPriceCents = (int) $part['sale_price_cents'];
            if ($editingOrder && (int) $editing['stock_deducted'] === 1) {
                $availableQuantity += (int) $item['quantity'];
            }
            break;
        }
    }
    $partItemsForView[] = array(
        'id' => (int) $item['part_id'],
        'name' => $item['part_name'],
        'quantity' => (int) $item['quantity'],
        'unit_price_cents' => (int) $item['unit_price_cents'],
        'catalog_price_cents' => $catalogPriceCents,
        'available_quantity' => $availableQuantity
    );
}
foreach ($parts as $part) {
    $availableQuantity = (int) $part['stock_quantity'];
    if ($editingOrder && (int) $editing['stock_deducted'] === 1) {
        foreach ($activePartItems as $item) {
            if ((int) $item['part_id'] === (int) $part['id']) {
                $availableQuantity += (int) $item['quantity'];
            }
        }
    }
    $partsForPicker[] = array(
        'id' => (int) $part['id'],
        'name' => $part['name'],
        'description' => $part['description'],
        'sale_price_cents' => (int) $part['sale_price_cents'],
        'available_quantity' => $availableQuantity
    );
}
$partItemsJson = json_encode($partItemsForView, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$legacyPartsDescription = $page === 'ordens' && $editing && !$activePartItems ? $editing['parts_description'] : '';
$legacyPartsCents = $page === 'ordens' && $editing && !$activePartItems ? (int) $editing['parts_cents'] : 0;
$partsLocked = $editingOrder && $editing['status'] === 'Cancelada';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?> | Oficina</title>
    <style>
        :root { --ink: #202725; --muted: #68716d; --paper: #f4f5f0; --white: #fff; --line: #dfe3dc; --green: #236b51; --green-dark: #174936; --pale: #e8efe9; --red: #a83d32; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font: 15px/1.5 "Segoe UI", Arial, sans-serif; }
        .shell { display: grid; grid-template-columns: 230px minmax(0, 1fr); min-height: 100vh; }
        aside { background: var(--green-dark); color: #fff; padding: 28px 18px; }
        .brand { margin: 0 0 34px; font-size: 20px; font-weight: 700; }
        .brand span { display: block; color: #a9c5b3; font-size: 11px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; }
        nav { display: grid; gap: 6px; }
        .nav-group { display: grid; gap: 5px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #ffffff30; }
        .nav-group-title { padding: 5px 12px 2px; color: #a9c5b3; font-size: 11px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; }
        nav .nav-group a { padding-left: 22px; }
        nav a { color: #dce8e0; padding: 11px 12px; text-decoration: none; border-radius: 4px; }
        nav a:hover, nav a.active { background: #2a6048; color: #fff; }
        main { min-width: 0; padding: 36px clamp(20px, 4vw, 56px); }
        .topline { margin-bottom: 28px; }
        h1 { margin: 0; font-size: 29px; line-height: 1.2; font-weight: 650; }
        .subtle { color: var(--muted); margin: 5px 0 0; }
        .layout { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(280px, .9fr); gap: 26px; align-items: start; }
        .section-title { margin: 0 0 13px; font-size: 16px; }
        .panel { background: var(--white); border: 1px solid var(--line); border-radius: 5px; padding: 20px; }
        .table-wrap { overflow-x: auto; }
        .datatable-controls, .datatable-footer { display: flex; align-items: end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .datatable-controls { margin-bottom: 14px; }
        .datatable-search { width: min(100%, 380px); }
        .datatable-length { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 13px; }
        .datatable-length select { width: auto; min-width: 72px; }
        .table-sort { display: inline-flex; align-items: center; gap: 6px; padding: 0; border: 0; background: transparent; color: inherit; font: inherit; font-weight: inherit; text-align: left; text-transform: inherit; cursor: pointer; }
        .table-sort:hover { color: var(--green-dark); }
        .table-sort-indicator { color: var(--green); }
        .datatable-footer { align-items: center; margin-top: 14px; color: var(--muted); font-size: 13px; }
        .datatable-pagination { display: flex; align-items: center; gap: 9px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 10px 12px; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        td { padding: 13px 12px; border-bottom: 1px solid #edf0eb; vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        .primary { font-weight: 650; }
        .small { color: var(--muted); font-size: 13px; }
        .badge { display: inline-block; background: var(--pale); color: var(--green-dark); border-radius: 3px; padding: 3px 8px; font-size: 12px; font-weight: 650; white-space: nowrap; }
        .badge.done { background: #f1e9dc; color: #865323; }
        .badge.cancelled { background: #f8e9e6; color: var(--red); }
        form { margin: 0; }
        .form-grid { display: grid; gap: 13px; }
        .search-form { display: flex; gap: 8px; margin-bottom: 16px; }
        .search-form input { min-width: 120px; }
        .date-range { display: flex; align-items: end; gap: 12px; flex-wrap: wrap; }
        .date-range label { min-width: 180px; }
        .utility-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .utility-select { max-width: 520px; }
      /*.select-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: end; } */
        .select-row {
                        display: flex;
                        align-items: center; /* Alinha todos os filhos verticalmente no centro */
                        gap: 15px;           /* Cria um espaçamento horizontal entre o campo e o botão */
                    }
        .autocomplete { position: relative; }
        .autocomplete-results { position: absolute; z-index: 5; top: calc(100% + 3px); left: 0; right: 0; max-height: 280px; overflow-y: auto; border: 1px solid var(--line); border-radius: 3px; background: var(--white); box-shadow: 0 8px 20px #20272524; }
        .autocomplete-results[hidden], .autocomplete-results button[hidden], .autocomplete-results p[hidden] { display: none; }
        .autocomplete-results button { display: block; width: 100%; padding: 9px 11px; border: 0; border-bottom: 1px solid #edf0eb; background: #fff; color: var(--ink); text-align: left; font: inherit; cursor: pointer; }
        .autocomplete-results button:last-of-type { border-bottom: 0; }
        .autocomplete-results button:hover, .autocomplete-results button.active { background: var(--pale); }
        .autocomplete-results small { display: block; color: var(--muted); font-size: 12px; }
        .autocomplete-results p { margin: 0; padding: 10px 11px; color: var(--muted); font-size: 13px; }
        .row-actions { display: flex; align-items: center; gap: 7px; }
        .text-button { border: 0; background: none; color: var(--green); padding: 4px; font: inherit; font-size: 13px; cursor: pointer; text-decoration: underline; }
        .delete-button { color: var(--red); }
        .cancel-link { color: var(--muted); font-size: 13px; }
        label { display: grid; gap: 5px; color: #46514b; font-size: 13px; font-weight: 600; }
        input, select, textarea { width: 100%; border: 1px solid #cfd6cf; border-radius: 3px; background: #fff; color: var(--ink); padding: 10px 11px; font: inherit; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: 2px solid #b5d1c1; border-color: var(--green); }
        textarea { min-height: 76px; resize: vertical; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 9px 14px; border: 0; border-radius: 3px; background: var(--green); color: #fff; font: inherit; font-weight: 650; cursor: pointer; text-decoration: none; }
        .button:hover { background: var(--green-dark); }
        /* .button.secondary { min-height: 32px; padding: 5px 9px; font-size: 12px; } */
        .button.secondary {
                                            margin: 0; /* Remove margens que possam puxar o botão para cima ou para baixo */
                                        }
        .notice { margin-bottom: 18px; border-radius: 3px; padding: 11px 14px; background: #e5f1e9; color: var(--green-dark); }
        .notice.error { background: #f8e9e6; color: var(--red); }
        .empty { padding: 28px 15px; color: var(--muted); text-align: center; }
        .form-hint { color: var(--muted); font-size: 12px; font-weight: 400; }
        .print-sheet { max-width: 900px; margin: 0 auto; padding: 30px; background: #fff; border: 1px solid var(--line); }
        .print-heading { display: flex; align-items: start; justify-content: space-between; gap: 20px; padding-bottom: 18px; border-bottom: 2px solid var(--ink); }
        .print-heading h2 { margin: 0; font-size: 23px; }
        .print-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; padding: 18px 0; }
        .print-meta h3, .print-section h3 { margin: 0 0 8px; font-size: 14px; }
        .print-meta p { margin: 3px 0; }
        .print-section { margin-top: 18px; }
        .print-total { margin-top: 18px; text-align: right; font-size: 17px; font-weight: 700; }
        .print-actions { display: flex; gap: 10px; margin-bottom: 16px; }
        .print-page .shell { display: block; min-height: auto; }
        .print-page aside, .print-page .topline { display: none; }
        .print-page main { padding: 20px; }
        @media print {
            body, .print-page { background: #fff; }
            .print-page aside, .print-page .topline, .no-print { display: none !important; }
            .print-page .shell { display: block; min-height: auto; }
            .print-page main { padding: 0; }
            .print-sheet { max-width: none; padding: 0; border: 0; }
            .print-sheet, .print-sheet * { color: #000 !important; }
            .print-sheet table { page-break-inside: auto; }
            .print-sheet tr { page-break-inside: avoid; }
        }
        @media (max-width: 850px) { .shell { grid-template-columns: 1fr; } aside { padding: 17px 18px; } .brand { margin: 0 0 12px; } nav { display: flex; overflow-x: auto; } nav a { white-space: nowrap; } main { padding: 25px 18px; } .layout { grid-template-columns: 1fr; } }
        @media (max-width: 520px) { h1 { font-size: 25px; } .panel { padding: 15px; } .two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="<?php echo $page === 'imprimir' ? 'print-page' : ''; ?>">
<div class="shell">
    <aside>
        <p class="brand">Oficina <span>MOTO RACING</span></p>
        <nav aria-label="Navegação principal">
            <?php foreach (array('motos', 'pecas', 'clientes', 'ordens') as $key): ?>
                <?php $pageTitle = $pageTitles[$key]; ?>
                <a class="<?php echo $page === $key ? 'active' : ''; ?>" href="/?page=<?php echo e($key); ?>"><?php echo e($pageTitle); ?></a>
            <?php endforeach; ?>
            <div class="nav-group">
                <span class="nav-group-title">Utilidades</span>
                <?php foreach (array('financeiro', 'backup', 'restaurar') as $key): ?>
                    <a class="<?php echo $page === $key ? 'active' : ''; ?>" href="/?page=<?php echo e($key); ?>"><?php echo e($pageTitles[$key]); ?></a>
                <?php endforeach; ?>
            </div>
        </nav>
    </aside>
    <main>
        <header class="topline"><h1><?php echo e($title); ?></h1><p class="subtle">Controle simples da rotina da oficina.</p></header>
        <?php if ($success !== ''): ?><div class="notice" role="status"><?php echo e($success); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="notice error" role="alert"><?php echo e($error); ?></div><?php endif; ?>

        <?php if ($page === 'clientes'): ?>
            <div class="layout">
                <section class="panel">
                    <h2 class="section-title">Clientes cadastrados</h2>
                    <div class="datatable-controls">
                        <label class="datatable-search">Pesquisar<input type="search" id="client-table-search" value="<?php echo e($search); ?>" placeholder="Nome, telefone ou e-mail"></label>
                        <label class="datatable-length">Mostrar<select id="client-page-size"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> linhas</label>
                    </div>
                    <?php if (!$clients): ?><div class="empty">Nenhum cliente cadastrado.</div><?php else: ?>
                        <div class="table-wrap"><table id="client-table"><thead><tr>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="0">Nome <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="1">Telefone <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="2">E-mail <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th><?php echo $clientLinkWizard ? 'Vincular moto' : 'Ações'; ?></th>
                        </tr></thead><tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr data-search="<?php echo e($client['name'] . ' ' . $client['phone'] . ' ' . $client['email']); ?>">
                                    <td class="primary"><?php echo e($client['name']); ?></td>
                                    <td><?php echo e($client['phone']); ?></td>
                                    <td><?php echo e($client['email'] ?: '—'); ?></td>
                                    <td><?php if ($clientLinkWizard): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="link_pending_motorcycle"><input type="hidden" name="client_id" value="<?php echo (int) $client['id']; ?>"><button class="button secondary" type="submit">Vincular</button></form><?php else: ?><div class="row-actions"><a class="text-button" href="/?page=clientes&amp;edit=<?php echo (int) $client['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir este cliente?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="delete_client"><input type="hidden" name="id" value="<?php echo (int) $client['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="text-button delete-button" type="submit">Excluir</button></form></div><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                    <div class="datatable-footer"><span id="client-table-info" aria-live="polite"></span><div class="datatable-pagination"><button class="button secondary" type="button" id="client-previous">Anterior</button><span id="client-page-info"></span><button class="button secondary" type="button" id="client-next">Próxima</button></div></div>
                    <div class="empty" id="client-no-matches" hidden>Nenhum cliente corresponde à pesquisa.</div>
                </section>
                <section class="panel">
                    <?php if ($clientLinkWizard): ?>
                        <?php if ($newClientWizard): ?>
                            <h2 class="section-title">Novo cliente para a moto</h2>
                            <p class="small"><?php echo e($pendingMotorcycle['brand'] . ' ' . $pendingMotorcycle['model'] . ' · ' . $pendingMotorcycle['plate']); ?></p>
                            <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="add_client">
                                <label>Nome completo<input name="name" required maxlength="150" autofocus></label>
                                <label>Telefone<input name="phone" required maxlength="40" placeholder="(11) 99999-9999"></label>
                                <label>E-mail <span class="form-hint">Opcional</span><input type="email" name="email" maxlength="150"></label>
                                <button class="button" type="submit">Cadastrar cliente e vincular moto</button>
                                <a class="cancel-link" href="/?page=clientes&amp;link_moto=1">Voltar para clientes existentes</a>
                            </form>
                        <?php else: ?>
                            <h2 class="section-title">Vincular moto a cliente</h2>
                            <p class="small"><?php echo e($pendingMotorcycle['brand'] . ' ' . $pendingMotorcycle['model'] . ' · ' . $pendingMotorcycle['plate']); ?></p>
                            <a class="button" href="/?page=clientes&amp;link_moto=1&amp;new=1">Novo cliente</a>
                            <form method="post" class="form-grid" style="margin-top: 16px"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="cancel_pending_motorcycle"><button class="text-button delete-button" type="submit">Cancelar cadastro da moto</button></form>
                        <?php endif; ?>
                    <?php else: ?>
                        <h2 class="section-title"><?php echo $editing ? 'Editar cliente' : 'Novo cliente'; ?></h2>
                        <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="<?php echo $editing ? 'update_client' : 'add_client'; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>">
                            <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>"><?php endif; ?>
                            <label>Nome completo<input name="name" required maxlength="150" value="<?php echo e($editing ? $editing['name'] : ''); ?>"></label>
                            <label>Telefone<input name="phone" required maxlength="40" placeholder="(11) 99999-9999" value="<?php echo e($editing ? $editing['phone'] : ''); ?>"></label>
                            <label>E-mail <span class="form-hint">Opcional</span><input type="email" name="email" maxlength="150" value="<?php echo e($editing ? $editing['email'] : ''); ?>"></label>
                            <button class="button" type="submit"><?php echo $editing ? 'Salvar alterações' : 'Cadastrar cliente'; ?></button>
                            <?php if ($editing): ?><a class="cancel-link" href="/?page=clientes&amp;q=<?php echo urlencode($search); ?>">Cancelar edição</a><?php endif; ?>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        <?php elseif ($page === 'motos'): ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Motocicletas cadastradas</h2>
                    <div class="datatable-controls">
                        <label class="datatable-search">Pesquisar<input type="search" id="motorcycle-table-search" value="<?php echo e($search); ?>" placeholder="Marca, modelo, placa ou cliente"></label>
                        <label class="datatable-length">Mostrar<select id="motorcycle-page-size"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> linhas</label>
                    </div>
                    <?php if (!$motorcycles): ?><div class="empty">Nenhuma moto cadastrada.</div><?php else: ?>
                        <div class="table-wrap"><table id="motorcycle-table"><thead><tr>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="0">Moto <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="1">Placa <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="2">Cliente <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="3">Ano / km <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th>Ações</th>
                        </tr></thead><tbody>
                            <?php foreach ($motorcycles as $motorcycle): ?>
                                <tr data-search="<?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model'] . ' ' . $motorcycle['plate'] . ' ' . $motorcycle['client_name'] . ' ' . $motorcycle['year'] . ' ' . $motorcycle['mileage']); ?>">
                                    <td class="primary"><?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?></td>
                                    <td><?php echo e($motorcycle['plate']); ?></td>
                                    <td><?php echo e($motorcycle['client_name']); ?></td>
                                    <td><?php echo e($motorcycle['year'] ?: '—'); ?> / <?php echo number_format((int) $motorcycle['mileage'], 0, ',', '.'); ?> km</td>
                                    <td><div class="row-actions"><a class="text-button" href="/?page=motos&amp;edit=<?php echo (int) $motorcycle['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir esta motocicleta?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="delete_motorcycle"><input type="hidden" name="id" value="<?php echo (int) $motorcycle['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="text-button delete-button" type="submit">Excluir</button></form></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                    <div class="datatable-footer"><span id="motorcycle-table-info" aria-live="polite"></span><div class="datatable-pagination"><button class="button secondary" type="button" id="motorcycle-previous">Anterior</button><span id="motorcycle-page-info"></span><button class="button secondary" type="button" id="motorcycle-next">Próxima</button></div></div>
                    <div class="empty" id="motorcycle-no-matches" hidden>Nenhuma moto corresponde à pesquisa.</div>
                </section>
                <section class="panel"><h2 class="section-title"><?php echo $motorcycleWizard ? 'Nova moto para a OS' : ($editing ? 'Editar motocicleta' : 'Nova motocicleta'); ?></h2>
                    <?php if ($motorcycleWizard): ?>
                        <p class="small">Informe a moto. Na próxima tela você poderá vinculá-la a um cliente existente ou cadastrar um novo.</p>
                        <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="stage_motorcycle_for_order">
                            <div class="two-col"><label>Marca<input name="brand" required maxlength="80" autofocus></label><label>Modelo<input name="model" required maxlength="100"></label></div>
                            <div class="two-col"><label>Placa<input name="plate" required maxlength="10" placeholder="ABC1D23"></label><label>Ano<input type="number" name="year" min="1950" max="2100"></label></div>
                            <label>Quilometragem<input type="number" name="mileage" min="0" value="0"></label>
                            <button class="button" type="submit">Salvar moto e escolher cliente</button>
                            <a class="cancel-link" href="/?page=ordens">Voltar para a OS</a>
                        </form>
                    <?php elseif (!$clients): ?><div class="empty">Cadastre um cliente antes de incluir uma moto.</div><?php else: ?>
                        <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="<?php echo $editing ? 'update_motorcycle' : 'add_motorcycle'; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>">
                            <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>"><?php endif; ?>
                            <label>Proprietário<select name="client_id" required><option value="">Selecione</option><?php foreach ($clients as $client): ?><option value="<?php echo (int) $client['id']; ?>" <?php echo $editing && (int) $editing['client_id'] === (int) $client['id'] ? 'selected' : ''; ?>><?php echo e($client['name']); ?></option><?php endforeach; ?></select></label>
                            <div class="two-col"><label>Marca<input name="brand" required maxlength="80" value="<?php echo e($editing ? $editing['brand'] : ''); ?>"></label><label>Modelo<input name="model" required maxlength="100" value="<?php echo e($editing ? $editing['model'] : ''); ?>"></label></div>
                            <div class="two-col"><label>Placa<input name="plate" required maxlength="10" placeholder="ABC1D23" value="<?php echo e($editing ? $editing['plate'] : ''); ?>"></label><label>Ano<input type="number" name="year" min="1950" max="2100" value="<?php echo e($editing ? $editing['year'] : ''); ?>"></label></div>
                            <label>Quilometragem<input type="number" name="mileage" min="0" value="<?php echo e($editing ? $editing['mileage'] : '0'); ?>"></label>
                            <button class="button" type="submit"><?php echo $editing ? 'Salvar alterações' : 'Cadastrar moto'; ?></button>
                            <?php if ($editing): ?><a class="cancel-link" href="/?page=motos&amp;q=<?php echo urlencode($search); ?>">Cancelar edição</a><?php endif; ?>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        <?php elseif ($page === 'pecas'): ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Peças cadastradas</h2>
                    <div class="datatable-controls">
                        <label class="datatable-search">Pesquisar<input type="search" id="part-table-search" value="<?php echo e($search); ?>" placeholder="Nome ou descrição"></label>
                        <label class="datatable-length">Mostrar<select id="part-page-size"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> linhas</label>
                    </div>
                    <?php if (!$parts): ?><div class="empty">Nenhuma peça cadastrada.</div><?php else: ?>
                        <div class="table-wrap"><table id="part-table"><thead><tr>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="0">Peça <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="1">Descrição <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="2">Estoque <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="3">Valor de venda <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th>Ações</th>
                        </tr></thead><tbody>
                            <?php foreach ($parts as $part): ?>
                                <tr data-search="<?php echo e($part['name'] . ' ' . $part['description']); ?>">
                                    <td class="primary" data-sort="<?php echo e($part['name']); ?>"><?php echo e($part['name']); ?></td>
                                    <td data-sort="<?php echo e($part['description']); ?>"><?php echo e($part['description'] ?: '—'); ?></td>
                                    <td data-sort="<?php echo (int) $part['stock_quantity']; ?>"><?php echo number_format((int) $part['stock_quantity'], 0, ',', '.'); ?></td>
                                    <td data-sort="<?php echo (int) $part['sale_price_cents']; ?>"><?php echo money($part['sale_price_cents']); ?></td>
                                    <td><div class="row-actions"><a class="text-button" href="/?page=pecas&amp;edit=<?php echo (int) $part['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir esta peça do estoque?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="delete_part"><input type="hidden" name="id" value="<?php echo (int) $part['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="text-button delete-button" type="submit">Excluir</button></form></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                    <div class="datatable-footer"><span id="part-table-info" aria-live="polite"></span><div class="datatable-pagination"><button class="button secondary" type="button" id="part-previous">Anterior</button><span id="part-page-info"></span><button class="button secondary" type="button" id="part-next">Próxima</button></div></div>
                    <div class="empty" id="part-no-matches" hidden>Nenhuma peça corresponde à pesquisa.</div>
                </section>
                <section class="panel"><h2 class="section-title"><?php echo $editing ? 'Editar peça' : 'Nova peça'; ?></h2>
                    <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="<?php echo $editing ? 'update_part' : 'add_part'; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>">
                        <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>"><?php endif; ?>
                        <label>Nome da peça<input name="name" required maxlength="150" value="<?php echo e($editing ? $editing['name'] : ''); ?>"></label>
                        <label>Descritivo<textarea name="description" maxlength="1000"><?php echo e($editing ? $editing['description'] : ''); ?></textarea></label>
                        <label>Quantidade em estoque<input type="number" name="stock_quantity" min="0" step="1" required value="<?php echo e($editing ? $editing['stock_quantity'] : '0'); ?>"></label>
                        <label>Valor de venda (R$)<input name="sale_price" inputmode="decimal" required value="<?php echo $editing ? e(number_format($editing['sale_price_cents'] / 100, 2, ',', '.')) : '0,00'; ?>"></label>
                        <button class="button" type="submit"><?php echo $editing ? 'Salvar alterações' : 'Cadastrar peça'; ?></button>
                        <?php if ($editing): ?><a class="cancel-link" href="/?page=pecas&amp;q=<?php echo urlencode($search); ?>">Cancelar edição</a><?php endif; ?>
                    </form>
                </section>
            </div>
        <?php elseif ($page === 'financeiro'): ?>
            <div class="stack">
                <section class="panel">
                    <h2 class="section-title">Período financeiro</h2>
                    <form method="get" class="date-range">
                        <input type="hidden" name="page" value="financeiro">
                        <label>Data de<input type="text" name="from" value="<?php echo e($rangeFromDisplay); ?>" inputmode="numeric" placeholder="dd/mm/aaaa" maxlength="10" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" required></label>
                        <label>Data até<input type="text" name="to" value="<?php echo e($rangeToDisplay); ?>" inputmode="numeric" placeholder="dd/mm/aaaa" maxlength="10" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" required></label>
                        <button class="button" type="submit">Filtrar</button>
                    </form>
                    <p class="form-hint">O período usa a data de criação para OS abertas, de faturamento para faturadas e de cancelamento para canceladas. Cancelamentos antigos sem data registrada usam a data de criação da OS.</p>
                </section>
                <section class="panel">
                    <h2 class="section-title">Resumo por status</h2>
                    <div class="table-wrap"><table><thead><tr><th>Status</th><th>Quantidade de OS</th><th>Valor total das OS</th></tr></thead><tbody>
                        <?php foreach ($financialReport['summary'] as $status => $totals): ?>
                            <tr><td><span class="badge <?php echo $status === 'Faturada' ? 'done' : ($status === 'Cancelada' ? 'cancelled' : ''); ?>"><?php echo e($status); ?></span></td><td><?php echo (int) $totals['count']; ?></td><td><?php echo money($totals['total_cents']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                </section>
                <section class="panel">
                    <h2 class="section-title">Ordens no período</h2>
                    <?php if (!$financialReport['orders']): ?><div class="empty">Nenhuma OS encontrada nesse período.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>OS</th><th>Cliente / moto</th><th>Status</th><th>Data do status</th><th>Valor</th></tr></thead><tbody>
                            <?php foreach ($financialReport['orders'] as $order): ?>
                                <tr>
                                    <td class="primary">#<?php echo (int) $order['id']; ?></td>
                                    <td><?php echo e($order['client_name']); ?><br><span class="small"><?php echo e($order['brand'] . ' ' . $order['model'] . ' · ' . $order['plate']); ?></span></td>
                                    <td><span class="badge <?php echo $order['status'] === 'Faturada' ? 'done' : ($order['status'] === 'Cancelada' ? 'cancelled' : ''); ?>"><?php echo e($order['status']); ?></span></td>
                                    <td><?php echo e(date('d/m/Y H:i', strtotime($order['status_date']))); ?></td>
                                    <td><?php echo money($order['total_cents']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>
            </div>
        <?php elseif ($page === 'backup'): ?>
            <div class="layout">
                <section class="panel">
                    <h2 class="section-title">Fazer backup</h2>
                    <p class="small">Escolha a pasta onde deseja guardar uma cópia completa do banco da oficina.</p>
                    <input type="hidden" id="backup-csrf" value="<?php echo e($csrf); ?>">
                    <button class="button" type="button" id="backup-folder-button">Escolher pasta e salvar backup</button>
                    <p class="form-hint" id="backup-feedback" role="status"></p>
                    <a class="button secondary" href="/?page=backup&amp;download=1">Baixar backup pelo navegador</a>
                </section>
            </div>
        <?php elseif ($page === 'restaurar'): ?>
            <div class="layout">
                <section class="panel">
                    <h2 class="section-title">Restaurar backup</h2>
                    <p class="small">Escolha a pasta de backups e selecione o arquivo SQLite que deseja restaurar.</p>
                    <input type="hidden" id="restore-csrf" value="<?php echo e($csrf); ?>">
                    <div class="form-grid">
                        <button class="button" type="button" id="restore-folder-button">Escolher pasta de backups</button>
                        <label for="restore-backup-select">Backup encontrado</label>
                        <select id="restore-backup-select" class="utility-select" disabled><option value="">Escolha uma pasta primeiro</option></select>
                        <button class="button delete-button" type="button" id="restore-backup-button" disabled>Restaurar backup selecionado</button>
                        <label class="cancel-link" for="restore-file-fallback">Ou escolha um arquivo SQLite</label>
                        <input type="file" id="restore-file-fallback" accept=".sqlite,.sqlite3,.db" hidden>
                        <p class="form-hint">A restauração substitui os dados atuais. A cópia será validada antes da troca.</p>
                        <p class="form-hint" id="restore-feedback" role="status"></p>
                    </div>
                </section>
            </div>
        <?php elseif ($page === 'imprimir'): ?>
            <?php if (!$printOrder): ?>
                <div class="notice error">Ordem de serviço não encontrada.</div>
                <a class="button no-print" href="/?page=ordens">Voltar para ordens de serviço</a>
            <?php else: ?>
                <div class="print-actions no-print">
                    <button class="button" type="button" onclick="window.print()">Imprimir OS</button>
                    <a class="button secondary" href="/?page=ordens">Voltar</a>
                </div>
                <article class="print-sheet">
                    <header class="print-heading">
                        <div>
                            <h2>Ordem de serviço #<?php echo (int) $printOrder['id']; ?></h2>
                            <p><span class="badge <?php echo $printOrder['status'] === 'Faturada' ? 'done' : ($printOrder['status'] === 'Cancelada' ? 'cancelled' : ''); ?>"><?php echo e($printOrder['status']); ?></span></p>
                        </div>
                        <div class="small">Emitida em <?php echo e(date('d/m/Y H:i')); ?></div>
                    </header>
                    <div class="print-meta">
                        <section>
                            <h3>Cliente</h3>
                            <p><?php echo e($printOrder['client_name']); ?></p>
                            <p>Telefone: <?php echo e($printOrder['client_phone'] ?: '—'); ?></p>
                            <p>E-mail: <?php echo e($printOrder['client_email'] ?: '—'); ?></p>
                        </section>
                        <section>
                            <h3>Motocicleta</h3>
                            <p><?php echo e($printOrder['brand'] . ' ' . $printOrder['model']); ?></p>
                            <p>Placa: <?php echo e($printOrder['plate']); ?> · Ano: <?php echo e($printOrder['year'] ?: '—'); ?></p>
                            <p>Quilometragem: <?php echo number_format((int) $printOrder['mileage'], 0, ',', '.'); ?> km</p>
                        </section>
                        <section>
                            <h3>Datas</h3>
                            <p>Criada: <?php echo e(date('d/m/Y H:i', strtotime($printOrder['created_at']))); ?></p>
                            <?php if ($printOrder['status'] === 'Faturada' && $printOrder['completed_at']): ?><p>Faturada: <?php echo e(date('d/m/Y H:i', strtotime($printOrder['completed_at']))); ?></p><?php endif; ?>
                            <?php if ($printOrder['status'] === 'Cancelada' && $printOrder['cancelled_at']): ?><p>Cancelada: <?php echo e(date('d/m/Y H:i', strtotime($printOrder['cancelled_at']))); ?></p><?php endif; ?>
                        </section>
                    </div>
                    <section class="print-section">
                        <h3>Serviços executados</h3>
                        <p><?php echo nl2br(e($printOrder['services_description'] ?: '—')); ?></p>
                    </section>
                    <section class="print-section">
                        <h3>Peças utilizadas</h3>
                        <?php if ($printOrder['part_items']): ?>
                            <div class="table-wrap"><table><thead><tr><th>Peça</th><th>Quantidade</th><th>Valor unitário</th><th>Subtotal</th></tr></thead><tbody>
                                <?php foreach ($printOrder['part_items'] as $partItem): ?>
                                    <tr><td><?php echo e($partItem['part_name']); ?></td><td><?php echo (int) $partItem['quantity']; ?></td><td><?php echo money($partItem['unit_price_cents']); ?></td><td><?php echo money($partItem['line_total_cents']); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                        <?php else: ?>
                            <p><?php echo nl2br(e($printOrder['parts_description'] ?: '—')); ?></p>
                        <?php endif; ?>
                    </section>
                    <section class="print-total">
                        <p>Peças: <?php echo money($printOrder['parts_cents']); ?></p>
                        <p>Mão de obra: <?php echo money($printOrder['labor_cents']); ?></p>
                        <p>Total da OS: <?php echo money($printOrder['total_cents']); ?></p>
                    </section>
                </article>
            <?php endif; ?>
        <?php else: ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Histórico de ordens</h2>
                    <div class="datatable-controls">
                        <label class="datatable-search">Pesquisar<input type="search" id="order-table-search" value="<?php echo e($search); ?>" placeholder="OS, cliente, moto, serviço, peça ou status"></label>
                        <label class="datatable-length">Mostrar<select id="order-page-size"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> linhas</label>
                    </div>
                    <?php if (!$orders): ?><div class="empty"><?php echo $search !== '' ? 'Nenhuma OS corresponde à busca.' : 'Nenhuma OS cadastrada. Crie a primeira ao lado.'; ?></div><?php else: ?>
                        <div class="table-wrap"><table id="order-table"><thead><tr>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="0">OS <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="1">Cliente / moto <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="2">Serviços / peças <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="3">Valores <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th aria-sort="none"><button type="button" class="table-sort" data-column="4">Status / datas <span class="table-sort-indicator" aria-hidden="true"></span></button></th>
                            <th>Ações</th>
                        </tr></thead><tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr data-search="<?php echo e($order['id'] . ' ' . $order['client_name'] . ' ' . $order['brand'] . ' ' . $order['model'] . ' ' . $order['plate'] . ' ' . $order['services_description'] . ' ' . $order['parts_description'] . ' ' . $order['status'] . ' ' . $order['created_at'] . ' ' . $order['completed_at'] . ' ' . $order['cancelled_at']); ?>">
                                    <td class="primary" data-sort="<?php echo (int) $order['id']; ?>">#<?php echo (int) $order['id']; ?></td>
                                    <td data-sort="<?php echo e($order['client_name'] . ' ' . $order['brand'] . ' ' . $order['model'] . ' ' . $order['plate']); ?>"><span class="primary"><?php echo e($order['client_name']); ?></span><br><span class="small"><?php echo e($order['brand'] . ' ' . $order['model'] . ' · ' . $order['plate']); ?></span></td>
                                    <td data-sort="<?php echo e($order['services_description'] . ' ' . $order['parts_description']); ?>"><?php echo e($order['services_description'] ?: 'Sem serviços descritos'); ?><br><span class="small"><?php echo e($order['parts_description'] ?: 'Sem peças descritas'); ?></span></td>
                                    <td data-sort="<?php echo (int) $order['total_cents']; ?>"><span class="small">Peças <?php echo money($order['parts_cents']); ?><br>Mão de obra <?php echo money($order['labor_cents']); ?></span><br><span class="primary"><?php echo money($order['total_cents']); ?></span></td>
                                    <td data-sort="<?php echo e($order['status'] . ' ' . ($order['completed_at'] ?: $order['cancelled_at'] ?: $order['created_at'])); ?>"><span class="badge <?php echo $order['status'] === 'Faturada' ? 'done' : ($order['status'] === 'Cancelada' ? 'cancelled' : ''); ?>"><?php echo e($order['status']); ?></span><br><span class="small">Criada <?php echo e(date('d/m/Y H:i', strtotime($order['created_at']))); ?><br><?php if ($order['status'] === 'Faturada' && $order['completed_at']): ?>Faturada <?php echo e(date('d/m/Y H:i', strtotime($order['completed_at']))); ?><?php elseif ($order['status'] === 'Cancelada' && $order['cancelled_at']): ?>Cancelada <?php echo e(date('d/m/Y H:i', strtotime($order['cancelled_at']))); ?><?php else: ?>Sem término<?php endif; ?></span></td>
                                    <td><div class="row-actions"><a class="text-button" href="/?page=imprimir&amp;id=<?php echo (int) $order['id']; ?>" target="_blank" rel="noopener">Imprimir</a><a class="text-button" href="/?page=ordens&amp;edit=<?php echo (int) $order['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><?php if ($order['status'] === 'Aberta'): ?><form method="post" onsubmit="return confirm('Confirmar que o pagamento da OS #<?php echo (int) $order['id']; ?> foi recebido?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="bill_order"><input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="button secondary" type="submit">Faturar</button></form><form method="post" onsubmit="return confirm('Cancelar a OS #<?php echo (int) $order['id']; ?>?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="cancel_order"><input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="button secondary delete-button" type="submit">Cancelar</button></form><?php endif; ?></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                    <div class="datatable-footer"><span id="order-table-info" aria-live="polite"></span><div class="datatable-pagination"><button class="button secondary" type="button" id="order-previous">Anterior</button><span id="order-page-info"></span><button class="button secondary" type="button" id="order-next">Próxima</button></div></div>
                    <div class="empty" id="order-no-matches" hidden>Nenhuma OS corresponde à pesquisa.</div>
                </section>
                <section class="panel"><h2 class="section-title"><?php echo $editing ? 'Editar OS #' . (int) $editing['id'] : 'Nova ordem de serviço'; ?></h2>
                    <?php if (!$motorcycles): ?><div class="form-hint">Ainda não há motos cadastradas. Use Novo para cadastrar a moto desta OS.</div><?php endif; ?>
                        <form method="post" id="order-form" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="<?php echo $editing ? 'update_order' : 'add_order'; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>">
                            <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>"><div class="form-hint">Status e data de faturamento/cancelamento são registrados pelos botões correspondentes.</div><?php endif; ?>
                            <input type="hidden" name="client_id" id="client-id" value="<?php echo $activeMotorcycle ? (int) $activeMotorcycle['client_id'] : ''; ?>">
                            <div class="select-row">
                                <div>
                                    <label for="motorcycle-search">Moto</label>
                                    <div class="autocomplete">
                                        <input type="search" id="motorcycle-search" placeholder="Digite placa, modelo ou cliente" autocomplete="off" required aria-required="true" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="motorcycle-results" value="<?php echo $activeMotorcycle ? e($activeMotorcycle['brand'] . ' ' . $activeMotorcycle['model'] . ' · ' . $activeMotorcycle['plate']) : ''; ?>">
                                        <input type="hidden" name="motorcycle_id" id="motorcycle-id" value="<?php echo $activeMotorcycle ? (int) $activeMotorcycle['id'] : ''; ?>">
                                        <div class="autocomplete-results" id="motorcycle-results" role="listbox" hidden>
                                            <?php foreach ($motorcycles as $motorcycle): ?>
                                                <button type="button" role="option" aria-selected="false" id="motorcycle-option-<?php echo (int) $motorcycle['id']; ?>" data-id="<?php echo (int) $motorcycle['id']; ?>" data-client="<?php echo (int) $motorcycle['client_id']; ?>" data-client-name="<?php echo e($motorcycle['client_name']); ?>" data-label="<?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model'] . ' · ' . $motorcycle['plate']); ?>" data-search="<?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model'] . ' ' . $motorcycle['plate'] . ' ' . $motorcycle['year'] . ' ' . $motorcycle['client_name']); ?>">
                                                    <?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model'] . ' · ' . $motorcycle['plate']); ?><small><?php echo e($motorcycle['client_name']); ?></small>
                                                </button>
                                            <?php endforeach; ?>
                                            <p id="motorcycle-no-results" hidden>Nenhuma moto encontrada.</p>
                                        </div>
                                    </div>
                                    <span class="form-hint" id="motorcycle-owner"><?php echo $activeMotorcycle ? 'Cliente: ' . e($activeMotorcycle['client_name']) : 'Selecione uma moto para carregar o cliente.'; ?></span>
                                </div>
                                <a class="button secondary" href="/?page=motos&amp;new=order">Novo</a>
                            </div>
                            <div class="two-col"><label>Data de criação<input type="text" name="created_date" value="<?php echo e($editing ? date('d/m/Y', strtotime($editing['created_at'])) : $todayForInput); ?>" inputmode="numeric" placeholder="dd/mm/aaaa" maxlength="10" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" required></label><label>Hora de criação<input type="time" name="created_time" value="<?php echo e($editing ? date('H:i', strtotime($editing['created_at'])) : $currentTimeForInput); ?>" required></label></div>
                            <label>Serviços executados<textarea name="services_description" maxlength="2000" placeholder="Descreva os serviços realizados"><?php echo e($editing ? $editing['services_description'] : ''); ?></textarea></label>
                            <?php if ($legacyPartsDescription !== ''): ?><p class="form-hint">Peças anotadas antes do controle de estoque: <?php echo e($legacyPartsDescription); ?>. Esse valor histórico será preservado.</p><?php endif; ?>
                            <div id="parts-picker" data-locked="<?php echo $partsLocked ? '1' : '0'; ?>" data-legacy-cents="<?php echo $legacyPartsCents; ?>">
                                <input type="hidden" name="part_items" id="part-items" value="<?php echo e($partItemsJson ?: '[]'); ?>">
                                <div class="select-row">
                                    <div>
                                        <label for="part-search">Pesquisar peça</label>
                                        <div class="autocomplete">
                                            <input type="search" id="part-search" placeholder="Digite o nome ou descrição" autocomplete="off" <?php echo $partsLocked ? 'disabled' : ''; ?> role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="part-results">
                                            <input type="hidden" id="part-selected-id">
                                            <div class="autocomplete-results" id="part-results" role="listbox" hidden>
                                                <?php foreach ($partsForPicker as $part): ?>
                                                    <button type="button" role="option" aria-selected="false" id="part-option-<?php echo (int) $part['id']; ?>" data-id="<?php echo (int) $part['id']; ?>" data-name="<?php echo e($part['name']); ?>" data-description="<?php echo e($part['description']); ?>" data-price="<?php echo (int) $part['sale_price_cents']; ?>" data-stock="<?php echo (int) $part['available_quantity']; ?>" data-search="<?php echo e($part['name'] . ' ' . $part['description']); ?>">
                                                        <?php echo e($part['name']); ?><small>Estoque: <?php echo (int) $part['available_quantity']; ?> · <?php echo money($part['sale_price_cents']); ?></small>
                                                    </button>
                                                <?php endforeach; ?>
                                                <p id="part-no-results" hidden>Nenhuma peça encontrada.</p>
                                            </div>
                                        </div>
                                        <span class="form-hint" id="part-selection-hint"><?php echo $partsLocked ? 'OS cancelada: peças registradas somente para consulta.' : 'Selecione uma peça para incluir na OS.'; ?></span>
                                    </div>
                                    <a class="button secondary" href="/?page=pecas">Cadastrar peça</a>
                                </div>
                                <div class="two-col">
                                    <label>Quantidade<input type="number" id="part-quantity" min="1" step="1" value="1" <?php echo $partsLocked ? 'disabled' : ''; ?>></label>
                                    <button class="button secondary" type="button" id="part-add-button" disabled <?php echo $partsLocked ? 'disabled' : ''; ?>>Adicionar peça</button>
                                </div>
                                <div class="table-wrap">
                                    <table id="selected-parts-table"><thead><tr><th>Peça</th><th>Quantidade</th><th>Valor unitário</th><th>Subtotal</th><th></th></tr></thead><tbody id="selected-parts-body"></tbody></table>
                                </div>
                                <div class="empty" id="selected-parts-empty" hidden>Nenhuma peça adicionada.</div>
                            </div>
                            <?php if ($partsLocked): ?><p class="form-hint">A edição de uma OS cancelada não movimenta o estoque.</p><?php endif; ?>
                            <div class="two-col"><label>Valor das peças (R$)<input type="text" id="parts-value" name="parts_value" value="<?php echo $editing ? e(number_format($editing['parts_cents'] / 100, 2, ',', '.')) : '0,00'; ?>" readonly></label><label>Mão de obra (R$)<input name="labor_value" inputmode="decimal" value="<?php echo $editing ? e(number_format($editing['labor_cents'] / 100, 2, ',', '.')) : '0,00'; ?>" required></label></div>
                            <p class="form-hint">O total é calculado automaticamente: peças + mão de obra.</p>
                            <button class="button" type="submit"><?php echo $editing ? 'Salvar OS' : 'Criar ordem de serviço'; ?></button>
                            <?php if ($editing): ?><a class="cancel-link" href="/?page=ordens&amp;q=<?php echo urlencode($search); ?>">Cancelar edição</a><?php endif; ?>
                        </form>
                </section>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>
(function () {
    var motorcycleSearch = document.getElementById('motorcycle-search');
    var motorcycleId = document.getElementById('motorcycle-id');
    var clientId = document.getElementById('client-id');
    var owner = document.getElementById('motorcycle-owner');
    var results = document.getElementById('motorcycle-results');
    var noResults = document.getElementById('motorcycle-no-results');
    var orderForm = document.getElementById('order-form');
    if (!motorcycleSearch || !motorcycleId || !clientId || !owner || !results || !orderForm) return;
    var options = Array.prototype.slice.call(results.querySelectorAll('[role="option"]'));
    var visibleOptions = [];
    var activeIndex = -1;
    var closeResults = function () {
        results.hidden = true;
        motorcycleSearch.setAttribute('aria-expanded', 'false');
        motorcycleSearch.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    };
    var showResults = function () {
        var query = motorcycleSearch.value.trim().toLocaleLowerCase();
        visibleOptions = options.filter(function (option) {
            return option.getAttribute('data-search').toLocaleLowerCase().indexOf(query) !== -1;
        }).slice(0, 10);
        options.forEach(function (option) {
            option.hidden = visibleOptions.indexOf(option) === -1;
            option.classList.remove('active');
            option.setAttribute('aria-selected', option.getAttribute('data-id') === motorcycleId.value ? 'true' : 'false');
        });
        noResults.hidden = visibleOptions.length > 0;
        results.hidden = false;
        motorcycleSearch.setAttribute('aria-expanded', 'true');
        motorcycleSearch.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    };
    var selectOption = function (option) {
        motorcycleId.value = option.getAttribute('data-id');
        clientId.value = option.getAttribute('data-client');
        motorcycleSearch.value = option.getAttribute('data-label');
        owner.textContent = 'Cliente: ' + option.getAttribute('data-client-name');
        options.forEach(function (item) {
            item.setAttribute('aria-selected', item === option ? 'true' : 'false');
        });
        closeResults();
    };
    var setActiveOption = function (index) {
        if (!visibleOptions.length) return;
        activeIndex = (index + visibleOptions.length) % visibleOptions.length;
        options.forEach(function (option) { option.classList.remove('active'); });
        visibleOptions[activeIndex].classList.add('active');
        motorcycleSearch.setAttribute('aria-activedescendant', visibleOptions[activeIndex].id);
    };
    motorcycleSearch.addEventListener('focus', showResults);
    motorcycleSearch.addEventListener('input', function () {
        motorcycleId.value = '';
        clientId.value = '';
        motorcycleSearch.setCustomValidity('');
        owner.textContent = 'Selecione uma moto para carregar o cliente.';
        showResults();
    });
    motorcycleSearch.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (results.hidden) showResults();
            setActiveOption(activeIndex < 0 ? 0 : activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveOption(activeIndex < 0 ? visibleOptions.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter' && !results.hidden && activeIndex >= 0) {
            event.preventDefault();
            selectOption(visibleOptions[activeIndex]);
        } else if (event.key === 'Escape') {
            closeResults();
        }
    });
    options.forEach(function (option) {
        option.addEventListener('mousedown', function (event) { event.preventDefault(); });
        option.addEventListener('click', function () { selectOption(option); });
    });
    document.addEventListener('click', function (event) {
        if (!results.contains(event.target) && event.target !== motorcycleSearch) closeResults();
    });
    orderForm.addEventListener('submit', function (event) {
        motorcycleSearch.setCustomValidity(motorcycleId.value ? '' : 'Selecione uma moto na lista ou use Novo para cadastrá-la.');
        if (!motorcycleId.value) {
            event.preventDefault();
            motorcycleSearch.reportValidity();
        }
    });
}());
</script>
<script>
(function () {
    var picker = document.getElementById('parts-picker');
    var search = document.getElementById('part-search');
    var selectedId = document.getElementById('part-selected-id');
    var results = document.getElementById('part-results');
    var noResults = document.getElementById('part-no-results');
    var quantityInput = document.getElementById('part-quantity');
    var addButton = document.getElementById('part-add-button');
    var itemsInput = document.getElementById('part-items');
    var rowsBody = document.getElementById('selected-parts-body');
    var emptyMessage = document.getElementById('selected-parts-empty');
    var totalInput = document.getElementById('parts-value');
    if (!picker || !search || !selectedId || !results || !noResults || !quantityInput || !addButton || !itemsInput || !rowsBody || !emptyMessage || !totalInput) return;

    var locked = picker.getAttribute('data-locked') === '1';
    var options = Array.prototype.slice.call(results.querySelectorAll('[role="option"]'));
    var items = [];
    try { items = JSON.parse(itemsInput.value || '[]'); } catch (error) { items = []; }
    var selectedPart = null;
    var visibleOptions = [];
    var activeIndex = -1;

    var formatMoney = function (cents) {
        return 'R$ ' + (cents / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    var closeResults = function () {
        results.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    };
    var renderResults = function () {
        var query = search.value.trim().toLocaleLowerCase();
        visibleOptions = options.filter(function (option) {
            return option.getAttribute('data-search').toLocaleLowerCase().indexOf(query) !== -1;
        }).slice(0, 10);
        options.forEach(function (option) {
            option.hidden = visibleOptions.indexOf(option) === -1;
            option.classList.remove('active');
            option.setAttribute('aria-selected', option.getAttribute('data-id') === selectedId.value ? 'true' : 'false');
        });
        noResults.hidden = visibleOptions.length > 0;
        results.hidden = false;
        search.setAttribute('aria-expanded', 'true');
        search.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    };
    var renderItems = function () {
        rowsBody.innerHTML = '';
        var total = parseInt(picker.getAttribute('data-legacy-cents'), 10) || 0;
        items.forEach(function (item, index) {
            var quantity = parseInt(item.quantity, 10) || 0;
            var unitPrice = parseInt(item.unit_price_cents, 10) || 0;
            var lineTotal = quantity * unitPrice;
            total += lineTotal;
            var row = document.createElement('tr');
            [item.name, String(quantity), formatMoney(unitPrice), formatMoney(lineTotal)].forEach(function (value) {
                var cell = document.createElement('td');
                cell.textContent = value;
                row.appendChild(cell);
            });
            var actionCell = document.createElement('td');
            if (!locked) {
                var removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-button delete-button';
                removeButton.textContent = 'Remover';
                removeButton.addEventListener('click', function () {
                    items.splice(index, 1);
                    renderItems();
                });
                actionCell.appendChild(removeButton);
            }
            row.appendChild(actionCell);
            rowsBody.appendChild(row);
        });
        itemsInput.value = JSON.stringify(items.map(function (item) { return { id: item.id, quantity: item.quantity }; }));
        totalInput.value = (total / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        emptyMessage.hidden = items.length > 0;
    };
    var selectOption = function (option) {
        selectedPart = {
            id: parseInt(option.getAttribute('data-id'), 10),
            name: option.getAttribute('data-name'),
            salePrice: parseInt(option.getAttribute('data-price'), 10),
            available: parseInt(option.getAttribute('data-stock'), 10)
        };
        selectedId.value = String(selectedPart.id);
        search.value = selectedPart.name;
        var existing = items.find(function (item) { return parseInt(item.id, 10) === selectedPart.id; });
        var alreadySelected = existing ? parseInt(existing.quantity, 10) : 0;
        var remaining = Math.max(0, selectedPart.available - alreadySelected);
        quantityInput.value = remaining > 0 ? '1' : '0';
        quantityInput.max = String(remaining);
        addButton.disabled = locked || remaining === 0;
        var hint = document.getElementById('part-selection-hint');
        if (hint) hint.textContent = 'Estoque disponível para esta OS: ' + remaining + ' · ' + formatMoney(selectedPart.salePrice);
        options.forEach(function (item) { item.setAttribute('aria-selected', item === option ? 'true' : 'false'); });
        closeResults();
    };
    var setActiveOption = function (index) {
        if (!visibleOptions.length) return;
        activeIndex = (index + visibleOptions.length) % visibleOptions.length;
        options.forEach(function (option) { option.classList.remove('active'); });
        visibleOptions[activeIndex].classList.add('active');
        search.setAttribute('aria-activedescendant', visibleOptions[activeIndex].id);
    };

    search.addEventListener('focus', renderResults);
    search.addEventListener('input', function () {
        selectedPart = null;
        selectedId.value = '';
        addButton.disabled = true;
        renderResults();
    });
    search.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (results.hidden) renderResults();
            setActiveOption(activeIndex < 0 ? 0 : activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveOption(activeIndex < 0 ? visibleOptions.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter' && !results.hidden && activeIndex >= 0) {
            event.preventDefault();
            selectOption(visibleOptions[activeIndex]);
        } else if (event.key === 'Escape') {
            closeResults();
        }
    });
    options.forEach(function (option) {
        option.addEventListener('mousedown', function (event) { event.preventDefault(); });
        option.addEventListener('click', function () { selectOption(option); });
    });
    document.addEventListener('click', function (event) {
        if (!results.contains(event.target) && event.target !== search) closeResults();
    });
    addButton.addEventListener('click', function () {
        if (!selectedPart) return;
        var quantity = parseInt(quantityInput.value, 10) || 0;
        var existing = items.find(function (item) { return parseInt(item.id, 10) === selectedPart.id; });
        var nextQuantity = quantity + (existing ? parseInt(existing.quantity, 10) : 0);
        if (quantity < 1 || nextQuantity > selectedPart.available) {
            var hint = document.getElementById('part-selection-hint');
            if (hint) hint.textContent = 'Quantidade indisponível. Estoque máximo: ' + selectedPart.available + '.';
            return;
        }
        if (existing) {
            existing.quantity = nextQuantity;
            existing.unit_price_cents = selectedPart.salePrice;
        } else {
            items.push({ id: selectedPart.id, name: selectedPart.name, quantity: quantity, unit_price_cents: selectedPart.salePrice, catalog_price_cents: selectedPart.salePrice, available_quantity: selectedPart.available });
        }
        renderItems();
        search.value = '';
        selectedId.value = '';
        selectedPart = null;
        quantityInput.value = '1';
        quantityInput.removeAttribute('max');
        addButton.disabled = true;
        var hint = document.getElementById('part-selection-hint');
        if (hint) hint.textContent = 'Selecione uma peça para incluir na OS.';
        closeResults();
    });
    renderItems();
}());
</script>
<script>
(function () {
    var setupDataTable = function (prefix, rowLabel, emptyLabel) {
        var table = document.getElementById(prefix + '-table');
        var searchInput = document.getElementById(prefix + '-table-search');
        var pageSizeSelect = document.getElementById(prefix + '-page-size');
        var info = document.getElementById(prefix + '-table-info');
        var pageInfo = document.getElementById(prefix + '-page-info');
        var previousButton = document.getElementById(prefix + '-previous');
        var nextButton = document.getElementById(prefix + '-next');
        var noMatches = document.getElementById(prefix + '-no-matches');
        if (!searchInput || !pageSizeSelect || !info || !pageInfo || !previousButton || !nextButton || !noMatches) return;
        if (!table) {
            info.textContent = emptyLabel;
            pageInfo.textContent = 'Página 0 de 0';
            previousButton.disabled = true;
            nextButton.disabled = true;
            return;
        }

        var tableWrap = table.closest('.table-wrap');
        var rows = Array.prototype.slice.call(table.tBodies[0].rows);
        var sortColumn = -1;
        var sortDirection = 1;
        var currentPage = 0;
        var renderTable = function () {
            var query = searchInput.value.trim().toLocaleLowerCase();
            var matchingRows = rows.filter(function (row) {
                return (row.getAttribute('data-search') || '').toLocaleLowerCase().indexOf(query) !== -1;
            });
            if (sortColumn >= 0) {
                matchingRows.sort(function (left, right) {
                    var leftText = left.cells[sortColumn].textContent.trim();
                    var rightText = right.cells[sortColumn].textContent.trim();
                    return leftText.localeCompare(rightText, 'pt-BR', { numeric: true, sensitivity: 'base' }) * sortDirection;
                });
            }

            var pageSize = parseInt(pageSizeSelect.value, 10) || 10;
            var pageCount = Math.ceil(matchingRows.length / pageSize);
            currentPage = Math.min(currentPage, Math.max(0, pageCount - 1));
            var first = currentPage * pageSize;
            var visibleRows = matchingRows.slice(first, first + pageSize);
            matchingRows.forEach(function (row) { table.tBodies[0].appendChild(row); });
            rows.forEach(function (row) { row.hidden = visibleRows.indexOf(row) === -1; });

            tableWrap.hidden = matchingRows.length === 0;
            noMatches.hidden = matchingRows.length !== 0;
            info.textContent = matchingRows.length
                ? 'Mostrando ' + (first + 1) + ' a ' + (first + visibleRows.length) + ' de ' + matchingRows.length + ' ' + rowLabel
                : 'Nenhum registro encontrado';
            pageInfo.textContent = pageCount ? 'Página ' + (currentPage + 1) + ' de ' + pageCount : 'Página 0 de 0';
            previousButton.disabled = currentPage === 0 || pageCount === 0;
            nextButton.disabled = currentPage >= pageCount - 1 || pageCount === 0;

            rows.forEach(function (row) {
                var editLink = row.querySelector('a.text-button');
                var returnSearch = row.querySelector('input[name="return_q"]');
                if (editLink) {
                    var editPath = editLink.href.split('&q=')[0];
                    editLink.href = query ? editPath + '&q=' + encodeURIComponent(searchInput.value.trim()) : editPath;
                }
                if (returnSearch) returnSearch.value = searchInput.value.trim();
            });
        };

        table.querySelectorAll('.table-sort').forEach(function (button) {
            button.addEventListener('click', function () {
                var column = parseInt(button.getAttribute('data-column'), 10);
                sortDirection = sortColumn === column ? sortDirection * -1 : 1;
                sortColumn = column;
                table.querySelectorAll('.table-sort').forEach(function (otherButton) {
                    var active = otherButton === button;
                    otherButton.parentElement.setAttribute('aria-sort', active ? (sortDirection === 1 ? 'ascending' : 'descending') : 'none');
                    otherButton.querySelector('.table-sort-indicator').textContent = active ? (sortDirection === 1 ? '▲' : '▼') : '';
                });
                currentPage = 0;
                renderTable();
            });
        });
        searchInput.addEventListener('input', function () { currentPage = 0; renderTable(); });
        pageSizeSelect.addEventListener('change', function () { currentPage = 0; renderTable(); });
        previousButton.addEventListener('click', function () { currentPage--; renderTable(); });
        nextButton.addEventListener('click', function () { currentPage++; renderTable(); });
        renderTable();
    };

    setupDataTable('client', 'clientes', 'Nenhum cliente cadastrado');
    setupDataTable('motorcycle', 'motocicletas', 'Nenhuma motocicleta cadastrada');
    setupDataTable('part', 'peças', 'Nenhuma peça cadastrada');
    setupDataTable('order', 'ordens de serviço', 'Nenhuma ordem de serviço cadastrada');
}());
</script>
<script>
(function () {
    var backupButton = document.getElementById('backup-folder-button');
    var backupFeedback = document.getElementById('backup-feedback');
    if (backupButton && backupFeedback) {
        backupButton.addEventListener('click', async function () {
            if (!window.showDirectoryPicker) {
                backupFeedback.textContent = 'Este navegador não permite escolher pastas. Use o link para baixar o backup.';
                return;
            }
            try {
                var directory = await window.showDirectoryPicker({ mode: 'readwrite' });
                backupFeedback.textContent = 'Gerando backup...';
                var response = await fetch('/?page=backup&download=1');
                if (!response.ok) throw new Error('Não foi possível gerar o backup.');
                var disposition = response.headers.get('Content-Disposition') || '';
                var match = disposition.match(/filename="([^"]+)"/);
                var filename = match ? match[1] : 'oficina-backup.sqlite';
                var fileHandle = await directory.getFileHandle(filename, { create: true });
                var writable = await fileHandle.createWritable();
                await writable.write(await response.blob());
                await writable.close();
                backupFeedback.textContent = 'Backup salvo: ' + filename;
            } catch (error) {
                if (error.name !== 'AbortError') backupFeedback.textContent = error.message || 'Não foi possível salvar o backup.';
            }
        });
    }

    var restoreFolderButton = document.getElementById('restore-folder-button');
    var restoreSelect = document.getElementById('restore-backup-select');
    var restoreButton = document.getElementById('restore-backup-button');
    var restoreFallback = document.getElementById('restore-file-fallback');
    var restoreFeedback = document.getElementById('restore-feedback');
    var restoreCsrf = document.getElementById('restore-csrf');
    if (!restoreFolderButton || !restoreSelect || !restoreButton || !restoreFallback || !restoreFeedback || !restoreCsrf) return;

    var selectedFiles = [];
    var uploadBackup = async function (file) {
        if (!file) return;
        if (!window.confirm('A restauração substituirá todos os dados atuais da oficina. Deseja continuar?')) return;
        restoreButton.disabled = true;
        restoreFeedback.textContent = 'Validando e restaurando backup...';
        var formData = new FormData();
        formData.append('action', 'restore_backup');
        formData.append('csrf_token', restoreCsrf.value);
        formData.append('backup_file', file, file.name);
        try {
            var response = await fetch('/?page=restaurar', { method: 'POST', body: formData });
            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }
            var responseHtml = await response.text();
            var parsedPage = new DOMParser().parseFromString(responseHtml, 'text/html');
            var errorNotice = parsedPage.querySelector('.notice.error');
            throw new Error(errorNotice ? errorNotice.textContent.trim() : 'Não foi possível restaurar esse arquivo.');
        } catch (error) {
            restoreFeedback.textContent = error.message || 'Falha ao restaurar backup.';
            restoreButton.disabled = false;
        }
    };

    restoreFolderButton.addEventListener('click', async function () {
        if (!window.showDirectoryPicker) {
            restoreFeedback.textContent = 'Este navegador não permite escolher pastas. Use a opção de arquivo abaixo.';
            restoreFallback.click();
            return;
        }
        try {
            var directory = await window.showDirectoryPicker({ mode: 'read' });
            selectedFiles = [];
            restoreSelect.innerHTML = '<option value="">Selecione um backup</option>';
            for await (var entry of directory.values()) {
                if (entry.kind === 'file' && /\.(sqlite|sqlite3|db)$/i.test(entry.name)) {
                    selectedFiles.push(await entry.getFile());
                    var option = document.createElement('option');
                    option.value = String(selectedFiles.length - 1);
                    option.textContent = entry.name;
                    restoreSelect.appendChild(option);
                }
            }
            restoreSelect.disabled = selectedFiles.length === 0;
            restoreButton.disabled = true;
            restoreFeedback.textContent = selectedFiles.length ? selectedFiles.length + ' backup(s) encontrado(s).' : 'Nenhum arquivo SQLite encontrado nessa pasta.';
        } catch (error) {
            if (error.name !== 'AbortError') restoreFeedback.textContent = error.message || 'Não foi possível ler essa pasta.';
        }
    });

    restoreSelect.addEventListener('change', function () {
        restoreButton.disabled = restoreSelect.value === '';
    });
    restoreButton.addEventListener('click', function () {
        uploadBackup(selectedFiles[Number(restoreSelect.value)]);
    });
    restoreFallback.addEventListener('change', function () {
        uploadBackup(restoreFallback.files[0]);
    });
}());
</script>
</body>
</html>