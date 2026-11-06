<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * meu_historico.php - Histórico de Uso do Sistema pelo Usuário Conectado
 * Atende ao requisito: "todos os usuarios devem conseguir ver seus historicos de uso de sistema"
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/logger.php';

exigir_login();

$tituloPagina = 'Meu Histórico | OPI Estoque';
$usuarioId = (int)obter_usuario_id();

try {
    // 1. Estatísticas de uso do usuário
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM logs_auditoria WHERE usuario_id = :uid");
    $stmtTotal->execute([':uid' => $usuarioId]);
    $totalAcoes = (int)$stmtTotal->fetchColumn();

    $stmtUltimoLogin = $pdo->prepare("SELECT criado_em, ip_origem FROM logs_auditoria 
                                      WHERE usuario_id = :uid AND acao = 'LOGIN' 
                                      ORDER BY id DESC LIMIT 1");
    $stmtUltimoLogin->execute([':uid' => $usuarioId]);
    $ultimoLogin = $stmtUltimoLogin->fetch();

    // 2. Histórico detalhado
    $historico = obter_historico_usuario($pdo, $usuarioId, 100, 0);

} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao buscar seu histórico: ' . $e->getMessage());
    $totalAcoes = 0;
    $ultimoLogin = null;
    $historico = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Meu Histórico de Ações</h3>
        <p class="text-muted small mb-0">Linha do tempo de todas as atividades realizadas por você no sistema</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary px-3 py-2">
            <i class="bi bi-clock-history me-1"></i> <?= $totalAcoes ?> Ações Registradas
        </span>
    </div>
</div>

<!-- Resumo do Usuário Mobile-First -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card card-opi p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Operador Conectado</span>
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars(obter_usuario_nome()) ?></h5>
                    <div class="small text-muted mt-1">
                        Funções ativas: 
                        <?php foreach (obter_funcoes_usuario() as $f): ?>
                            <span class="badge bg-secondary badge-role"><?= htmlspecialchars($f) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card card-opi p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-semibold">Última Autenticação</span>
                    <h5 class="fw-bold mb-0 text-dark">
                        <?= $ultimoLogin ? formatar_data_hora($ultimoLogin['criado_em']) : 'Hoje' ?>
                    </h5>
                    <div class="small text-muted mt-1">
                        IP de Acesso: <code><?= htmlspecialchars($ultimoLogin['ip_origem'] ?? '127.0.0.1') ?></code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Linha do Tempo / Tabela do Histórico do Usuário -->
<div class="card card-opi border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-activity me-2 text-primary"></i> Suas Atividades Recentes
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($historico)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-check display-4 d-block mb-3 text-secondary"></i>
                <h5>Nenhuma ação registrada para sua conta</h5>
                <p class="small">Suas alterações em produtos e estoque aparecerão aqui automaticamente.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data / Hora</th>
                            <th>Ação</th>
                            <th>Entidade / Módulo</th>
                            <th>IP Registrado</th>
                            <th class="text-end">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): 
                            $badgeClass = match(true) {
                                str_contains($h['acao'], 'EXCLUID') || str_contains($h['acao'], 'SAIDA') => 'bg-danger',
                                str_contains($h['acao'], 'CRIAD') || str_contains($h['acao'], 'ENTRADA') => 'bg-success',
                                str_contains($h['acao'], 'EDITAD') || str_contains($h['acao'], 'AJUSTE') => 'bg-warning text-dark',
                                str_contains($h['acao'], 'LOGIN') => 'bg-info text-dark',
                                default => 'bg-secondary'
                            };
                        ?>
                            <tr>
                                <td class="small text-muted text-nowrap"><?= formatar_data_hora($h['criado_em']) ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass ?> font-monospace">
                                        <?= htmlspecialchars($h['acao']) ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <?php if ($h['tabela_afetada']): ?>
                                        <span class="fw-semibold"><?= htmlspecialchars(ucfirst($h['tabela_afetada'])) ?></span>
                                        <?php if ($h['registro_id']): ?>
                                            <span class="text-muted">(#<?= $h['registro_id'] ?>)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted font-monospace"><?= htmlspecialchars($h['ip_origem'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if (!empty($h['detalhes'])): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                data-bs-action="view-details"
                                                data-titulo="Detalhe da Atividade - <?= htmlspecialchars($h['acao']) ?>"
                                                data-detalhes='<?= htmlspecialchars($h['detalhes'], ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="bi bi-eye"></i> Ver Detalhes
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
