# Configurações Gerais

As configurações gerais do OWBN Core: o gateway que este site oferece aos outros sites da OWbN, de onde vêm os dados, o cache e quem é avisado quando os dados mudam.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **OWBN Core** → **Settings** (a tela se chama **OWBN Client Settings**) → **General**.

## A tela

- **API Gateway**
  - **Enable Gateway** - liga as rotas `owbn/v1/` deste site, de onde os outros sites da OWbN leem dados. Só precisa dele um site que fornece dados aos outros.
  - **Gateway Base URL** - só leitura: o endereço que os outros sites usam.
  - **API Key** - a chave única que os outros sites enviam para ler os dados deste site. Aparece como pontos e os quatro últimos caracteres. Clique nela para digitar uma chave nova, ou clique em **Generate** para uma aleatória.
  - **Auth Methods** - **API Key (x-api-key header)** e **Application Password (Authorization: Basic)**.
  - **Domain Whitelist** - um domínio por linha. Só vale para pedidos feitos por um navegador (os que dizem de que página vieram); os pedidos entre sites da OWbN dependem só da chave. Deixe vazio para aceitar qualquer domínio.
  - **Request Logging** - grava uma linha por pedido ao gateway no log de erros PHP do site.
  - **SSO Server URL** e **SSO API Key** - só no site do Arquivista: o OAT confere com sso.owbn.net que a pessoa tem uma conta da OWbN antes de criar a conta dela no site do Arquivista. A chave é a chave de gateway do próprio sso.owbn.net.
- **Data Sources**
  - Uma aba marcada **Off** pertence a um tipo de dado desligado aqui.
  - **Default Remote Gateway** - **Remote URL** e a chave dela: o site de onde lê todo tipo de dado em Remote, a não ser que a aba do tipo indique outro.
  - Uma tabela com cada **Feature**, se está **Enabled** e o seu **Mode**. Cada um tem a sua própria aba para o resto.
- **Cache Settings** - **Cache TTL (seconds)**: por quanto tempo os dados trazidos de outros sites são guardados. `0` é sem cache; o padrão é 3600 (uma hora).
- **Content Authorship** - **Anonymize Post Author**: posts novos da maioria dos tipos são salvos sem autor (os anexos mantêm o seu). A staff ainda pode definir um autor depois, e editar um post existente não muda o autor dele.
- **Status** - cada fonte de dados com o seu modo e quanto está em cache, e se C&C Manager, Territory Manager e Elementor estão neste site.
- **Cache Management** - **Clear All Cache** (a próxima página carregada busca dados novos), **Refresh All Cache** (limpa e busca de novo na hora) e **Refresh Entity Cache** (refaz a lista que transforma os slugs de crônicas e coordenadores em nomes).
- **Change Notifications** - **Notification Email**: endereços, separados por vírgulas, que recebem um email quando os dados de crônicas ou coordenadores mudam. Deixe em branco para não enviar nada.

**Save Changes** salva a aba.

## Tarefas comuns

### Deixar outro site da OWbN ler os dados deste site

1. Marque **Enable Gateway**.
2. Clique em **Generate** e copie a chave antes de salvar.
3. Clique em **Save Changes**.
4. No outro site, coloque o endereço deste site e a chave [nas configurações de fonte de dados dele](settings-data.md).

### Buscar dados novos agora

1. Em **Cache Management**, clique em **Refresh All Cache**.

## Bom saber

- Salvar a aba mantém toda chave que ainda aparece com pontos. Uma chave só muda quando você digita ou gera uma nova.
- Mudar a **API Key** corta todo site que ainda usa a antiga até ele receber a nova.
- Os dados de outros sites são tão recentes quanto o **Cache TTL** permite; **Refresh All Cache** pula a espera.

## Solução de problemas

- **Outro site recebe "Authentication required."** Ele não está enviando a **API Key** atual deste site. Passe a chave de novo.
- **Um pedido do navegador recebe "Request origin not permitted."** O domínio dele não está na **Domain Whitelist**. Inclua-o, ou esvazie a lista.
- **Status mostra uma fonte sem nada em cache.** O site não alcança o gateway dela. Confira o endereço e a chave na aba dessa fonte e em **Default Remote Gateway**, e clique em **Refresh All Cache**.

## Relacionados

- [Chronicles, Coordinators, Territories e Vote History](settings-data.md)
- [Configurações do accessSchema](settings-accessschema.md)
- [Solução de problemas do OWBN Core](troubleshooting.md)
- [Visão geral do OWBN Core](../overview.md)
