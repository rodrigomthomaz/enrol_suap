<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/user/lib.php';
require_once $CFG->dirroot . '/lib/accesslib.php';
require_once $CFG->libdir . '/gdlib.php';
require_once dirname(__FILE__) . '/lib.php';

define('ROLE_TYPES', ['Moderador' => 4, 
                      'Principal' => 3, 
                      'Professor' => 3, 
                      'Aluno' => 5, 
                      'Tutor' => 4, 
                      'Formador' => 3
                    ]);

define ('ENROL_TYPES' , ['Moderador' => 'suap', 
                         'Principal' => 'suap', 
                         'Professor' => 'suap', 
                         'Aluno' => 'suap', 
                         'Tutor' => 'suap', 
                         'Formador' => 'suap'
                        ]);


if (isset($CFG->block_suap_type_user)){
    define ('DOCUMENTO_IDENTIFICADOR_COMO_USERNAME' , $CFG->block_suap_type_user);
} else {
    define ('DOCUMENTO_IDENTIFICADOR_COMO_USERNAME' , false);
}



function generateHashArray(array $randomArray): string {
    // Converte o array para uma string única, incluindo chaves e valores
    $data = '';
    foreach ($randomArray as $key => $value) {
        $data .= $key . '=' . $value . ';';
    }    
    $hash = sha1($data);        
    return $hash;
}
/**
 * Classe que representa a API do SUAP
 */

 class SuapAPI {
    public static function get_url(){
        global $CFG;
        return $CFG->block_suap_url_api;
    }
    public static function get_token(){
        global $CFG;
        return $CFG->block_suap_token;
    }
    public static function json_request($service, $params){
        global $CFG;
        $curl = curl_init(self::get_url() . "/$service/");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array("Content-type: application/json",
            "Authorization: Token $CFG->block_suap_token")
        );
        curl_setopt($curl, CURLOPT_POST, true);

        if (isset($params)) {
            $content = json_encode($params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        }
        $json_response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status != 200) {
            return NULL;
        }
        if (substr($json_response, 0, 8) == '{"erro":') {
            $erro = json_decode($json_response, true);
            return NULL;
        }

        $result = json_decode($json_response, true);

        return count($result) == 1 && array_key_exists(0, $result) ? [] : $result;
    }
}


/**
 * Classe abstrata que representa uma entidade no SUAP
 */
class SuapEntidadeAbstrata {
    public $id_suap;
    public $id_moodle;
    public $parent_id_suap;
    public $parent_id_moodle;
    public $suap_info = [];
    public $moodle_info = [];
    public static $suap_servico_listar = '';
    public static $objects;
    private static $api_data_cache = [];
    protected function __construct($id_suap){
        $this->id_suap = (int) $id_suap;
    }
    public function __toString(){
        return json_encode($this);
    }  

    public function __get($item){
        try{
            if (isset($this->suap_info[$item])){
                return $this->suap_info[$item];
            }  
        } catch (Exception $e){

        }
 
                         
        return NULL;
    }  
    public function get_context_level(){
        return CONTEXT_COURSECAT;
    }
    protected static function atualizar_via_api($arr){ 
        global $CFG; 
        $suap_class = get_called_class();  
        $arr_guid = generateHashArray($arr);
        if (isset(SuapEntidadeAbstrata::$api_data_cache[$suap_class][$arr_guid])){
            debugging("<pre>$suap_class $arr_guid cached</pre>");
            return SuapEntidadeAbstrata::$api_data_cache[$suap_class][$arr_guid];
        } 
        debugging("<pre>$suap_class $arr_guid search</pre>");       
        $response = SuapAPI::json_request(static::$suap_servico_listar, $arr);  
        $lista = array();
        if ($response){
            foreach ($response as $id_suap => $info_obj) {
                $suap_obj = new $suap_class($id_suap);            
                $suap_obj->suap_info = $info_obj;
                $lista[$id_suap] = $suap_obj;
                $suap_obj->parent_id_suap = reset($arr);
            }
        } 
        self::$api_data_cache[$suap_class][$arr_guid] = $lista ;  
        return $lista;
    }
}


/**
 * Classe que representa um campus no SUAP
 */
class SuapCampus extends SuapEntidadeAbstrata {
    public static $suap_servico_listar = 'listar_campus_ead';    
    public $_cursos;

    public static function obter_todos(){ 
        return parent::atualizar_via_api([]);
    }

    public function get_cursos($ano_letivo = NULL, $periodo_letivo = NULL){
        global $CFG;
        if (!$ano_letivo){
            $ano_letivo = $CFG->block_suap_auto_semestre_ano;            
        }
        if (!$periodo_letivo){
            $periodo_letivo = $CFG->block_suap_auto_semestre_semestre;
        }
        if (!isset($this->_cursos)){
            $this->_cursos = SuapCurso::obter_todos($this->id_suap, $ano_letivo, $periodo_letivo);
        }
        return $this->_cursos;
    }

}


