<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * actions/usuario_salvar.php - Criação e Edição de Usuários com Múltiplas Funções
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

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$nome = trim($_POST['nome'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = trim($_POST['senha'] ?? '');
$status = in_array($_POST['status'] ?? '', ['ativo', 'inativo']) ? $_POST['status'] : 'ativo';
$funcoesSelecionadas = $_POST['funcoes'] ?? []; // Array de IDs de funções

if (empty($nome) || !$email) {
    definir_toast('warning', 'Campos Obrigatórios', 'Nome e e-mail válidos são obrigatórios.');
    header('Location: ../usuarios.php');
    exit;
}

if (!$id && empty($senha)) {
    definir_toast('warning', 'Senha Obrigatória', 'Informe uma senha segura para cadastrar o usuário.');
    header('Location: ../usuarios.php');
    exit;
}

$adminLogadoId = obter_usuario_id();

try {
    // Verificar se o e-mail já pertence a outro usuário
    $sqlEmail = "SELECT id FROM usuarios WHERE email = :email" . ($id ? " AND id != :id" : "");
    $stmtE = $pdo->prepare($sqlEmail);
    $paramsE = [':email' => $email];
    if ($id) $paramsE[':id'] = $id;
    $stmtE->execute($paramsE);
    if ($stmtE->fetch()) {
        definir_toast('danger', 'E-mail em Uso', "O e-mail '{$email}' já está associado a outro usuário.");
        header('Location: ../usuarios.php');
        exit;
    }

    $pdo->beginTransaction();

    if ($id) {
        // --- EDIÇÃO DE USUÁRIO EXISTENTE ---
        if (!empty($senha)) {
            // Nova senha fornecida: criptografar com Bcrypt seguro
            $hashSenha = password_hash($senha, PASSWORD_BCRYPT);
            $sqlUp = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, status = :status WHERE id = :id";
            $stmtUp = $pdo->prepare($sqlUp);
            $stmtUp->execute([
                ':nome'   => $nome,
                ':email'  => $email,
                ':senha'  => $hashSenha,
                ':status' => $status,
                ':id'     => $id
            ]);
        } else {
            // Mantém a senha existente
            $sqlUp = "UPDATE usuarios SET nome = :nome, email = :email, status = :status WHERE id = :id";
            $stmtUp = $pdo->prepare($sqlUp);
            $stmtUp->execute([
                ':nome'   => $nome,
                ':email'  => $email,
                ':status' => $status,
                ':id'     => $id
            ]);
        }

        // Atualizar Associação de Múltiplas Funções
        $stmtDelF = $pdo->prepare("DELETE FROM usuario_funcoes WHERE usuario_id = :usuario_id");
        $stmtDelF->execute([':usuario_id' => $id]);

        $nomesFuncoes = [];
        if (!empty($funcoesSelecionadas)) {
            $stmtInsF = $pdo->prepare("INSERT INTO usuario_funcoes (usuario_id, funcao_id) VALUES (:usuario_id, :funcao_id)");
            foreach ($funcoesSelecionadas as $fId) {
                $stmtInsF->execute([
                    ':usuario_id' => $id,
                    ':funcao_id'  => (int)$fId
                ]);
            }
        }

        registrar_log($pdo, $adminLogadoId, 'USUARIO_EDITADO', 'usuarios', $id, [
            'nome' => $nome,
            'email' => $email,
            'status' => $status,
            'senha_alterada' => !empty($senha),
            'funcoes_ids' => $funcoesSelecionadas
        ]);

        $pdo->commit();
        definir_toast('success', 'Usuário Atualizado', "O usuário '{$nome}' foi atualizado com sucesso.");

    } else {
        // --- CRIAÇÃO DE NOVO USUÁRIO ---
        // Armazenamento de senha com algoritmo de Hash seguro (Bcrypt)
        $hashSenha = password_hash($senha, PASSWORD_BCRYPT);

        $sqlIns = "INSERT INTO usuarios (nome, email, senha, status, criado_em)
                   VALUES (:nome, :email, :senha, :status, NOW())";
        $stmtIns = $pdo->prepare($sqlIns);
        $stmtIns->execute([
            ':nome'   => $nome,
            ':email'  => $email,
            ':senha'  => $hashSenha,
            ':status' => $status
        ]);
        $novoUsuarioId = $pdo->lastInsertId();

        // Associar Múltiplas Funções selecionadas
        if (!empty($funcoesSelecionadas)) {
            $stmtInsF = $pdo->prepare("INSERT INTO usuario_funcoes (usuario_id, funcao_id) VALUES (:usuario_id, :funcao_id)");
            foreach ($funcoesSelecionadas as $fId) {
                $stmtInsF->execute([
                    ':usuario_id' => $novoUsuarioId,
                    ':funcao_id'  => (int)$fId
                ]);
            }
        }

        registrar_log($pdo, $adminLogadoId, 'USUARIO_CRIADO', 'usuarios', $novoUsuarioId, [
            'nome' => $nome,
            'email' => $email,
            'status' => $status,
            'funcoes_ids' => $funcoesSelecionadas
        ]);

        $pdo->commit();
        definir_toast('success', 'Usuário Criado', "O usuário '{$nome}' foi registrado com suas respectivas funções.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    definir_toast('danger', 'Erro no Servidor', 'Falha ao salvar dados do usuário: ' . $e->getMessage());
}

header('Location: ../usuarios.php');
exit;
