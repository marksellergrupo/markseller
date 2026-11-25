# Site Mark Seller

Site institucional estático (HTML/CSS/JS) + blog com painel de administração em **PHP 8 + MySQL**.

## Pôr o blog a funcionar (XAMPP)

1. Copie a pasta do projecto para `C:\xampp\htdocs\markseller` (ou aponte um virtual host para ela).
2. No XAMPP, inicie **Apache** e **MySQL**.
3. Confirme os dados em `config/config.php` (criado a partir de `config/config.sample.php`; não vai para o git).
   Em produção, use uma base de dados e um utilizador próprios, com palavra-passe forte.
4. Abra `http://localhost/markseller/admin/setup.php`, introduza a `setup_key` do `config/config.php` e crie o administrador.
   Isto cria a base de dados, as tabelas (`database/schema.sql`) e, opcionalmente, importa 4 artigos de exemplo.
5. Entre em `http://localhost/markseller/admin/` e escreva artigos.

## Estrutura

| Caminho | Função |
|---|---|
| `blog.php`, `artigo.php` | Páginas públicas do blog (lêem da base de dados) |
| `api/posts.php` | Últimos artigos em JSON (usado na página inicial) |
| `admin/` | Painel: entrar, lista, editor, conta |
| `includes/` | Arranque, markdown, layout partilhado |
| `database/` | `schema.sql` e artigos de exemplo |
| `uploads/blog/` | Imagens enviadas pelo admin (não vai para o git) |
| `tools/` | Scripts Python para o HTML estático |

## Alterar o menu ou o rodapé

O cabeçalho e o rodapé das páginas PHP vêm de `includes/layout-*.html`, gerados a partir de `tools/template.py`:

```
python tools/export_layout.py
```

## Segurança (resumo)

- Palavras-passe com `password_hash`, sessão com cookie `HttpOnly`/`SameSite`, CSRF em todos os formulários,
  limite de tentativas de login, consultas preparadas (PDO) e conteúdo escapado.
- Envio de imagens: valida tipo real e tamanho; nomes aleatórios; `uploads/blog/.htaccess` impede executar scripts.
- `config/`, `includes/` e `database/` estão protegidos por `.htaccess` (Apache). Em Nginx, bloqueie estas pastas na configuração.
- Depois da instalação, `admin/setup.php` deixa de funcionar (só corre se não existir nenhum administrador).
