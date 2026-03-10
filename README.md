# Plugin Moodle: Solicitações de Gerenciamento de Curso (`local_solicitacoes`)

> **⚠️ ATUALIZAÇÃO IMPORTANTE:** Este plugin foi migrado para usar o sistema nativo de busca de usuários do Moodle.  
> **Requisito:** Usuários precisam da permissão `moodle/user:viewdetails` para usar a busca de usuários.

**Versão:** v1.4.0 (`2026030903`)  
**Requer:** Moodle 4.0 ou superior (`2022041900`)  
**Maturidade:** Estável  
**Licença:** GNU GPL v3

---

## Descrição

Plugin local do Moodle que fornece um fluxo de solicitações para operações de gerenciamento de usuários e cursos. Usuários autorizados submetem pedidos (inscrição, remoção, suspensão, cadastro de usuário, criação de disciplina) que ficam pendentes até serem aprovados ou negados por um gerente/administrador.

---

## Funcionalidades

| Tipo de solicitação | Descrição |
|---|---|
| `inscricao` | Inscrever um ou mais usuários em um ou mais cursos com papel específico |
| `remocao` | Remover usuários de cursos |
| `suspensao` | Suspender usuários em cursos temporariamente |
| `cadastro` | Solicitar criação de novo usuário + inscrição em curso |
| `criar_curso` | Solicitar criação de nova disciplina em uma unidade acadêmica |
| `remove_course` | Solicitar a exclusão/remoção de uma disciplina |

**Recursos gerais:**
- Formulários nativos usando Moodle Forms API
- Seleção de cursos com autocomplete — filtra apenas cursos aos quais o solicitante tem acesso (matrícula direta, papel em categoria ou papel em nível de sistema)
- Cursos ocultos acessíveis ao usuário também são listados
- **Busca avançada de usuários** via sistema nativo do Moodle (`core_user/form_user_selector`):
  - 🔍 **Busca por múltiplos campos:** firstname, lastname, fullname, username e email
  - 🎯 **Busca inteligente:** não diferencia maiúsculas/minúsculas
  - 👥 **Seleção múltipla** com interface intuitiva
  - **⚠️ Requer permissão `moodle/user:viewdetails`**
- Notificações internas (popup) e por e-mail nos eventos: criação, aprovação e negação
- Painel de gerenciamento com filtros por status e tipo
- Suporte multilíngue: Português (Brasil) e Inglês

---

## Instalação

1. Copie a pasta para `{raiz_moodle}/local/solicitacoes/`
2. Acesse **Administração do site → Notificações** e conclua a instalação
3. Atribua as capabilities necessárias aos papéis desejados (ver seção Capacidades)
4. Limpe o cache de JavaScript em **Administração → Desenvolvimento → Limpar todos os caches**

---

## Estrutura de Arquivos

