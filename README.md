# enrol_suap

O plugin de inscrições automáticas via SUAP permite a importação automática de professores e alunos de um diário. 
Ao adicionar este método de inscrição em um curso do Moodle (comumente conhecido como disciplina/diário), é possível inserir o código do diário do SUAP no Moodle, sincronizando assim os mesmos alunos e professores entre as plataformas.


## Instalação

- Descompacte a versão mais recente do plugin no repositório do GitLab: https://gitlab.ifsp.edu.br/ead/enrol_suap ;
- Extraia o conteúdo do arquivo para o diretório: */var/www/html/moodle/enrol* e renomeie o diretório do plugin para *suap*;
- Faça login no seu ambiente Moodle como usuário administrador;
- O Moodle identificará automaticamente a nova instalação e apresentará a tela para configurar o plugin. Insira as informações essenciais.
  As configurações antigas do plugin *block_suap* serão mantidas e não precisarão ser reinseridas.
- Clique no botão para finalizar a instalação.

## Configuração como método de inscrição

- Acesse um curso/disciplina no Moodle;
- Vá para a seção "Participantes";
- No menu suspenso, selecione "Métodos de Inscrição";
- Adicione o método "Inscrições automáticas do SUAP";
- Insira um nome (opcional) e o código do diário do SUAP;
- Salve as alterações.

## Baixar dados do SUAP

Existem três maneiras de baixar conteúdo do SUAP:

1. **Botão "Atualizar"**: Na lista de métodos de inscrição, clique no ícone "Atualizar" da instância de inscrição SUAP. Isso baixará os dados do diário da instância selecionada. Por padrão, os professores podem realizar essa ação manualmente.
2. **Botão "Buscar/atualizar inscritos no SUAP"**: Na lista de usuários inscritos, clique no botão "Buscar/atualizar inscritos no SUAP". Isso baixará todos os dados de todas as instâncias de inscrição SUAP. Por padrão, os professores podem realizar essa ação manualmente.
3. **Tarefas agendadas**: Na seção "Servidor" da "Administração do site", vá em "Tarefas agendadas" e edite a tarefa "Buscar e atualizar inscritos dos diários do SUAP". Por padrão, apenas administradores podem editar essa funcionalidade.

## Outras configurações

Caso já tenha o plugin *block_suap* instalado e configurado em seu sistema, as informações abaixo não serão necessárias.

### Permissões de acesso aos dados no SUAP

Inclua a permissão *Sincronizador Moodle* para o usuário que fará a sincronização.
Esta permissão é concedida normalmente pelo diretor acadêmico do Campus.

Para isso:
- Faça login no SUAP;
- No menu lateral vá em *ENSINO* > *Cadastros Gerais* > *Diretorias Acadêmicas* > Aba *Outras Atividades* > Sincronizador Moodle



