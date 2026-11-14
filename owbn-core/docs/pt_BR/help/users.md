# Users

O que o OWBN Core acrescenta à lista Users do wp-admin e ao perfil de cada membro.

## Quem pode usar

Administradores do site, e qualquer outra pessoa que possa listar usuários.

## Como chegar

wp-admin → **Users**.

## A tela

Na lista:

- **ASC Roles** - os cargos da OWbN de cada membro, do cache de cargos.
- **Player ID** - o Player ID de cada membro.
- **Filter by ASC role**, e depois **Filter** - mostra só os membros com um cargo; **— No ASC Roles —** mostra os que não têm nenhum. Em sso.owbn.net, o filtro de cargos do próprio accessSchema fica no lugar dele.
- **Refresh ASC Roles** (ação em massa) - consulta de novo os cargos dos membros marcados.

No perfil de um membro:

- **Player Information** → **Player ID** - veja [Configurações do Player ID](settings-player-id.md).
- **AccessSchema Roles** → **Current Roles**, e **Refresh Roles**, que limpa os cargos em cache desse membro e os busca de novo.

## Tarefas comuns

### Pegar os cargos que mudaram hoje

1. Marque os membros.
2. Escolha **Refresh ASC Roles** e clique em **Apply**.

## Bom saber

- **Refresh ASC Roles** trata no máximo 25 membros por vez; o aviso diz quantos ficaram. O servidor de cargos responde cerca de 60 consultas por minuto por site.
- Os cargos mudam no servidor de cargos, nunca aqui.

## Solução de problemas

- **"N more were not refreshed: refresh at most 25 users at a time."** Rode de novo nos membros que ficaram, um minuto depois.

## Relacionados

- [Configurações do accessSchema](settings-accessschema.md)
- [Configurações do Player ID](settings-player-id.md)
