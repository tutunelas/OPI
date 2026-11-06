/**
 * OPI - Sistema de Controle Automatizado de Estoque
 * assets/js/app.js - Interações, Toasts e Modais
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inicializar e exibir automaticamente todos os Toasts Bootstrap 5
    const toastElements = document.querySelectorAll('.toast');
    toastElements.forEach(function (toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 4500
        });
        toast.show();
    });

    // 2. Configurar confirmações de exclusão usando Modal nativo do Bootstrap 5
    // Atende ao requisito: "caixas de mensagens devem sempre ser tratadas em um modal dentro da interface"
    const deleteButtons = document.querySelectorAll('[data-bs-action="confirm-delete"]');
    const modalDeleteEl = document.getElementById('modalConfirmarExclusao');
    
    if (modalDeleteEl && deleteButtons.length > 0) {
        const modalDelete = new bootstrap.Modal(modalDeleteEl);
        const formDelete = document.getElementById('formModalExclusao');
        const textDelete = document.getElementById('textoModalExclusao');
        const inputIdDelete = document.getElementById('idModalExclusao');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const actionUrl = this.getAttribute('data-action');
                const nomeItem = this.getAttribute('data-nome') || 'este item';

                if (formDelete && textDelete && inputIdDelete) {
                    formDelete.setAttribute('action', actionUrl);
                    inputIdDelete.value = id;
                    textDelete.textContent = `Tem certeza que deseja excluir permanentemente "${nomeItem}"? Esta ação registrará um log de auditoria.`;
                    modalDelete.show();
                }
            });
        });
    }

    // 3. Exibir detalhes de auditoria ou avisos em Modal
    const auditDetailButtons = document.querySelectorAll('[data-bs-action="view-details"]');
    const modalDetailsEl = document.getElementById('modalVisualizarDetalhes');
    if (modalDetailsEl && auditDetailButtons.length > 0) {
        const modalDetails = new bootstrap.Modal(modalDetailsEl);
        const modalTitle = document.getElementById('modalVisualizarTitulo');
        const modalContent = document.getElementById('modalVisualizarConteudo');

        auditDetailButtons.forEach(button => {
            button.addEventListener('click', function () {
                const titulo = this.getAttribute('data-titulo') || 'Detalhes';
                const dados = this.getAttribute('data-detalhes') || 'Sem informações detalhadas.';
                
                modalTitle.textContent = titulo;
                try {
                    // Formata JSON se for válido
                    const parsed = JSON.parse(dados);
                    modalContent.textContent = JSON.stringify(parsed, null, 2);
                } catch (e) {
                    modalContent.textContent = dados;
                }
                modalDetails.show();
            });
        });
    }

    // 4. Modal de Movimentação Rápida de Estoque
    const btnMovimentarEstoque = document.querySelectorAll('[data-bs-action="movimentar-estoque"]');
    const modalMovimentacaoEl = document.getElementById('modalMovimentarEstoque');
    if (modalMovimentacaoEl && btnMovimentarEstoque.length > 0) {
        const modalMov = new bootstrap.Modal(modalMovimentacaoEl);
        const inputProdId = document.getElementById('mov_produto_id');
        const labelProdNome = document.getElementById('mov_produto_nome');
        const labelEstoqueAtual = document.getElementById('mov_estoque_atual');

        btnMovimentarEstoque.forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const nome = this.getAttribute('data-nome');
                const estoque = this.getAttribute('data-estoque');

                if (inputProdId && labelProdNome) {
                    inputProdId.value = id;
                    labelProdNome.textContent = nome;
                    if (labelEstoqueAtual) {
                        labelEstoqueAtual.textContent = estoque;
                    }
                    modalMov.show();
                }
            });
        });
    }
});
