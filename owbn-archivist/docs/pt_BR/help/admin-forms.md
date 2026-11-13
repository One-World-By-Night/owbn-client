# Forms

Os pedidos que os membros registram, como R&U Request, Registration ou Chronicle Report, e o domínio de cada um.

## Quem pode usar

Administradores do site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Toolbox** → **Forms**.

## A tela

- **A lista** - **Slug**, **Label**, **Fields** (quantos), **Domains**, **Order** e **Active**.
- **O formulário de edição:**
  - **Label** - o nome que os membros veem na lista Form.
  - **Slug** - a chave do formulário; os campos, as regras e as etapas se referem a ela.
  - **Description** - uma linha curta sobre o formulário.
  - **Active** - um formulário inativo sai de Fazer um Pedido.
  - **Sort Order** - a posição dele na lista Form.
  - **Assigned Domains** - onde ele é registrado.
  - **Manage Form Fields** - abre os campos dele.
  - **Add Form** ou **Update Form** - grava.

## Tarefas comuns

### Incluir um formulário

1. Clique em **Add Form**, preencha Label, Slug e Description, e marque o domínio dele em **Assigned Domains**.
2. Grave, depois clique em **Manage Form Fields** e inclua o que ele pergunta.
3. Abra-o em [Fazer um Pedido](submit.md) e confira os campos.

## Bom saber

- Cada formulário tem a sua página de ajuda para os membros; veja [Fazer um Pedido](submit.md#relacionados).
- O slug de um formulário faz parte do endereço da página de ajuda dele em support.owbn.net, e as regras de envio e as etapas também o citam, então não o mude depois que ele estiver em uso.

## Solução de problemas

- **Um formulário não aparece em Fazer um Pedido.** Ele está inativo, não tem domínio, ou o domínio dele está inativo.

## Relacionados

- [Form Fields](admin-form-fields.md)
- [Domains](admin-domains.md)
- [Submission Rules](admin-submission-rules.md)
