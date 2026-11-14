# Visão geral do OWBN Core

O OWBN Core roda em todos os sites da OWbN e os liga: crônicas, coordenadores, territórios, votações, os cargos e Player IDs dos membros, e o OAT são lidos todos do site que é dono de cada um, para que todo site mostre o mesmo.

## O que ele faz

- **Dados compartilhados.** As crônicas ficam em chronicles.owbn.net, os coordenadores e as votações em council.owbn.net, o OAT em archivist.owbn.net, cargos e Player IDs em sso.owbn.net. Os outros sites os leem pelo gateway desse site e guardam uma cópia por um tempo.
- **Páginas e widgets.** Listas e páginas de detalhe de crônicas, coordenadores e territórios, como widgets do Elementor e shortcodes. As páginas do OAT vêm junto.
- **Cargos.** Todo plugin da OWbN pergunta ao OWBN Core que cargos um membro tem (HST, Coordenador, um escritório executivo) antes de deixá-lo fazer algo.
- **Player ID.** O Player ID da OWbN de cada membro chega com o login dele por sso.owbn.net.
- **O menu OWBN.** O logo da OWBN na barra de administração abre links para os outros sites, e faz o login dos membros no caminho.
- **Um gateway.** Um site dono de dados os fornece aos outros.

## Quem faz o quê

- **Os membros** nunca mexem nele: veem os dados compartilhados e entram uma vez para todos os sites.
- **Os administradores do site** definem de onde vem cada tipo de dado, veja [Configurações Gerais](help/settings-general.md).
- **O escritório de Web** monta os sites novos e as chaves deles.

## Por onde começar

- Montando um site: [Configurações Gerais](help/settings-general.md), e depois [Chronicles, Coordinators, Territories e Vote History](help/settings-data.md).
- Cargos: [Configurações do accessSchema](help/settings-accessschema.md).
- Algo não funciona: [Solução de problemas do OWBN Core](help/troubleshooting.md).

## Ajuda numa tela

Toda tela do OWBN Core no wp-admin tem um **?** ao lado do título que abre a página de ajuda dela ali mesmo, e toda página está também aqui em support.owbn.net.
