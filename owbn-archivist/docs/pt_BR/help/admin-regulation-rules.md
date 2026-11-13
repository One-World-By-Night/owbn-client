# Regulation Rules

As regras que dizem o que um pedido precisa: qual Coordenador controla um item, e se ele não é regulamentado, se precisa avisar um Coordenador, se precisa da aprovação de um Coordenador, se precisa de votação no conselho, ou se é proibido.

## Quem pode usar

Administradores do site do Arquivista. Os membros veem as regras de um pedido na página dele, e as escolhem nos formulários de R&U.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Settings** → aba **Regulation Rules**.

## A tela

- **Search** e a lista - **ID**, **Ref**, **Genre**, **Category**, **Subcategory**, **Condition**, **PC Level**, **NPC Level**, **Coordinator**, **Elevated** e **Active**.
- **Add New Rule** - Genre, Category, Subcategory, **Condition Name**, **PC Level** e **NPC Level** (Unregulated, Coordinator Notify, Coordinator Approval, Council Vote ou Disallowed), **Controlling Coordinator**, e **Elevation**.
- **CSV Export** - **Download CSV** salva todas as regras.
- **CSV Import (append only)** - **Import CSV** inclui as linhas de um arquivo.
- **CSV Sync (smart upsert)** - **Sync CSV** atualiza as regras que correspondem e inclui o resto.

## Tarefas comuns

### Incluir uma regra

1. Clique em **Add New Rule**, preencha, e defina os níveis e o Controlling Coordinator.
2. Grave; a regra aparece no seletor Regulation Rules dos formulários de R&U.

### Atualizar muitas regras de uma vez

1. Faça **Download CSV**, edite, e faça **Sync CSV** de volta.

## Bom saber

- A Coordinator Review de um pedido vai para o Controlling Coordinator da regra.
- **Elevated** impede uma etapa com prazo de se aprovar sozinha.
- O Ref de uma regra é o artigo do regulamento de onde ela vem; os pedidos o mostram.

## Solução de problemas

- **Uma regra não aparece no seletor.** Ela está inativa.
- **Uma Coordinator Review foi para o escritório errado.** Confira o Controlling Coordinator da regra.

## Relacionados

- [Como um Pedido Anda](approval-flow.md)
- [R&U Request](form-cl_ru_request.md)
- [Submission Rules](admin-submission-rules.md)
