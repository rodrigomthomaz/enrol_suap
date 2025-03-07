<?php
$string['pluginname'] = 'Inscrições automáticas via SUAP';
$string['pluginname_desc'] = 'Utilize o SUAP para controlar as inscrições.';
$string['enrolname'] = 'SUAP';
$string['enrolusers'] = 'Usuários';
$string['configurlapi'] = 'Insira a URL da API do SUAP';
$string['searchenrolusers'] = 'Buscar/atualizar inscritos no SUAP';
$string['searchenrolusers_aluno'] = 'Buscar/atualizar alunos no SUAP';
$string['searchenrolusers_professor'] = 'Buscar/atualizar professores no SUAP';
$string['enrolupdate'] = 'Atualizar';
$string['atualizardiariostask'] = 'Buscar e atualizar inscritos dos diários do SUAP';
$string['campus'] = 'Campus';
$string['configcampus'] = 'Selecione o campus que será utilizado para a inscrição.';
$string['type_user'] = 'Tipo de Sincronização de contas';
$string['configtype_user'] = 'Define se o moodle importará os usuários usando prontuario (uma pessoa pode ter várias contas) ou documento identificador (uma conta no moodle por usuário). Depois de alterado para documento identificador, não será possível voltar para prontuário.'; 
$string['minyear'] = 'Ano inicial de importação do SUAP';
$string['configminyear'] = 'Define o ano inicial para Importação do SUAP';
$string['token'] = 'Token do SUAP';
$string['configtoken'] = 'Token de um usuário com a permissão edu.pode_sincronizar_dados no SUAP';
$string['urlapi'] = 'URL da API do SUAP';
$string['cursos'] = 'Cursos';
$string['configcursos'] = 'Cursos disponíveis';
$string['config_essencial'] = 'Configurações essenciais do SUAP';

$string['config_domain'] = 'Domínios válidos para e-mail em OAuth2';
$string['config_domain_desc'] = "<p>A importação dos inscritos do SUAP permitirá, em OAuth2, apenas a importação de domínios permitidos.<br>
                             Liste-os aqui separados por espaços. Qualquer outro domínio será ignorado e removido. 
                             Para permitir subdomínios, escreva o domínio precedido por '.'. 
                             Para permitir um domínio raiz junto com seus domínios, adicione o domínio duas vezes - uma vez precedido de '.' e outra sem. 
                             Por exemplo: .nossafaculdade.edu.br nossafaculdade.edu.br. </p><p>Se vazio, a configuração respeitará os domínios configurados em <a href=\"search.php?query=allowemailaddresses\">Domínios de e-mail permitidos</a>.</p>";
$string['config_proxima_exec'] = 'Tempo necessário para aguardar para próxima importação de inscritos SUAP ao diário (em segundos)';
$string['config_proxima_exec_desc'] = '<p>Isso evita que os usuários fiquem forçando a atualização do SUAP.</p><p>Será considerado como 60 segundos qualquer valor abaixo de 60.</p>';
$string['config_baixar_foto_manual'] = 'Baixar fotos nas atualizações manuais';
$string['config_baixar_foto_manual_desc'] = 'Permite que as fotos sejam baixadas durante a atualização manual. Para que o processo não seja interrompido nos diários com muitos inscritos, a variável <code>max_execution_time</code> precisará ter um valor alto.';

$string['config_tempo_foto'] = 'Tempo da foto dos usuários em cache (em segundos)';
$string['config_tempo_foto_desc'] = '<p>Define a quantidade de tempo necessária para que a foto seja mantida em cache antes de buscar por uma nova atualização.</p>';
$string['notallowed'] = 'Ação não permitida';
$string['suap:manage'] = 'Gerenciar inscritos via SUAP';
$string['suap:unenrol'] = 'Desincrever inscritos via SUAP';
$string['suap:update'] = 'Atualizar inscritos via SUAP';
$string['deleteselectedusers'] = 'Remover TODOS os inscritos';
$string['deletesuspendedusers'] = 'Remover APENAS os inscritos SUSPENSOS';
$string['confirmbulkdeleteenrolment'] = 'Deseja remover os inscritos selecionados?';
$string['unenrolusers'] = 'Remover inscritos do SUAP';
$string['config_diario'] = 'Código do diário no SUAP para esta disciplina';
$string['config_diario_desc'] = 'Código do diário no SUAP';
$string['config_diario_desc_help'] = 'Este é mesmo código do diário utilizado no SUAP';
$string['config_turma'] = 'Turma no SUAP para atualizações agrupadas';
$string['config_turma_desc'] = 'Código da turma no SUAP';
$string['config_turma_desc_help'] = 'Este é mesmo código da turma utilizada no SUAP';



$string['settings_desc'] = 'O plugin de inscrições automáticas via SUAP permite que os professores e alunos de um diário sejam importados automaticamente. Quando este método de inscrição é inserido em um curso (disciplina), é possível inserir o código do diário do Moodle. O professor pode fazer a atualização dos inscritos através do botão disponível na lista de inscritos.';
$string['config_geral'] = 'Opções gerais';
$string['config_foto'] = 'Fotos';
$string['config_oauth2'] = 'OAuth2';
$string['mensagem_repeitar_tempo'] = 'É preciso respeitar o tempo entre uma atualização e outra. Segundos restantes: ';
$string['suap:config'] = 'Configurar inscrições via SUAP';
$string['apierror'] = 'Houve um erro ao tentar obter dados do SUAP. Verifique os dados de API (URL e Token) e depois tente novamente.';


