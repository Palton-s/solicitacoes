# Plugin Moodle: Solicitações de Gerenciamento de Curso (`local_solicitacoes`)

> **v1.6.0:** a busca de cursos e de usuários passou a ser assíncrona (AJAX via web services do próprio plugin).
> Nenhuma lista de cursos é carregada junto com a página, o que evita erros 502/504 em ambientes lentos.
> A busca de usuários **não exige mais** `moodle/user:viewdetails`/`viewalldetails`: basta `local/solicitacoes:submit`.

**Versão:** v1.6.0 (`2026111900`)  
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
- Seleção de cursos com autocomplete **assíncrono** (mín. 3 caracteres): filtra apenas cursos aos quais o solicitante tem acesso (matrícula direta, papel em categoria ou papel em nível de sistema)
- Cursos ocultos acessíveis ao usuário também são listados
- **Busca de usuários assíncrona** (web service `local_solicitacoes_search_users`): nome, sobrenome, username/CPF e e-mail; seleção múltipla
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
│   │   ├── course_selector.js      # Autocomplete de cursos (AJAX)
│   │   ├── user_selector.js        # Autocomplete de usuários (AJAX)
│   │   └── similar_courses.js      # Aviso de disciplinas similares (gerenciar.php)
│   └── build/                      # Versões carregadas pelo Moodle (*.min.js)
├── classes/
│   ├── solicitacoes_controller.php # Controlador principal (salvar, notificar)
│   ├── external/                   # Web services AJAX (search_courses, search_users, get_similar_courses)
│   ├── local/                      # course_search, user_search, similar_courses
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
├── limpar_cache.php                # Limpa cache de strings (somente admin, com confirmação)
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

## Busca assíncrona (desempenho em ambientes lentos)

Os formulários não carregam mais listas de cursos no HTML. Os campos de curso e de
usuário usam o elemento `autocomplete` do Moodle com módulos AMD próprios, que chamam
web services do plugin via `lib/ajax/service.php` (sessão do usuário + sesskey; **não**
é preciso habilitar os web services do site).

| Função externa | Capability | Uso |
|---|---|---|
| `local_solicitacoes_search_courses` (`query`, `scope`, `limit`) | `local/solicitacoes:submit` | Campo de cursos dos formulários |
| `local_solicitacoes_search_users` (`query`, `limit`) | `local/solicitacoes:submit` | Campos de usuários/professores |
| `local_solicitacoes_get_similar_courses` (`requestids[]`) | `local/solicitacoes:manage` | Aviso de disciplinas similares em `gerenciar.php` |

**O que foi otimizado:**
- Busca só dispara com 3+ caracteres, espera o usuário parar de digitar e devolve no máximo 30 resultados (`LIMIT` no banco).
- Resultados repetidos ficam em cache no navegador; os cursos/categorias acessíveis ao usuário ficam em cache de sessão (MUC `useraccess`, 5 min).
- Os caminhos das categorias permitidas ficam em cache de aplicação (MUC `allowedcatpaths`, 10 min).
- A validação do formulário confere **apenas os IDs selecionados**, em uma consulta.
- O lock de sessão é liberado durante a busca, então requisições em paralelo não ficam enfileiradas.
- Em `gerenciar.php`, a verificação de disciplinas similares roda depois que a página abre (lotes de 50) e usa um pré-filtro por tamanho que descarta os cursos que nunca chegariam a 90% antes de chamar `similar_text`. Os solicitantes são carregados em uma única consulta.

**Busca de usuários:**
- Termo com `@` → e-mail começando com o termo
- Só dígitos (CPF, com ou sem pontuação) → username começando com os dígitos (usa índice)
- Demais → cada palavra precisa aparecer no nome, sobrenome, ou no início do username/e-mail

> Observação: como os acessos ficam em cache por até 5 minutos, um usuário recém-matriculado pode levar esse tempo para ver o curso na busca.

---

## Lógica de Filtragem de Cursos

O escopo é definido por campo (`course_search::SCOPE_*`):

| Escopo | Formulários | Cursos |
|---|---|---|
| `allowed` | Inscrição, Cadastro | Acessíveis ao solicitante **e** dentro das categorias permitidas nas configurações |
| `accessible` | Remoção, Suspensão | Acessíveis ao solicitante |
| `all` | Remover curso | Todos |

"Acessíveis":

| Condição | Cursos |
|---|---|
| Usuário é `siteadmin` ou tem papel no contexto do sistema | Todos os cursos (visíveis e ocultos) |
| Demais usuários | Cursos com matrícula ativa + cursos de categorias onde o usuário possui papel atribuído |

A validação server-side (`course_search::validate_selection()`) rejeita IDs de cursos fora do escopo do solicitante, prevenindo manipulação do formulário.

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