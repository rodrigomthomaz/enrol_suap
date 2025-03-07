<?php

defined('MOODLE_INTERNAL') || die;

require_once dirname(__FILE__) . '/../../config.php';
require_once $CFG->dirroot . "/enrol/suap/locallib.php";
require_once $CFG->dirroot . "/enrol/suap/settingslib.php";

$ADMIN->add('enrolments', new admin_category('enrol_suap_main', new lang_string('pluginname', 'enrol_suap'),
    $this->is_enabled() === false));

$settings = new admin_settingpage($section, get_string('config_essencial', 'enrol_suap'), 'moodle/site:config', $this->is_enabled() === false);
$renderizar_cursos = TRUE;
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('enrol_suap_help', '', get_string('settings_desc', 'enrol_suap')));
    $settings->add(new admin_setting_heading('enrol_suap_geral', get_string('config_geral', 'enrol_suap'), ''));

    $settings->add(
        new admin_setting_configtext(
            'block_suap_url_api',
            get_string('urlapi', 'enrol_suap'),
            get_string('configurlapi', 'enrol_suap'),
            'https://suap.ifsp.edu.br/edu/api',
            PARAM_URL
        )
    );  
    $settings->add(
        new admin_setting_configtext(
            'block_suap_token',
            get_string('token', 'enrol_suap'),
            get_string('configtoken', 'enrol_suap'),
            'ad89f708cdba20d73c05112a2dcadfa489e9d508',
            PARAM_ALPHANUMEXT
        )
    );
    $settings->add(
        new admin_setting_configselect(
            'block_suap_type_user',
            get_string('type_user', 'block_suap'),
            get_string('configtype_user', 'block_suap'),
            false,
            [false=>"Usando Prontuário",true=>"Usando Documento Identificador"]
        )
    );
    
    $settings->add(new admin_setting_heading('enrol_suap_manual', get_string('config_manual', 'enrol_suap'), ''));
    $settings->add(
        new admin_setting_configtext(
            'enrol_suap_update_set_time_limit',
            get_string('config_update_set_time_limit', 'enrol_suap'),
            get_string('config_update_set_time_limit_desc', 'enrol_suap'),
            300,
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'enrol_suap_tempo_proxima',
            get_string('config_proxima_exec', 'enrol_suap'),
            get_string('config_proxima_exec_desc', 'enrol_suap'),
            240,
            PARAM_INT
        )
    );
    $settings->add(new admin_setting_heading('enrol_suap_foto', get_string('config_foto', 'enrol_suap'), ''));
    $settings->add(
        new admin_setting_configselect(
            'enrol_suap_baixar_foto_manual',
            get_string('config_baixar_foto_manual', 'enrol_suap'),
            get_string('config_baixar_foto_manual_desc', 'enrol_suap'),
            false,
            [true=>"Sim",false=>"Não"]
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'enrol_suap_tempo_foto',
            get_string('config_tempo_foto', 'enrol_suap'),
            get_string('config_tempo_foto_desc', 'enrol_suap'),
            86400,
            PARAM_INT
        )
    );

    $settings->add(new admin_setting_heading('enrol_suap_oauth2', get_string('config_oauth2', 'enrol_suap'), ''));
    $settings->add(
        new admin_setting_configtext(
            'enrol_suap_emailaddresses',
            get_string('config_domain', 'enrol_suap'),
            get_string('config_domain_desc', 'enrol_suap'),
            'ifsp.edu.br',
            PARAM_URL
        )
    );
    

    $settings->add(new admin_setting_heading('enrol_suap_campus', get_string('campus', 'enrol_suap'), ''));  

    if ($CFG->block_suap_token != 'ad89f708cdba20d73c05112a2dcadfa489e9d508') {
        $id_campus_choices = array();
        $campi = SuapCampus::obter_todos();
        if ($campi) {          
            foreach ($campi as $campus) {
                $id_campus_choices[$campus->id_suap] = "($campus->id_suap) {$campus->descricao} ($campus->sigla)";
            }        
            $settings->add(
                new admin_setting_configselect(
                    'block_suap_id_campus',
                    get_string('campus', 'enrol_suap'),
                    get_string('configcampus', 'enrol_suap'),
                    7,
                    $id_campus_choices
                )
            ); 
            
            


        } else {
            $notify = new \core\output\notification(get_string('apierror', 'enrol_suap'), \core\output\notification::NOTIFY_ERROR);

            $settings->add(new admin_setting_heading('enrol_apierror', '', $OUTPUT->render($notify)));
        }       
    } 
    $renderizar_cursos = FALSE;
}
$ADMIN->add('enrol_suap_main', $settings);
$titulo_cursos =  get_string('cursos', 'enrol_suap');
$settings = new admin_settingpage('enrol_suap_cursos', $titulo_cursos, 'moodle/site:config', $renderizar_cursos);
$settings->add(new admin_setting_heading('enrol_suap_cursos_table', '', render_cursos($renderizar_cursos)));
$ADMIN->add('enrol_suap_main', $settings);
$settings = null;