class SuapEntidadeDependentePeriodo extends SuapEntidadeAbstrata {
    public static function obter_objetos_pelo_suap($parent_name, $id_parent = NULL, $ano_letivo = NULL, $periodo_letivo = NULL){        
        global $CFG;
        if (!$ano_letivo){
            $ano_letivo = $CFG->block_suap_auto_semestre_ano;            
        }
        if (!$periodo_letivo){
            $periodo_letivo = $CFG->block_suap_auto_semestre_semestre;
        }
        return parent::atualizar_via_api(
            [
                $parent_name => $id_parent,
                'ano_letivo' => $ano_letivo,
                'periodo_letivo' => $periodo_letivo
            ]
        );
    }
}
class SuapPolo extends SuapEntidadeDependentePeriodo {
    public $courseid;
    public static $suap_servico_listar = 'listar_polos_ead';  
    public static function obter_todos(){ 
        return parent::atualizar_via_api([]);
    }
    public static function instance($idnumber, $moodle_course_id = NULL){
        global $DB;
        $polos = self::obter_todos();
        if (!$polos || !isset($polos[$idnumber])){
            return NULL;
        }
        $polo = $polos[$idnumber];
        if ($moodle_course_id){           
           $data = ['courseid'=> $moodle_course_id, 'idnumber'=> $idnumber];
           $group = $DB->get_record('groups', $data);
           if ($group){
                $group->timemodified = time();
                $DB->update_record('groups', $group);
           } else {
                $data_novo_polo = $data;
                $data_novo_polo['name'] = $polo->descricao;
                debugging(var_export($data_novo_polo,true));
                groups_create_group((object) $data_novo_polo);
                $group = $DB->get_record('groups', $data);
           }
           $polo->moodle_info = $group;
           $polo->id_moodle = $group->id;
        }        
        return $polo;
    }
    public function inserir_usuario_ao_polo($inscrito_suap){
        global $DB;
        if ($DB->get_record('groups_members', ['groupid' => $this->id_moodle, 'userid' => $inscrito_suap->id_moodle, ])) {
            debugging("Já estava no grupo {$this->descricao}.", true);

        } else {
            debugging("Adicionado ao grupo {$this->descricao}.", true);            
            groups_add_member($this->id_moodle, $inscrito_suap->id_moodle);
        }
    }
}

/**
 * Classe que representa um curso no SUAP
 */
class SuapCurso extends SuapEntidadeDependentePeriodo {
    public static $suap_servico_listar = 'listar_cursos_ead';  
    public $_turmas;
    public $_componentes;

    public static function obter_todos($id_campus = NULL, $ano_letivo = NULL, $periodo_letivo = NULL, $tudo_mesmo = false){        
        global $DB;
        $cursos_suap = parent::obter_objetos_pelo_suap('id_campus', $id_campus, $ano_letivo, $periodo_letivo);
        if ($tudo_mesmo){
            $cursos_moodle = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE id_suap LIKE '%curso%'");
            foreach ($cursos_moodle as $curso_moodle){
                $id_suap_json = $curso_moodle->id_suap;
                $id_suap_json = str_replace("'",'"', $id_suap_json);                
                $suap_curso_id = json_decode($id_suap_json)->curso;                     
                foreach ($cursos_suap as $curso_suap){
                    if ($suap_curso_id == $curso_suap->id_suap){
                        $curso_suap->moodle_info = $curso_moodle;
                        $curso_suap->id_moodle = $curso_moodle->id;
                    }
                }
            }            
        }        
        return $cursos_suap;
    }

    public function get_turmas(){
        global $CFG;
        if (!isset($this->_turmas)){
            $this->_turmas = SuapTurma::obter_todos($this->id_suap);
        }
        return $this->_turmas;
    }

    public function get_componentes(){
        global $CFG;
        if (!isset($this->_componentes)){
            $this->_componentes = SuapComponenteCurricular::obter_todos($this->id_suap);
        }
        return $this->_componentes;
    }
}


/**
 * Classe que representa um componente curricular de um curso no SUAP
 */
class SuapComponenteCurricular extends SuapEntidadeAbstrata {
    public static $suap_servico_listar = 'listar_componentes_curriculares_ead'; 
    public $_turmas;
    public static function obter_todos($id_curso){
        return parent::atualizar_via_api(
            [
                'id_curso' => $id_curso
            ]
        );
    }
    public function get_turmas(){
        global $CFG;
        if (!isset($this->_turmas)){
            $this->_turmas = SuapTurma::obter_todos($this->id_suap);
        }
        return $this->_turmas;
    }
}

/**
 * Classe que representa uma turma no SUAP
 */
