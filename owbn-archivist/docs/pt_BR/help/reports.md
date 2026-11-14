# Relatórios

Contagens e listas tiradas do registro e dos pedidos: relatórios de crônica, pedidos por domínio e status, conteúdo personalizado, ações disciplinares, e totais de personagens.

## Quem pode usar

HSTs, staff e CMs veem as próprias crônicas; Coordenadores e Subcoordenadores veem o próprio escritório. Tudo fica aberto para:

| Tela de relatórios | Tudo para |
| --- | --- |
| OAT Reports (todos os sites da OWbN) | Administradores do site, o Arquivista e a staff do Arquivista, o Coordenador de Membros, o Coordenador-Chefe, os AHCs, o Coordenador de Web e o escritório Admin |
| Details (o site do Arquivista) | Os mesmos |

Jogadores e ASTs não têm relatórios.

## Como chegar

- **OAT Reports:** em archivist.owbn.net, wp-admin → menu **Archivist** → **Reports**. Os outros sites da OWbN não têm.
- **Details:** em archivist.owbn.net, wp-admin → menu **Archivist** → **Details**. Se o seu menu não mostra, abra archivist.owbn.net/wp-admin/admin.php?page=oat-reports.

## A tela

**OAT Reports** tem uma aba por relatório:

- **Chronicle Reports** - uma linha por crônica, com o rótulo "Cidade, Estado, País: Nome"; clique em uma linha para ver os relatórios dela, do mais novo ao mais antigo. Uma caixa de filtro procura pelo nome, cidade, estado e região.
- **Pending Reports** - relatórios de crônica ainda sem decisão, do mais antigo ao mais novo, cada um marcado "Awaiting archivist" ou "Sent back to submitter".
- **Entries by Domain**, **Entries by Status** e **Entries by Coordinator** - contagens de pedidos.
- **Custom Content by Type** - conteúdo personalizado aprovado, por tipo.
- **Disciplinary Actions** e **DA by Level** - ações disciplinares, e quantas há em cada nível.
- **Recent Activity** - os pedidos e as ações mais recentes.

**Details** tem uma aba por lista: **All Characters**, **Active Only**, **By Region**, **By Chronicle**, **By Player**, **By Creature Type** e **By R&U Classification**. Cada aba exporta para CSV. Um Coordenador vê os itens que o escritório dele controla; **Show All** acrescenta os demais nos mesmos personagens.

## Tarefas comuns

### Encontrar os relatórios de uma crônica

1. Abra **OAT Reports** → **Chronicle Reports**.
2. Digite parte do nome, da cidade ou do estado da crônica no filtro e clique na linha dela.

### Ver quais relatórios de crônica ainda esperam

1. Abra **Pending Reports**.

### Exportar uma lista

1. Abra **Details** e a aba que você quer.
2. Clique em **Export CSV**.

## Bom saber

- As duas telas partem dos mesmos pedidos; Details lista personagens e itens de R&U, e OAT Reports conta pedidos.
- O seu alcance em cada tela vem dos seus cargos, então um cargo novo aparece em cerca de quinze minutos ou na próxima vez que você entrar.
- Um Coordenador também vê, em Chronicle Reports, todo relatório que marcou o escritório dele em **Notify Coordinators**; o email que esses relatórios mandam aponta para cá.

## Solução de problemas

- **"You do not have permission to view reports."** Os relatórios são para staff, Coordenadores e os escritórios executivos.
- **Um relatório mostra só parte da OWbN.** Ele mostra o que o seu cargo alcança; veja [Quem pode usar](#quem-pode-usar).

## Relacionados

- [Quem Pode Fazer o Quê](roles.md)
- [Como um Pedido Anda](approval-flow.md)
- [Chronicle Report](form-ca_reporting.md)
