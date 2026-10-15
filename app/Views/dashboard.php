<?php
/** @var array $pageTitles */
/** @var string $page */
/** @var array $clients */
/** @var array $motorcycles */
/** @var array $orders */
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
$activeMotorcycleId = $editing ? (int) $editing['motorcycle_id'] : (int) $selectedMotorcycleId;
$activeMotorcycle = null;
foreach ($motorcycles as $motorcycle) {
    if ((int) $motorcycle['id'] === $activeMotorcycleId) {
        $activeMotorcycle = $motorcycle;
        break;
    }
}
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
        .select-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: end; }
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
        .button.secondary { min-height: 32px; padding: 5px 9px; font-size: 12px; }
        .notice { margin-bottom: 18px; border-radius: 3px; padding: 11px 14px; background: #e5f1e9; color: var(--green-dark); }
        .notice.error { background: #f8e9e6; color: var(--red); }
        .empty { padding: 28px 15px; color: var(--muted); text-align: center; }
        .form-hint { color: var(--muted); font-size: 12px; font-weight: 400; }
        @media (max-width: 850px) { .shell { grid-template-columns: 1fr; } aside { padding: 17px 18px; } .brand { margin: 0 0 12px; } nav { display: flex; overflow-x: auto; } nav a { white-space: nowrap; } main { padding: 25px 18px; } .layout { grid-template-columns: 1fr; } }
        @media (max-width: 520px) { h1 { font-size: 25px; } .panel { padding: 15px; } .two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <p class="brand">Oficina <span>MOTO RACING</span></p>
        <nav aria-label="Navegação principal">
            <?php foreach (array('motos', 'clientes', 'ordens') as $key): ?>
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
                    <form method="get" class="search-form"><input type="hidden" name="page" value="clientes"><input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Buscar por nome, telefone ou e-mail"><button class="button" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="cancel-link" href="/?page=clientes">Limpar</a><?php endif; ?></form>
                    <?php if (!$clients): ?><div class="empty">Nenhum cliente cadastrado.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Telefone</th><th>E-mail</th><th><?php echo $clientLinkWizard ? 'Vincular moto' : 'Ações'; ?></th></tr></thead><tbody>
                            <?php foreach ($clients as $client): ?><tr><td class="primary"><?php echo e($client['name']); ?></td><td><?php echo e($client['phone']); ?></td><td><?php echo e($client['email'] ?: '—'); ?></td><td><?php if ($clientLinkWizard): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="link_pending_motorcycle"><input type="hidden" name="client_id" value="<?php echo (int) $client['id']; ?>"><button class="button secondary" type="submit">Vincular</button></form><?php else: ?><div class="row-actions"><a class="text-button" href="/?page=clientes&amp;edit=<?php echo (int) $client['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir este cliente?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="delete_client"><input type="hidden" name="id" value="<?php echo (int) $client['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="text-button delete-button" type="submit">Excluir</button></form></div><?php endif; ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
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
                    <form method="get" class="search-form"><input type="hidden" name="page" value="motos"><input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Buscar por marca, modelo, placa ou cliente"><button class="button" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="cancel-link" href="/?page=motos">Limpar</a><?php endif; ?></form>
                    <?php if (!$motorcycles): ?><div class="empty">Nenhuma moto cadastrada.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>Moto</th><th>Placa</th><th>Cliente</th><th>Ano / km</th><th>Ações</th></tr></thead><tbody>
                            <?php foreach ($motorcycles as $motorcycle): ?><tr><td class="primary"><?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?></td><td><?php echo e($motorcycle['plate']); ?></td><td><?php echo e($motorcycle['client_name']); ?></td><td><?php echo e($motorcycle['year'] ?: '—'); ?> / <?php echo number_format((int) $motorcycle['mileage'], 0, ',', '.'); ?> km</td><td><div class="row-actions"><a class="text-button" href="/?page=motos&amp;edit=<?php echo (int) $motorcycle['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir esta motocicleta?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="delete_motorcycle"><input type="hidden" name="id" value="<?php echo (int) $motorcycle['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="text-button delete-button" type="submit">Excluir</button></form></div></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
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
        <?php else: ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Histórico de ordens</h2>
                    <form method="get" class="search-form"><input type="hidden" name="page" value="ordens"><input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Buscar OS, cliente, moto, serviço, peça ou status"><button class="button" type="submit">Buscar</button><?php if ($search !== ''): ?><a class="cancel-link" href="/?page=ordens">Limpar</a><?php endif; ?></form>
                    <?php if (!$orders): ?><div class="empty"><?php echo $search !== '' ? 'Nenhuma OS corresponde à busca.' : 'Nenhuma OS cadastrada. Crie a primeira ao lado.'; ?></div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>OS</th><th>Cliente / moto</th><th>Serviços / peças</th><th>Valores</th><th>Status / datas</th><th>Ações</th></tr></thead><tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="primary">#<?php echo (int) $order['id']; ?></td>
                                    <td><span class="primary"><?php echo e($order['client_name']); ?></span><br><span class="small"><?php echo e($order['brand'] . ' ' . $order['model'] . ' · ' . $order['plate']); ?></span></td>
                                    <td><?php echo e($order['services_description'] ?: 'Sem serviços descritos'); ?><br><span class="small"><?php echo e($order['parts_description'] ?: 'Sem peças descritas'); ?></span></td>
                                    <td><span class="small">Peças <?php echo money($order['parts_cents']); ?><br>Mão de obra <?php echo money($order['labor_cents']); ?></span><br><span class="primary"><?php echo money($order['total_cents']); ?></span></td>
                                    <td><span class="badge <?php echo $order['status'] === 'Faturada' ? 'done' : ($order['status'] === 'Cancelada' ? 'cancelled' : ''); ?>"><?php echo e($order['status']); ?></span><br><span class="small">Criada <?php echo e(date('d/m/Y H:i', strtotime($order['created_at']))); ?><br><?php if ($order['status'] === 'Faturada' && $order['completed_at']): ?>Faturada <?php echo e(date('d/m/Y H:i', strtotime($order['completed_at']))); ?><?php elseif ($order['status'] === 'Cancelada' && $order['cancelled_at']): ?>Cancelada <?php echo e(date('d/m/Y H:i', strtotime($order['cancelled_at']))); ?><?php else: ?>Sem término<?php endif; ?></span></td>
                                    <td><div class="row-actions"><a class="text-button" href="/?page=ordens&amp;edit=<?php echo (int) $order['id']; ?>&amp;q=<?php echo urlencode($search); ?>">Editar</a><?php if ($order['status'] === 'Aberta'): ?><form method="post" onsubmit="return confirm('Confirmar que o pagamento da OS #<?php echo (int) $order['id']; ?> foi recebido?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="bill_order"><input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="button secondary" type="submit">Faturar</button></form><form method="post" onsubmit="return confirm('Cancelar a OS #<?php echo (int) $order['id']; ?>?');"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="cancel_order"><input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>"><input type="hidden" name="return_q" value="<?php echo e($search); ?>"><button class="button secondary delete-button" type="submit">Cancelar</button></form><?php endif; ?></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
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
                            <label>Peças utilizadas<textarea name="parts_description" maxlength="1500" placeholder="Descreva as peças e quantidades"><?php echo e($editing ? $editing['parts_description'] : ''); ?></textarea></label>
                            <div class="two-col"><label>Valor das peças (R$)<input name="parts_value" inputmode="decimal" value="<?php echo $editing ? e(number_format($editing['parts_cents'] / 100, 2, ',', '.')) : '0,00'; ?>" required></label><label>Mão de obra (R$)<input name="labor_value" inputmode="decimal" value="<?php echo $editing ? e(number_format($editing['labor_cents'] / 100, 2, ',', '.')) : '0,00'; ?>" required></label></div>
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