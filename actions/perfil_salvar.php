<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/perfil_salvar.php - Atualização dos próprios dados e senha
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../perfil.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validar_csrf_token($csrf)) {
    definir_toast('danger', 'Erro de Segurança', 'Token expirado ou inválido.');
    header('Location: ../perfil.php');
    exit;
}

$usuarioId = (int)obter_usuario_id();
$nome = trim($_POST['nome'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senhaAtual = trim($_POST['senha_atual'] ?? '');
$novaSenha = trim($_POST['nova_senha'] ?? '');
$confirmarSenha = trim($_POST['confirmar_senha'] ?? '');

if (empty($nome) || !$email) {
    definir_toast('warning', 'Campos Obrigatórios', 'Nome e e-mail válidos são obrigatórios.');
    header('Location: ../perfil.php');
    exit;
}

try {
    // Buscar dados atuais do usuário
    $stmtUser = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE id = :id");
    $stmtUser->execute([':id' => $usuarioId]);
    $usuario = $stmtUser->fetch();

    if (!$usuario) {
        definir_toast('danger', 'Erro', 'Usuário não encontrado.');
        header('Location: ../perfil.php');
        exit;
    }

    // Se forneceu nova senha, deve validar senha atual e conferência
    $trocarSenha = false;
    $hashNovaSenha = null;

    if (!empty($novaSenha)) {
        if (empty($senhaAtual)) {
            definir_toast('warning', 'Senha Atual Obrigatória', 'Para alterar a senha, informe sua senha atual.');
            header('Location: ../perfil.php');
            exit;
        }

        if (!password_verify($senhaAtual, $usuario['senha'])) {
            definir_toast('danger', 'Senha Incorreta', 'A senha atual informada está incorreta.');
            header('Location: ../perfil.php');
            exit;
        }

        if ($novaSenha !== $confirmarSenha) {
            definir_toast('warning', 'Divergência', 'A nova senha e a confirmação não coincidem.');
            header('Location: ../perfil.php');
            exit;
        }

        if (strlen($novaSenha) < 6) {
            definir_toast('warning', 'Senha Fraca', 'A nova senha deve ter no mínimo 6 caracteres.');
            header('Location: ../perfil.php');
            exit;
        }

        $trocarSenha = true;
        $hashNovaSenha = password_hash($novaSenha, PASSWORD_BCRYPT);
    }

    if ($trocarSenha) {
        $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $hashNovaSenha,
            ':id'    => $usuarioId
        ]);
    } else {
        $sql = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':id'    => $usuarioId
        ]);
    }

    // Atualizar dados na sessão nativa
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_email'] = $email;

    // Registrar no log de auditoria
    registrar_log($pdo, $usuarioId, 'PERFIL_ATUALIZADO', 'usuarios', $usuarioId, [
        'nome' => $nome,
        'email' => $email,
        'senha_modificada' => $trocarSenha
    ]);

    definir_toast('success', 'Perfil Atualizado', 'Seus dados foram salvos com sucesso.');

} catch (PDOException $e) {
    definir_toast('danger', 'Erro', 'Falha ao salvar dados de perfil: ' . $e->getMessage());
}

header('Location: ../perfil.php');
exit;