```
local/solicitacoes/
├── amd/
│   ├── src/
│   │   └── user_selector.js        # [OBSOLETO] Seletor customizado - não usado
│   └── build/
│       └── user_selector.min.js    # [OBSOLETO] Versão minificada - não usado
├── ajax/
│   ├── buscar-cursos.php           # Endpoint AJAX: autocomplete de cursos
│   ├── buscar-usuarios.php         # [OBSOLETO] Endpoint customizado - não usado
│   ├── buscar-categorias.php       # Endpoint AJAX: autocomplete de categorias
│   ├── test_db.php                 # Diagnóstico de BD (desenvolvimento)
│   └── test_simple.php             # Teste simples (desenvolvimento)
├── classes/
│   ├── solicitacoes_controller.php # Controlador principal (salvar, notificar)
│   └── form/
│       └── request_form.php        # Formulário genérico (Form API)
├── db/
│   ├── access.php                  # Definições de capabilities
│   ├── install.php                 # Dados iniciais pós-instalação
│   ├── install.xml                 # Estrutura do banco de dados (XMLDB)
│   ├── messages.php                # Provedores de mensagens/notificações
│   └── upgrade.php                 # Scripts de atualização de versão
├── lang/
│   ├── en/
│   │   └── local_solicitacoes.php  # Strings em inglês
│   └── pt_br/
│       └── local_solicitacoes.php  # Strings em português (Brasil)
├── styles/
│   ├── action_selection.css        # Estilos da página de seleção de ação
│   ├── tomselect_custom.css        # Customização do componente TomSelect
│   └── view_details.css            # Estilos da página de detalhes
├── templates/
│   ├── manage_requests.mustache    # Template: painel de gerenciamento
│   ├── my_requests.mustache        # Template: minhas solicitações
│   └── selecionar_acao.mustache    # Template: seleção do tipo de ação
├── confirmacao.php                 # Página de confirmação pós-envio
├── configuracoes.php               # Página de configurações do plugin
├── detalhes.php                    # Detalhes de uma solicitação
├── gerenciar.php                   # Painel de gerenciamento (requer :manage)
├── index.php                       # Página inicial / redirecionamento
├── lib.php                         # Funções auxiliares e callbacks do Moodle
├── limpar_cache.php                # Utilitário para limpar cache (dev)
├── minhas-solicitacoes.php         # Lista de solicitações do usuário logado
├── negar-solicitacao.php           # Formulário de negação com motivo
├── selecionar-acao.php             # Seleção do tipo de solicitação
├── settings.php                    # Links no painel de administração
├── solicitar-cadastro.php          # Formulário: cadastro de novo usuário
├── solicitar-curso.php             # Formulário: criação de disciplina
├── solicitar-inscricao.php         # Formulário: inscrição de usuários
├── solicitar-remocao.php           # Formulário: remoção de usuários
├── solicitar-remover-curso.php     # Formulário: exclusão de disciplina
├── solicitar-suspensao.php         # Formulário: suspensão de usuários
├── version.php                     # Metadados de versão do plugin
└── README.md                       # Este arquivo
```

---

## Banco de Dados

O plugin cria três tabelas. O prefixo padrão do Moodle (`mdl_`) é omitido nas descrições — o nome real depende da configuração do ambiente.

### Diagrama de relacionamentos

```
mdl_user ──────────────────────────────────────────────┐
    │                                                   │
    │ userid (FK)                        adminid (FK)   │
    ▼                                                   ▼
mdl_local_solicitacoes (tabela principal)
    │                         │
    │ id (PK)                 │ id (PK)
    │                         │
    ▼                         ▼
mdl_local_curso_solicitacoes     mdl_local_usuarios_solicitacoes
    │                                      │
    │ curso_id (FK)                         │ usuario_id (FK)
    ▼                                      ▼
mdl_course                             mdl_user
```

---

### Tabela `local_solicitacoes` — solicitação principal

| Campo | Tipo | Nulo | Padrão | Descrição |
|---|---|---|---|---|
| `id` | INT(10) | NÃO | auto | Chave primária |
| `userid` | INT(10) | NÃO | — | FK → `mdl_user.id` — usuário que abriu a solicitação |
| `timecreated` | INT(10) | NÃO | — | Timestamp Unix de criação |
| `timemodified` | INT(10) | NÃO | — | Timestamp Unix da última modificação |
| `tipo_acao` | VARCHAR(50) | NÃO | `inscricao` | Tipo: `inscricao`, `remocao`, `suspensao`, `cadastro`, `criar_curso`, `remove_course` |
| `papel` | VARCHAR(100) | SIM | — | Shortname do papel no curso (preenchido em `inscricao` e `cadastro`) |
| `observacoes` | TEXT | SIM | — | Observações livres do solicitante |
| `status` | VARCHAR(50) | NÃO | `pendente` | Estado atual: `pendente`, `aprovado`, `negado` |
| `adminid` | INT(10) | SIM | — | FK → `mdl_user.id` — gerente que avaliou a solicitação |
| `motivo_negacao` | TEXT | SIM | — | Preenchido quando `status = negado` |
| `firstname` | VARCHAR(100) | SIM | — | Primeiro nome do novo usuário (apenas `tipo_acao = cadastro`) |
| `lastname` | VARCHAR(100) | SIM | — | Sobrenome do novo usuário (apenas `tipo_acao = cadastro`) |
| `cpf` | VARCHAR(14) | SIM | — | CPF sem formatação — usado como `username` (apenas `cadastro`) |
| `email` | VARCHAR(100) | SIM | — | E-mail do novo usuário (apenas `cadastro`) |
| `codigo_sigaa` | TEXT | SIM | — | Código SIGAA da turma (apenas `criar_curso`) |
| `course_shortname` | VARCHAR(100) | SIM | — | Nome curto da disciplina a criar (apenas `criar_curso`) |
| `course_summary` | TEXT | SIM | — | Descrição/ementa da disciplina (apenas `criar_curso`) |
| `unidade_academica_id` | INT(10) | SIM | — | ID da categoria de destino (apenas `criar_curso`) |
| `ano_semestre` | VARCHAR(20) | SIM | — | Ex.: `2026.1` (apenas `criar_curso`) |
| `razoes_criacao` | TEXT | SIM | — | Justificativa para criar a disciplina (apenas `criar_curso`) |

