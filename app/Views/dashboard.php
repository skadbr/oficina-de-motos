<?php
/** @var array $pageTitles */
/** @var string $page */
/** @var array $clients */
/** @var array $motorcycles */
/** @var array $services */
/** @var array $orders */
/** @var string $success */
/** @var string $error */
/** @var string $csrf */
/** @var string $nowForInput */
$title = $pageTitles[$page];
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
        form { margin: 0; }
        .form-grid { display: grid; gap: 13px; }
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
        .service-options { display: grid; gap: 7px; max-height: 150px; overflow-y: auto; border: 1px solid var(--line); border-radius: 3px; padding: 10px; }
        .service-option { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-weight: 400; }
        .service-option span { display: flex; align-items: center; gap: 8px; }
        .service-option input { width: auto; }
        .form-hint { color: var(--muted); font-size: 12px; font-weight: 400; }
        @media (max-width: 850px) { .shell { grid-template-columns: 1fr; } aside { padding: 17px 18px; } .brand { margin: 0 0 12px; } nav { display: flex; overflow-x: auto; } nav a { white-space: nowrap; } main { padding: 25px 18px; } .layout { grid-template-columns: 1fr; } }
        @media (max-width: 520px) { h1 { font-size: 25px; } .panel { padding: 15px; } .two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <p class="brand">Oficina <span>Gestão de motos</span></p>
        <nav aria-label="Navegação principal">
            <?php foreach ($pageTitles as $key => $pageTitle): ?>
                <a class="<?php echo $page === $key ? 'active' : ''; ?>" href="/?page=<?php echo e($key); ?>"><?php echo e($pageTitle); ?></a>
            <?php endforeach; ?>
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
                    <?php if (!$clients): ?><div class="empty">Nenhum cliente cadastrado.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>Nome</th><th>Telefone</th><th>E-mail</th></tr></thead><tbody>
                            <?php foreach ($clients as $client): ?><tr><td class="primary"><?php echo e($client['name']); ?></td><td><?php echo e($client['phone']); ?></td><td><?php echo e($client['email'] ?: '—'); ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>
                <section class="panel"><h2 class="section-title">Novo cliente</h2>
                    <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="add_client">
                        <label>Nome completo<input name="name" required maxlength="150"></label>
                        <label>Telefone<input name="phone" required maxlength="40" placeholder="(11) 99999-9999"></label>
                        <label>E-mail <span class="form-hint">Opcional</span><input type="email" name="email" maxlength="150"></label>
                        <button class="button" type="submit">Cadastrar cliente</button>
                    </form>
                </section>
            </div>
        <?php elseif ($page === 'motos'): ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Motocicletas cadastradas</h2>
                    <?php if (!$motorcycles): ?><div class="empty">Nenhuma moto cadastrada.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>Moto</th><th>Placa</th><th>Cliente</th><th>Ano / km</th></tr></thead><tbody>
                            <?php foreach ($motorcycles as $motorcycle): ?><tr><td class="primary"><?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model']); ?></td><td><?php echo e($motorcycle['plate']); ?></td><td><?php echo e($motorcycle['client_name']); ?></td><td><?php echo e($motorcycle['year'] ?: '—'); ?> / <?php echo number_format((int) $motorcycle['mileage'], 0, ',', '.'); ?> km</td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>
                <section class="panel"><h2 class="section-title">Nova motocicleta</h2>
                    <?php if (!$clients): ?><div class="empty">Cadastre um cliente antes de incluir uma moto.</div><?php else: ?>
                        <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="add_motorcycle">
                            <label>Proprietário<select name="client_id" required><option value="">Selecione</option><?php foreach ($clients as $client): ?><option value="<?php echo (int) $client['id']; ?>"><?php echo e($client['name']); ?></option><?php endforeach; ?></select></label>
                            <div class="two-col"><label>Marca<input name="brand" required maxlength="80"></label><label>Modelo<input name="model" required maxlength="100"></label></div>
                            <div class="two-col"><label>Placa<input name="plate" required maxlength="10" placeholder="ABC1D23"></label><label>Ano<input type="number" name="year" min="1950" max="2100"></label></div>
                            <label>Quilometragem<input type="number" name="mileage" min="0" value="0"></label>
                            <button class="button" type="submit">Cadastrar moto</button>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        <?php elseif ($page === 'servicos'): ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Serviços cadastrados</h2>
                    <?php if (!$services): ?><div class="empty">Nenhum serviço cadastrado.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>Serviço</th><th>Descrição</th><th>Valor de referência</th></tr></thead><tbody>
                            <?php foreach ($services as $service): ?><tr><td class="primary"><?php echo e($service['name']); ?></td><td><?php echo e($service['description'] ?: '—'); ?></td><td><?php echo money($service['price_cents']); ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>
                <section class="panel"><h2 class="section-title">Novo serviço</h2>
                    <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="add_service">
                        <label>Nome<input name="name" required maxlength="120" placeholder="Troca de óleo"></label>
                        <label>Descrição<textarea name="description" maxlength="500"></textarea></label>
                        <label>Valor de referência (R$)<input name="price" inputmode="decimal" value="0,00" required></label>
                        <button class="button" type="submit">Cadastrar serviço</button>
                    </form>
                </section>
            </div>
        <?php else: ?>
            <div class="layout">
                <section class="panel"><h2 class="section-title">Histórico de ordens</h2>
                    <?php if (!$orders): ?><div class="empty">Nenhuma OS cadastrada. Crie a primeira ao lado.</div><?php else: ?>
                        <div class="table-wrap"><table><thead><tr><th>OS</th><th>Cliente / moto</th><th>Serviços / peças</th><th>Valores</th><th>Status / datas</th><th></th></tr></thead><tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="primary">#<?php echo (int) $order['id']; ?></td>
                                    <td><span class="primary"><?php echo e($order['client_name']); ?></span><br><span class="small"><?php echo e($order['brand'] . ' ' . $order['model'] . ' · ' . $order['plate']); ?></span></td>
                                    <td><?php echo e($order['services'] ? implode(', ', $order['services']) : '—'); ?><br><span class="small"><?php echo e($order['parts_description'] ?: 'Sem peças descritas'); ?></span></td>
                                    <td><span class="small">Peças <?php echo money($order['parts_cents']); ?><br>Mão de obra <?php echo money($order['labor_cents']); ?></span><br><span class="primary"><?php echo money($order['total_cents']); ?></span></td>
                                    <td><span class="badge <?php echo $order['status'] === 'Concluída' ? 'done' : ''; ?>"><?php echo e($order['status']); ?></span><br><span class="small">Criada <?php echo e(date('d/m/Y H:i', strtotime($order['created_at']))); ?><br><?php echo $order['completed_at'] ? 'Finalizada ' . e(date('d/m/Y H:i', strtotime($order['completed_at']))) : 'Sem data de término'; ?></span></td>
                                    <td><?php if ($order['status'] === 'Aberta'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="complete_order"><input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>"><button class="button secondary" type="submit">Concluir</button></form><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    <?php endif; ?>
                </section>
                <section class="panel"><h2 class="section-title">Nova ordem de serviço</h2>
                    <?php if (!$clients || !$motorcycles): ?><div class="empty">Cadastre ao menos um cliente e uma moto para abrir uma OS.</div><?php else: ?>
                        <form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>"><input type="hidden" name="action" value="add_order">
                            <label>Cliente<select name="client_id" id="client-select" required><option value="">Selecione</option><?php foreach ($clients as $client): ?><option value="<?php echo (int) $client['id']; ?>"><?php echo e($client['name']); ?></option><?php endforeach; ?></select></label>
                            <label>Moto<select name="motorcycle_id" id="motorcycle-select" required><option value="">Selecione o cliente primeiro</option><?php foreach ($motorcycles as $motorcycle): ?><option value="<?php echo (int) $motorcycle['id']; ?>" data-client="<?php echo (int) $motorcycle['client_id']; ?>"><?php echo e($motorcycle['brand'] . ' ' . $motorcycle['model'] . ' · ' . $motorcycle['plate']); ?></option><?php endforeach; ?></select></label>
                            <div class="two-col"><label>Data de criação<input type="datetime-local" name="created_at" value="<?php echo e($nowForInput); ?>" required></label><label>Término <span class="form-hint">Opcional</span><input type="datetime-local" name="completed_at"></label></div>
                            <label>Serviços executados <span class="form-hint">Selecione os itens do catálogo</span></label>
                            <?php if ($services): ?><div class="service-options"><?php foreach ($services as $service): ?><label class="service-option"><span><input type="checkbox" name="services[]" value="<?php echo (int) $service['id']; ?>"> <?php echo e($service['name']); ?></span><span class="small"><?php echo money($service['price_cents']); ?></span></label><?php endforeach; ?></div><?php else: ?><div class="form-hint">Sem serviços cadastrados. Você pode registrar a mão de obra abaixo.</div><?php endif; ?>
                            <label>Peças utilizadas<textarea name="parts_description" maxlength="1500" placeholder="Descreva as peças e quantidades"></textarea></label>
                            <div class="two-col"><label>Valor das peças (R$)<input name="parts_value" inputmode="decimal" value="0,00" required></label><label>Mão de obra (R$)<input name="labor_value" inputmode="decimal" value="0,00" required></label></div>
                            <p class="form-hint">O total é calculado automaticamente: peças + mão de obra.</p>
                            <button class="button" type="submit">Criar ordem de serviço</button>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>
(function () {
    var client = document.getElementById('client-select');
    var motorcycle = document.getElementById('motorcycle-select');
    if (!client || !motorcycle) return;
    var options = Array.prototype.slice.call(motorcycle.options, 1);
    client.addEventListener('change', function () {
        motorcycle.value = '';
        options.forEach(function (option) {
            option.hidden = option.getAttribute('data-client') !== client.value;
        });
    });
}());
</script>
</body>
</html>