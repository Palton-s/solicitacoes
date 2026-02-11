# Plugin Moodle: Solicitações de Gerenciamento de Curso

## Versão 1.2.1 - Rotas em Português

**🎉 Todas as rotas estão em português para melhor compreensão!**

### Rotas Principais
- ✅ `nova-solicitacao.php` - Criar nova solicitação
- ✅ `minhas-solicitacoes.php` - Ver próprias solicitações
- ✅ `gerenciar.php` - Gerenciar solicitações (admin)
- ✅ `detalhes.php` - Ver detalhes de solicitação
- ✅ `negar-solicitacao.php` - Negar solicitação (admin)
- ✅ `confirmacao.php` - Página de confirmação

### Rotas AJAX
- ✅ `ajax/buscar-cursos.php` - Buscar cursos (autocomplete)
- ✅ `ajax/buscar-usuarios.php` - Buscar usuários (autocomplete)

---

## Descrição

O plugin "Solicitações de Gerenciamento de Curso" é um sistema integrado ao Moodle que permite aos usuários solicitar inscrições, remoções ou suspensões de usuários em cursos, com aprovação dos administradores.

## Funcionalidades

- **Solicitações de Inscrição**: Inscrever usuários em cursos com papéis específicos
- **Solicitações de Remoção**: Remover usuários de cursos
- **Solicitações de Suspensão**: Suspender usuários em cursos
- **Painel administrativo**: Interface completa para gerenciar solicitações
- **Sistema de status**: Pendente, Em andamento, Concluído
- **Multilíngue**: Suporte para Português (Brasil) e Inglês

## Instalação

1. Copie a pasta do plugin para `local/solicitacoes` no diretório do Moodle
2. Acesse a administração do Moodle e complete a instalação
3. O plugin estará disponível em `/local/solicitacoes/`

## Estrutura de Arquivos

```
local/solicitacoes/
├── ajax/
│   ├── buscar-cursos.php       # AJAX: buscar cursos
│   ├── buscar-usuarios.php     # AJAX: buscar usuários
│   ├── test_db.php             # Teste de BD (dev)
│   └── test_simple.php         # Teste simples (dev)
├── classes/
│   ├── solicitacoes_controller.php  # Controlador principal
│   └── form/
│       └── request_form.php    # Form API (backup)
├── db/
│   ├── access.php          # Definições de capacidades
│   ├── install.php         # Script de instalação
│   ├── install.xml         # Estrutura do banco de dados
│   ├── upgrade.php         # Scripts de atualização
│   └── messages.php        # Definições de mensagens
├── lang/
│   ├── en/
│   │   └── local_solicitacoes.php    # Strings em inglês
│   └── pt_br/
│       └── local_solicitacoes.php    # Strings em português
├── styles/
│   ├── request_form.css    # Estilos do formulário
│   ├── request_form.js     # JavaScript do formulário
│   ├── tomselect_custom.css
│   └── view_details.css
├── templates/
│   ├── form_solicitacao.mustache     # Template do formulário
│   ├── manage_requests.mustache      # Template de gerenciamento
│   └── my_requests.mustache          # Template de minhas solicitações
├── nova-solicitacao.php    # ✅ Criar nova solicitação
├── minhas-solicitacoes.php # ✅ Ver próprias solicitações
├── gerenciar.php           # ✅ Gerenciar solicitações - admin
├── detalhes.php            # ✅ Ver detalhes
├── negar-solicitacao.php   # ✅ Negar solicitação
├── confirmacao.php         # ✅ Página de confirmação
├── clear_cache.php         # Limpar cache (dev)
├── debug.php               # Debug (dev)
├── lib.php                 # Funções da biblioteca
├── settings.php            # Configurações do admin
├── version.php             # Informações da versão (v1.2.1)
├── README.md               # Este arquivo
└── CHANGELOG.md            # Histórico de mudanças
```

## Capacidades

- `local/solicitacoes:submit` - Enviar solicitações (todos os usuários)
- `local/solicitacoes:manage` - Gerenciar solicitações (apenas administradores)

## Banco de Dados

### Tabela: local_solicitacoes

| Campo           | Tipo        | Descrição                           |
|----------------|-------------|-------------------------------------|
| id             | int(10)     | ID único da solicitação             |
| userid         | int(10)     | ID do usuário que fez a solicitação |
| tipo_acao      | varchar(20) | Tipo: inscricao, remocao, suspensao |
| curso_nome     | varchar(255)| Nome do curso                       |
| usuarios_nomes | text        | Nomes dos usuários (um por linha)   |
| papel          | varchar(100)| Papel no curso (apenas inscrições)  |
| observacoes    | text        | Observações adicionais (opcional)   |
| status         | varchar(20) | Status (pendente/em_andamento/concluido)|
| adminid        | int(10)     | ID do admin que atendeu (opcional)  |
| timecreated    | int(10)     | Timestamp de criação                |
| timemodified   | int(10)     | Timestamp da última modificação     |


### Tabela: mdl_local_curso_solicitacoes

+----------------+------------+------+-----+---------+----------------+
| Field          | Type       | Null | Key | Default | Extra          |
+----------------+------------+------+-----+---------+----------------+
| id             | bigint(10) | NO   | PRI | NULL    | auto_increment |
| solicitacao_id | bigint(10) | NO   | MUL | NULL    |                |
| curso_id       | bigint(10) | NO   | MUL | NULL    |                |
| timecreated    | bigint(10) | NO   |     | NULL    |                |
+----------------+------------+------+-----+---------+----------------+

### Tabela: mdl_local_usuarios_solicitacoes

+----------------+------------+------+-----+---------+----------------+
| Field          | Type       | Null | Key | Default | Extra          |
+----------------+------------+------+-----+---------+----------------+
| id             | bigint(10) | NO   | PRI | NULL    | auto_increment |
| solicitacao_id | bigint(10) | NO   | MUL | NULL    |                |
| usuario_id     | bigint(10) | NO   | MUL | NULL    |                |
| timecreated    | bigint(10) | NO   |     | NULL    |                |
+----------------+------------+------+-----+---------+----------------+

## Uso

### Para Usuários
1. Acesse `/local/solicitacoes/`
2. Selecione o tipo de ação (Inscrição, Remoção ou Suspensão)
3. Informe o nome do curso
4. Liste os nomes dos usuários (um por linha)
5. Para inscrições, selecione o papel desejado
6. Adicione observações se necessário
7. Clique em "Enviar Solicitação"

### Para Administradores
1. Acesse `/local/solicitacoes/manage.php` ou pelo menu de administração
2. Visualize todas as solicitações com detalhes organizados
3. Clique em "Ver" para detalhes completos de cada solicitação
4. Altere o status conforme o andamento (Pendente → Em andamento → Concluído)

## Permissões

- **Usuários**: Podem enviar solicitações
- **Administradores**: Podem visualizar e gerenciar todas as solicitações

## Tecnologias

- PHP 7.4+
- Moodle 3.9+
- HTML/CSS
- Moodle Forms API

## Licença

GPL v3 - compatível com a licença do Moodle