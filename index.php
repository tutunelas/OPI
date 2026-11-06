<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * index.php - Painel de Controle (Dashboard) Mobile-First
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';

exigir_login();

$tituloPagina = 'Dashboard | OPI Estoque';

// 1. Estatísticas Gerais via queries PDO preparadas
try {
    // Total de produtos cadastrados
    $stmtTotalProd = $pdo->query("SELECT COUNT(*) FROM produtos");
    $totalProdutos = (int)$stmtTotalProd->fetchColumn();

    // Total de itens físicos e valor financeiro estimado
    $stmtValores = $pdo->query("SELECT 
        SUM(quantidade_estoque) AS total_unidades,
        SUM(quantidade_estoque * preco_custo) AS valor_custo_total,
        SUM(quantidade_estoque * preco_venda) AS valor_venda_total
        FROM produtos");
    $dadosValores = $stmtValores->fetch();
    $totalUnidades = (int)($dadosValores['total_unidades'] ?? 0);
    $valorCustoTotal = (float)($dadosValores['valor_custo_total'] ?? 0);
    $valorVendaTotal = (float)($dadosValores['valor_venda_total'] ?? 0);

    // Produtos com estoque em nível de alerta (menor ou igual ao mínimo)
    $stmtBaixoEstoque = $pdo->query("SELECT p.*, c.nome AS categoria_nome 
                                     FROM produtos p
                                     LEFT JOIN categorias c ON p.categoria_id = c.id
                                     WHERE p.quantidade_estoque <= p.estoque_minimo 
                                     ORDER BY p.quantidade_estoque ASC 
                                     LIMIT 8");
    $produtosBaixoEstoque = $stmtBaixoEstoque->fetchAll();
    $qtdAlertasEstoque = count($produtosBaixoEstoque);

    // Últimas movimentações registradas
    $stmtUltimasMov = $pdo->query("SELECT m.*, p.nome AS produto_nome, p.codigo_sku, u.nome AS usuario_nome
                                   FROM movimentacoes m
                                   JOIN produtos p ON m.produto_id = p.id
                                   JOIN usuarios u ON m.usuario_id = u.id
                                   ORDER BY m.id DESC 
                                   LIMIT 8");
    $ultimasMovimentacoes = $stmtUltimasMov->fetchAll();

} catch (PDOException $e) {
    definir_toast('danger', 'Erro ao Carregar Dados', 'Não foi possível carregar os indicadores do estoque: ' . $e->getMessage());
    $totalProdutos = 0;
    $totalUnidades = 0;
    $valorCustoTotal = 0;
    $valorVendaTotal = 0;
    $produtosBaixoEstoque = [];
    $qtdAlertasEstoque = 0;
    $ultimasMovimentacoes = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Cabeçalho da Página -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Visão Geral do Estoque</h3>
        <p class="text-muted small mb-0">Controle automatizado e monitoramento em tempo real</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if (usuario_tem_qualquer_funcao(['Administrador', 'Gerente de Estoque', 'Operador de Estoque'])): ?>
            <a href="produtos.php?acao=novo" class="btn btn-primary btn-sm btn-action">
                <i class="bi bi-plus-circle"></i> Novo Produto
            </a>
            <a href="movimentacoes.php?acao=novo" class="btn btn-outline-primary btn-sm btn-action">
                <i class="bi bi-arrow-down-up"></i> Registrar Movimentação
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Cards de Indicadores Mobile-First (Grid Responsiva 1 a 4 colunas) -->
<div class="row g-3 mb-4">
    <!-- Card Total Produtos -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-opi card-stat h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Produtos Cadastrados</span>
                    <h3 class="fw-bold my-1"><?= number_format($totalProdutos, 0, ',', '.') ?></h3>
                    <span class="small text-muted"><i class="bi bi-box me-1"></i> Itens no Catálogo</span>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Total em Peças/Unidades -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-opi card-stat stat-success h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Saldo Físico Total</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($totalUnidades, 0, ',', '.') ?></h3>
                    <span class="small text-muted"><i class="bi bi-layers me-1"></i> Unidades em Estoque</span>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Alerta de Estoque Baixo -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-opi card-stat <?= $qtdAlertasEstoque > 0 ? 'stat-danger' : 'stat-success' ?> h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Estoque Crítico / Mínimo</span>
                    <h3 class="fw-bold my-1 <?= $qtdAlertasEstoque > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= $qtdAlertasEstoque ?>
                    </h3>
                    <span class="small text-muted">
                        <?= $qtdAlertasEstoque > 0 ? 'Necessitam reposição imediata' : 'Níveis sob controle' ?>
                    </span>
                </div>
                <div class="stat-icon <?= $qtdAlertasEstoque > 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' ?>">
                    <i class="bi <?= $qtdAlertasEstoque > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?>"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Avaliação Financeira -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-opi card-stat stat-warning h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Valor em Estoque (Venda)</span>
                    <h4 class="fw-bold my-1 text-dark"><?= formatar_moeda($valorVendaTotal) ?></h4>
                    <span class="small text-muted">Custo: <?= formatar_moeda($valorCustoTotal) ?></span>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seção Principal: Alertas Críticos e Últimas Movimentações -->
<div class="row g-4">
    <!-- Tabela de Alertas de Reposição -->
    <div class="col-12 col-lg-5">
        <div class="card card-opi h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="fw-bold mb-0 text-danger">
                    <i class="bi bi-bell-fill me-2"></i> Itens Abaixo do Estoque Mínimo
                </h6>
                <span class="badge bg-danger rounded-pill"><?= count($produtosBaixoEstoque) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($produtosBaixoEstoque)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check2-circle fs-1 text-success mb-2 d-block"></i>
                        <p class="mb-0 small">Excelente! Nenhum item com estoque abaixo do limite mínimo.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Qtd Atual</th>
                                    <th class="text-center">Mínimo</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtosBaixoEstoque as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($item['nome']) ?>">
                                                <?= htmlspecialchars($item['nome']) ?>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($item['codigo_sku']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= $item['quantidade_estoque'] ?> <?= $item['unidade_medida'] ?></span>
                                        </td>
                                        <td class="text-center text-muted small">
                                            <?= $item['estoque_minimo'] ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success btn-action" 
                                                    data-bs-action="movimentar-estoque"
                                                    data-id="<?= $item['id'] ?>"
                                                    data-nome="<?= htmlspecialchars($item['nome']) ?>"
                                                    data-estoque="<?= $item['quantidade_estoque'] ?>">
                                                <i class="bi bi-plus-lg"></i> Repor
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top text-center py-2">
                <a href="produtos.php?filtro_estoque=baixo" class="small text-decoration-none fw-semibold">
                    Ver todos os produtos com estoque baixo <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Tabela de Últimas Movimentações -->
    <div class="col-12 col-lg-7">
        <div class="card card-opi h-100 shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Movimentações Recentes
                </h6>
                <a href="movimentacoes.php" class="btn btn-sm btn-outline-secondary">Ver Histórico Completo</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasMovimentacoes)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
                        <p class="mb-0 small">Nenhuma movimentação de estoque registrada recentemente.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data / Hora</th>
                                    <th>Produto</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-center">Saldo</th>
                                    <th>Operador</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasMovimentacoes as $mov): 
                                    $badgeClass = match($mov['tipo']) {
                                        'entrada' => 'bg-success',
                                        'saida'   => 'bg-danger',
                                        'ajuste'  => 'bg-primary',
                                        default   => 'bg-secondary'
                                    };
                                    $tipoLabel = match($mov['tipo']) {
                                        'entrada' => '+ Entrada',
                                        'saida'   => '- Saída',
                                        'ajuste'  => 'Ajuste',
                                        default   => $mov['tipo']
                                    };
                                ?>
                                    <tr>
                                        <td class="small text-muted"><?= formatar_data_hora($mov['criado_em']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($mov['produto_nome']) ?>">
                                                <?= htmlspecialchars($mov['produto_nome']) ?>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($mov['codigo_sku']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $badgeClass ?>"><?= $tipoLabel ?></span>
                                        </td>
                                        <td class="text-center fw-bold"><?= $mov['quantidade'] ?></td>
                                        <td class="text-center small">
                                            <span class="text-muted"><?= $mov['quantidade_anterior'] ?></span>
                                            <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                            <strong class="text-dark"><?= $mov['quantidade_resultante'] ?></strong>
                                        </td>
                                        <td class="small text-truncate" style="max-width: 100px;">
                                            <?= htmlspecialchars($mov['usuario_nome']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     Modal de Movimentação Rápida de Estoque
     Atende ao requisito: "caixas de mensagens devem sempre ser tratadas em um modal dentro da interface"
     ======================================================== -->
<div class="modal fade" id="modalMovimentarEstoque" tabindex="-1" aria-labelledby="modalMovimentarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalMovimentarTitulo">
                    <i class="bi bi-arrow-left-right me-2"></i> Movimentar Estoque
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="actions/movimentacao_salvar.php" method="POST">
                <div class="modal-body py-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">
                    <input type="hidden" name="produto_id" id="mov_produto_id" value="">
                    <input type="hidden" name="origem" value="index">

                    <div class="mb-3 p-2 bg-light rounded">
                        <span class="text-muted small">Produto Selecionado:</span>
                        <div class="fw-bold fs-6 text-dark" id="mov_produto_nome">-</div>
                        <div class="small text-muted mt-1">Saldo Atual em Estoque: <strong id="mov_estoque_atual" class="text-primary">-</strong></div>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_mov" class="form-label small fw-semibold">Tipo de Movimentação</label>
                        <select class="form-select" name="tipo" id="tipo_mov" required>
                            <option value="entrada" selected>Entrada (+ Adicionar ao estoque)</option>
                            <option value="saida">Saída (- Retirar do estoque)</option>
                            <option value="ajuste">Ajuste (Definir saldo exato)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantidade_mov" class="form-label small fw-semibold">Quantidade</label>
                        <input type="number" class="form-control" name="quantidade" id="quantidade_mov" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="motivo_mov" class="form-label small fw-semibold">Motivo / Justificativa</label>
                        <input type="text" class="form-control" name="motivo" id="motivo_mov" placeholder="Ex: Compra de reposição, avaria, contagem física..." required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> Gravar Movimentação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
