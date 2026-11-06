<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * produtos.php - Gestão Completa de Produtos (Adicionar, Editar e Excluir)
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';

exigir_login();

$tituloPagina = 'Produtos | OPI Estoque';

// Filtros de Busca e Paginação
$busca = trim($_GET['busca'] ?? '');
$categoriaFiltro = !empty($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
$filtroEstoque = trim($_GET['filtro_estoque'] ?? '');

try {
    // 1. Carregar Categorias para os filtros e formulário
    $stmtCats = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC");
    $categorias = $stmtCats->fetchAll();

    // 2. Construção dinâmica da query PDO segura
    $where = [];
    $params = [];

    if (!empty($busca)) {
        $where[] = "(p.nome LIKE :busca OR p.codigo_sku LIKE :busca OR p.descricao LIKE :busca)";
        $params[':busca'] = '%' . $busca . '%';
    }

    if ($categoriaFiltro) {
        $where[] = "p.categoria_id = :cat_id";
        $params[':cat_id'] = $categoriaFiltro;
    }

    if ($filtroEstoque === 'baixo') {
        $where[] = "p.quantidade_estoque <= p.estoque_minimo";
    } elseif ($filtroEstoque === 'zerado') {
        $where[] = "p.quantidade_estoque = 0";
    }

    $sqlWhere = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            {$sqlWhere}
            ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();

} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao buscar produtos: ' . $e->getMessage());
    $categorias = [];
    $produtos = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Catálogo de Produtos</h3>
        <p class="text-muted small mb-0">Adicione, edite, controle quantidades e limites mínimos</p>
    </div>
    <div>
        <?php if (usuario_tem_qualquer_funcao(['Administrador', 'Gerente de Estoque', 'Operador de Estoque'])): ?>
            <button type="button" class="btn btn-primary btn-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProduto" onclick="prepararModalNovoProduto()">
                <i class="bi bi-plus-lg"></i> Novo Produto
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros de Pesquisa Mobile-First -->
<div class="card card-opi border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="produtos.php" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome, SKU ou descrição..." value="<?= htmlspecialchars($busca) ?>">
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <select name="categoria_id" class="form-select form-select-sm">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoriaFiltro === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <select name="filtro_estoque" class="form-select form-select-sm">
                    <option value="">Todos os Níveis de Estoque</option>
                    <option value="baixo" <?= $filtroEstoque === 'baixo' ? 'selected' : '' ?>>Abaixo ou no Limite Mínimo</option>
                    <option value="zerado" <?= $filtroEstoque === 'zerado' ? 'selected' : '' ?>>Estoque Zerado (0 un)</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($busca) || $categoriaFiltro || !empty($filtroEstoque)): ?>
                    <a href="produtos.php" class="btn btn-outline-secondary btn-sm" title="Limpar Filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Listagem Responsiva de Produtos -->
