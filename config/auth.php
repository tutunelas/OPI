<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * config/auth.php - Gerenciamento de Sessão e Autenticação
 */

require_once __DIR__ . '/config.php';

/**
 * Verifica se o usuário atual está logado no sistema
 */
function esta_logado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtém o ID do usuário atualmente autenticado
 */
function obter_usuario_id() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Obtém o nome do usuário logado
 */
function obter_usuario_nome() {
    return $_SESSION['usuario_nome'] ?? 'Convidado';
}

/**
 * Obtém as funções atribuídas ao usuário logado (Array de nomes das funções)
 * Suporte nativo ao requisito: "um usuário pode ter multiplas funções dentro do sistema"
 */
function obter_funcoes_usuario() {
    return $_SESSION['usuario_funcoes'] ?? [];
}

/**
 * Verifica se o usuário logado possui uma função específica
 */
function usuario_tem_funcao($nomeFuncao) {
    if (!esta_logado()) {
        return false;
    }
    
    // Se for Administrador, possui acesso amplo
    $funcoes = obter_funcoes_usuario();
    if (in_array('Administrador', $funcoes)) {
        return true;
    }

    return in_array($nomeFuncao, $funcoes);
}

/**
 * Verifica se o usuário possui ao menos UMA das funções fornecidas
 */
function usuario_tem_qualquer_funcao(array $listaFuncoes) {
    if (!esta_logado()) {
        return false;
    }

    $funcoes = obter_funcoes_usuario();
    if (in_array('Administrador', $funcoes)) {
        return true;
    }

    foreach ($listaFuncoes as $func) {
        if (in_array($func, $funcoes)) {
            return true;
        }
    }

    return false;
}

/**
 * Protege rotas exigindo que o usuário esteja autenticado
 */
function exigir_login() {
    if (!esta_logado()) {
        definir_toast('warning', 'Acesso Restrito', 'Por favor, realize login para acessar o sistema.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Protege rotas exigindo funções específicas
 */
function exigir_funcao(array $funcoesPermitidas) {
    exigir_login();
    if (!usuario_tem_qualquer_funcao($funcoesPermitidas)) {
        definir_toast('danger', 'Acesso Negado', 'Seu usuário não possui permissão para executar esta ação.');
        header('Location: index.php');
        exit;
    }
}

/**
 * Gera ou recupera um token CSRF seguro para formulários
 */
function obter_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF submetido
 */
function validar_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
