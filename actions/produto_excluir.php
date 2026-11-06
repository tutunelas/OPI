<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/produto_excluir.php - Exclusão de produto com auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

exigir_login();
exigir_funcao(['Administrador', 'Gerente de Estoque']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../produtos.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validar_csrf_token($csrf)) {
    definir_toast('danger', 'Erro de Segurança', 'Token de formulário expirado ou inválido.');
    header('Location: ../produtos.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    definir_toast('warning', 'Identificador Inválido', 'Produto não especificado para exclusão.');
    header('Location: ../produtos.php');
    exit;
}

$usuarioId = obter_usuario_id();

try {
    // Buscar informações do produto para registro no log de auditoria
    $stmtBusca = $pdo->prepare("SELECT nome, codigo_sku, quantidade_estoque FROM produtos WHERE id = :id");
    $stmtBusca->execute([':id' => $id]);
    $prod = $stmtBusca->fetch();

    if (!$prod) {
        definir_toast('danger', 'Não Encontrado', 'O produto selecionado não existe ou já foi excluído.');
        header('Location: ../produtos.php');
        exit;
    }

    // Exclusão segura com PDO
    $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    // Registrar no log de auditoria futura
    registrar_log($pdo, $usuarioId, 'PRODUTO_EXCLUIDO', 'produtos', $id, [
        'nome' => $prod['nome'],
        'sku' => $prod['codigo_sku'],
        'quantidade_em_estoque_na_exclusao' => $prod['quantidade_estoque']
    ]);

    definir_toast('success', 'Produto Excluído', "O produto '{$prod['nome']}' ({$prod['codigo_sku']}) foi removido com sucesso.");

} catch (PDOException $e) {
    definir_toast('danger', 'Erro na Exclusão', 'Não foi possível excluir o produto: ' . $e->getMessage());
}

header('Location: ../produtos.php');
exit;
