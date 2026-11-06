<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/movimentacao_salvar.php - Entradas, Saídas e Ajustes de Estoque
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

exigir_login();
exigir_funcao(['Administrador', 'Gerente de Estoque', 'Operador de Estoque']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../movimentacoes.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validar_csrf_token($csrf)) {
    definir_toast('danger', 'Erro de Segurança', 'Token de formulário expirado ou inválido.');
    header('Location: ../movimentacoes.php');
    exit;
}

$produto_id = (int)($_POST['produto_id'] ?? 0);
$tipo = strtolower(trim($_POST['tipo'] ?? ''));
$quantidade = (int)($_POST['quantidade'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
$origem = $_POST['origem'] ?? 'movimentacoes';

if ($produto_id <= 0 || !in_array($tipo, ['entrada', 'saida', 'ajuste']) || $quantidade <= 0) {
    definir_toast('warning', 'Dados Inválidos', 'Informe o produto, tipo de movimentação válido e quantidade positiva.');
    header("Location: ../" . ($origem === 'produtos' ? 'produtos.php' : 'movimentacoes.php'));
    exit;
}

if (empty($motivo)) {
    $motivo = match($tipo) {
        'entrada' => 'Recebimento de mercadoria / Compra',
        'saida'   => 'Saída para venda ou consumo interno',
        'ajuste'  => 'Ajuste de inventário periódico'
    };
}

$usuarioId = obter_usuario_id();

try {
    // Iniciar Transação Atômica via PDO para garantia de integridade do estoque
    $pdo->beginTransaction();

    // Bloquear linha do produto para leitura/escrita concorrente segura
    $stmtBusca = $pdo->prepare("SELECT id, nome, codigo_sku, quantidade_estoque, estoque_minimo FROM produtos WHERE id = :id FOR UPDATE");
    $stmtBusca->execute([':id' => $produto_id]);
    $produto = $stmtBusca->fetch();

    if (!$produto) {
        $pdo->rollBack();
        definir_toast('danger', 'Não Encontrado', 'Produto não encontrado.');
        header('Location: ../movimentacoes.php');
        exit;
    }

    $qtdAnterior = (int)$produto['quantidade_estoque'];
    $qtdResultante = $qtdAnterior;

    // Calcular novo estoque baseado na operação solicitada
    if ($tipo === 'entrada') {
        $qtdResultante = $qtdAnterior + $quantidade;
    } elseif ($tipo === 'saida') {
        if ($qtdAnterior < $quantidade) {
            $pdo->rollBack();
            definir_toast('danger', 'Estoque Insuficiente', "Saldo atual ({$qtdAnterior}) é menor do que a saída solicitada ({$quantidade}).");
            header("Location: ../" . ($origem === 'produtos' ? 'produtos.php' : 'movimentacoes.php'));
            exit;
        }
        $qtdResultante = $qtdAnterior - $quantidade;
    } elseif ($tipo === 'ajuste') {
        // No ajuste, a quantidade informada se torna a nova quantidade exata em estoque
        $qtdResultante = $quantidade;
    }

    // 1. Atualizar o estoque atual do produto
    $stmtUpd = $pdo->prepare("UPDATE produtos SET quantidade_estoque = :novo_estoque WHERE id = :id");
    $stmtUpd->execute([
        ':novo_estoque' => $qtdResultante,
        ':id' => $produto_id
    ]);

    // 2. Registrar o histórico na tabela de movimentações
    $stmtMov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, usuario_id, tipo, quantidade, quantidade_anterior, quantidade_resultante, motivo, criado_em)
                              VALUES (:prod_id, :user_id, :tipo, :qtd, :qtd_ant, :qtd_res, :motivo, NOW())");
    $stmtMov->execute([
        ':prod_id'  => $produto_id,
        ':user_id'  => $usuarioId,
        ':tipo'     => $tipo,
        ':qtd'      => $quantidade,
        ':qtd_ant'  => $qtdAnterior,
        ':qtd_res'  => $qtdResultante,
        ':motivo'   => $motivo
    ]);
    $movId = $pdo->lastInsertId();

    // 3. Registrar o log de auditoria do sistema
    $acaoAuditoria = match($tipo) {
        'entrada' => 'ESTOQUE_ENTRADA',
        'saida'   => 'ESTOQUE_SAIDA',
        'ajuste'  => 'ESTOQUE_AJUSTE'
    };

    registrar_log($pdo, $usuarioId, $acaoAuditoria, 'movimentacoes', $movId, [
        'produto_id' => $produto_id,
        'produto_nome' => $produto['nome'],
        'sku' => $produto['codigo_sku'],
        'tipo' => $tipo,
        'quantidade' => $quantidade,
        'saldo_anterior' => $qtdAnterior,
        'saldo_atual' => $qtdResultante,
        'motivo' => $motivo
    ]);

    // Commit da transação
    $pdo->commit();

    // Mensagem de sucesso com verificação de estoque baixo
    $alertaEstoque = '';
    if ($qtdResultante <= (int)$produto['estoque_minimo']) {
        $alertaEstoque = " Atenção: o estoque atual ({$qtdResultante}) está no limite mínimo ({$produto['estoque_minimo']}).";
    }

    definir_toast(
        $qtdResultante <= (int)$produto['estoque_minimo'] ? 'warning' : 'success',
        'Movimentação Concluída',
        "Operação de {$tipo} para '{$produto['nome']}' realizada com sucesso! Novo saldo: {$qtdResultante}.{$alertaEstoque}"
    );

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    definir_toast('danger', 'Erro na Movimentação', 'Falha ao processar o estoque: ' . $e->getMessage());
}

header("Location: ../" . ($origem === 'produtos' ? 'produtos.php' : 'movimentacoes.php'));
exit;
