# Archivist Toolbox

Onde o OAT é configurado: os domínios, os formulários, os campos de cada formulário, e as etapas por que passam os pedidos de cada domínio.

## Quem pode usar

Administradores do site do Arquivista. Uma mudança aqui muda o que todos os membros veem em todos os sites da OWbN.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Toolbox**.

## A tela

Quatro abas, cada uma uma tela própria:

- **Domains** - os tipos de pedido. Veja [Domains](admin-domains.md).
- **Forms** - os pedidos que os membros registram. Veja [Forms](admin-forms.md).
- **Workflow Steps** - as etapas por que passam os pedidos de um domínio. Veja [Workflow Steps](admin-workflow-steps.md).
- **Form Fields** - o que cada formulário pergunta. Veja [Form Fields](admin-form-fields.md).

## Tarefas comuns

### Incluir um novo tipo de pedido

1. Inclua o formulário em **Forms** e dê um domínio a ele.
2. Inclua os campos dele em **Form Fields**.
3. Confira as etapas do domínio em **Workflow Steps**.
4. Abra o novo formulário em [Fazer um Pedido](submit.md) e confira os campos. Não registre um pedido de teste: os pedidos são registros e ficam.

## Bom saber

- O que se grava aqui vale na hora, em todos os sites.
- Uma configuração que cita um campo, um valor ou uma etapa que não existe falha em silêncio: nada dá erro, o pedido só nunca anda. Confira o formulário em Fazer um Pedido depois de cada mudança, e acompanhe o próximo pedido real.

## Solução de problemas

- **"You do not have permission to access the toolbox."** O Toolbox é para administradores do site.

## Relacionados

- [Domains](admin-domains.md)
- [Forms](admin-forms.md)
- [Form Fields](admin-form-fields.md)
- [Workflow Steps](admin-workflow-steps.md)
