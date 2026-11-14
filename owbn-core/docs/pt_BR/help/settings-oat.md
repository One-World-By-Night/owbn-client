# Configurações do OAT

De onde as páginas do OAT deste site (Dashboard, Inbox, Submit, a página de um pedido, o Registry) tiram os dados.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **OWBN Core** → **Settings** → **OAT**.

## A tela

- **Data Source**
  - **Local** - o próprio plugin OAT roda neste site. Só archivist.owbn.net.
  - **Remote** - toda página do OAT aqui lê e grava pelo gateway de archivist.owbn.net. Todos os outros sites da OWbN.
- **Remote URL Override** e **API Key Override** - só quando os dados do OAT vêm de um site diferente do **Default Remote Gateway** em [General](settings-general.md).
- **Status** - o modo, se o plugin OAT está neste site, e o endereço que ele usa.

**Save Changes** salva a aba.

## Bom saber

- A chave aqui é a chave de gateway do site do Arquivista. Os membros nunca a veem: as páginas do OAT mandam os pedidos pelo servidor deste site.
- Os membros usam a mesma conta da OWbN em todos os sites; o site do Arquivista cria a conta deles na primeira vez que usam o OAT a partir de outro site.

## Solução de problemas

- **As páginas do OAT dizem que o pedido falhou, ou não mostram nada.** Confira se o endereço é archivist.owbn.net e se a chave é a chave de gateway atual do site do Arquivista, e tente de novo.
- **"OAT remote gateway URL is not configured."** ou **"OAT remote API key is not configured."** Preencha a substituição, ou o **Default Remote Gateway** em General.

## Relacionados

- [Configurações Gerais](settings-general.md)
- [Solução de problemas do OWBN Core](troubleshooting.md)
