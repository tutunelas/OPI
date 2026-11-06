<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * perfil.php - Perfil do Usuário e Alteração Segura de Senha
 */

require_once __DIR__ . '/bd.php';
require_once __DIR__ . '/config/auth.php';

exigir_login();

$tituloPagina = 'Meu Perfil | OPI Estoque';
$usuarioId = (int)obter_usuario_id();

try {
    $stmtUser = $pdo->prepare("SELECT id, nome, email, criado_em, atualizado_em FROM usuarios WHERE id = :id");
    $stmtUser->execute([':id' => $usuarioId]);
    $usuario = $stmtUser->fetch();
} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao recuperar dados do usuário: ' . $e->getMessage());
    $usuario = null;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pb-3 mb-4 border-bottom gap-2">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Meu Perfil e Segurança</h3>
        <p class="text-muted small mb-0">Atualize seus dados pessoais e modifique sua senha com criptografia Bcrypt</p>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card card-opi border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-gear me-2 text-primary"></i> Dados Cadastrais
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="actions/perfil_salvar.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="perfil_nome" class="form-label small fw-semibold">Nome Completo</label>
                            <input type="text" class="form-control" name="nome" id="perfil_nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="perfil_email" class="form-label small fw-semibold">E-mail Cadastrado</label>
                            <input type="email" class="form-control" name="email" id="perfil_email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="mb-4 p-3 bg-light rounded">
                        <h6 class="fw-semibold small text-uppercase text-secondary mb-2">Funções e Níveis de Acesso Atribuídos</h6>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach (obter_funcoes_usuario() as $func): ?>
                                <span class="badge bg-primary badge-role"><?= htmlspecialchars($func) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted d-block mt-2">Para alterações de permissão, contate o Administrador.</small>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-shield-lock me-1 text-primary"></i> Alterar Senha (Opcional)
                    </h6>
                    <p class="text-muted small mb-3">Preencha os campos abaixo apenas se desejar substituir sua senha atual.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="senha_atual" class="form-label small fw-semibold">Senha Atual</label>
                            <input type="password" class="form-control" name="senha_atual" id="senha_atual" placeholder="••••••••">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="nova_senha" class="form-label small fw-semibold">Nova Senha</label>
                            <input type="password" class="form-control" name="nova_senha" id="nova_senha" placeholder="Mínimo 6 dígitos">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="confirmar_senha" class="form-label small fw-semibold">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" name="confirmar_senha" id="confirmar_senha" placeholder="Repita a nova senha">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary btn-action">
                            <i class="bi bi-check2-circle"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
