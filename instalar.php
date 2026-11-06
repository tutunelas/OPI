<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * instalar.php - Assistente Visual de Instalação e Migração do Banco de Dados
 */

require_once __DIR__ . '/config/config.php';

$mensagem = '';
$tipoAlerta = '';
$instalado = false;

// Tentar conectar sem especificar o banco para poder criá-lo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar_instalacao'])) {
    try {
        $dsnSemBanco = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdoInit = new PDO($dsnSemBanco, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);

        // Ler arquivo sql/database.sql
        $caminhoSql = __DIR__ . '/sql/database.sql';
        if (!file_exists($caminhoSql)) {
            throw new Exception("O arquivo de script sql/database.sql não foi localizado.");
        }

        $sqlConteudo = file_get_contents($caminhoSql);

        // Executar script SQL
        $pdoInit->exec($sqlConteudo);

        $mensagem = "O banco de dados <strong>opi_estoque</strong> e todas as tabelas foram configuradas com sucesso! As sementes iniciais foram aplicadas.";
        $tipoAlerta = "success";
        $instalado = true;

    } catch (Exception $e) {
        $mensagem = "Falha durante a instalação do banco de dados: " . htmlspecialchars($e->getMessage());
        $tipoAlerta = "danger";
    }
} else {
    // Checar se já está instalado
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdoCheck = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]);
        $check = $pdoCheck->query("SHOW TABLES LIKE 'produtos'");
        if ($check && $check->rowCount() > 0) {
            $instalado = true;
            $mensagem = "O banco de dados já está instalado e pronto para operar!";
            $tipoAlerta = "info";
        }
    } catch (Exception $e) {
        // Ainda não configurado
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistente de Instalação | OPI Estoque</title>
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            
            <div class="card card-opi border-0 shadow-lg p-3 p-sm-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 56px; height: 56px;">
                        <i class="bi bi-database-check fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-0">Instalação do OPI</h3>
                    <p class="text-muted small">Configuração automática do banco de dados MySQL via PDO</p>
                </div>

                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $tipoAlerta ?> d-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="bi <?= $tipoAlerta === 'success' ? 'bi-check-circle-fill' : ($tipoAlerta === 'info' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill') ?> fs-4"></i>
                        <div><?= $mensagem ?></div>
                    </div>
                <?php endif; ?>

                <div class="bg-light p-3 rounded mb-4">
                    <h6 class="fw-bold mb-2 text-dark small text-uppercase">Parâmetros de Conexão Atuais</h6>
                    <ul class="list-unstyled small mb-0 text-muted">
                        <li><strong>Host:</strong> <?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></li>
                        <li><strong>Banco de Dados:</strong> <?= htmlspecialchars(DB_NAME) ?></li>
                        <li><strong>Usuário MySQL:</strong> <?= htmlspecialchars(DB_USER) ?></li>
                        <li><strong>Charset:</strong> <?= htmlspecialchars(DB_CHARSET) ?></li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold mb-2 text-dark">Recursos Configurados:</h6>
                    <div class="row g-2 small">
                        <div class="col-6"><i class="bi bi-check-lg text-success me-1"></i> Tabela de Produtos</div>
                        <div class="col-6"><i class="bi bi-check-lg text-success me-1"></i> Categorias</div>
                        <div class="col-6"><i class="bi bi-check-lg text-success me-1"></i> Movimentações</div>
                        <div class="col-6"><i class="bi bi-check-lg text-success me-1"></i> Logs de Auditoria</div>
                        <div class="col-6"><i class="bi bi-check-lg text-success me-1"></i> Múltiplas Funções</div>
                        <div class="col-6"><i class="bi bi-check-lg text-success me-1"></i> Hash Bcrypt</div>
                    </div>
                </div>

                <form method="POST" action="instalar.php">
                    <input type="hidden" name="executar_instalacao" value="1">
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-play-circle me-1"></i> <?= $instalado ? 'Reinstalar / Atualizar Banco de Dados' : 'Executar Instalação do Banco' ?>
                        </button>
                        
                        <?php if ($instalado): ?>
                            <a href="login.php" class="btn btn-success py-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Acessar Tela de Login
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted small">
                    Administrador padrão: <code>admin@opi.com</code> / Senha: <code>adm1234</code>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
