# Domains

Os tipos de pedido que o OAT trata: Character Lifecycle, Custom Content, Chronicle Actions, Binding Agreements, Disciplinary Actions, Governance Records e Player Actions.

## Quem pode usar

Administradores do site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Toolbox** → **Domains**.

## A tela

- **A lista** - **Slug**, **Label**, **Order**, **Archivist Mode**, **Active**, e links para **Workflow Steps** e **Form Fields** do domínio. Arraste uma linha pela alça para reordenar.
- **O formulário de edição:**
  - **Label** - o nome que os membros veem na lista Domain de [Fazer um Pedido](submit.md).
  - **Slug** - a chave do domínio; as etapas e os formulários se referem a ela.
  - **Active** - um domínio inativo sai da lista Domain.
  - **Sort Order** - números menores vêm primeiro; números iguais seguem a ordem do nome.
  - **Archivist Mode** - **Auto** registra o pedido na Timeline quando a última etapa o aprova; **Manual** deixa o registro para o botão **Log** do Arquivista.
  - **Assigned Forms** - os formulários registrados neste domínio.
  - **Quick Links** - **Manage Workflow Steps**, **Fields** e **Create a form**.
  - **Add Domain** ou **Update Domain** - grava.

## Tarefas comuns

### Renomear um domínio

1. Clique no domínio, mude **Label** e clique em **Update Domain**. Não mexa no **Slug**: as etapas e os formulários usam ele.

### Tirar um domínio de uso

1. Desmarque **Active** e clique em **Update Domain**. Os pedidos dele continuam onde estão.

## Bom saber

- Mudar um slug quebra todas as etapas e todos os formulários que citam o antigo.

## Solução de problemas

- **Um domínio não aparece em Fazer um Pedido.** Ele está inativo, ou não tem formulário ativo.

## Relacionados

- [Forms](admin-forms.md)
- [Workflow Steps](admin-workflow-steps.md)
- [Archivist Toolbox](admin-toolbox.md)
