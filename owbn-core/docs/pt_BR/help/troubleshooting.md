# Solução de problemas do OWBN Core

Como resolver o que mais dá errado com o OWBN Core e os dados que ele traz de outros sites da OWbN.

## Quem pode usar

Administradores do site.

## Como chegar

Cada solução abaixo começa em wp-admin → **OWBN Core** → **Settings**.

## A tela

A maioria das soluções usa três lugares: as seções **Status** e **Cache Management** em [General](settings-general.md), e a aba própria de cada tipo de dado.

## Tarefas comuns

### Os dados não aparecem numa página

1. Em **General**, confira se o tipo de dado está ligado em **Data Sources**.
2. Na aba dele, confira se **Data Source** está em **Remote** (a não ser que este site seja o dono dos dados) e se o endereço está certo.
3. Em **General**, olhe **Status**. Nada em cache quer dizer que o site não alcança a fonte.
4. Clique em **Refresh All Cache**.

### Os dados estão desatualizados

1. Em **General**, clique em **Refresh All Cache**.
2. Se isso acontece muito, diminua o **Cache TTL**.

## Bom saber

- Os endereços são só a URL base do site, como `https://chronicles.owbn.net`, sem caminho.
- Uma chave precisa ser a que o gateway do outro site espera; peça-a ao administrador daquele site.

## Solução de problemas

- **"Authentication required."** A chave enviada não é a chave de gateway do outro site.
- **"Request origin not permitted."** A **Domain Whitelist** do outro site não lista o seu domínio.
- **Os widgets da OWBN não aparecem no Elementor.** Ligue o tipo de dado em **Data Sources**; os widgets só aparecem para os tipos ligados.
- **O cargo novo de alguém não funciona.** Clique em **Refresh Roles** no perfil da pessoa; veja [Configurações do accessSchema](settings-accessschema.md).
- **Um membro não tem Player ID.** Ele entra uma vez por sso.owbn.net; veja [Configurações do Player ID](settings-player-id.md).
- **As páginas do OAT falham.** Veja [Configurações do OAT](settings-oat.md).

## Relacionados

- [Configurações Gerais](settings-general.md)
- [Chronicles, Coordinators, Territories e Vote History](settings-data.md)
- [Visão geral do OWBN Core](../overview.md)