class SuapTurma extends SuapEntidadeDependentePeriodo {
    public static $suap_servico_listar = 'listar_turmas_ead'; 
    public $codigo;
    public $_diarios;

    public static function obter_todos($id_curso, $ano_letivo = NULL, $periodo_letivo = NULL){
        return parent::obter_objetos_pelo_suap('id_curso', $id_curso, $ano_letivo, $periodo_letivo);
    }

    public function get_diarios(){
        global $CFG;
        if (!isset($this->_diarios)){
            $this->_diarios = SuapDiario::obter_todos($this->id_suap);
        }
        return $this->_diarios;
    }

}


/**
 * Classe que representa um diário no SUAP
 */
class SuapDiario extends SuapEntidadeAbstrata {
    public static $suap_servico_listar = 'listar_diarios_ead'; 
    public $_professores;
    public $_alunos;
    public $enrols = array();
    public function __construct($id_suap){
        parent::__construct($id_suap);
    }
    public function get_context_level(){
        return CONTEXT_COURSE;
    }
    public static function obter_todos($id_turma){
        global $DB;
        $diarios_suap = parent::atualizar_via_api(
            [
                'id_turma' => $id_turma
            ]
        );

        $suap_turma_id = $id_turma;
        $enrols_turma = MoodleEnrol::instance(NULL, NULL, NULL, NULL, NULL, $suap_turma_id);
        foreach($diarios_suap as $diario){
            foreach($enrols_turma as $enrol){
                if($enrol->customint1 == $diario->id_suap){                    
                    switch ($enrol->roleid){
                        case ROLE_TYPES['Principal']:
                            $diario->get_professores($enrol->id);
                            break;                        
                        case ROLE_TYPES['Aluno']:                            
                            $diario->get_alunos($enrol->id);
                            break;                        
                    }
                    $diario->enrols[] = $enrol->id;
                }
            }
        }

        return $diarios_suap;
    }
    public function get_professores(){
        global $CFG;
        if (!isset($this->_professores)){
            $this->_professores = SuapProfessor::obter_todos($this->id_suap);
        }
        return $this->_professores;
    }
    public function get_alunos(){
        global $CFG;
        if (!isset($this->_alunos)){
            $this->_alunos = SuapAluno::obter_todos($this->id_suap);
        }
        return $this->_alunos;
    } 
    public function get_inscritos()  {
        return array_merge($this->get_professores(),$this->get_alunos());
    } 
}



/**
 * Class SuapInscrito
 *
 * Esta classe representa um inscrito de um diário no sistema SUAP. 
 * Ela fornece métodos para obter informações do inscrito, atualizar a foto do usuário no Moodle, 
 * importar o usuário para o Moodle e importar o usuário em um diário do Moodle.
 * 
 */
class SuapInscrito extends SuapEntidadeAbstrata {
    public $role_id = ROLE_TYPES['Aluno'];
    private $_email_institucional = NULL;

    public function get_context_level(){
        return CONTEXT_COURSECAT;
    }

    public function __get($item){
        global $CFG;
        switch ($item) {
            case 'username_documento':
                if(isset($this->suap_info['documento_identificador'])){
                    $whitelist = '/[^a-zA-Z0-9]/';
                    return strtolower(preg_replace($whitelist, '', $this->suap_info['documento_identificador'])); 
                }
                return NULL;  
            case 'username':
                $username = NULL;
                if (isset($this->suap_info['login'])){
                    $username = strtolower($this->suap_info['login']);
                } elseif (isset($this->suap_info['matricula'])){
                    $username = strtolower($this->suap_info['matricula']);
                }
                if(DOCUMENTO_IDENTIFICADOR_COMO_USERNAME && $this->username_documento){
                    $username = $this->username_documento;                         
                }   
                return $username;   

            case 'email':                
                return @$this->suap_info['email'] ? $this->suap_info['email'] : @$this->suap_info['email_secundario'];
            
            case 'email_institucional':
                if (!is_null($this->_email_institucional)){
                    return $this->_email_institucional;
                }                 
                if (empty($CFG->enrol_suap_emailaddresses)){
                    $email_config = $CFG->allowemailaddresses;
                } else {
                    $email_config = $CFG->enrol_suap_emailaddresses;
                }
                if (empty($email_config)){
                    return $this->email;
                } else{
                    $emails = explode(" ", $email_config);
                    foreach($emails as $email){
                        if (strpos($this->suap_info['email'], $email) > 0){
                            $this->_email_institucional = $this->suap_info['email'];
                            return $this->suap_info['email'];
                        } elseif (strpos($this->suap_info['email_secundario'], $email) > 0){
                            $this->_email_institucional = $this->suap_info['email_secundario'];
                            return $this->suap_info['email_secundario'];
                        }                        
                    }                    
                }                
                return NULL;

            case 'foto_url':
                return isset($this->suap_info['foto_url']) ?  $this->suap_info['foto_url'] : NULL;
            case 'matriculas':
                if (isset($this->suap_info['matriculas']) && $this->suap_info['matriculas']){
                    $m = array_map('strtolower', $this->suap_info['matriculas']);
                } else {
                    $m = [];
                }                
                $m[] = strtolower($this->username);
                if ($this->login) {
                    $m[] = strtolower($this->login);
                }
                if ($this->username_documento) {
                    $m[] = strtolower($this->username_documento);
                }               
                return array_unique($m);
            default:
                return parent::__get($item);
        }        
    }