**Chaves e índices:**

| Nome | Tipo | Campos | Referência |
|---|---|---|---|
| `primary` | PRIMARY KEY | `id` | — |
| `userid` | FOREIGN KEY | `userid` | `mdl_user(id)` |
| `adminid` | FOREIGN KEY | `adminid` | `mdl_user(id)` |
| `status_idx` | INDEX | `status` | — |
| `tipo_acao_idx` | INDEX | `tipo_acao` | — |

---

### Tabela `local_curso_solicitacoes` — cursos vinculados

Relação N:N entre `local_solicitacoes` e `mdl_course`. Uma solicitação pode envolver múltiplos cursos.

| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | INT(10) | NÃO | Chave primária |
| `solicitacao_id` | INT(10) | NÃO | FK → `local_solicitacoes.id` |
| `curso_id` | INT(10) | NÃO | FK → `mdl_course.id` |
| `timecreated` | INT(10) | NÃO | Timestamp Unix de inserção |

**Chaves e índices:**

| Nome | Tipo | Campos | Referência |
|---|---|---|---|
| `primary` | PRIMARY KEY | `id` | — |
| `solicitacao_id` | FOREIGN KEY | `solicitacao_id` | `local_solicitacoes(id)` |
| `curso_id` | FOREIGN KEY | `curso_id` | `mdl_course(id)` |
| `solicitacao_curso_idx` | INDEX | `solicitacao_id, curso_id` | — |

---

### Tabela `local_usuarios_solicitacoes` — usuários afetados

Relação N:N entre `local_solicitacoes` e `mdl_user`. Armazena quais usuários serão inscritos/removidos/suspensos. **Não é preenchida** para `cadastro` e `criar_curso`.

| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | INT(10) | NÃO | Chave primária |
| `solicitacao_id` | INT(10) | NÃO | FK → `local_solicitacoes.id` |
| `usuario_id` | INT(10) | NÃO | FK → `mdl_user.id` — usuário afetado pela ação |
| `timecreated` | INT(10) | NÃO | Timestamp Unix de inserção |

**Chaves e índices:**

| Nome | Tipo | Campos | Referência |
|---|---|---|---|
| `primary` | PRIMARY KEY | `id` | — |
| `solicitacao_id` | FOREIGN KEY | `solicitacao_id` | `local_solicitacoes(id)` |
| `usuario_id` | FOREIGN KEY | `usuario_id` | `mdl_user(id)` |
| `solicitacao_usuario_idx` | INDEX | `solicitacao_id, usuario_id` | — |

---

## Capacidades (`db/access.php`)

| Capability | Tipo | Risco | Padrão para |
|---|---|---|---|
| `local/solicitacoes:view` | read | — | Todos os usuários autenticados |
| `local/solicitacoes:viewall` | read | RISK_PERSONAL | `editingteacher`, `manager` |
| `local/solicitacoes:submit` | write | RISK_SPAM | `editingteacher`, `manager` |
| `local/solicitacoes:manage` | write | RISK_CONFIG, RISK_DATALOSS | `manager` |

Todas as capabilities são definidas no contexto `CONTEXT_SYSTEM`.

---

## Notificações (`db/messages.php`)

O plugin registra três provedores de mensagem, enviados via popup e e-mail por padrão:

| Provedor | Disparado quando |
|---|---|
| `solicitacao_criada` | Uma nova solicitação é submetida |
| `solicitacao_aprovada` | Um gerente aprova a solicitação |
| `solicitacao_negada` | Um gerente nega a solicitação |

