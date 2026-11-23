# A Página de um Pedido

Um pedido: o que foi pedido, em que ponto está, com quem está agora, e tudo o que aconteceu com ele.

## Quem pode usar

Quem registrou o pedido; quem já o recebeu; quem tem o cargo que a etapa atual pede; o HST, os CMs, a staff e os ASTs da crônica dele; os Coordenadores e Subcoordenadores dos escritórios a que ele pertence, inclusive os que um relatório de crônica marca; o jogador do personagem de que ele trata; e o escritório do Arquivista e os escritórios executivos que supervisionam o OAT. Ações disciplinares também ficam abertas a todo HST, CM, membro da staff, Coordenador e executivo, como em [Relatórios](reports.md). Qualquer outra pessoa vê "You do not have access to this request." Os botões que você recebe dependem do seu papel nele:

| Você é | Você pode |
| --- | --- |
| Responsável pela etapa atual (pelo nome ou pelo cargo) | Approve, Deny, Request Changes, Reassign, Delegate |
| Responsável por uma etapa Coordinator Review, ou da equipe do Arquivista antes da etapa do Arquivista | Add a Coordinator, também |
| Responsável pela etapa atual de um pedido registrado com outras linhas | Approve All Lines, e Veto em cada linha |
| Quem o registrou | Cancel / Withdraw, Bump; Resubmit quando ele volta para você |
| Responsável pela etapa atual e da equipe do Arquivista | Log, também |
| Administrador do site do Arquivista | Hold, Extend Timer; Council Override em um pedido negado |
| Responsável pela etapa atual de um pedido em espera, ou administrador do site do Arquivista | Resume |

## Como chegar

- **No site:** o seu Archivist Inbox ou Dashboard → clique no pedido. Registrar um pedido abre a página dele.
- **No wp-admin:** Archivist → Workspace → aba **Inbox** → clique no pedido.

## A tela

De cima para baixo:

- **O cabeçalho** - o formulário e o número do pedido, o status (pending, held, approved, denied, cancelled, auto-approved ou auto-denied) e **Step:**, a etapa em que ele está. **Watch** acompanha o pedido: ele passa a aparecer no seu inbox, você recebe um email quando ele anda ou termina, e o botão mostra **Watching**.
- **O resumo** - a caixa sob o cabeçalho, em palavras simples: quem pede o quê, por exemplo "Marcus Vell (player: Sam Okafor · Lantern) is requesting R&U approval for Brujah 5+." O nome do personagem é um link que abre o registro do personagem em outra aba. Abaixo, **The bylaw in question is:** lista cada regra em que o pedido se encaixa, com o número da cláusula, o texto, a aprovação que ela pede e o Coordenador que decide. Depois vêm em que pé o pedido está e com quem ele está, e quem o registrou e quando. Um pedido sobre nenhum personagem (um Chronicle Report, um registro de governança) diz quem o está enviando, e um pedido que não se apoia em nenhuma regra não tem a linha da regra.
- **Request submitted** (só logo depois de você registrar o pedido) - uma caixa verde com um botão **Submit another** para o mesmo formulário.
- **Changes Requested** (só quando o pedido foi devolvido) - o que mudar, e quem pediu.
- **Lines in this request** (só quando várias linhas foram registradas juntas) - uma linha para cada uma, com o assunto e em que etapa está. Uma linha vetada diz **Vetoed** e traz o motivo, quando há. Quem revisa ganha uma caixa **Veto** em cada linha aberta. Quando as linhas são sobre mais de um personagem, uma coluna **Character** diz qual.
- **Active Timer** - quando a etapa se aprova ou se nega sozinha se ninguém agir, com a data em que **Expires**.
- **Actions** - os botões que você tem, cada um com uma caixa de nota.
- **History** - cada ação, com quem a tomou, quando, e a nota, para a staff do pedido, os Coordenadores e o Arquivista; um jogador vê o status e a etapa do pedido no cabeçalho e no resumo. **Show History** e **Hide History** recolhem o histórico.
- **Details** - **Originator**, **Chronicle**, **Coordinator Genre**, **Created** e **Updated**, e depois tudo o que o formulário perguntou, como foi registrado. Abaixo: **Linked Regulation Rules**, as regras em que o pedido se encaixa, com **ID**, **Genre**, **Category**, **Condition**, **PC Level** de cada regra e se ela é **Elevated**; **Assignees**, quem teve cada etapa, e o status de cada um; e **Linked Entries**, o pedido **Parent:** e os pedidos registrados a partir deste.

