# Form Fields

O que cada formulário pergunta: cada campo, o tipo dele, se é obrigatório, e quando aparece.

## Quem pode usar

Administradores do site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Toolbox** → **Form Fields**, ou **Manage Form Fields** em um formulário.

## A tela

- **A lista** - **Order**, **Key**, **Type**, **Label**, **Context**, **Req** (obrigatório) e **Active**, com links de edição.
- **O formulário de edição:**
  - **Domain** e **Form** - onde o campo fica.
  - **Field Key** - o nome com que o pedido guarda a resposta; as regras, as etapas e os relatórios leem ele.
  - **Type** - texto, uma lista, um seletor de personagem, um seletor de crônica, um seletor de pessoa, e assim por diante.
  - **Label**, **Placeholder** e **Help Text** - o que o membro vê.
  - **Required** - o formulário não é registrado sem ele.
  - **Context** - onde o campo aparece: no formulário, ou em uma etapa de revisão.
  - **Sort Order** - a posição dele no formulário.
  - **Default Value** - preenchido quando o membro não preenche.
  - **Options JSON** - as opções de uma lista; **Import Options CSV** as carrega de um arquivo.
  - **Validation JSON**, **Condition JSON** (mostra o campo só quando outra resposta corresponde) e **Attributes JSON** (configurações que um tipo de campo lê, como o cargo que filtra um seletor de personagem).
  - **Public Registry** - a resposta aparece em páginas públicas como o Fame Registry.
  - **Active**, depois **Add Field** ou **Update Field**.

## Tarefas comuns

### Incluir uma pergunta em um formulário

1. Clique em **Add Field**, escolha o Domain e o Form, e preencha Field Key, Type e Label.
2. Marque **Required** se o formulário não pode ir sem ele, e grave.
3. Abra o formulário em [Fazer um Pedido](submit.md) para vê-lo.

### Fazer um campo aparecer só para algumas respostas

1. Coloque a condição em **Condition JSON**, citando a chave do outro campo e o valor que deve corresponder.

## Bom saber

- Ao gravar, o pedido descarta qualquer resposta cujo campo não está definido aqui, então uma etapa ou regra que lê um campo precisa do campo no formulário, nem que seja oculto.
- Uma chave de campo citada por uma etapa ou regra tem de corresponder exatamente; um erro de digitação falha em silêncio.

## Solução de problemas

- **Um campo não aparece.** Ele está inativo, o Context dele é uma etapa de revisão, ou o Condition JSON dele não corresponde.

## Relacionados

- [Forms](admin-forms.md)
- [Workflow Steps](admin-workflow-steps.md)
- [Referência dos Campos](form-field-reference.md)
