# Plugin Moodle: Solicitações de Gerenciamento de Curso

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
plugin_moodle_a2/
├── classes/
│   └── form/
│       └── request_form.php    # Classe do formulário
├── db/
│   ├── access.php          # Definições de capacidades
│   ├── install.php         # Script de instalação
│   └── install.xml         # Estrutura do banco de dados
├── lang/
│   ├── en/
│   │   └── local_solicitacoes.php    # Strings em inglês
│   └── pt_br/
│       └── local_solicitacoes.php    # Strings em português
├── index.php              # Formulário para usuários
├── manage.php             # Interface administrativa
├── settings.php           # Configurações do admin
├── styles.css             # Estilos personalizados
├── version.php            # Informações da versão
└── README.md              # Este arquivo
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