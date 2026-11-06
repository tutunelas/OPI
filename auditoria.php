<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * auditoria.php - Painel de Auditoria e Logs de Conformidade
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

exigir_login();
exigir_funcao(['Administrador', 'Auditor', 'Gerente de Estoque']);

$tituloPagina = 'Auditoria do Sistema | OPI Estoque';

// Filtros
$acaoFiltro = trim($_GET['acao'] ?? '');
$tabelaFiltro = trim($_GET['tabela'] ?? '');
$usuarioFiltro = !empty($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : null;
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');

try {
    // Buscar lista de usuários para filtro
    $stmtUsers = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome ASC");
    $listaUsuarios = $stmtUsers->fetchAll();

    // Buscar logs utilizando a função modularizada do logger
    $filtros = [
        'acao' => $acaoFiltro,
        'tabela' => $tabelaFiltro,
        'usuario_id' => $usuarioFiltro,
        'data_inicio' => $dataInicio,
        'data_fim' => $dataFim
    ];

    $logs = obter_logs_auditoria($pdo, $filtros, 100, 0);

} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao recuperar logs de auditoria: ' . $e->getMessage());
    $listaUsuarios = [];
    $logs = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Logs de Auditoria e Conformidade</h3>
        <p class="text-muted small mb-0">Rastreabilidade integral de acessos, cadastros e alterações no sistema</p>
    </div>
    <div>
        <span class="badge bg-secondary px-3 py-2">
            <i class="bi bi-shield-lock me-1"></i> Auditoria Segura
        </span>
    </div>
</div>

<!-- Filtros de Auditoria -->
<div class="card card-opi border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="auditoria.php" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <input type="text" name="acao" class="form-control form-control-sm" placeholder="Ação (Ex: LOGIN, PRODUTO...)" value="<?= htmlspecialchars($acaoFiltro) ?>">
            </div>

            <div class="col-12 col-sm-6 col-md-2">
                <select name="tabela" class="form-select form-select-sm">
                    <option value="">Todas as Tabelas</option>
                    <option value="produtos" <?= $tabelaFiltro === 'produtos' ? 'selected' : '' ?>>Produtos</option>
                    <option value="movimentacoes" <?= $tabelaFiltro === 'movimentacoes' ? 'selected' : '' ?>>Movimentações</option>
                    <option value="usuarios" <?= $tabelaFiltro === 'usuarios' ? 'selected' : '' ?>>Usuários</option>
                    <option value="sistema" <?= $tabelaFiltro === 'sistema' ? 'selected' : '' ?>>Sistema</option>
                </select>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <select name="usuario_id" class="form-select form-select-sm">
                    <option value="">Todos os Operadores</option>
                    <?php foreach ($listaUsuarios as $usr): ?>
                        <option value="<?= $usr['id'] ?>" <?= $usuarioFiltro === (int)$usr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usr['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($dataInicio) ?>" title="Data Inicial">
            </div>

            <div class="col-6 col-md-1">
                <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= htmlspecialchars($dataFim) ?>" title="Data Final">
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($acaoFiltro) || !empty($tabelaFiltro) || $usuarioFiltro || !empty($dataInicio) || !empty($dataFim)): ?>
                    <a href="auditoria.php" class="btn btn-outline-secondary btn-sm" title="Limpar Filtros">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Logs Responsiva -->
<div class="card card-opi border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x display-4 d-block mb-3 text-secondary"></i>
                <h5>Nenhum registro de auditoria encontrado</h5>
                <p class="small">Ajuste os filtros de pesquisa para visualizar outros eventos.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Data / Hora</th>
                            <th>Operador</th>
                            <th>Ação Executada</th>
                            <th>Tabela / Registro</th>
                            <th>IP Origem</th>
                            <th class="text-end">Detalhes (JSON)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $badgeClass = match(true) {
                                str_contains($log['acao'], 'EXCLUID') || str_contains($log['acao'], 'SAIDA') || str_contains($log['acao'], 'FALHA') => 'bg-danger',
                                str_contains($log['acao'], 'CRIAD') || str_contains($log['acao'], 'ENTRADA') => 'bg-success',
                                str_contains($log['acao'], 'EDITAD') || str_contains($log['acao'], 'ATUALIZAD') || str_contains($log['acao'], 'AJUSTE') => 'bg-warning text-dark',
                                str_contains($log['acao'], 'LOGIN') => 'bg-info text-dark',
                                default => 'bg-secondary'
                            };
                        ?>
                            <tr>
                                <td class="small text-muted">#<?= $log['id'] ?></td>
                                <td class="small text-nowrap"><?= formatar_data_hora($log['criado_em']) ?></td>
                                <td class="small">
                                    <?php if ($log['usuario_nome']): ?>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($log['usuario_nome']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($log['usuario_email']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Sistema / Anônimo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> font-monospace">
                                        <?= htmlspecialchars($log['acao']) ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <?php if ($log['tabela_afetada']): ?>
                                        <code><?= htmlspecialchars($log['tabela_afetada']) ?></code>
                                        <?php if ($log['registro_id']): ?>
                                            (ID: <?= $log['registro_id'] ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted font-monospace"><?= htmlspecialchars($log['ip_origem'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if (!empty($log['detalhes'])): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                data-bs-action="view-details"
                                                data-titulo="Auditoria #<?= $log['id'] ?> - <?= htmlspecialchars($log['acao']) ?>"
                                                data-detalhes='<?= htmlspecialchars($log['detalhes'], ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="bi bi-eye"></i> Inspecionar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
