<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/logout_action.php - Encerramento seguro da sessão nativa
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validar_csrf_token($csrf)) {
        definir_toast('danger', 'Erro', 'Token de segurança inválido ao encerrar sessão.');
        header('Location: ../index.php');
        exit;
    }

    $usuarioId = obter_usuario_id();
    if ($usuarioId) {
        // Registrar log de auditoria da saída
        registrar_log($pdo, $usuarioId, 'LOGOUT', 'usuarios', $usuarioId, [
            'mensagem' => 'Sessão encerrada voluntariamente pelo usuário'
        ]);
    }

    // Limpar variáveis de sessão
    $_SESSION = [];

    // Excluir cookie da sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruir sessão
    session_destroy();

    // Iniciar nova sessão limpa para exibir toast de confirmação
    session_start();
    definir_toast('info', 'Sessão Encerrada', 'Você saiu do sistema com segurança.');
    header('Location: ../login.php');
    exit;
}

header('Location: ../index.php');
exit;
