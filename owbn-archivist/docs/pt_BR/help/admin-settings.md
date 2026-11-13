# Archivist Settings

As configurações do próprio OAT no site do Arquivista: quem pode criar personagens, as regras de regulamentação, e a taxonomia de criaturas.

## Quem pode usar

Contas com o acesso do Arquivista no site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Settings**.

## A tela

Três abas:

- **General** - **Character Creation — Allowed Roles**: um padrão de cargo por linha, com `*` para qualquer slug, como `chronicle/*/hst`, `coordinator/*/coordinator` e `exec/*/coordinator`. Quem tem um cargo que corresponde pode criar personagens em Fazer um Pedido; deixe vazio para que todos com login possam criar. **Save Settings** grava.
- **Regulation Rules** - veja [Regulation Rules](admin-regulation-rules.md).
- **Creature Taxonomy** - veja [Creature Taxonomy](admin-taxonomy.md).

## Tarefas comuns

### Deixar outro cargo criar personagens

1. Em **General**, inclua o padrão do cargo em uma linha própria.
2. Clique em **Save Settings**.

## Bom saber

- Administradores do site sempre podem criar personagens, diga a lista o que disser.
- Os outros sites da OWbN leem esta lista pelo site do Arquivista e guardam uma cópia por cinco minutos. Se não conseguem lê-la, ninguém além dos administradores do site cria personagens ali até conseguirem.

## Solução de problemas

- **Alguém com o cargo ainda não consegue criar personagens.** Os cargos dessa pessoa chegam ao OAT em cerca de quinze minutos, ou quando ela entra de novo.

## Relacionados

- [Regulation Rules](admin-regulation-rules.md)
- [Creature Taxonomy](admin-taxonomy.md)
- [Quem Pode Fazer o Quê](roles.md)
