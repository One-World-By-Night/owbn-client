# Configurações do accessSchema

Onde este site consulta os cargos da OWbN dos membros (HST, Coordenador, escritórios executivos e os demais), que todo plugin da OWbN usa para decidir quem pode fazer o quê.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **OWBN Core** → **Settings** → **accessSchema**.

## A tela

- **Mode**
  - **Local — accessSchema server plugin is installed on this site** - só o site que roda o servidor de cargos.
  - **Remote — Connect to a remote accessSchema server** - todos os outros sites.
- **Server URL** e **API Key** - o servidor de cargos e a chave dele. A chave aparece como pontos e os quatro últimos caracteres; clique nela para digitar uma nova.
- **Role Cache TTL (seconds)** - por quanto tempo os cargos de um membro são guardados antes de serem consultados de novo. `0` é sem cache; o padrão é 3600 (uma hora).
- **Status** - de quantos membros os cargos estão em cache, e quais plugins usam a consulta de cargos (**Registered Clients**).
- **Search Data** - digite para procurar nos cargos que este site tem em cache.

**Save Changes** salva a aba.

## Tarefas comuns

### Pegar já o cargo novo de um membro

1. Abra o membro em wp-admin → **Users**.
2. Em **AccessSchema Roles**, clique em **Refresh Roles**.

## Bom saber

- Os cargos mudam no servidor de cargos, nunca aqui. Este site só guarda uma cópia por **Role Cache TTL** segundos.
- Os cargos de um membro são consultados quando ele precisa deles pela primeira vez, então um cargo novo aparece dentro do tempo de cache ou depois de **Refresh Roles**.
- O servidor de cargos responde cerca de 60 consultas por minuto por site. Atualize os membros aos poucos, nunca todos de uma vez.

## Solução de problemas

- **O cargo novo de alguém ainda não funciona.** Clique em **Refresh Roles** no perfil da pessoa, ou espere o **Role Cache TTL**.
- **Os cargos de ninguém carregam.** Confira **Server URL** e **API Key** contra as configurações do servidor de cargos.

## Relacionados

- [Users](users.md)
- [Configurações Gerais](settings-general.md)
- [Solução de problemas do OWBN Core](troubleshooting.md)
