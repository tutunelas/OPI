<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * bd.php - Conexão Segura com Banco de Dados MySQL via PDO
 */

require_once __DIR__ . '/config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Previne SQL Injection de forma nativa no driver MySQL
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Se o banco de dados não foi criado ou as credenciais estão incorretas
    $erroMsg = $e->getMessage();
    
    // Se a requisição atual não for a tela de instalação, podemos sugerir o instalador
    $scriptAtual = basename($_SERVER['PHP_SELF'] ?? '');
    if ($scriptAtual !== 'instalar.php') {
        // Se a tabela ou banco não existe, redireciona amigavelmente para o instalador
        if (strpos($erroMsg, "Unknown database") !== false || strpos($erroMsg, "Base table or view not found") !== false) {
            header('Location: instalar.php');
            exit;
        }
    }
    
    // Registrar erro crítico sem expor credenciais na tela do usuário
    die("<h3>Erro de Conexão com o Banco de Dados</h3>" .
        "<p>Não foi possível conectar ao banco de dados MySQL via PDO.</p>" .
        "<p><strong>Detalhes técnicos:</strong> " . htmlspecialchars($erroMsg) . "</p>" .
        "<p><a href='instalar.php' class='btn btn-primary'>Clique aqui para executar o Assistente de Instalação</a></p>");
}

/**
 * Função utilitária para obter a instância PDO ativa
 */
function obter_conexao() {
    global $pdo;
    return $pdo;
}