---

## Sistema de Busca de Usuários

O plugin agora utiliza o **sistema nativo do Moodle** (`core_user/form_user_selector`) para busca de usuários.

**⚠️ Requisito de Permissão:**
Os usuários que fazem solicitações precisam ter a permissão `moodle/user:viewdetails` para usar a busca de usuários.

**Como configurar a permissão:**
1. Vá em **Administração do Site → Usuários → Permissões → Definir papéis**
2. Edite o papel desejado (ex: "Authenticated user")
3. Encontre `moodle/user:viewdetails` e defina como **Permitir**
4. Salve as alterações

### 🔍 **Funcionalidades da Busca de Usuários**

**Campos de Busca:**
- ✅ **Firstname** (nome)
- ✅ **Lastname** (sobrenome)
- ✅ **Fullname** (nome completo)
- ✅ **Username** (nome de usuário)
- ✅ **Email** (endereço de e-mail)

**Características:**
- 🎯 **Busca inteligente:** não diferencia maiúsculas/minúsculas
- 🔍 **Busca parcial:** funciona com qualquer parte do texto
- 👥 **Seleção múltipla:** permite selecionar vários usuários simultaneamente
- 🛡️ **Filtros automaticos:** exclui usuários deletados e não confirmados
- 📋 **Preview completo:** mostra nome completo, username e email do usuario selecionado

**Exemplo de uso:**
- Digite "joão" → encontra usuários com firstname "João", lastname "João", ou username "joao123"
- Digite "@gmail" → encontra todos usuários com email do Gmail
- Digite "prof" → encontra usuários com "prof" no nome, username ou email

**Arquivos não utilizados (sistema anterior):**
- `amd/src/user_selector.js` — sistema customizado (não usado)
- `amd/build/user_selector.min.js` — sistema customizado (não usado)  
- `ajax/buscar-usuarios.php` — endpoint customizado (não usado)

> Estes arquivos podem ser removidos se você não planeja voltar ao sistema customizado.

---

## Lógica de Filtragem de Cursos

Nos formulários de inscrição, remoção, suspensão e cadastro, o campo de seleção de curso exibe apenas os cursos aos quais o solicitante tem acesso — incluindo cursos ocultos acessíveis:

| Condição | Cursos exibidos |
|---|---|
| Usuário é `siteadmin` | Todos os cursos (visíveis e ocultos) |
| Usuário tem papel no contexto do sistema | Todos os cursos (visíveis e ocultos) |
| Demais usuários | Cursos com matrícula ativa (`enrol_get_users_courses`) + cursos de categorias onde o usuário possui papel atribuído (`role_assignments` com `contextlevel = CONTEXT_COURSECAT`) |

A validação server-side no `validation()` re-executa `get_available_courses()` para rejeitar IDs de cursos fora do escopo do solicitante, prevenindo manipulação de formulário.

---

## Endpoints AJAX

Todos os endpoints exigem `require_login()` e `require_sesskey()`.

| Arquivo | Parâmetros GET | Retorno |
|---|---|---|
| `ajax/buscar-usuarios.php` | `q` (termo), `limit` (máx. 50) | JSON: `[{id, fullname, username, email, label, suspended}]` |
| `ajax/buscar-cursos.php` | `q` (termo), `categoryid` (opcional) | JSON: lista de cursos |
| `ajax/buscar-categorias.php` | `q` (termo) | JSON: lista de categorias |

---

## Administração

O plugin adiciona dois links ao painel de administração (`Plugins locais`):

- **Configurações** → `configuracoes.php` (requer `moodle/site:config`)
- **Gerenciar Solicitações** → `gerenciar.php` (requer `local/solicitacoes:manage`)

---

## Requisitos Técnicos

- Moodle 4.0 ou superior
- PHP 7.4 ou superior
- Banco de dados compatível com Moodle (MySQL 5.7+ / MariaDB 10.4+ / PostgreSQL 12+)

---

## Licença

GNU General Public License v3 — compatível com a licença do Moodle.  
Consulte `http://www.gnu.org/copyleft/gpl.html` para detalhes.