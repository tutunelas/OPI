<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * login.php - Tela de Autenticação Mobile-First
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

// Se já estiver logado, redireciona para a tela inicial
if (esta_logado()) {
    header('Location: index.php');
    exit;
}

$tituloPagina = 'Entrar | OPI - Controle de Estoque';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?></title>

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 56px; height: 56px;">
                    <i class="bi bi-boxes fs-2"></i>
                </div>
                <h3 class="fw-bold text-dark mb-0">OPI Estoque</h3>
                <p class="text-muted small">Controle Automatizado de Estoque</p>
            </div>

            <div class="card card-opi border-0 shadow-lg p-3 p-sm-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-center mb-3">Identifique-se</h5>
                    
                    <form action="actions/login_action.php" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">E-mail de Acesso</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="seu.email@empresa.com" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label small fw-semibold">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mt-2 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Acessar Painel
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top small text-muted">
                        <div class="p-2 bg-light rounded text-center">
                            <strong>Acesso Padrão:</strong><br>
                            <code>admin@opi.com</code> / <code>adm1234</code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="instalar.php" class="text-decoration-none small text-secondary">
                    <i class="bi bi-database-gear me-1"></i> Assistente de Instalação do Banco
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Renderizador de Toasts -->
<?php require_once __DIR__ . '/includes/toast.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>
