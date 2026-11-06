<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/usuario_excluir.php - Exclusão de usuário com auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

exigir_login();
exigir_funcao(['Administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../usuarios.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validar_csrf_token($csrf)) {
    definir_toast('danger', 'Erro de Segurança', 'Token expirado ou inválido.');
    header('Location: ../usuarios.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$adminLogadoId = (int)obter_usuario_id();

if ($id <= 0) {
    definir_toast('warning', 'Identificador Inválido', 'Usuário não especificado.');
    header('Location: ../usuarios.php');
    exit;
}

if ($id === $adminLogadoId) {
    definir_toast('danger', 'Operação Não Permitida', 'Você não pode excluir sua própria conta enquanto estiver logado.');
    header('Location: ../usuarios.php');
    exit;
}

try {
    $stmtBusca = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id");
    $stmtBusca->execute([':id' => $id]);
    $user = $stmtBusca->fetch();

    if (!$user) {
        definir_toast('danger', 'Não Encontrado', 'Usuário não localizado no sistema.');
        header('Location: ../usuarios.php');
        exit;
    }

    $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    registrar_log($pdo, $adminLogadoId, 'USUARIO_EXCLUIDO', 'usuarios', $id, [
        'nome' => $user['nome'],
        'email' => $user['email']
    ]);

    definir_toast('success', 'Usuário Removido', "O usuário '{$user['nome']}' ({$user['email']}) foi excluído com sucesso.");

} catch (PDOException $e) {
    definir_toast('danger', 'Erro na Exclusão', 'Não foi possível excluir o usuário: ' . $e->getMessage());
}

header('Location: ../usuarios.php');
exit;
