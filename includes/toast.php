<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * includes/toast.php - Renderizador de Notificações Toast do Bootstrap 5
 */

require_once __DIR__ . '/../config/config.php';
$toasts = obter_toasts();

if (!empty($toasts)): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <?php foreach ($toasts as $toast): 
        $tipo = htmlspecialchars($toast['tipo'] ?? 'info');
        $bgClass = match($tipo) {
            'success' => 'bg-success text-white',
            'danger'  => 'bg-danger text-white',
            'warning' => 'bg-warning text-dark',
            'info'    => 'bg-info text-dark',
            default   => 'bg-primary text-white'
        };

        $icone = match($tipo) {
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            'info'    => 'bi-info-circle-fill',
            default   => 'bi-bell-fill'
        };
    ?>
    <div class="toast align-items-center border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header <?= $bgClass ?>">
            <i class="bi <?= $icone ?> me-2"></i>
            <strong class="me-auto"><?= htmlspecialchars($toast['titulo'] ?? 'Mensagem') ?></strong>
            <small class="opacity-75">Agora</small>
            <button type="button" class="btn-close <?= in_array($tipo, ['success', 'danger']) ? 'btn-close-white' : '' ?>" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
        <div class="toast-body bg-white text-dark">
            <?= nl2br(htmlspecialchars($toast['mensagem'] ?? '')) ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