<div class="card card-opi border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($produtos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-box-seam display-4 d-block mb-3 text-secondary"></i>
                <h5>Nenhum produto localizado</h5>
                <p class="small">Tente ajustar seus termos de pesquisa ou cadastre um novo item.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código SKU</th>
                            <th>Produto / Detalhes</th>
                            <th>Categoria</th>
                            <th class="text-center">Estoque Atual</th>
                            <th class="text-center">Mínimo</th>
                            <th>Preço Custo</th>
                            <th>Preço Venda</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): 
                            $qtd = (int)$p['quantidade_estoque'];
                            $min = (int)$p['estoque_minimo'];
                            
                            // Definição da cor do badge conforme status automatizado do estoque
                            if ($qtd === 0) {
                                $badgeStatus = 'bg-danger text-white';
                                $labelStatus = 'Zerado';
                            } elseif ($qtd <= $min) {
                                $badgeStatus = 'bg-warning text-dark';
                                $labelStatus = 'Crítico';
                            } else {
                                $badgeStatus = 'bg-success text-white';
                                $labelStatus = 'Normal';
                            }
                        ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border fw-mono">
                                        <?= htmlspecialchars($p['codigo_sku']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['nome']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($p['descricao'] ?? '') ?>">
                                        <?= htmlspecialchars($p['descricao'] ?? '') ?>
                                    </div>
                                    <?php if (!empty($p['localizacao'])): ?>
                                        <small class="text-secondary"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($p['localizacao']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle">
                                        <?= htmlspecialchars($p['categoria_nome'] ?? 'Sem Categoria') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeStatus ?> px-2 py-1">
                                        <?= $qtd ?> <?= htmlspecialchars($p['unidade_medida']) ?>
                                    </span>
                                    <div class="small text-muted" style="font-size: 0.7rem;"><?= $labelStatus ?></div>
                                </td>
                                <td class="text-center text-muted small">
                                    <?= $min ?> <?= htmlspecialchars($p['unidade_medida']) ?>
                                </td>
                                <td class="small text-muted"><?= formatar_moeda($p['preco_custo']) ?></td>
                                <td class="fw-semibold text-dark"><?= formatar_moeda($p['preco_venda']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Botão Movimentar Estoque -->
                                        <button type="button" 
                                                class="btn btn-outline-success" 
                                                title="Entrada / Saída de Estoque"
                                                data-bs-action="movimentar-estoque"
                                                data-id="<?= $p['id'] ?>"
                                                data-nome="<?= htmlspecialchars($p['nome']) ?>"
                                                data-estoque="<?= $p['quantidade_estoque'] ?>">
                                            <i class="bi bi-arrow-left-right"></i>
                                        </button>

                                        <?php if (usuario_tem_qualquer_funcao(['Administrador', 'Gerente de Estoque', 'Operador de Estoque'])): ?>
                                            <!-- Botão Editar Produto -->
                                            <button type="button" 
                                                    class="btn btn-outline-primary" 
                                                    title="Editar Produto"
                                                    onclick='prepararModalEditarProduto(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (usuario_tem_qualquer_funcao(['Administrador', 'Gerente de Estoque'])): ?>
                                            <!-- Botão Excluir Produto (Modal de Confirmação Seguro) -->
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Excluir Produto"
                                                    data-bs-action="confirm-delete"
                                                    data-id="<?= $p['id'] ?>"
                                                    data-action="actions/produto_excluir.php"
                                                    data-nome="<?= htmlspecialchars($p['nome']) ?>">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
     Modal de Cadastro / Edição de Produto
     Atende ao requisito: "caixas de mensagens devem sempre ser tratadas em um modal dentro da interface"
     ======================================================== -->
<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalProdutoTitulo">
                    <i class="bi bi-box-seam me-2"></i> Formulário de Produto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="actions/produto_salvar.php" method="POST">
                <div class="modal-body py-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">
                    <input type="hidden" name="id" id="prod_id" value="">

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="prod_sku" class="form-label small fw-semibold">Código SKU *</label>
                            <input type="text" class="form-control" name="codigo_sku" id="prod_sku" placeholder="Ex: ELET-001" required>
                        </div>

                        <div class="col-12 col-md-8">
                            <label for="prod_nome" class="form-label small fw-semibold">Nome do Produto *</label>
                            <input type="text" class="form-control" name="nome" id="prod_nome" placeholder="Ex: Teclado Mecânico USB" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="prod_categoria" class="form-label small fw-semibold">Categoria</label>
                            <select class="form-select" name="categoria_id" id="prod_categoria">
                                <option value="">Sem Categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="prod_unidade" class="form-label small fw-semibold">Unidade de Medida</label>
                            <select class="form-select" name="unidade_medida" id="prod_unidade">
                                <option value="UN">UN - Unidade</option>
                                <option value="CX">CX - Caixa</option>
                                <option value="KG">KG - Quilograma</option>
                                <option value="L">L - Litro</option>
                                <option value="M">M - Metro</option>
                                <option value="RL">RL - Rolo</option>
                                <option value="PCT">PCT - Pacote</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="prod_descricao" class="form-label small fw-semibold">Descrição Detalhada</label>
                            <textarea class="form-control" name="descricao" id="prod_descricao" rows="2" placeholder="Especificações técnicas, modelo, cor..."></textarea>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="prod_custo" class="form-label small fw-semibold">Preço de Custo (R$)</label>
                            <input type="number" step="0.01" class="form-control" name="preco_custo" id="prod_custo" value="0.00">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="prod_venda" class="form-label small fw-semibold">Preço de Venda (R$)</label>
                            <input type="number" step="0.01" class="form-control" name="preco_venda" id="prod_venda" value="0.00">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="prod_qtd" class="form-label small fw-semibold">Estoque Atual</label>
                            <input type="number" class="form-control" name="quantidade_estoque" id="prod_qtd" value="0">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="prod_min" class="form-label small fw-semibold">Estoque Mínimo</label>
                            <input type="number" class="form-control" name="estoque_minimo" id="prod_min" value="5">
                        </div>

                        <div class="col-12">
                            <label for="prod_localizacao" class="form-label small fw-semibold">Localização no Depósito / Prateleira</label>
                            <input type="text" class="form-control" name="localizacao" id="prod_localizacao" placeholder="Ex: Corredor B, Prateleira 3, Caixa 12">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Salvar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     Modal de Movimentação Rápida de Estoque
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
                    <input type="hidden" name="origem" value="produtos">

                    <div class="mb-3 p-2 bg-light rounded">
                        <span class="text-muted small">Produto:</span>
                        <div class="fw-bold fs-6 text-dark" id="mov_produto_nome">-</div>
                        <div class="small text-muted mt-1">Saldo Atual: <strong id="mov_estoque_atual" class="text-primary">-</strong></div>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_mov" class="form-label small fw-semibold">Tipo de Movimentação</label>
                        <select class="form-select" name="tipo" id="tipo_mov" required>
                            <option value="entrada" selected>Entrada (+ Recebimento)</option>
                            <option value="saida">Saída (- Baixa / Venda)</option>
                            <option value="ajuste">Ajuste (Inventário)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quantidade_mov" class="form-label small fw-semibold">Quantidade</label>
                        <input type="number" class="form-control" name="quantidade" id="quantidade_mov" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="motivo_mov" class="form-label small fw-semibold">Motivo</label>
                        <input type="text" class="form-control" name="motivo" id="motivo_mov" placeholder="Ex: Compra, quebra, conferência..." required>
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

<script>
function prepararModalNovoProduto() {
    document.getElementById('modalProdutoTitulo').innerHTML = '<i class="bi bi-box-seam me-2"></i> Novo Produto';
    document.getElementById('prod_id').value = '';
    document.getElementById('prod_sku').value = '';
    document.getElementById('prod_nome').value = '';
    document.getElementById('prod_categoria').value = '';
    document.getElementById('prod_unidade').value = 'UN';
    document.getElementById('prod_descricao').value = '';
    document.getElementById('prod_custo').value = '0.00';
    document.getElementById('prod_venda').value = '0.00';
    document.getElementById('prod_qtd').value = '0';
    document.getElementById('prod_min').value = '5';
    document.getElementById('prod_localizacao').value = '';
}

function prepararModalEditarProduto(prod) {
    document.getElementById('modalProdutoTitulo').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Editar Produto';
    document.getElementById('prod_id').value = prod.id;
    document.getElementById('prod_sku').value = prod.codigo_sku;
    document.getElementById('prod_nome').value = prod.nome;
    document.getElementById('prod_categoria').value = prod.categoria_id || '';
    document.getElementById('prod_unidade').value = prod.unidade_medida || 'UN';
    document.getElementById('prod_descricao').value = prod.descricao || '';
    document.getElementById('prod_custo').value = prod.preco_custo;
    document.getElementById('prod_venda').value = prod.preco_venda;
    document.getElementById('prod_qtd').value = prod.quantidade_estoque;
    document.getElementById('prod_min').value = prod.estoque_minimo;
    document.getElementById('prod_localizacao').value = prod.localizacao || '';

    const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
