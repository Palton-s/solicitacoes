# Sistema de Capabilities - Plugin Solicitações

## 🎯 Capabilities Disponíveis

### 1. `local/solicitacoes:view`
**Ver próprias solicitações**
- Permite ao usuário visualizar apenas suas próprias solicitações
- **Padrão**: Todos os usuários autenticados (user)
- **Risco**: Nenhum
- **Páginas**: `myrequests.php`, `view.php` (próprias)

### 2. `local/solicitacoes:viewall`
**Ver todas as solicitações**
- Permite visualizar solicitações de todos os usuários
- **Padrão**: Professores Editores e Gerentes
- **Risco**: RISK_PERSONAL (acesso a dados pessoais)
- **Páginas**: `manage.php` (visualização), `view.php` (todas)

### 3. `local/solicitacoes:submit`
**Criar/enviar solicitações**
- Permite criar novas solicitações de gerenciamento de curso
- **Padrão**: Professores Editores e Gerentes
- **Risco**: RISK_SPAM
- **Páginas**: `index.php` (formulário)

### 4. `local/solicitacoes:manage`
**Gerenciar solicitações**
- Permite aprovar, negar e excluir solicitações
- **Padrão**: Apenas Gerentes (manager)
- **Risco**: RISK_CONFIG | RISK_DATALOSS
- **Páginas**: `manage.php` (gerenciamento completo)

---

## 👥 Como Configurar Papéis

### Passo 1: Acessar Definição de Papéis
```
Administração do site → Usuários → Permissões → Definir papéis
```

### Passo 2: Criar Papel Customizado

#### Exemplo 1: Solicitante
```
Nome: Solicitante de Inscrições
Capabilities:
  ✅ local/solicitacoes:view = Permitir
  ✅ local/solicitacoes:submit = Permitir
  ❌ local/solicitacoes:viewall = Não definido
  ❌ local/solicitacoes:manage = Não definido
```

#### Exemplo 2: Aprovador
```
Nome: Aprovador de Solicitações
Capabilities:
  ✅ local/solicitacoes:view = Permitir
  ✅ local/solicitacoes:submit = Permitir
  ✅ local/solicitacoes:viewall = Permitir
  ✅ local/solicitacoes:manage = Permitir
```

#### Exemplo 3: Visualizador
```
Nome: Auditor de Solicitações
Capabilities:
  ✅ local/solicitacoes:view = Permitir
  ✅ local/solicitacoes:viewall = Permitir
  ❌ local/solicitacoes:submit = Não definido
  ❌ local/solicitacoes:manage = Não definido
```

### Passo 3: Atribuir Papel aos Usuários
```
Administração do site → Usuários → Permissões → Atribuir papéis do sistema
```

---

## 🔒 Níveis de Acesso

### Nível 1: Usuário Comum
- ✅ Ver próprias solicitações
- ❌ Ver solicitações de outros
- ❌ Criar solicitações
- ❌ Aprovar/Negar

### Nível 2: Solicitante (Editing Teacher)
- ✅ Ver próprias solicitações
- ❌ Ver solicitações de outros
- ✅ Criar solicitações
- ❌ Aprovar/Negar

### Nível 3: Coordenador/Auditor
- ✅ Ver próprias solicitações
- ✅ Ver solicitações de todos
- ⚪ Criar solicitações (opcional)
- ❌ Aprovar/Negar

### Nível 4: Aprovador (Manager)
- ✅ Ver próprias solicitações
- ✅ Ver solicitações de todos
- ✅ Criar solicitações
- ✅ Aprovar/Negar/Excluir

---

## 📋 Matriz de Permissões

| Papel/Capability | view | viewall | submit | manage |
|------------------|------|---------|--------|--------|
| **user** (padrão) | ✅ | ❌ | ❌ | ❌ |
| **editingteacher** | ✅ | ✅ | ✅ | ❌ |
| **manager** | ✅ | ✅ | ✅ | ✅ |
| **Solicitante** (custom) | ✅ | ❌ | ✅ | ❌ |
| **Aprovador** (custom) | ✅ | ✅ | ✅ | ✅ |
| **Auditor** (custom) | ✅ | ✅ | ❌ | ❌ |

---

## 🔧 Funções Helper

O plugin fornece funções auxiliares em `lib.php`:

```php
// Verificar se pode criar solicitações
if (local_solicitacoes_can_submit($context)) {
    // Mostrar botão "Nova Solicitação"
}

// Verificar se pode gerenciar
if (local_solicitacoes_can_manage($context)) {
    // Mostrar botões de aprovar/negar
}

// Verificar se pode ver todas
if (local_solicitacoes_can_viewall($context)) {
    // Mostrar todas as solicitações
}

// Verificar se pode ver uma solicitação específica
if (local_solicitacoes_can_view_request($requestid)) {
    // Permitir acesso
}
```

---

## 🚀 Navegação Automática

O plugin adiciona automaticamente itens ao menu de navegação baseado nas permissões do usuário:

- **Todos**: Não veem o menu
- **Com `:view`**: Veem "Minhas Solicitações"
- **Com `:submit`**: Veem "Nova Solicitação"
- **Com `:manage`**: Veem "Gerenciar Solicitações"

---

## ⚡ Atualizando o Plugin

Após alterar `db/access.php`, execute:

```bash
php admin/cli/upgrade.php
```

Ou acesse:
```
Administração do site → Notificações
```

---

## 📚 Referências

- [Moodle Roles and Capabilities](https://docs.moodle.org/en/Roles_and_capabilities)
- [Access API](https://docs.moodle.org/dev/Access_API)
- [Defining Capabilities](https://docs.moodle.org/dev/NEWMODULE_Adding_capabilities)
