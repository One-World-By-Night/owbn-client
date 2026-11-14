# Configurações do Player ID

Como este site recebe o Player ID da OWbN de cada membro.

## Quem pode usar

Administradores do site.

## Como chegar

wp-admin → **OWBN Core** → **Settings** → **Player ID**.

## A tela

- **Mode**
  - **Server — This site manages Player IDs (SSO server)** - só sso.owbn.net.
  - **Client — Capture Player ID from SSO login** - todos os outros sites: o Player ID de um membro vem junto com o login dele por sso.owbn.net.
- **SSO Server URL** - num cliente, o servidor de login. Só as respostas de login vindas deste endereço são lidas para pegar um Player ID.
- **Status** - o modo e o servidor de login.

**Save Changes** salva a aba.

## Tarefas comuns

### Ver o Player ID de um membro

1. Abra o membro em wp-admin → **Users**.
2. Olhe em **Player Information** → **Player ID**.

## Bom saber

- Um Player ID é único, e depois de definido não pode ser mudado: outros sites ligam contas e acessos a ele.
- Num site cliente o campo diz "Managed by SSO server." e não pode ser editado ali.
- A lista Users mostra o Player ID de cada membro numa coluna própria.

## Solução de problemas

- **"Your Player ID is not set. Contact an administrator or log in via SSO to sync it."** Saia e entre de novo por sso.owbn.net; se continuar vazio, o membro ainda não tem Player ID em sso.owbn.net.
- **"Player ID "…" is already in use."** Outra conta já tem esse. É um Player ID por pessoa.

## Relacionados

- [Users](users.md)
- [Configurações do accessSchema](settings-accessschema.md)
