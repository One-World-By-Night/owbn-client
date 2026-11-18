# Account Sync

Dá a cada conta do www uma conta no site do Arquivista, uma vez por dia, para que quem tem um papel e nunca entrou aqui ainda possa receber um pedido.

## Quem pode usar

Administradores do site do Arquivista.

## Como chegar

Em archivist.owbn.net, wp-admin → menu **Archivist** → **Account Sync**.

## A tela

- **Last run** - quando a sincronização rodou pela última vez.
- **Accounts on www** - quantas contas o www tem.
- **Already here (same email)** - contas do www cujo email já pertence a uma conta deste site.
- **Created in this run** - as contas novas criadas desta vez.
- **Skipped (no usable email, or the web admin)** - a conta do administrador web e qualquer conta sem email utilizável.
- **Could not create (login taken)** - uma conta do www com email novo cujo login já pertence a outra conta aqui. Cada uma aparece abaixo da tabela, com a conta que tem o login.
- **Characters linked to an account in this run** - personagens sem conta ligada que foram ligados desta vez, porque o email de jogador do personagem é igual ao email de uma conta.
- **Characters with a player email and no linked account** - personagens que ainda esperam. O email deles não pertence a nenhuma conta aqui ainda, ou pertence a duas.
- **Errors** - contas que não foram salvas.
- **Run it now** roda a sincronização na hora.
- **Recent runs** - as últimas execuções, da mais nova para a mais antiga, com quantos personagens cada uma ligou.

## Tarefas comuns

### Conferir se todas as contas estão cobertas

1. Abra **Account Sync**.
2. Leia a linha abaixo da tabela: ela diz quantas contas do www têm conta aqui. As que faltam estão na lista **Accounts it could not create**.

### Rodar a sincronização agora

1. Clique em **Run it now**. Quem entrou no www hoje de manhã tem conta aqui quando ela termina.

### Resolver um conflito de login

1. Ache a conta que tem o login; a lista diz qual é.
2. Se for a mesma pessoa, troque o email dessa conta para o do www, em wp-admin → **Users**. A próxima execução acha a correspondência.
3. Se for outra pessoa, o login precisa mudar no www. Fale com a equipe Web.

## Bom saber

- As contas se casam só pelo email, nunca pelo login nem pelo ID de jogador.
- A sincronização só acrescenta contas. Nunca muda nem apaga uma que já existe.
- Uma conta nova tem senha aleatória e o papel Subscriber. A pessoa entra pelo SSO como sempre, e ninguém recebe email.
- Os personagens se ligam só pelo email. Um personagem sem conta ligada é ligado à conta cujo email é igual ao email de jogador do personagem: depois de cada sincronização, quando uma conta é criada e quando a pessoa entra. Email de jogador em branco, email que nenhuma conta tem, email que duas contas têm e o email do administrador web ficam de fora, e um personagem que já está ligado a alguém continua dessa pessoa. O Player Email do registro nunca muda.
- Um pedido só pode ser atribuído a quem tem um papel e uma conta aqui. É isto que dá a conta.

## Solução de problemas

- **"The www account source is not set".** Este site não tem endereço nem chave do www. Fale com a equipe Web.
- **Um aviso no alto das páginas do Archivist: "The daily account sync from www has not succeeded lately".** A última execução falhou, ou nenhuma deu certo em 36 horas. Abra **Account Sync**; a última execução mostra o motivo. Se citar o www ou a chave, fale com a equipe Web.
- **Uma pessoa está no www mas não tem conta aqui.** Clique em **Run it now** e olhe em **Accounts it could not create**.
- **Um jogador não consegue abrir o personagem.** Abra o [Character Record](registry-detail.md). Se o Player Email estiver em branco ou não for o email da conta dele, corrija ali, ou peça que ele entre com a conta que tem esse email. A ligação é feita no próximo login dele ou na próxima sincronização.

## Relacionados

- [Archivist Settings](admin-settings.md)
- [Character Record](registry-detail.md)
