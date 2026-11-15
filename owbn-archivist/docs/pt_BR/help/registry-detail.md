# Ficha do Personagem

A ficha de um personagem no registro: quem o joga, o que ele é, quais crônicas e escritórios de Coordenador podem vê-lo, e todos os pedidos aprovados nele.

## Quem pode usar

Você pode abrir a ficha de um personagem se for o jogador vinculado a ele, staff (HST, AST ou CM) de uma crônica à qual o personagem tem acesso concedido, um Coordenador ou Subcoordenador cujo escritório tem acesso ao personagem, ou o Arquivista, um AHC, o Coordenador-Chefe, o Coordenador de Web ou um administrador do site.

O que você pode alterar é o mesmo em todos os sites da OWbN, no wp-admin e no site:

| Quem | Pode alterar |
| --- | --- |
| Administradores do site; o Arquivista, AHC1, AHC2 e o Coordenador de Web | Todos os campos |
| O HST, a staff, o CM ou o AST da própria crônica do personagem | Todos os campos, nos PCs e NPCs da crônica |
| Um Coordenador ou Subcoordenador | Todos os campos, nos NPCs do próprio escritório |
| O jogador vinculado ao personagem | Só o Status, para aposentá-lo |
| Qualquer outra pessoa que possa abri-la | Nada; a ficha é só leitura |

## Como chegar

- **No site:** a página Archivist Registry → clique no nome do personagem.
- **No wp-admin:** Archivist → Workspace → aba **Registry** → clique no nome do personagem.

O Arquivista e cada site da OWbN com o registro (chronicles, council, players) mostram a mesma ficha.

## A tela

- **Character Name**, **Chronicle** (procure uma crônica; para um NPC de escritório, um escritório de Coordenador), **PC/NPC** e **Status** (Active, Inactive, Dead, Shelved).
- **Creature** - quatro listas que se filtram: **Genre**, **Faction**, **Type** e, quando o tipo tem, **Variant**. Mudar a Faction mantém o Type. Um Type de Vampiro que não está na lista da nova Faction continua selecionado e aparece como "(Sect Defector)"; qualquer outro valor fora da sua lista aparece como "(not on this list)". A ficha grava o nome simples.
- **Player Name** e **Player Email** (só PCs) - a pessoa que joga o personagem, como texto.
- **Linked Account** (quando você pode alterar todos os campos; no site, só PCs) - a conta da OWbN à qual o personagem pertence. É ela que decide em qual My Characters o personagem aparece.
  - **Currently linked** mostra a conta, com **Unlink**.
  - A caixa de busca encontra contas por nome, login ou caminho de cargo (dois caracteres ou mais), ou por um endereço de email inteiro; parte de um endereço não encontra ninguém. Cada opção mostra o nome da pessoa com o login dela, ou o cargo pelo qual ela foi encontrada, e uma busca pelo endereço inteiro mostra esse endereço. Escolher uma preenche Player Name quando está vazio, e também Player Email quando você buscou pelo endereço.
  - **Use the Player Email** encontra a conta cujo email é exatamente o de Player Email.
- **NPC Owner** (só NPCs) - a crônica ou o escritório de Coordenador que controla o NPC. O wp-admin mostra isso como **NPC Coordinator** (o escritório) e **NPC Type** (chronicle ou coordinator).
- **Update Character** - grava as alterações. Um vínculo ou desvínculo só vale quando você grava.
- **Active Grants** - as crônicas e escritórios de Coordenador que podem ver este personagem, com quando cada um foi incluído e quando expira. **Revoke** encerra um acesso.
- **Add Grant** - uma crônica ou escritório de Coordenador, uma data opcional em **Expires**, e **Add Grant**.
- **Grant History** - acessos que expiraram ou foram encerrados.
- **Custom Content Created** - conteúdo personalizado aprovado com este personagem como criador.
- **Registry Entries** - todos os pedidos aprovados no personagem. Cada um abre com Entry ID, Domain, Form, Approving Authority, Regulation Level, o item, Status e data.
- **Show All** (Coordenadores) - Registry Entries começa com os itens de R&U que o seu escritório controla; marque Show All para incluir os itens de outros Coordenadores.

Quando só o Status cabe a você, a ficha mostra os dados como texto e uma lista de Status com Inactive, Dead e Shelved.

