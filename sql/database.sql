-- ========================================================
-- OPI - Sistema de Controle Automatizado de Estoque
-- Script de Criação do Banco de Dados e Tabelas
-- ========================================================

CREATE DATABASE IF NOT EXISTS `opi_estoque` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `opi_estoque`;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Funções / Cargos (Suporte a Múltiplas Funções)
CREATE TABLE IF NOT EXISTS `funcoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(50) NOT NULL UNIQUE,
    `descricao` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela Associativa: Usuário pode ter Múltiplas Funções
CREATE TABLE IF NOT EXISTS `usuario_funcoes` (
    `usuario_id` INT NOT NULL,
    `funcao_id` INT NOT NULL,
    PRIMARY KEY (`usuario_id`, `funcao_id`),
    CONSTRAINT `fk_uf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uf_funcao` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Categorias de Produtos
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL UNIQUE,
    `descricao` TEXT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Produtos do Estoque
CREATE TABLE IF NOT EXISTS `produtos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_sku` VARCHAR(60) NOT NULL UNIQUE,
    `nome` VARCHAR(150) NOT NULL,
    `categoria_id` INT NULL,
    `descricao` TEXT NULL,
    `unidade_medida` VARCHAR(20) NOT NULL DEFAULT 'UN',
    `preco_custo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `preco_venda` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `quantidade_estoque` INT NOT NULL DEFAULT 0,
    `estoque_minimo` INT NOT NULL DEFAULT 5,
    `localizacao` VARCHAR(80) NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Movimentações de Estoque (Entradas, Saídas, Ajustes)
CREATE TABLE IF NOT EXISTS `movimentacoes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produto_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `tipo` ENUM('entrada', 'saida', 'ajuste') NOT NULL,
    `quantidade` INT NOT NULL,
    `quantidade_anterior` INT NOT NULL,
    `quantidade_resultante` INT NOT NULL,
    `motivo` VARCHAR(255) NOT NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mov_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Logs de Auditoria do Sistema
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NULL,
    `acao` VARCHAR(80) NOT NULL,
    `tabela_afetada` VARCHAR(60) NULL,
    `registro_id` INT NULL,
    `detalhes` TEXT NULL,
    `ip_origem` VARCHAR(45) NULL,
    `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Dados Iniciais (Cargas Padrão / Seeders)
-- ========================================================

-- Inserir Funções Padrão
INSERT INTO `funcoes` (`id`, `nome`, `descricao`) VALUES
(1, 'Administrador', 'Acesso total a todas as rotinas, produtos, usuários e logs do sistema'),
(2, 'Gerente de Estoque', 'Gerencia catálogo de produtos, estoque mínimo e relatórios'),
(3, 'Operador de Estoque', 'Realiza entradas, saídas e conferências de itens no estoque'),
(4, 'Auditor', 'Acompanha o histórico de atividades e relatórios para compliance')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Inserir Usuário Administrador Padrão
-- Senha padrão: adm1234 (armazenada com hash Bcrypt seguro)
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `status`) VALUES
(1, 'Administrador do Sistema', 'admin@opi.com', '$2y$12$QzuRTiQGHf.zLR6xWAUYNuKFRzXrjvh2gMpyg8eYOzSVJxxEtrmMu', 'ativo')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`), `senha` = VALUES(`senha`);

-- Associar Administrador a Múltiplas Funções (Exemplo: Administrador + Gerente de Estoque)
INSERT INTO `usuario_funcoes` (`usuario_id`, `funcao_id`) VALUES
(1, 1),
(1, 2)
ON DUPLICATE KEY UPDATE `usuario_id` = VALUES(`usuario_id`);

-- Categorias Iniciais
INSERT INTO `categorias` (`id`, `nome`, `descricao`) VALUES
(1, 'Eletrônicos', 'Equipamentos, periféricos e peças de informática'),
(2, 'Escritório', 'Suprimentos para ambiente de escritório e papelaria'),
(3, 'Matéria-Prima', 'Itens e insumos para produção'),
(4, 'Embalagens', 'Caixas, fitas e materiais de envio')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Produtos Iniciais de Demonstração
INSERT INTO `produtos` (`id`, `codigo_sku`, `nome`, `categoria_id`, `descricao`, `unidade_medida`, `preco_custo`, `preco_venda`, `quantidade_estoque`, `estoque_minimo`, `localizacao`) VALUES
(1, 'ELET-001', 'Teclado Mecânico RGB USB', 1, 'Teclado padrão ABNT2 com switches azuis', 'UN', 120.00, 229.90, 25, 10, 'Prateleira A1'),
(2, 'ELET-002', 'Mouse Óptico 7200 DPI', 1, 'Mouse ergonômico 6 botões', 'UN', 45.00, 89.90, 8, 12, 'Prateleira A2'),
(3, 'ELET-003', 'Monitor LED 24 Pol Full HD', 1, 'Monitor IPS com entrada HDMI/VGA', 'UN', 480.00, 799.00, 4, 5, 'Prateleira B1'),
(4, 'ESC-001', 'Papel Sulfite A4 75g (Resma 500fls)', 2, 'Papel multiuso para impressão rápida', 'CX', 22.50, 34.90, 60, 20, 'Palete C3'),
(5, 'EMB-001', 'Fita Adesiva Transparente 45mm x 100m', 4, 'Fita de alta aderência para fechamento de caixas', 'RL', 4.20, 8.50, 15, 30, 'Prateleira D2')
ON DUPLICATE KEY UPDATE `codigo_sku` = VALUES(`codigo_sku`);

-- Registro de Log Inicial da Instalação
INSERT INTO `logs_auditoria` (`usuario_id`, `acao`, `tabela_afetada`, `registro_id`, `detalhes`, `ip_origem`) VALUES
(1, 'SISTEMA_INSTALADO', 'sistema', 1, '{"mensagem":"Banco de dados inicializado com sucesso e sementes aplicadas."}', '127.0.0.1');
