INSTRUÇÕES DE INSTALAÇÃO DO PAINEL "ADMIN-CLAUS"

Passo 1: Banco de Dados (MySQL)
---------------------------------------------------------
1. Acesse o phpMyAdmin da sua hospedagem Hostinger.
   - Banco de Dados: u770915504_openclaw
2. Clique na aba "Importar" ou vá em "SQL".
3. Copie o conteúdo do arquivo "database_setup.sql" (que está nesta pasta) e cole na caixa de comando SQL, ou faça upload do arquivo.
4. Execute para criar as tabelas (agent_config, admin_users, contacts, agent_logs).

Passo 2: Upload dos Arquivos (FTP ou Gerenciador de Arquivos)
---------------------------------------------------------
Você pode usar um cliente FTP (como FileZilla) ou o Gerenciador de Arquivos da Hostinger.

Dados de FTP (baseado no que você forneceu):
- Host: ftp://147.93.37.68
- Usuário: u770915504.n8nbalao.com
- Senha: Aa366560402@
- Porta: 21

Onde colocar os arquivos:
1. Navegue até a pasta "public_html".
2. Crie uma nova pasta chamada "admin-claus" (ou o nome que preferir).
3. Envie os seguintes arquivos para dentro desta pasta:
   - db.php
   - api.php
   - index.php

Passo 3: Acessar o Painel
---------------------------------------------------------
Abra seu navegador e acesse:
http://seusite.com.br/admin-claus/
(Substitua "seusite.com.br" pelo seu domínio real, aparentemente n8nbalao.com)

Exemplo: http://n8nbalao.com/admin-claus/

Nota sobre db.php:
O arquivo db.php já foi configurado com os dados que você forneceu:
- Banco: u770915504_openclaw
- Usuário: u770915504_openclaw
- Senha: Aa366560402@
- Host: localhost (Padrão da Hostinger)

Se tiver erro de conexão "Connection failed", edite o arquivo db.php e tente trocar $servername = "localhost" por $servername = "srv1889.hstgr.io".
