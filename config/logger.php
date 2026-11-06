<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * config/logger.php - Motor de Registro e Consulta de Auditoria
 */

require_once __DIR__ . '/config.php';

/**
 * Registra uma ação executada no sistema para auditoria futura
 * 
 * @param PDO $pdo Instância ativa do PDO
 * @param int|null $usuario_id ID do usuário responsável (ou null para ações de sistema)
 * @param string $acao Identificador da ação (ex: LOGIN, PRODUTO_CRIADO, PRODUTO_EDITADO, ENTRADA_ESTOQUE)
 * @param string|null $tabela_afetada Nome da tabela relacionada (ex: produtos, usuarios, movimentacoes)
 * @param int|null $registro_id ID do registro impactado
 * @param array|string|null $detalhes Informações adicionais / diff de alterações
 * @return bool Sucesso do registro
 */
function registrar_log($pdo, $usuario_id, $acao, $tabela_afetada = null, $registro_id = null, $detalhes = null) {
    try {
        if (is_array($detalhes)) {
            $detalhes = json_encode($detalhes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $ip = obter_ip_cliente();

        $sql = "INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, detalhes, ip_origem, criado_em)
                VALUES (:usuario_id, :acao, :tabela_afetada, :registro_id, :detalhes, :ip_origem, NOW())";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':usuario_id'       => $usuario_id,
            ':acao'             => strtoupper(trim($acao)),
            ':tabela_afetada'   => $tabela_afetada,
            ':registro_id'      => $registro_id,
            ':detalhes'         => $detalhes,
            ':ip_origem'        => $ip
        ]);
    } catch (PDOException $e) {
        // Falha no log não deve interromper a operação principal, mas registramos no error_log
        error_log("Erro ao registrar log de auditoria: " . $e->getMessage());
        return false;
    }
}

/**
 * Recupera logs gerais com suporte a filtros e paginação
 */
function obter_logs_auditoria($pdo, $filtros = [], $limite = 50, $offset = 0) {
    $where = [];
    $params = [];

    if (!empty($filtros['usuario_id'])) {
        $where[] = "l.usuario_id = :usuario_id";
        $params[':usuario_id'] = $filtros['usuario_id'];
    }

    if (!empty($filtros['acao'])) {
        $where[] = "l.acao LIKE :acao";
        $params[':acao'] = '%' . $filtros['acao'] . '%';
    }

    if (!empty($filtros['tabela'])) {
        $where[] = "l.tabela_afetada = :tabela";
        $params[':tabela'] = $filtros['tabela'];
    }

    if (!empty($filtros['data_inicio'])) {
        $where[] = "l.criado_em >= :data_inicio";
        $params[':data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
    }

    if (!empty($filtros['data_fim'])) {
        $where[] = "l.criado_em <= :data_fim";
        $params[':data_fim'] = $filtros['data_fim'] . ' 23:59:59';
    }

    $sqlWhere = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT l.*, u.nome AS usuario_nome, u.email AS usuario_email
            FROM logs_auditoria l
            LEFT JOIN usuarios u ON l.usuario_id = u.id
            {$sqlWhere}
            ORDER BY l.id DESC
            LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Recupera o histórico específico do usuário autenticado
 * Atende ao requisito: "todos os usuarios devem conseguir ver seus historicos de uso de sistema"
 */
function obter_historico_usuario($pdo, $usuario_id, $limite = 50, $offset = 0) {
    $sql = "SELECT * FROM logs_auditoria
            WHERE usuario_id = :usuario_id
            ORDER BY id DESC
            LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario_id', (int)$usuario_id, PDO::PARAM_INT);
    $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
