<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/login_action.php - Autenticação com Bcrypt e Sessões Nativas
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// Validação do Token CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!validar_csrf_token($csrf)) {
    definir_toast('danger', 'Erro de Segurança', 'Sessão ou token inválido. Tente novamente.');
    header('Location: ../login.php');
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = trim($_POST['senha'] ?? '');

if (!$email || empty($senha)) {
    definir_toast('warning', 'Campos Obrigatórios', 'Por favor, informe seu e-mail e senha.');
    header('Location: ../login.php');
    exit;
}

// Consulta segura via PDO preparado
$sql = "SELECT id, nome, email, senha, status FROM usuarios WHERE email = :email LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch();

// Validação de credenciais usando algoritmo Bcrypt seguro
if ($usuario && password_verify($senha, $usuario['senha'])) {
    if ($usuario['status'] !== 'ativo') {
        definir_toast('danger', 'Conta Inativa', 'Sua conta está desativada. Entre em contato com o administrador.');
        header('Location: ../login.php');
        exit;
    }

    // Carregar todas as funções atribuídas ao usuário (relação N:N)
    $sqlFuncoes = "SELECT f.nome FROM funcoes f
                   INNER JOIN usuario_funcoes uf ON uf.funcao_id = f.id
                   WHERE uf.usuario_id = :usuario_id";
    $stmtF = $pdo->prepare($sqlFuncoes);
    $stmtF->execute([':usuario_id' => $usuario['id']]);
    $funcoes = $stmtF->fetchAll(PDO::FETCH_COLUMN);

    // Regenerar ID de sessão para prevenir Session Fixation
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_funcoes'] = !empty($funcoes) ? $funcoes : ['Visualizador'];

    // Registro de auditoria do login bem-sucedido
    registrar_log($pdo, $usuario['id'], 'LOGIN', 'usuarios', $usuario['id'], [
        'mensagem' => 'Login realizado com sucesso',
        'funcoes'  => $_SESSION['usuario_funcoes']
    ]);

    definir_toast('success', 'Bem-vindo(a)!', "Olá, {$usuario['nome']}. Você está autenticado no OPI Estoque.");
    header('Location: ../index.php');
    exit;
} else {
    // Registro de auditoria de tentativa falha
    registrar_log($pdo, null, 'LOGIN_FALHA', 'usuarios', null, [
        'email_tentativa' => $email,
        'motivo' => 'Credenciais incorretas'
    ]);

    definir_toast('danger', 'Falha no Login', 'E-mail ou senha incorretos.');
    header('Location: ../login.php');
    exit;
}