## Tarefas comuns

### Vincular um personagem à conta do jogador

1. Abra a ficha.
2. Em **Linked Account**, digite parte do nome ou do login do jogador, ou o endereço de email inteiro dele, e escolha-o na lista. Se **Player Email** já tem o endereço dele, clique em **Use the Player Email**.
3. Clique em **Update Character**.

### Passar um personagem para outra conta

1. Abra a ficha. **Currently linked** mostra a conta atual.
2. Procure a pessoa certa e escolha-a, ou clique em **Unlink** para deixá-lo sem vínculo.
3. Clique em **Update Character**.

### Registrar uma deserção de seita

1. Depois que os Coordenadores da Seita e do Clã aprovarem, abra a ficha.
2. Escolha a nova **Faction**. O Type continua; se a lista da nova Seita não o tem, ele aparece como "(Sect Defector)".
3. Clique em **Update Character**.

### Aposentar o seu próprio personagem (jogador)

1. Abra o seu personagem em My Characters na página Archivist Registry.
2. Escolha **Inactive**, **Dead** ou **Shelved** em **Status**.
3. Clique em **Update Character**.

### Deixar uma crônica ou escritório de Coordenador ver um personagem

1. Em **Add Grant**, procure a crônica ou o escritório de Coordenador.
2. Preencha **Expires** se o acesso deve acabar em uma data.
3. Clique em **Add Grant**.

### Ver itens de outros Coordenadores (Coordenador)

1. Marque **Show All** acima de Registry Entries.

## Bom saber

- Um personagem aparece em My Characters de uma pessoa só quando a Linked Account dele é a conta dela. Player Name e Player Email são texto e não vinculam nada sozinhos.
- Um PC cujo Player Email ainda não tem conta na OWbN se vincula a essa pessoa quando a conta dela chega ao site do Arquivista: na primeira vez que ela entra lá, ou usa as páginas do Arquivista em qualquer site da OWbN.
- A busca de contas e o vínculo gravado usam sempre as contas do site do Arquivista, em qualquer site da OWbN onde você esteja.
- Só staff, Coordenadores, o Arquivista e administradores do site podem buscar contas; um jogador não vê resultados.
- Incluir um acesso é permitido a administradores do site, ao Arquivista, aos AHCs, ao Coordenador de Web e ao HST, à staff, ao CM ou ao AST de qualquer crônica. Revogar um é permitido a administradores do site, ao Arquivista, aos AHCs e ao Coordenador de Web.
- As listas de Creature trazem o que cada Seita pode escolher na criação do personagem. Um clã fora dessa lista é um Sect Defector pelos Character Bylaws, o que precisa da aprovação dos Coordenadores da Seita e do Clã; a ficha deixa você gravar depois que eles aprovarem.
- Para voltar um personagem aposentado a ativo, o jogador precisa de um HST.

## Solução de problemas

- **"You do not have permission to view this character."** O personagem não está vinculado à sua conta nem tem acesso concedido à sua crônica ou escritório. Fale com o HST dele ou com o Arquivista.
- **"You do not have permission to edit this character."** Só o Status cabe a você, ou nada. Veja [Quem pode usar](#quem-pode-usar).
- **"Players can only mark their own character as inactive, dead, or shelved. Contact an HST to reactivate."** Escolha Inactive, Dead ou Shelved; um HST é quem volta um personagem a ativo.
- **"No account matches that."** Ninguém tem uma conta da OWbN com esse nome, login ou endereço de email ainda, ou você digitou só parte de um endereço (digite-o inteiro), ou você é jogador e não pode buscar contas. Deixe o Player Email preenchido: o personagem se vincula sozinho quando a pessoa entrar pela primeira vez.
- **O personagem não aparece em My Characters do jogador.** A Linked Account dele está vazia ou é de outra pessoa. Vincule-o como acima.
- **O Type aparece como "(Sect Defector)".** O clã do personagem não está na lista de criação da Seita dele. Isso é esperado depois de uma deserção aprovada.

## Relacionados

- [Archivist Registry](registry.md)
- [Fazer um Pedido](submit.md)
- [Quem Pode Fazer o Quê](roles.md)
- [Guia do Arquivista](../admin-guide.md#personagens-e-o-registro)
