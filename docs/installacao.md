# Manual de Instalação

## Requisitos do servidor

- PHP 8.0+ com extensões `pdo_mysql` e `json` habilitadas
- MySQL / MariaDB 10.4+
- Servidor web Apache ou Nginx
- Composer não é obrigatório, pois este projeto não usa frameworks externos

## Estrutura de pastas

- `/src/api`: lógica de backend em PHP
- `/src/public`: frontend, HTML/CSS/JS
- `/database`: script SQL de criação das tabelas e dados de teste
- `/docs`: documentação do projeto

## Passos de configuração

1. Copie o diretório `src/public` para a raiz pública do servidor (por exemplo, `public_html` ou `www`).
2. Configure o servidor para apontar para `src/public/index.html` como página inicial.
3. No arquivo `src/api/config.php`, atualize as credenciais do banco de dados:

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'project_admin',
    'db_user' => '************ AQUI ***********',
    'db_pass' => '************ AQUI ***********',
    'db_charset' => 'utf8mb4',
];
```

4. Crie o banco de dados e execute o script de migração:

```sql
-- abra o cliente MySQL/MariaDB e rode:
CREATE DATABASE project_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE project_admin;
SOURCE /caminho/para/projectAdmin/database/migration.sql;
```

4. Configure o servidor web para usar `src/public` como raiz do site.
   - Se usar Apache, ative `mod_rewrite` e mantenha o arquivo `.htaccess` em `src/public`.
   - Se usar Nginx, direcione `/api` para `src/api/index.php` ou use um alias similar.

5. Ajuste as permissões do servidor para que o PHP possa ler `src/api` e `src/public`.

## Usuários de teste

- Administrador
  - email: `admin@origami.test`
  - senha: `Admin@123`

- Jefe de proyecto
  - email: `jefe@origami.test`
  - senha: `Jefe@123`

- Colaborador
  - email: `colaborador@origami.test`
  - senha: `Colab@123`

## Observações de segurança

- Todas as entradas são sanitizadas no backend.
- Todas as consultas usam prepared statements para evitar SQL Injection.
- Senhas são armazenadas usando `password_hash()` com bcrypt.

## Próximos passos

- Implementar o gerenciamento completo de usuários no frontend.
- Desenvolver os endpoints de autenticação e projetos.
- Construir o dashboard com AJAX para operações sem recarga.
