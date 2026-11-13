# Workflow Steps

As etapas por que passam os pedidos de cada domínio, com quem fica cada etapa, para onde levam aprovar, negar ou devolver, e o prazo, se houver.

## Quem pode usar

Administradores do site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Toolbox** → **Workflow Steps**, ou **Manage Workflow Steps** em um domínio.

## A tela

- **A lista** - **#**, **Step ID**, **Label**, **Assignee Role**, **Tier**, **On Approve**, **On Deny**, **On Req Changes**, **Timer** e **Active**.
- **O formulário de edição:**
  - **Domain**, **Step ID** (a chave para onde as outras etapas apontam), **Label** (o que os membros veem em **Step:**) e **Sort Order**.
  - **Assignee Role** - o cargo que fica com a etapa, como `Chronicle/{chronicle_slug}/HST` ou `Exec/Archivist/Coordinator`. As chaves se preenchem a partir do pedido; `|` junta vários cargos, e todos que têm qualquer um deles ficam com a etapa.
  - **Visibility Tier** - quem vê as entradas desta etapa na Timeline: staff, coordinator ou archivist.
  - **On Approve**, **On Deny** e **On Request Changes** - o Step ID para onde cada ação leva; vazio quer dizer que o pedido termina ali.
  - **Timer Config (JSON)** - quanto tempo até a etapa se aprovar ou se negar sozinha, e quantos bumps ela precisa antes.
  - **Condition (JSON)** - quando a etapa se aplica; quando não se aplica, o pedido pula para o On Approve desta etapa.
  - **Multi-Approve** - todos os responsáveis precisam aprovar, não só um.
  - **Active**, depois **Add Step** ou **Update Step**.

## Tarefas comuns

### Dar uma etapa a outras pessoas

1. Edite a etapa e mude **Assignee Role**.
2. Acompanhe o próximo pedido real que chegar à etapa: os **Assignees** na página dele mostram para quem ele foi.

### Incluir uma etapa

1. Inclua-a, depois aponte a etapa anterior para ela com **On Approve**.
2. Confira que alguma etapa leva até ela; uma etapa a que nada leva nunca acontece.

## Bom saber

- Um pedido só anda por On Approve, On Deny e On Request Changes; Sort Order define a ordem da lista, não o caminho.
- Uma etapa pulada passa o pedido para o próprio On Approve.
- Um cargo entre chaves tem de corresponder a um campo que o formulário coleta, e o cargo preenchido tem de ser um que alguém tenha. Quando uma etapa cita `{coordinator_genre}` e o pedido não tem, a etapa é pulada em vez de ficar esperando.
- O que você grava aqui vale mais que as etapas que vêm no plugin.

## Solução de problemas

- **Os pedidos ficam parados em uma etapa sem Assignees.** O Assignee Role dela cita um cargo que ninguém tem, ou um campo que o formulário não coleta.
- **Uma etapa nunca acontece.** Nada leva até ela, ou a Condition dela nunca corresponde.

## Relacionados

- [Como um Pedido Anda](approval-flow.md)
- [Domains](admin-domains.md)
- [Form Fields](admin-form-fields.md)
