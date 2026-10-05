# OPI

## Objetivo
é um sistema de controle automatizado de estoque, nele você gerencia o estoque da sua empresa com mais facilidade, com nosso sistema você consegue adicionar, editar e excluir produtos para facilitar a gestao da sua empresa

### Stack Tecnológico
Backend: PHP estruturado com sessoes nativas
Banco de dados: MyQL (PDO para segurança) trate sempre as credenciais com hash (Bcrypt) 
Frontend: HTML5, CSS (usando bootstrap 5)

#### Regras de Negócios (Core)
as senhas devem ser armazenadas com hash seguro
o sistema deve sempre manter logs para consulta de toda as mudanças feitas dentro dele para auditoria futura.
todos os usuarios devem conseguir ver seus historicos de uso de sistema.
um usuário pode ter multiplas funções dentro do sistema.

##### Regras Globais
sempre ultilize PDO para conexões e queries do MySQL para evitar SQL injections.
Mantenha o codigo limpo e comente apenas çogicas complexas
Não faça um sistema monolitico, sempre modularize o sistema para facilitar os futuros upgrades.
Estilize as telas do bootstrap 5 CSS de forma responsiva e pensem sempre em Mobilefist
separe os arquivos de forma lógica: um arquivo para conexão da base (bd.php), scripts de backend isolados e views em HTML/PHP
Retorne sem os erros de forma clara na interface para o usuario, pode utilizar (TOAST) 
caixas de mensagens devem sempre ser tratadas em um modal dentro da interface.
