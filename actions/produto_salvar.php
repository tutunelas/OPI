<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/produto_salvar.php - Criação e Edição de Produtos com Log de Auditoria
 */

require_once __DIR__ . '/../bd.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/logger.php';

exigir_login();
exigir_funcao(['Administrador', 'Gerente de Estoque', 'Operador de Estoque']);

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

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$codigo_sku = strtoupper(trim($_POST['codigo_sku'] ?? ''));
$nome = trim($_POST['nome'] ?? '');
$categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
$descricao = trim($_POST['descricao'] ?? '');
$unidade_medida = strtoupper(trim($_POST['unidade_medida'] ?? 'UN'));
$preco_custo = (float)str_replace(',', '.', $_POST['preco_custo'] ?? '0');
$preco_venda = (float)str_replace(',', '.', $_POST['preco_venda'] ?? '0');
$quantidade_estoque = (int)($_POST['quantidade_estoque'] ?? 0);
$estoque_minimo = (int)($_POST['estoque_minimo'] ?? 5);
$localizacao = trim($_POST['localizacao'] ?? '');

// Validações básicas
if (empty($codigo_sku) || empty($nome)) {
    definir_toast('warning', 'Campos Obrigatórios', 'O código SKU e o Nome do produto são obrigatórios.');
    header('Location: ../produtos.php');
    exit;
}

$usuarioId = obter_usuario_id();

try {
    // Verificar unicidade do código SKU
    $sqlVerifica = "SELECT id FROM produtos WHERE codigo_sku = :sku" . ($id ? " AND id != :id" : "");
    $stmtVerifica = $pdo->prepare($sqlVerifica);
    $paramsVerifica = [':sku' => $codigo_sku];
    if ($id) {
        $paramsVerifica[':id'] = $id;
    }
    $stmtVerifica->execute($paramsVerifica);
    if ($stmtVerifica->fetch()) {
        definir_toast('danger', 'Código Duplicado', "O SKU '{$codigo_sku}' já pertence a outro produto cadastrado.");
        header('Location: ../produtos.php');
        exit;
    }

    if ($id) {
        // --- ATUALIZAÇÃO ---
        // Buscar dados antigos para computar log de auditoria
        $stmtAntigo = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
        $stmtAntigo->execute([':id' => $id]);
        $dadosAntigos = $stmtAntigo->fetch();

        if (!$dadosAntigos) {
            definir_toast('danger', 'Não Encontrado', 'O produto informado não existe no sistema.');
            header('Location: ../produtos.php');
            exit;
        }

        $sql = "UPDATE produtos SET 
                    codigo_sku = :sku,
                    nome = :nome,
                    categoria_id = :categoria_id,
                    descricao = :descricao,
                    unidade_medida = :unidade_medida,
                    preco_custo = :preco_custo,
                    preco_venda = :preco_venda,
                    quantidade_estoque = :quantidade_estoque,
                    estoque_minimo = :estoque_minimo,
                    localizacao = :localizacao
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sku' => $codigo_sku,
            ':nome' => $nome,
            ':categoria_id' => $categoria_id,
            ':descricao' => $descricao,
            ':unidade_medida' => $unidade_medida,
            ':preco_custo' => $preco_custo,
            ':preco_venda' => $preco_venda,
            ':quantidade_estoque' => $quantidade_estoque,
            ':estoque_minimo' => $estoque_minimo,
            ':localizacao' => $localizacao,
            ':id' => $id
        ]);

        // Registrar alterações no log
        $alteracoes = [];
        $camposComparar = ['codigo_sku', 'nome', 'categoria_id', 'unidade_medida', 'preco_custo', 'preco_venda', 'quantidade_estoque', 'estoque_minimo', 'localizacao'];
        $novosDados = [
            'codigo_sku' => $codigo_sku, 'nome' => $nome, 'categoria_id' => $categoria_id,
            'unidade_medida' => $unidade_medida, 'preco_custo' => $preco_custo, 'preco_venda' => $preco_venda,
            'quantidade_estoque' => $quantidade_estoque, 'estoque_minimo' => $estoque_minimo, 'localizacao' => $localizacao
        ];

        foreach ($camposComparar as $campo) {
            if ((string)$dadosAntigos[$campo] !== (string)$novosDados[$campo]) {
                $alteracoes[$campo] = [
                    'anterior' => $dadosAntigos[$campo],
                    'novo' => $novosDados[$campo]
                ];
            }
        }

        registrar_log($pdo, $usuarioId, 'PRODUTO_EDITADO', 'produtos', $id, [
            'produto' => $nome,
            'sku' => $codigo_sku,
            'mudancas' => $alteracoes
        ]);

        definir_toast('success', 'Produto Atualizado', "O produto '{$nome}' foi alterado com sucesso.");
    } else {
        // --- INSERÇÃO ---
        $sql = "INSERT INTO produtos (codigo_sku, nome, categoria_id, descricao, unidade_medida, preco_custo, preco_venda, quantidade_estoque, estoque_minimo, localizacao, criado_em)
                VALUES (:sku, :nome, :categoria_id, :descricao, :unidade_medida, :preco_custo, :preco_venda, :quantidade_estoque, :estoque_minimo, :localizacao, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sku' => $codigo_sku,
            ':nome' => $nome,
            ':categoria_id' => $categoria_id,
            ':descricao' => $descricao,
            ':unidade_medida' => $unidade_medida,
            ':preco_custo' => $preco_custo,
            ':preco_venda' => $preco_venda,
            ':quantidade_estoque' => $quantidade_estoque,
            ':estoque_minimo' => $estoque_minimo,
            ':localizacao' => $localizacao
        ]);

        $novoId = $pdo->lastInsertId();

        // Registrar auditoria da inclusão
        registrar_log($pdo, $usuarioId, 'PRODUTO_CRIADO', 'produtos', $novoId, [
            'produto' => $nome,
            'sku' => $codigo_sku,
            'quantidade_inicial' => $quantidade_estoque,
            'preco_venda' => $preco_venda
        ]);

        definir_toast('success', 'Produto Cadastrado', "O produto '{$nome}' foi incluído com sucesso no estoque.");
    }

} catch (PDOException $e) {
    definir_toast('danger', 'Erro no Banco de Dados', 'Não foi possível salvar o produto: ' . $e->getMessage());
}

header('Location: ../produtos.php');
exit;
