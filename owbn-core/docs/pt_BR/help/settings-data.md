# Chronicles, Coordinators, Territories e Vote History

De onde este site tira cada tipo de dado da OWbN, e as páginas que o mostram.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **OWBN Core** → **Settings** → a aba **Chronicles**, **Coordinators**, **Territories** ou **Vote History**.

## A tela

Cada aba tem as mesmas partes:

- **Data Source**
  - **Local** - os dados ficam neste site: crônicas e coordenadores no C&C Manager, territórios no Territory Manager, votações no plugin de votação.
  - **Remote** - os dados vêm do gateway de outro site da OWbN.
- **Remote URL Override** e **API Key Override** - só quando esse tipo de dado vem de um site diferente do **Default Remote Gateway** em [General](settings-general.md). Senão, deixe-os vazios.
- **Chronicles List Page** e **Chronicles Detail Page** (em Chronicles), **Coordinators List Page** e **Coordinators Detail Page** (em Coordinators) - as páginas deste site que os listam e mostram um deles. Uma lista liga cada item à página de detalhe.
- **Status** - o modo, se o plugin local está aqui, o endereço de onde lê e quantos registros estão em cache.
- **Search Data** - digite para procurar nos registros em cache e conferir o que este site vê.

**Vote History** acrescenta as votações de uma crônica e de um coordenador às páginas de detalhe deles.

## Tarefas comuns

### Apontar um site para os dados compartilhados

1. Em [General](settings-general.md), defina o **Default Remote Gateway** e ligue os tipos de dados.
2. Em cada aba, escolha **Remote** e deixe as duas substituições vazias.
3. Clique em **Save Changes**, e depois em **Refresh All Cache** em General.

### Conferir o que este site vê

1. Digite parte de um nome em **Search Data**.

## Bom saber

- A maioria dos sites da OWbN lê tudo remotamente. As crônicas ficam em chronicles.owbn.net; os coordenadores e as votações em council.owbn.net.
- Um tipo de dado desligado em General não mostra nada, diga o que disser a aba dele.

## Solução de problemas

- **Uma página de lista está vazia.** Confira se o tipo está ligado e se a fonte está certa, e depois atualize o cache em General.
- **Cached Records diz None.** O site não alcança a fonte. Confira **Remote URL Override** e **API Key Override**, ou os padrões em General.

## Relacionados

- [Configurações Gerais](settings-general.md)
- [Configurações do OAT](settings-oat.md)
- [Solução de problemas do OWBN Core](troubleshooting.md)