## Tarefas comuns

### Aprovar, negar ou devolver um pedido

1. Abra o pedido pelo seu inbox.
2. Leia **Details** e as **Linked Regulation Rules**.
3. Escreva uma nota e clique em **Approve**, **Deny** ou **Request Changes**. Request Changes devolve o pedido a uma etapa anterior, muitas vezes a quem o registrou.

### Corrigir e reenviar um pedido devolvido a você

1. Abra o pedido; o quadro **Changes Requested** no topo diz o que mudar. A mesma nota chega por email.
2. Mude o que foi pedido, acrescente uma nota e clique em **Resubmit**.

### Passar um pedido para outra pessoa

1. Escreva uma nota.
2. **Reassign** entrega a etapa a outra pessoa de vez. **Delegate** pede que outra pessoa olhe, e depois o pedido volta para você.
3. Procure a pessoa e escolha-a.

### Aprovar várias linhas de uma vez, ou vetar uma

1. Abra qualquer linha do pedido. **Lines in this request** lista todas.
2. Para deixar uma linha de fora, clique em **Veto** na linha dela, escreva um motivo se quiser (é opcional, e quem registrou o pedido o vê ao lado da linha) e clique em **Veto this line**. Só essa linha é encerrada; as outras seguem.
3. Em **Approve All Lines**, escreva uma nota e clique no botão. Todas as linhas que esperam nesta etapa e que você pode aprovar seguem juntas.

### Chamar outro Coordenador

1. Abra o pedido enquanto ele está em Coordinator Review. O Arquivista também pode fazer isso em qualquer etapa antes de Archivist Review.
2. Na caixa **Add a Coordinator**, marque os escritórios cujo poder, regra ou item o pedido usa. Os escritórios que já estão no pedido aparecem marcados e travados.
3. Escreva uma nota dizendo o motivo e clique em **Add a Coordinator**.
4. Os Coordenadores de cada escritório que você adicionar recebem email na hora, e o pedido espera a aprovação de todos os escritórios. Sua nota aparece no History.

### Retirar o seu próprio pedido

1. Escreva uma nota e clique em **Cancel / Withdraw**.

## Bom saber

- Toda ação, menos Bump, precisa de uma nota.
- **Bump** conta para os bumps que algumas etapas com prazo precisam antes de aprovar ou negar sozinhas. Só funciona enquanto a etapa tem um prazo ativo, e não muda o prazo.
- O History mostra a cada pessoa o que o papel dela permite: a staff do pedido vê as entradas da staff, os Coordenadores veem as deles e as da staff, e o Arquivista vê tudo. Jogadores não veem o History.
- O que acontece depois que você aprova depende do formulário; veja [Como um Pedido Anda](approval-flow.md).

## Solução de problemas

- **"Please log in to view this entry."** Entre na sua conta primeiro.
- **"You do not have access to this request."** Ele não faz parte do seu trabalho: você não o registrou, ele não está na sua etapa, e não é da sua crônica nem para o seu escritório. Peça a quem mandou o link, ou ao Arquivista.
- **"Entry not found."** Nenhum pedido tem esse número.
- **"You cannot take that action on this request."** O pedido andou, ou a etapa não é sua. Recarregue a página para ver os botões que você tem agora.
- **"A note is required for this action."** Escreva uma nota antes de clicar.
- **"No active timer to bump."** A etapa não tem prazo correndo, então não há o que dar bump.
- **Não tenho botões.** A etapa atual não é sua. **Assignees** mostra com quem ela está.

## Relacionados

- [Como um Pedido Anda](approval-flow.md)
- [Fazer um Pedido](submit.md)
- [Archivist Inbox](inbox.md)
- [Como Revisar Pedidos](../reviewing-guide.md)
