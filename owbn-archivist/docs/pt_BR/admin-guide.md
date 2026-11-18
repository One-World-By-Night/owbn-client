# Guia do Arquivista

Como administrar o OAT, para a equipe do Arquivista e os administradores do site em archivist.owbn.net.

## Onde as coisas estão

Em archivist.owbn.net, o wp-admin tem um menu **Archivist**. Todos que entram encontram ali o **Workspace** (Inbox, New Submission, Registry), **Reports** e **Details**; a equipe do Arquivista também encontra **All Entries**, **Settings**, **Toolbox**, **Rules**, **Account Sync** e **Untagged R&U**. Nos outros sites da OWbN, o menu Archivist abre o Workspace.

## Configurando os pedidos

- [Domains](help/admin-domains.md), [Forms](help/admin-forms.md), [Form Fields](help/admin-form-fields.md) e [Workflow Steps](help/admin-workflow-steps.md), no [Archivist Toolbox](help/admin-toolbox.md), decidem o que os membros podem registrar e para onde vai.
- [Regulation Rules](help/admin-regulation-rules.md) decidem qual Coordenador controla um item e do que ele precisa.
- [Submission Rules](help/admin-submission-rules.md) bloqueiam, avisam ou exigem coisas quando um pedido é registrado.

Uma configuração que cita um campo, um valor ou uma etapa que não existe falha em silêncio: nada dá erro, o pedido só nunca anda, ou uma etapa nunca acontece. Depois de cada mudança, confira o formulário em [Fazer um Pedido](help/submit.md) e acompanhe o próximo pedido real; não registre pedidos de teste, porque os pedidos são registros e ficam.

## Personagens e o registro

- Um personagem pertence a uma pessoa pela **Linked Account** na [Ficha do Personagem](help/registry-detail.md). Vincule ou mude ali; Player Name e Player Email são só texto.
- Um PC criado para um jogador ainda sem conta se vincula sozinho na primeira vez que a conta da pessoa chega ao site do Arquivista.
- Um clã que não está na lista da sua Seita é um Sect Defector; a ficha o mantém e o marca. As listas são a [Creature Taxonomy](help/admin-taxonomy.md).
- Os acessos decidem quais crônicas e escritórios de Coordenador veem um personagem. Aprovar um item que um escritório controla concede o acesso a esse escritório.

## Contas

- [Account Sync](help/admin-account-sync.md) dá a cada conta do www uma conta aqui, uma vez por dia, casada pelo email, para que quem tem um papel e nunca entrou aqui ainda possa receber um pedido. Mostra o que cada execução fez e lista as contas que não conseguiu criar.

## Quem pode criar personagens

[Archivist Settings](help/admin-settings.md) → General lista os cargos que podem criar personagens em Fazer um Pedido. Administradores do site sempre podem.

## Acompanhando o trabalho

- Os [Relatórios](help/reports.md) e o Details mostram as contagens e as listas.
- Todos os atribuídos a uma etapa recebem email quando um pedido chega a ela. Uma etapa sem ninguém atribuído não manda email, então uma etapa silenciosa pode ser um pedido que ninguém recebeu. Pending Reports e All Entries mostram o que está esperando.

## As páginas de ajuda

Estas páginas são escritas na pasta `docs/` do plugin owbn-archivist, em inglês e em português, e publicadas em support.owbn.net. Toda versão que muda uma tela atualiza a página de ajuda dela nas duas línguas antes de sair.
