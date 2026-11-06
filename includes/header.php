<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * includes/header.php - Cabeçalho responsivo Mobile-First com Bootstrap 5
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

$paginaAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$tituloPagina = $tituloPagina ?? 'OPI - Controle Automatizado de Estoque';
$usuarioLogado = esta_logado();
$funcoesUsuario = obter_funcoes_usuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($tituloPagina) ?></title>

    <!-- Bootstrap 5.3.3 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- CSS Customizado (Mobile-First) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar Responsiva Mobile-First -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-opi sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-boxes text-primary fs-4"></i>
            <span>OPI</span>
            <span class="brand-badge">ESTOQUE</span>
        </a>

        <?php if ($usuarioLogado): ?>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPrincipal" aria-controls="navbarPrincipal" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'produtos.php' ? 'active fw-bold' : '' ?>" href="produtos.php">
                        <i class="bi bi-box-seam me-1"></i> Produtos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'movimentacoes.php' ? 'active fw-bold' : '' ?>" href="movimentacoes.php">
                        <i class="bi bi-arrow-left-right me-1"></i> Movimentações
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'meu_historico.php' ? 'active fw-bold' : '' ?>" href="meu_historico.php">
                        <i class="bi bi-clock-history me-1"></i> Meu Histórico
                    </a>
                </li>

                <?php if (usuario_tem_qualquer_funcao(['Administrador', 'Auditor', 'Gerente de Estoque'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'auditoria.php' ? 'active fw-bold' : '' ?>" href="auditoria.php">
                        <i class="bi bi-shield-check me-1"></i> Auditoria Geral
                    </a>
                </li>
                <?php endif; ?>

                <?php if (usuario_tem_funcao('Administrador')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'usuarios.php' ? 'active fw-bold' : '' ?>" href="usuarios.php">
                        <i class="bi bi-people me-1"></i> Usuários
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#" id="dropdownUsuario" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: 600;">
                            <?= strtoupper(substr(obter_usuario_nome(), 0, 1)) ?>
                        </div>
                        <div class="d-flex flex-column text-start">
                            <span class="small fw-semibold"><?= htmlspecialchars(obter_usuario_nome()) ?></span>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($funcoesUsuario as $funcao): ?>
                                    <span class="badge bg-secondary badge-role" style="font-size: 0.65rem;"><?= htmlspecialchars($funcao) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="dropdownUsuario">
                        <li><h6 class="dropdown-header">Minha Conta</h6></li>
                        <li>
                            <a class="dropdown-item" href="perfil.php">
                                <i class="bi bi-person-gear me-2"></i> Perfil e Senha
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="meu_historico.php">
                                <i class="bi bi-journal-text me-2"></i> Histórico de Ações
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="actions/logout_action.php" method="POST" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Encerrar Sessão
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <?php else: ?>
        <div class="d-flex">
            <a href="login.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<main class="py-4">
    <div class="container-fluid px-3 px-lg-4">
