# OPI - Sistema de Controle Automatizado de Estoque

O **OPI** é um sistema completo e moderno para gerenciamento e controle automatizado de estoque empresarial. Desenvolvido rigorosamente de acordo com as especificações contidas em `requisitos.md`.

---

## 🚀 Funcionalidades Principais

- **Catálogo de Produtos:** Adição, edição, exclusão e monitoramento de saldo em estoque, estoque mínimo, localização física e precificação (custo x venda).
- **Controle Automatizado de Estoque:** Alertas visuais dinâmicos para produtos com estoque zerado ou abaixo do limite mínimo com opção de reposição rápida.
- **Movimentações de Estoque:** Registro transacional atômico de Entradas, Saídas e Ajustes de inventário com rastreamento de saldo anterior e posterior.
- **Segurança com Hash Bcrypt:** Senhas criptografadas utilizando `password_hash($senha, PASSWORD_BCRYPT)` e verificadas via `password_verify()`.
- **Prevenção a SQL Injection:** Todas as conexões e queries ao banco de dados utilizam **PDO com Prepared Statements** nativos (`PDO::ATTR_EMULATE_PREPARES => false`).
- **Logs de Auditoria do Sistema:** Histórico imutável de todas as ações de inserção, edição, exclusão, movimentações e autenticação para conformidade e auditorias futuras.
- **Histórico Individual de Uso:** Cada usuário consegue consultar sua própria linha do tempo de operações dentro do sistema (`meu_historico.php`).
- **Múltiplas Funções por Usuário:** Modelagem relacional N:N que permite a um mesmo colaborador acumular múltiplos papéis (ex.: *Administrador*, *Gerente de Estoque*, *Operador de Estoque*, *Auditor*).
- **Interface Mobile-First com Bootstrap 5:** Layout responsivo otimizado para celulares, tablets e computadores, com ícones do Bootstrap Icons.
- **Notificações em TOAST e Caixas de Mensagem em MODAL:** Feedbacks de sucesso/erro via Bootstrap Toasts flutuantes e confirmações críticas em caixas modais integradas.

---

## 🛠️ Stack Tecnológico

| Camada | Tecnologia |
|---|---|
| **Backend** | PHP 8+ estruturado com Sessões Nativas (`$_SESSION`) |
| **Banco de Dados** | MySQL com driver PDO e charset `utf8mb4` |
| **Frontend** | HTML5, CSS3 Customizado e Bootstrap 5.3.3 (CDN) |
| **Ícones** | Bootstrap Icons 1.11.3 |

---

## 📁 Estrutura Modular do Projeto

```
antigraviti/
├── bd.php                      # Conexão PDO segura com MySQL (requisito obrigatório)
├── login.php                   # Tela de login responsiva mobile-first
├── index.php                   # Dashboard com estatísticas, alertas e indicadores
├── produtos.php                # Catálogo de produtos (CRUD e controle de estoque)
├── movimentacoes.php           # Histórico e lançamentos de entradas, saídas e ajustes
├── auditoria.php               # Painel geral de logs para auditoria futura
├── meu_historico.php           # Histórico de uso exclusivo do usuário conectado
├── usuarios.php                # Gestão de usuários e atribuição de múltiplas funções
├── perfil.php                  # Perfil do usuário e alteração segura de senha
├── instalar.php                # Assistente visual de instalação do banco de dados
├── requisitos.md               # Especificação de requisitos original
├── README.md                   # Documentação detalhada
├── config/
│   ├── config.php              # Constantes, sessões seguras e helpers (moeda, data, toasts)
│   ├── auth.php                # Autenticação, controle de múltiplas funções e CSRF
│   └── logger.php              # Motor de auditoria e gravação de logs
├── actions/                    # Scripts de backend isolados
│   ├── login_action.php        # Autenticação com verificação de hash Bcrypt
│   ├── logout_action.php       # Encerramento seguro de sessão e log de auditoria
│   ├── produto_salvar.php      # Criação / Edição de produtos com registro de log
│   ├── produto_excluir.php     # Exclusão segura de produtos com log
│   ├── movimentacao_salvar.php # Lançamento transacional de movimentação de estoque
│   ├── usuario_salvar.php      # Gestão de usuários e associação de múltiplas funções
│   ├── usuario_excluir.php     # Exclusão com proteção contra auto-exclusão
│   └── perfil_salvar.php       # Troca de senha própria e dados cadastrais
├── includes/
│   ├── header.php              # Cabeçalho mobile-first com navbar e verificação de perfil
│   ├── footer.php              # Rodapé, bundle JS e modais reutilizáveis
│   └── toast.php               # Renderizador dinâmico de Toasts do Bootstrap 5
├── assets/
│   ├── css/
│   │   └── style.css           # Estilização customizada mobile-first
│   └── js/
│       └── app.js              # Inicializador de toasts e modais de confirmação
└── sql/
    └── database.sql            # Script SQL completo de criação e sementes iniciais
```

---

## ⚙️ Como Executar o Projeto

### Opção 1: Usando XAMPP / WAMP / Laragon
1. Copie ou clone esta pasta para o diretório raiz do seu servidor web (ex: `C:\xampp\htdocs\OPI` ou `C:\laragon\www\OPI`).
2. Inicie os serviços **Apache** e **MySQL** no painel do seu gerenciador.
3. Abra seu navegador e acesse:
   ```
   http://localhost/OPI/instalar.php
   ```
4. Clique no botão **"Executar Instalação do Banco"** para criar automaticamente o banco de dados `opi_estoque`, as tabelas e dados de teste.
5. Em seguida, acesse `http://localhost/OPI/login.php`.

### Opção 2: Usando o Servidor Embutido do PHP (PHP CLI)
1. Certifique-se de que o serviço MySQL esteja em execução na porta 3306 (usuário `root`, sem senha por padrão).
2. No terminal, execute:
   ```bash
   php -S localhost:8000
   ```
3. Acesse `http://localhost:8000/instalar.php` para configurar o banco.
4. Acesse `http://localhost:8000/login.php` para entrar.

---

## 🔑 Credenciais Padrão de Acesso

- **E-mail:** `admin@opi.com`
- **Senha:** `adm1234`
- **Funções:** Administrador, Gerente de Estoque
- **Criptografia:** Armazenada no banco como hash Bcrypt (`$2y$12$...`)

---

## 🛡️ Checklist de Conformidade com `requisitos.md`

- [x] **Objetivo:** Adicionar, editar e excluir produtos para gestão fácil de estoque.
- [x] **Stack:** PHP estruturado com sessões nativas + MySQL (PDO) + Bootstrap 5.
- [x] **Senhas:** Armazenadas com Bcrypt (`password_hash` / `password_verify`).
- [x] **Auditoria:** Tabela `logs_auditoria` e tela de auditoria completa (`auditoria.php`).
- [x] **Histórico individual:** Usuários conseguem ver seu próprio histórico de uso em `meu_historico.php`.
- [x] **Múltiplas Funções:** Suporte completo com tabela relacional `usuario_funcoes` e multi-select.
- [x] **Segurança PDO:** Prepared statements utilizados em 100% das consultas.
- [x] **Modularização:** Código desacoplado em `bd.php`, `config/`, `actions/`, `includes/` e `views`.
- [x] **Mobile-first:** Classes responsivas do Bootstrap 5, layout flexível e tags de viewport.
- [x] **Toasts e Modais:** Notificações em toasts flutuantes e caixas de confirmação/mensagem em modais.