    public static function obter_todos($id_diario){
        return parent::atualizar_via_api(
            [
                'id_diario' => $id_diario
            ]
        );
    }

    public function get_situacao_no_diario(){
        return strtolower(trim($this->situacao_no_diario)) == 'ativo' ? 
                    ENROL_USER_ACTIVE : 
                    ENROL_USER_SUSPENDED;        
    }

    protected function generate_password($length = 20) {
        $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`-=~!@#$%^&*()_+,./<>?;:[]{}\|';
        $str = '';
        $max = strlen($chars) - 1;
        for ($i=0; $i < $length; $i++) {
            $str .= $chars[rand(0, $max)];
        }        
        return $str;
    }

    public function get_suspended()  {
        return $this->get_status() == 'ativo' ? 0 : 1;
    }

    public function get_status() {
        return $this->status ? $this->status : $this->situacao;
    }
    public function atualizar_grupo_polo($courseid){
        $polo = SuapPolo::instance($this->polo, $courseid);
        if ($polo){
            $polo->inserir_usuario_ao_polo($this); 
        }        
    }
    /**
     * Atualiza a foto do usuário no Moodle.
     *
     * Esta função verifica se a foto do usuário precisa ser atualizada com base na data de modificação da foto
     * disponível na URL fornecida. Se a foto for mais recente ou não existir localmente, ela será baixada e salva
     * no diretório especificado. Em seguida, a função atualiza o ícone do usuário no Moodle e ajusta o campo 'picture'
     * na tabela 'user' do banco de dados.
     *
     * @global moodle_database $DB O objeto de banco de dados do Moodle.
     * @global stdClass $CFG O objeto de configuração global do Moodle.
     * @return void
     */
    public function atualizar_foto(){
        if ($this->foto_url){
            global $DB, $CFG;
            $folder = $CFG->dataroot."/pictures/";
            $filename = $this->username.".jpg";
            $userpath = $folder . $filename;
            if (file_exists($userpath)){
                $tempo_atual = time();
                $tempo_arquivo = filemtime($userpath);
                $tempo_diff = $tempo_atual - $tempo_arquivo;
                if ($tempo_diff < $CFG->enrol_suap_tempo_foto){                    
                    echo_cli(" - A foto $userpath é recente. Ignorar busca.");
                    return;
                }

            }
            if(! is_dir($folder)) {
                mkdir($folder);
            }
            $curl = curl_init($this->foto_url);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $content = curl_exec($curl);
            curl_close($curl);
            if (preg_match('/last-modified:\s?(?<date>.+)\n/i', $content, $m)) {
               if ((!file_exists($userpath)) || (filemtime($userpath) < strtotime($m['date']))) {
                    file_put_contents($userpath, file_get_contents($this->foto_url), LOCK_EX);
                    //touch($userpath, strtotime($m['date']), strtotime($m['date']));                
               }
            }            
            if (file_exists($userpath)){
                touch($userpath, time(), time()); 
                $context = \context_user::instance($this->id_moodle);
                $fileId = process_new_icon($context, 'user', 'icon', 0, $userpath);
                if ($fileId) {
                    $DB->set_field('user', 'picture', $fileId, array('id' => $this->id_moodle));
                }
            }
        }        
    }
    
    /**
     * Importa um usuário para o Moodle.
     *
     * Esta função importa um usuário para o Moodle, criando ou atualizando o registro do usuário
     * na base de dados do Moodle. Se o usuário não existir, ele será criado. Se o usuário já existir,
     * suas informações serão atualizadas.
     *
     * @global moodle_database $DB Objeto de banco de dados do Moodle.
     * @global stdClass $USER Objeto do usuário atual.
     * @throws Exception Se a importação do usuário falhar.
     */
    public function importar_em_moodle(){
        global $DB, $USER;           
        $nome_parts = explode(' ', $this->nome);
        $lastname = array_pop($nome_parts);
        $firstname = implode(' ', $nome_parts); 
        $user_info = [
            'lastname'      => $lastname,
            'firstname'     => $firstname,
            'alternatename' => $this->nome,
            'username'      => $this->username,
            'idnumber'      => $this->username,
            'auth'          => 'manual',
            'password'      => $this->generate_password(),
            'email'         => $this->email,
            'suspended'     => $this->get_suspended(),
            'timezone'      => '99',
            'lang'          => 'pt_br',
            'confirmed'     => 1,
            'mnethostid'    => 1,
        ];  

        
        
        //var_dump($this->matriculas);  
        //$contas = $DB->get_in_or_equals($sql_multiplas_contas, array($this->matriculas)); 
         
        list($insql, $inparams) = $DB->get_in_or_equal($this->matriculas);
        //exit(var_dump([$insql, $inparams]));  
        $sql_multiplas_contas = "SELECT * FROM {user} WHERE username $insql AND suspended = 0 AND deleted = 0";
        $contas = $DB->get_records_sql($sql_multiplas_contas, $this->matriculas); 
        //exit(var_dump($contas));    
        if (count($contas) == 0){ 
            try{
                $usuario_id = user_create_user($user_info, false, false);                         
                \core\event\user_created::create_from_userid($usuario_id)->trigger();                
            } catch (Exception $e){
                throw New Exception("A criação do usuário $this->username para o Moodle falhou: \n" . 
                                json_encode($this) . "\n" . 
                                json_encode($user_info) . "\n" .
                                json_encode($e) . "\n"     
                            );
            }
             
        }elseif (count($contas) == 1) {
            $conta = reset($contas);             
            $user_info['id'] = $conta->id;
            try{
                user_update_user($user_info, false, false); 
                \core\event\user_updated::create_from_userid($conta->id)->trigger(); 
            } catch(Exception $e){
                throw New Exception("A atualização do usuário $this->username para o Moodle falhou: \n" . 
                                        json_encode($this) . "\n" . 
                                        json_encode($user_info) . "\n" .
                                        json_encode($e) . "\n"                                         
                                    );
            }
            

        } elseif (count($contas) > 1){
            throw new Exception("Multíplas contas para $this->username: " . json_encode($this->matriculas));
        } 
        $usuario = $DB->get_record("user", array("username" => $this->username, 'suspended' => 0)); 
        $this->moodle_info = $usuario;
        $this->id_moodle = $usuario->id;    
    }
    

    /**
     * Importa informações do OAuth2 para o usuário.
     *
     * Esta função importa informações do OAuth2 para o usuário, criando ou atualizando registros na tabela `auth_oauth2_linked_login`.
     * 
     * - Se o email institucional do usuário não for nulo, cria um novo registro de login vinculado.
     * - Se a constante `DOCUMENTO_IDENTIFICADOR_COMO_USERNAME` não estiver definida, cria registros de login vinculados para cada matrícula do usuário.
     * - Atualiza o registro de login vinculado se o email institucional do usuário for diferente do email armazenado.
     * - Se a constante `DOCUMENTO_IDENTIFICADOR_COMO_USERNAME` estiver definida, remove registros de login vinculados para cada matrícula do usuário.
     *
     * @global moodle_database $DB Objeto de banco de dados do Moodle.
     * @throws Exception Se ocorrer um erro ao criar ou atualizar registros de login vinculados.
     */
    public function importar_oauth2_info(){        
        global $DB;     
        if (!is_null($this->email_institucional)){           
            $issuerdata = MoodleInfo::get_suap_oauth2_issuer();  
            $record = new stdClass();
            $record = (object)[
                'issuerid'            => $issuerdata->id,
                'username'            => $this->username,
                'userid'              => $this->id_moodle,
                'email'               => $this->email_institucional,
                'confirmtoken'        => '',
                'confirmtokenexpires' => 0
            ];
            try {
                $linkedlogin = new \auth_oauth2\linked_login(0, $record);
                $linkedlogin->create();                
                if(!DOCUMENTO_IDENTIFICADOR_COMO_USERNAME){
                    foreach ($this->matriculas as $matricula) {    
                        $record->username = $matricula;
                        $linkedlogin = new \auth_oauth2\linked_login(0, $record);
                        $linkedlogin->create();
                    }
                }           
            } catch (Exception $e) {
                
            }      
            //Atualiza linked_login
            $ulinked_login = $DB->get_record('auth_oauth2_linked_login', [
                'issuerid'  => $issuerdata->id,
                'userid'    => $this->id_moodle,
                'username'  => $this->username
            ]);
            if (!is_null($ulinked_login)){
                if ($ulinked_login->email != NULL && $ulinked_login->email != $this->email_institucional) {
                    try {
                        $ulinked_login->email = $this->email_institucional;
                        $ulinked_login->timemodified = time();
                        $DB->update_record('auth_oauth2_linked_login', $ulinked_login);
                    } catch (Exception $e) {
                        
                    }
                }
            }
        }       
        if (DOCUMENTO_IDENTIFICADOR_COMO_USERNAME && $this->matriculas){
            foreach ($this->matriculas as $matricula) {  
                $linked_login = $DB->delete_records('auth_oauth2_linked_login', ['username'  => $this->matricula]); 
            }
        }      
    }

    
}


/**
 * Classe que representa um professor no SUAP
 */
class SuapProfessor extends SuapInscrito {
    public static $suap_servico_listar = 'listar_professores_ead';     
    public $role_id = ROLE_TYPES['Principal'];
}


/**
 * Classe que representa um aluno no SUAP
 */
class SuapAluno extends SuapInscrito {
    public static $suap_servico_listar = 'listar_alunos_ead'; 
    public function get_situacao_no_diario(){
        $situacao_aluno = explode(" ", strtolower(trim($this->situacao_no_diario)))[0];
        $return_situacao = in_array($situacao_aluno, ['cancelado', 'dispensado', 'trancado', 'transferido']) ? 
                            ENROL_USER_SUSPENDED : 
                            ENROL_USER_ACTIVE;
        return $return_situacao;
    } 
}

abstract class MoodleEntidadeAbstrata {
    public $moodle_info;
    private $_course;
    public function __get($item){
        global $DB;
        switch ($item){
            case 'course':                
                if ($this->_course){
                    return $this->_course;
                }
                if ($this->moodle_info->courseid){
                    $this->_course = MoodleCourse::instance($this->moodle_info->courseid);                    
                }                 
                return $this->_course;
        }
        try {
            if (isset($this->moodle_info->$item)){
                return $this->moodle_info->$item;
            }   
        } catch(Exception $e){}
        try{
            if (isset($this->moodle_info[$item])){
                return $this->moodle_info[$item];
            }
        } catch(Exception $e){}
                            
        return NULL;
    }  
}

class MoodleCourse extends MoodleEntidadeAbstrata{    
    private $_context;
    public function __get($item){
        global $DB;
        switch ($item){
            case 'context':
                if ($this->_context){
                    return $this->_context;
                }
                if ($this->moodle_info->id){
                    $this->_context = $DB->get_record(
                        'context',
                        ['contextlevel' => CONTEXT_COURSE, 
                         'instanceid' => $this->id]
                    );              
                }                 
                return $this->_context;
        }
        return parent::__get($item);
    }
    public static function instance($course_id=NULL){
        global $DB;
        $moodle_course = new MoodleCourse();
        $moodle_course->moodle_info = $DB->get_record(
            'course',
            ['id' => $course_id]
        );
        return $moodle_course; 
    }
}

class MoodleEnrol extends MoodleEntidadeAbstrata{
    public $id;
    public $id_diario;
    public $id_turma;
    public $codigo;
    public $roleid;
    public $inscritos_suap;

    protected function __construct($moodle_info){
        $this->moodle_info = $moodle_info;
        $this->id = $moodle_info->id;
        $this->id_diario = $moodle_info->customint1;
        $this->id_turma = $moodle_info->customint2; 
        $this->roleid = (int) $moodle_info->roleid;        
    }

    public static function all(){
        global $DB;
        $opt = array(
            'enrol' => 'suap'
        );
        $recs = $DB->get_records('enrol', $opt);
        $ret = array();
        foreach($recs as $moodle_info){
            $ret[] = new MoodleEnrol($moodle_info);
        } 
        return $ret;
    }

    public static function instance($enrol_id=NULL, $moodle_course_id=NULL, $moodle_role_id=NULL, $suap_diario_id=NULL, $suap_turma_id=NULL){
        global $DB;
        $opt = array();
        if ($moodle_course_id){
            $opt['courseid'] = $moodle_course_id;
        }
        if ($moodle_role_id){
            $opt['roleid'] = $moodle_role_id;
        }
        if ($suap_diario_id){
            $opt['customint1'] = $suap_diario_id;
        }
        if ($suap_turma_id){
            $opt['customint2'] = $suap_turma_id;
        }
        $opt['enrol'] = 'suap';
        if (is_int($enrol_id)){
            $opt['id'] = $enrol_id;
            $moodle_info = $DB->get_record('enrol', $opt);
            if ($moodle_info){
                return new MoodleEnrol($moodle_info);
            }
            throw new Exception("Instância inválida para inscrição automática do SUAP: $enrol_id");
        }
        $recs = $DB->get_records('enrol', $opt);
        $ret = array();
        foreach($recs as $moodle_info){
            $ret[] = new MoodleEnrol($moodle_info);
        } 
        return $ret;
    }


    public function atualizar_data_modificacao(){
        global $DB, $USER;
        $DB->update_record('enrol', array('id'=>$this->id, 'timemodified'=>time(),'modifierid'=>$USER->id));
    }


    /**
     * Importa um inscrito do SUAP para o diário do Moodle.
     *
     * Esta função importa os dados de um inscrito do SUAP para o Moodle,
     * criando um registro de inscrição no diário do Moodle.
     *
     * @param SuapInscrito $inscrito_suap Objeto que representa o inscrito no SUAP.
     * @global moodle_database $DB Objeto global do banco de dados do Moodle.
     * @global stdClass $USER Objeto global que representa o usuário atual.
     */
    public function importar_em_diario_moodle(SuapInscrito $inscrito_suap){
        global $DB, $USER;
        if (!$inscrito_suap->id_moodle){
            throw new Exception("O parâmetro id_moodle para o usuário a ser importado no diário está vazio." . json_encode($inscrito_suap));
        }    
        $attribs = ['enrolid'=>$this->id,
                    'userid'=>$inscrito_suap->id_moodle];
        $ue = $DB->get_record(
            'user_enrolments',
            $attribs
        );
        if (!$ue){       
            $attribs = ['enrolid'=>$this->id,
                        'userid'=>$inscrito_suap->id_moodle,
                        'status'=>$inscrito_suap->get_situacao_no_diario(),
                        'timecreated'=>time(),
                        'timemodified'=>time(),
                        'timestart'=>time(),
                        'modifierid'=>$USER->id,
                        'timeend'=>0,];
            $ue = $DB->insert_record(
                'user_enrolments',
                $attribs
            ); 
        } else {
            $tem_motivo_para_atualizar = $inscrito_suap->get_situacao_no_diario() != $ue->status;
            if ($tem_motivo_para_atualizar){
                $ue->timemodified = time();
                $ue->modifierid = $USER->id;
                $ue->status = $inscrito_suap->get_situacao_no_diario();
                $DB->update_record('user_enrolments', $ue);
            }
        }
    }

    public function atualizar_perfil_em_diario_moodle(SuapInscrito $inscrito_suap){
        global $DB, $USER;
        $diario_context_id = $this->course->context->id;
        $roleid = $inscrito_suap->role_id;
        //debugging(var_dump($inscrito_suap));
        $dados_perfil = array(
                        'roleid' => $roleid,                                
                        'contextid' => $diario_context_id, 
                        'userid' => $inscrito_suap->id_moodle,
                        'itemid' => 0
                        );
       
        $assignment = $DB->get_record('role_assignments', $dados_perfil); 
        //exit(var_dump($dados_perfil));
        //exit(var_dump($assignment)); 
        if (!$assignment) {
            $id2 = $DB->insert_record('role_assignments',(object) $dados_perfil);            
        }         
        //exit(var_dump($id2)); 
    }
    
    /**
     * Remove um usuário do diário do Moodle.
     *
     * Esta função altera o status de um usuário inscrito no Moodle para "Suspenso".
     *
     * @param object $inscrito_moodle Objeto contendo informações do usuário inscrito no Moodle.
     * @global moodle_database $DB Objeto global do banco de dados do Moodle.
     * @global stdClass $USER Objeto global do usuário atual.
     */
    public function remover_de_diario_moodle($inscrito_moodle){
        global $DB, $USER;  
        if (!$inscrito_moodle->id_moodle){
            throw new Exception("O parâmetro id_moodle para o usuário a ser removido no diário está vazio.");
        }
        $param = array('enrolid'=>$this->id,
                        'userid'=>$inscrito_moodle->id_moodle);
        $moodle_info = $DB->get_record('user_enrolments', $param);       
        $moodle_info->status = ENROL_USER_SUSPENDED;
        $id = $DB->update_record(
             'user_enrolments',
             $moodle_info
        );
    }

    /**
     * Mescla as inscrições do SUAP com as inscrições do Moodle.
     *
     * Esta função sincroniza as inscrições do SUAP com as inscrições no Moodle.
     * Ela pode forçar a mesclagem, executar funções personalizadas quando uma correspondência é encontrada ou não encontrada,
     * e lidar com as inscrições restantes do Moodle.
     *
     * @param bool $force_merge Se deve forçar o processo de mesclagem.
     * @param callable|null $exec_quando_encontrado Função a ser executada quando uma inscrição do SUAP é encontrada no Moodle.
     * @param callable|null $exec_quando_nao_encontrado Função a ser executada quando uma inscrição do SUAP não é encontrada no Moodle.
     * @param callable|null $exec_inscritos_moodle_restantes Função a ser executada com as inscrições restantes do Moodle.
     */

    public function merge( $force_merge = TRUE,
                            $exec_quando_encontrado = NULL, 
                            $exec_quando_nao_encontrado = NULL, 
                            $exec_inscritos_moodle_restantes = NULL,
                            $respect_last_time = TRUE)
    {      
        $tg = tempo_inicio();  
        global $DB, $CFG;
        //exit(var_dump($this->course->context));
        $sql_filter_role_id = '';        
        if ($respect_last_time){
            $tempo_ultima_execucao = time() - $this->timemodified;
            $tempo_proxima_execucao = $CFG->enrol_suap_tempo_proxima;
            if ($tempo_ultima_execucao < $tempo_proxima_execucao){
                $restante = $tempo_proxima_execucao - $tempo_ultima_execucao ;
                throw new Exception(get_string('mensagem_repeitar_tempo', 'enrol_suap') . " $restante");
            }
        }
        $inscritos_suap_p = SuapProfessor::obter_todos($this->id_diario);
        $inscritos_suap_a = SuapAluno::obter_todos($this->id_diario);
        $this->inscritos_suap = array_merge($inscritos_suap_p, $inscritos_suap_a);
        
        $cols = 'u.id as id_moodle, ue.enrolid, roleid, e.enrol, e.courseid, ue.timecreated, ue.timemodified, ue.status, auth, idnumber, username, firstname, lastname, email, alternatename';
        $inscritos_moodle = $DB->get_records_sql("SELECT $cols 
                                FROM {user_enrolments} AS ue 
                                INNER JOIN {user} AS u ON u.id = ue.userid
                                INNER JOIN {enrol} AS e ON e.id = ue.enrolid
                                WHERE ue.enrolid IN (?) AND e.enrol = 'suap' $sql_filter_role_id", array($this->id));
        $inscritos_moodle_restantes = $inscritos_moodle;    
        // Itera sobre todos os inscritos do SUAP
        foreach ($this->inscritos_suap as $id_suap => $inscrito_suap){ 
            $inscrito_suap_encontrado = FALSE;
            // Verifica se o inscrito do SUAP já existe no Moodle
            foreach($inscritos_moodle_restantes as $key => $inscrito_moodle){          
                if ((isset($inscrito_suap->suap_info['email']) && $inscrito_moodle->email == $inscrito_suap->suap_info['email']) || 
                    (isset($inscrito_suap->suap_info['email_secundario']) && $inscrito_moodle->email == $inscrito_suap->suap_info['email_secundario'])){
                    // Se encontrado, associa o ID do Moodle ao inscrito do SUAP
                    $inscrito_suap->id_moodle = $inscrito_moodle->id_moodle;
                    $inscrito_suap->moodle_info = $inscrito_moodle;      
                    unset($inscritos_moodle_restantes[$key]);
                    $inscrito_suap_encontrado = TRUE; 
                    // Executa a função personalizada quando encontrado
                    if ($exec_quando_encontrado){                        
                        $exec_quando_encontrado($inscrito_suap);                    
                    }
                    break;
                }            
            }
            // Se forçado, importa o inscrito do SUAP para o Moodle
            if ($force_merge){ 
                debugging('<pre>importar_em_moodle</pre>');                
                $inscrito_suap->importar_em_moodle();
                if ($CFG->enrol_suap_baixar_foto_manual){
                    debugging('<pre>atualizar_foto</pre>');
                    $inscrito_suap->atualizar_foto();
                }
                debugging('<pre>importar_oauth2_info</pre>');
                $inscrito_suap->importar_oauth2_info();
                debugging('<pre>importar_em_diario_moodle</pre>');
                $this->importar_em_diario_moodle($inscrito_suap);
                debugging('<pre>atualizar_perfil_em_diario_moodle</pre>');
                $this->atualizar_perfil_em_diario_moodle($inscrito_suap);
                debugging('<pre>atualizar_grupo_polo</pre>');
                $inscrito_suap->atualizar_grupo_polo($this->courseid);
            }
            // Se o inscrito do SUAP não for encontrado no Moodle
            if (!$inscrito_suap_encontrado){ 
                // Executa a função personalizada quando não encontrado
                if ($exec_quando_nao_encontrado){                
                    $exec_quando_nao_encontrado($inscrito_suap);
                } 
            }
        }
        $this->atualizar_data_modificacao();
        // Se forçado, remove os inscritos restantes do Moodle que não foram encontrados no SUAP
        if ($force_merge){
            foreach($inscritos_moodle_restantes as $inscrito_restante){
                $this->remover_de_diario_moodle($inscrito_restante);
            }
        }
        // Executa a função personalizada com os inscritos restantes do Moodle
        if ($exec_inscritos_moodle_restantes){ 
            $exec_inscritos_moodle_restantes($inscritos_moodle_restantes);
        } 
    }
}

class MoodleInfo {
    private static $_issuer_data = NULL;
    public static function get_suap_oauth2_issuer(){
        global $DB;
        if (is_null(MoodleInfo::$_issuer_data)){
            MoodleInfo::$_issuer_data = $DB->get_record_sql('SELECT * FROM {oauth2_issuer} WHERE name LIKE ? ', ['%SUAP%']);
        }
        if (!(MoodleInfo::$_issuer_data)){
            throw new Exception("Autenticação OAuth2 do SUAP não está configurada corretamente.");
        }
        return MoodleInfo::$_issuer_data;
    }
}