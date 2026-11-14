# Relatório Chronicle Staff

O HST e o CM de cada crônica, conferidos com quem tem o cargo de CM de cada crônica no AccessSchema, para que os dois possam ser acertados.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **Reports** → **Chronicle Staff**.

## A tela

Duas visões, **By Chronicle** e **By User**.

**By Chronicle**, uma linha por crônica:

- Filtros: **Active**, **Inactive (Decommissioned)** ou **All**; **Game Status** **Full**, **Probationary** ou **Satellite**. **Filter** os aplica.
- **Chronicle**, **HST**, **HST Email**, **CM**, **CM Email**, **Prob** e **Sat**.
- **CM Role (ASC)** - quem tem `chronicle/<slug>/cm` no AccessSchema. Uma satélite mostra o CM da crônica principal, marcado "(from parent: …)".
- Cor: verde quando o CM da crônica e quem tem o cargo coincidem, amarelo para um nome parecido, vermelho quando falta o cargo, quando mais de uma pessoa o tem, ou quando é de outra pessoa.
- **Confirm as CM** (nas linhas que não estão verdes) - torna quem tem o cargo o CM da crônica e dá o cargo a essa pessoa.
- Em massa: **Confirm match** ou **Ignore (dismiss)** nas linhas que você marcar.
- **Confirm as CM** e as ações em massa funcionam em chronicles.owbn.net, onde as fichas das crônicas ficam.
- Contagens no pé: **Matched**, **Fuzzy**, **Problems** e **Ignored**.
- **Refresh ASC Data** - busca de novo quem tem os cargos; fora isso, eles ficam guardados por quatro horas.

**By User**, uma linha por pessoa com algum cargo `chronicle/<slug>/cm`, com cada cargo como etiqueta e o seu estado: se coincide com o CM da crônica. Em massa: **Confirm match**, **Revoke ASC role** ou **Ignore (dismiss)**.

## Tarefas comuns

### Acertar uma crônica cujo CM não coincide

1. Ache a linha vermelha ou amarela.
2. Se quem tem o cargo é o CM certo, clique em **Confirm as CM** em chronicles.owbn.net.
3. Se quem tem o cargo está errado, passe para **By User**, marque a etiqueta da pessoa para aquela crônica e escolha **Revoke ASC role**.

## Bom saber

- **Revoke ASC role** tira o cargo no AccessSchema na hora, e esta página não desfaz isso. Dê o cargo de novo no AccessSchema se precisar.
- **Ignore (dismiss)** só esconde uma linha deste relatório; nada muda.
- O relatório lista emails da staff, por isso só administradores do site o abrem.

## Solução de problemas

- **"owbn-core client API not available on this site."** O site não tem dados de crônicas; ligue Chronicles em [General](settings-general.md).
- **Sem o botão Confirm as CM nem ações em massa, ou "Chronicle not found."** Use o relatório em chronicles.owbn.net, onde as fichas das crônicas ficam.

## Relacionados

- [Relatório Conflicts](reports-conflicts.md)
- [Configurações do accessSchema](settings-accessschema.md)
