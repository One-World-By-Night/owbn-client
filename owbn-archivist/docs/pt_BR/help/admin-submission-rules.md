# Submission Rules

Verificações que rodam quando um membro registra um pedido: bloquear, avisar, ou exigir algo antes de ele entrar.

## Quem pode usar

Administradores do site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Rules**.

## A tela

- **Current Rules** - cada regra com o seu domínio, formulário, verificação e ação, e **Search**.
- **Add New Rule** e **Edit Rule:**
  - **Label** - para que a regra serve, como "Block transfers from probationary chronicles".
  - **Domain** e **Form** - onde ela vale; deixe Domain em branco para todos os domínios.
  - **Check Source** - o que ela lê: **Entry (meta/fields)**, as respostas do pedido; **Character (registry)**, a ficha do personagem; ou **Chronicle (post meta)**, o cadastro da crônica.
  - **Field Name** - a resposta ou o campo a verificar.
  - **Operator** - **Equals**, **Not Equals**, **In (comma-separated)**, **Not In (comma-separated)**, **Is Empty** ou **Is Not Empty**. Deixe **Value** em branco nas verificações de vazio.
  - **Value** - com o que comparar, como `1` ou `decommissioned,dissolved`.
  - **Action** - **Block — prevent submission**, **Warn — allow but show warning**, ou **Require — must meet condition to proceed**.
  - **Message** - mostrada ao membro quando a regra age.
  - **Priority** - números menores são verificados primeiro.
  - **Rule is active**, depois **Add Rule** ou **Update Rule**.

## Tarefas comuns

### Barrar um tipo de pedido

1. Inclua uma regra com **Action** em Block, uma **Message** clara, e a condição.
2. Confira a regra na lista; ela vale a partir do próximo pedido que alguém registrar. Não registre um pedido de teste: um pedido fica como registro, e um barrado deixa uma linha salva pela metade.

## Bom saber

- Uma regra que cita um slug de formulário ou um campo que não existe nunca dispara e não mostra erro. Teste toda regra nova.

## Solução de problemas

- **"You do not have permission to manage rules."** As regras são para administradores do site.
- **Uma regra nunca dispara.** Confira o slug do formulário e o nome do campo em [Forms](admin-forms.md) e [Form Fields](admin-form-fields.md).

## Relacionados

- [Regulation Rules](admin-regulation-rules.md)
- [Forms](admin-forms.md)
- [Fazer um Pedido](submit.md)
