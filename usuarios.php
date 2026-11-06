<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * usuarios.php - Gestão de Usuários e Múltiplas Funções
 * Atende ao requisito: "um usuário pode ter multiplas funções dentro do sistema"
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';

exigir_login();
exigir_funcao(['Administrador']);

$tituloPagina = 'Usuários e Permissões | OPI Estoque';

try {
    // 1. Carregar lista de todas as funções disponíveis
    $stmtFuncoes = $pdo->query("SELECT id, nome, descricao FROM funcoes ORDER BY id ASC");
    $todasFuncoes = $stmtFuncoes->fetchAll();

    // 2. Carregar todos os usuários com suas respectivas funções agrupadas
    $sqlUsuarios = "SELECT u.*, 
                           GROUP_CONCAT(f.nome SEPARATOR ', ') AS funcoes_nomes,
                           GROUP_CONCAT(f.id SEPARATOR ',') AS funcoes_ids
                    FROM usuarios u
                    LEFT JOIN usuario_funcoes uf ON uf.usuario_id = u.id
                    LEFT JOIN funcoes f ON f.id = uf.funcao_id
                    GROUP BY u.id
                    ORDER BY u.id ASC";
    $stmtUsuarios = $pdo->query($sqlUsuarios);
    $usuarios = $stmtUsuarios->fetchAll();

} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao consultar usuários: ' . $e->getMessage());
    $todasFuncoes = [];
    $usuarios = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Gestão de Usuários e Funções</h3>
        <p class="text-muted small mb-0">Atribua múltiplas funções, credenciais Bcrypt e controle de acessos</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary btn-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="prepararModalNovoUsuario()">
            <i class="bi bi-person-plus-fill"></i> Novo Usuário
        </button>
    </div>
</div>

<div class="card card-opi border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($usuarios)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people display-4 d-block mb-3 text-secondary"></i>
                <h5>Nenhum usuário cadastrado</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Nome Completo</th>
                            <th>E-mail</th>
                            <th>Funções Atribuídas (Múltiplas)</th>
                            <th class="text-center">Status</th>
                            <th>Data de Cadastro</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usr): 
                            $nomesF = !empty($usr['funcoes_nomes']) ? explode(', ', $usr['funcoes_nomes']) : ['Visualizador'];
                        ?>
                            <tr>
                                <td class="small text-muted">#<?= $usr['id'] ?></td>
                                <td class="fw-bold text-dark">
                                    <?= htmlspecialchars($usr['nome']) ?>
                                    <?php if ((int)$usr['id'] === (int)obter_usuario_id()): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size: 0.65rem;">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($usr['email']) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($nomesF as $n): ?>
                                            <span class="badge bg-secondary badge-role"><?= htmlspecialchars($n) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($usr['status'] === 'ativo'): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= formatar_data($usr['criado_em']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Editar Usuário -->
                                        <button type="button" 
                                                class="btn btn-outline-primary" 
                                                title="Editar Usuário"
                                                onclick='prepararModalEditarUsuario(<?= json_encode($usr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Excluir Usuário (Se não for o próprio conectado) -->
                                        <?php if ((int)$usr['id'] !== (int)obter_usuario_id()): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Excluir Usuário"
                                                    data-bs-action="confirm-delete"
                                                    data-id="<?= $usr['id'] ?>"
                                                    data-action="actions/usuario_excluir.php"
                                                    data-nome="<?= htmlspecialchars($usr['nome']) ?>">
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
     Modal de Cadastro / Edição de Usuário
     ======================================================== -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUsuarioTitulo">
                    <i class="bi bi-person-fill me-2"></i> Cadastro de Usuário
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="actions/usuario_salvar.php" method="POST">
                <div class="modal-body py-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">
                    <input type="hidden" name="id" id="usr_id" value="">

                    <div class="mb-3">
                        <label for="usr_nome" class="form-label small fw-semibold">Nome Completo *</label>
                        <input type="text" class="form-control" name="nome" id="usr_nome" required>
                    </div>

                    <div class="mb-3">
                        <label for="usr_email" class="form-label small fw-semibold">E-mail *</label>
                        <input type="email" class="form-control" name="email" id="usr_email" required>
                    </div>

                    <div class="mb-3">
                        <label for="usr_senha" class="form-label small fw-semibold">Senha (Criptografada com Bcrypt)</label>
                        <input type="password" class="form-control" name="senha" id="usr_senha" placeholder="••••••••">
                        <small class="text-muted d-block mt-1" id="usr_senha_ajuda">Para novos usuários, a senha é obrigatória.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block">Funções do Usuário (Suporte a Múltiplas Funções) *</label>
                        <div class="border rounded p-2 bg-light">
                            <?php foreach ($todasFuncoes as $f): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input check-funcao" type="checkbox" name="funcoes[]" value="<?= $f['id'] ?>" id="funcao_<?= $f['id'] ?>">
                                    <label class="form-check-label small" for="funcao_<?= $f['id'] ?>">
                                        <strong><?= htmlspecialchars($f['nome']) ?></strong>
                                        <span class="text-muted d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($f['descricao']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="usr_status" class="form-label small fw-semibold">Situação da Conta</label>
                        <select class="form-select" name="status" id="usr_status">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Salvar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepararModalNovoUsuario() {
    document.getElementById('modalUsuarioTitulo').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i> Novo Usuário';
    document.getElementById('usr_id').value = '';
    document.getElementById('usr_nome').value = '';
    document.getElementById('usr_email').value = '';
    document.getElementById('usr_senha').value = '';
    document.getElementById('usr_senha').setAttribute('required', 'required');
    document.getElementById('usr_senha_ajuda').textContent = 'Defina a senha inicial deste usuário.';
    document.getElementById('usr_status').value = 'ativo';

    // Desmarcar todas as funções
    document.querySelectorAll('.check-funcao').forEach(chk => chk.checked = false);
}

function prepararModalEditarUsuario(usr) {
    document.getElementById('modalUsuarioTitulo').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Editar Usuário';
    document.getElementById('usr_id').value = usr.id;
    document.getElementById('usr_nome').value = usr.nome;
    document.getElementById('usr_email').value = usr.email;
    document.getElementById('usr_senha').value = '';
    document.getElementById('usr_senha').removeAttribute('required');
    document.getElementById('usr_senha_ajuda').textContent = 'Deixe em branco para manter a senha atual sem modificações.';
    document.getElementById('usr_status').value = usr.status;

    // Marcar as funções que o usuário já possui
    const funcoesArray = usr.funcoes_ids ? usr.funcoes_ids.split(',') : [];
    document.querySelectorAll('.check-funcao').forEach(chk => {
        chk.checked = funcoesArray.includes(chk.value);
    });

    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
