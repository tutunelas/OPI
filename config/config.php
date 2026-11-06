<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * Arquivo de Configuração Global
 */

// Iniciar sessões nativas com segurança caso ainda não tenham sido iniciadas
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de cookie de sessão segura
    session_set_cookie_params([
        'lifetime' => 86400, // 24 horas
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Configurações do Banco de Dados MySQL
define('DB_HOST', getenv('DB_HOST') ?: 'sql207.infinityfree.com');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'if0_43036408_OPI');
define('DB_USER', getenv('DB_USER') ?: 'if0_43036408');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'myphouderu');
define('DB_CHARSET', 'utf8mb4');

// Nome da Aplicação
define('APP_NAME', 'OPI - Estoque');
define('APP_VERSION', '1.0.0');

/**
 * Função utilitária para definir mensagens TOAST na sessão
 * Tipos suportados: success, danger, warning, info
 */
function definir_toast($tipo, $titulo, $mensagem) {
    if (!isset($_SESSION['toasts'])) {
        $_SESSION['toasts'] = [];
    }
    $_SESSION['toasts'][] = [
        'tipo' => $tipo,
        'titulo' => $titulo,
        'mensagem' => $mensagem,
        'timestamp' => time()
    ];
}

/**
 * Recupera e limpa os toasts pendentes da sessão
 */
function obter_toasts() {
    $toasts = $_SESSION['toasts'] ?? [];
    unset($_SESSION['toasts']);
    return $toasts;
}

/**
 * Retorna o IP do cliente de forma segura para auditoria
 */
function obter_ip_cliente() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Formata valor numérico para padrão monetário Real (R$)
 */
function formatar_moeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

/**
 * Formata data e hora para padrão brasileiro
 */
function formatar_data_hora($dataStr) {
    if (!$dataStr) return '-';
    $timestamp = strtotime($dataStr);
    return date('d/m/Y H:i:s', $timestamp);
}

/**
 * Formata data simples para padrão brasileiro
 */
function formatar_data($dataStr) {
    if (!$dataStr) return '-';
    $timestamp = strtotime($dataStr);
    return date('d/m/Y', $timestamp);
}
