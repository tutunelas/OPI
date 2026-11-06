<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * movimentacoes.php - Histórico e Lançamentos de Movimentações
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';

exigir_login();

$tituloPagina = 'Movimentações de Estoque | OPI Estoque';

// Filtros
$tipoFiltro = trim($_GET['tipo'] ?? '');
$produtoFiltro = !empty($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');

try {
    // Carregar lista de produtos para o filtro e modal
    $stmtProdList = $pdo->query("SELECT id, codigo_sku, nome, quantidade_estoque FROM produtos ORDER BY nome ASC");
    $listaProdutos = $stmtProdList->fetchAll();

    $where = [];
    $params = [];

    if (!empty($tipoFiltro) && in_array($tipoFiltro, ['entrada', 'saida', 'ajuste'])) {
        $where[] = "m.tipo = :tipo";
        $params[':tipo'] = $tipoFiltro;
    }

    if ($produtoFiltro) {
        $where[] = "m.produto_id = :prod_id";
        $params[':prod_id'] = $produtoFiltro;
    }

    if (!empty($dataInicio)) {
        $where[] = "m.criado_em >= :data_inicio";
        $params[':data_inicio'] = $dataInicio . ' 00:00:00';
    }

    if (!empty($dataFim)) {
        $where[] = "m.criado_em <= :data_fim";
        $params[':data_fim'] = $dataFim . ' 23:59:59';
    }

    $sqlWhere = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT m.*, p.nome AS produto_nome, p.codigo_sku, p.unidade_medida, u.nome AS usuario_nome
            FROM movimentacoes m
            JOIN produtos p ON m.produto_id = p.id
            JOIN usuarios u ON m.usuario_id = u.id
            {$sqlWhere}
            ORDER BY m.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimentacoes = $stmt->fetchAll();

} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao buscar movimentações: ' . $e->getMessage());
    $listaProdutos = [];
    $movimentacoes = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Movimentações de Estoque</h3>
        <p class="text-muted small mb-0">Rastreabilidade completa de entradas, saídas e ajustes</p>
    </div>
    <div>
        <?php if (usuario_tem_qualquer_funcao(['Administrador', 'Gerente de Estoque', 'Operador de Estoque'])): ?>
            <button type="button" class="btn btn-primary btn-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNovaMovimentacao">
                <i class="bi bi-arrow-down-up"></i> Nova Movimentação
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros de Pesquisa -->
<div class="card card-opi border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="movimentacoes.php" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <select name="produto_id" class="form-select form-select-sm">
                    <option value="">Todos os Produtos</option>
                    <?php foreach ($listaProdutos as $item): ?>
                        <option value="<?= $item['id'] ?>" <?= $produtoFiltro === (int)$item['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['codigo_sku']) ?> - <?= htmlspecialchars($item['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos os Tipos</option>
                    <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="saida" <?= $tipoFiltro === 'saida' ? 'selected' : '' ?>>Saída</option>
                    <option value="ajuste" <?= $tipoFiltro === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                </select>
            </div>

            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($dataInicio) ?>" placeholder="Data Inicial">
            </div>

            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= htmlspecialchars($dataFim) ?>" placeholder="Data Final">
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($tipoFiltro) || $produtoFiltro || !empty($dataInicio) || !empty($dataFim)): ?>
                    <a href="movimentacoes.php" class="btn btn-outline-secondary btn-sm" title="Limpar Filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Listagem Responsiva de Movimentações -->
<div class="card card-opi border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($movimentacoes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-left-right display-4 d-block mb-3 text-secondary"></i>
                <h5>Nenhum registro de movimentação encontrado</h5>
                <p class="small">Aplique outros filtros ou registre uma nova entrada/saída.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data / Hora</th>
                            <th>Produto</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-center">Evolução do Saldo</th>
                            <th>Motivo / Observação</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentacoes as $m): 
                            $badgeClass = match($m['tipo']) {
                                'entrada' => 'bg-success',
                                'saida'   => 'bg-danger',
                                'ajuste'  => 'bg-primary',
                                default   => 'bg-secondary'
                            };

                            $tipoLabel = match($m['tipo']) {
                                'entrada' => '+ Entrada',
                                'saida'   => '- Saída',
                                'ajuste'  => 'Ajuste',
                                default   => $m['tipo']
                            };
                        ?>
                            <tr>
                                <td class="small text-muted text-nowrap"><?= formatar_data_hora($m['criado_em']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['produto_nome']) ?></div>
                                    <span class="badge bg-light text-muted border"><?= htmlspecialchars($m['codigo_sku']) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?>"><?= $tipoLabel ?></span>
                                </td>
                                <td class="text-center fw-bold fs-6">
                                    <?= $m['quantidade'] ?> <small class="text-muted fw-normal"><?= htmlspecialchars($m['unidade_medida']) ?></small>
                                </td>
                                <td class="text-center small">
                                    <span class="text-muted"><?= $m['quantidade_anterior'] ?></span>
                                    <i class="bi bi-arrow-right mx-1 text-primary"></i>
                                    <strong class="text-dark fs-6"><?= $m['quantidade_resultante'] ?></strong>
                                </td>
                                <td class="small text-muted" style="max-width: 250px;">
                                    <?= htmlspecialchars($m['motivo']) ?>
                                </td>
                                <td class="small text-nowrap">
                                    <i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($m['usuario_nome']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================
     Modal Nova Movimentação Geral
     ======================================================== -->
<div class="modal fade" id="modalNovaMovimentacao" tabindex="-1" aria-labelledby="modalNovaMovTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNovaMovTitulo">
                    <i class="bi bi-arrow-down-up me-2"></i> Registrar Movimentação
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="actions/movimentacao_salvar.php" method="POST">
                <div class="modal-body py-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">
                    <input type="hidden" name="origem" value="movimentacoes">

                    <div class="mb-3">
                        <label for="modal_prod_id" class="form-label small fw-semibold">Selecione o Produto *</label>
                        <select class="form-select" name="produto_id" id="modal_prod_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaProdutos as $item): ?>
                                <option value="<?= $item['id'] ?>">
                                    <?= htmlspecialchars($item['codigo_sku']) ?> - <?= htmlspecialchars($item['nome']) ?> (Estoque: <?= $item['quantidade_estoque'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="modal_tipo" class="form-label small fw-semibold">Tipo de Movimentação *</label>
                        <select class="form-select" name="tipo" id="modal_tipo" required>
                            <option value="entrada" selected>Entrada (+ Recebimento)</option>
                            <option value="saida">Saída (- Baixa / Venda)</option>
                            <option value="ajuste">Ajuste (Inventário)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="modal_qtd" class="form-label small fw-semibold">Quantidade *</label>
                        <input type="number" class="form-control" name="quantidade" id="modal_qtd" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="modal_motivo" class="form-label small fw-semibold">Motivo / Justificativa *</label>
                        <input type="text" class="form-control" name="motivo" id="modal_motivo" placeholder="Ex: NF-e 1234, inventário mensal, perda por validade..." required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> Confirmar Movimentação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
