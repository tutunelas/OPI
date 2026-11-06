<?php
/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * includes/footer.php - Rodapé global com Toasts e Modais de Mensagem
 */
?>
    </div> <!-- /container-fluid -->
</main>

<footer class="mt-auto py-3 bg-white border-top text-center text-muted small">
    <div class="container-fluid px-3 px-lg-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span>&copy; <?= date('Y') ?> <strong><?= htmlspecialchars(APP_NAME) ?></strong> - Controle Automatizado de Estoque</span>
        <span class="text-secondary">Segurança PDO MySQL &bull; Sessões Nativas &bull; Bcrypt</span>
    </div>
</footer>

<!-- ========================================================
     Modais Reutilizáveis do Sistema
     Atende: "caixas de mensagens devem sempre ser tratadas em um modal dentro da interface"
     ======================================================== -->

<!-- Modal Universal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-labelledby="tituloModalExclusao" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="tituloModalExclusao">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formModalExclusao" method="POST" action="">
                <div class="modal-body py-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(obter_csrf_token()) ?>">
                    <input type="hidden" name="id" id="idModalExclusao" value="">
                    <p class="mb-0 fs-6" id="textoModalExclusao">Tem certeza de que deseja realizar esta exclusão?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3-fill me-1"></i> Confirmar e Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Universal para Detalhes e Mensagens -->
<div class="modal fade" id="modalVisualizarDetalhes" tabindex="-1" aria-labelledby="modalVisualizarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalVisualizarTitulo">
                    <i class="bi bi-info-circle me-2"></i> Detalhes da Ação
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <pre id="modalVisualizarConteudo" class="json-preview"></pre>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Renderizador de Mensagens Toast -->
<?php require_once __DIR__ . '/toast.php'; ?>

<!-- Bootstrap 5.3.3 Bundle JS com Popper (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Scripts Customizados -->
<script src="assets/js/app.js"></script>

</body>
</html>
