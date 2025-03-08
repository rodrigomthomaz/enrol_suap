<?php

defined('MOODLE_INTERNAL') || die;

require_once dirname(__FILE__) . '/../../config.php';
require_once $CFG->dirroot . "/enrol/suap/locallib.php";
require_once $CFG->dirroot . "/enrol/suap/settingslib.php";

// --------------------------------------------------------------------------------
// Administração do site > Plugins > Inscrições > Inscrições automáticas via SUAP
// --------------------------------------------------------------------------------

$settings = new admin_settingpage('enrolsettingssuap', 
                'Inscrições automáticas do SUAP', 
                'moodle/site:config',  
                $this->is_enabled() === false
            );

if ($ADMIN->fulltree){


    $settings->add(new admin_setting_heading('enrol_suap_help', '', get_string('settings_desc', 'enrol_suap'))); 

    // ----------------------------------
    //   Opções gerais
    // ----------------------------------

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

    // ----------------------------------
    //   Atualizações manuais
    // ----------------------------------
    
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

    // ----------------------------------
    //   Fotos
    // ----------------------------------

    $settings->add(new admin_setting_heading('enrol_suap_foto', get_string('config_foto', 'enrol_suap'), ''));

    $settings->add(
        new admin_setting_configselect(
            'enrol_suap_baixar_foto_manual',
            get_string('config_baixar_foto_manual', 'enrol_suap'),
            get_string('config_baixar_foto_manual_desc', 'enrol_suap'),
            false,
            [false=>"Não", true=>"Sim"]
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

    // ----------------------------------
    //   OAuth2
    // ----------------------------------

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
}


$ADMIN->add('enrolments', $settings);


// --------------------------------------------------------------------------------
//  Administração do site > Cursos e Disciplinas > Selecionar campus para importação do SUAP
// --------------------------------------------------------------------------------


$settings = new admin_settingpage('courses_suap_campus', 
                'Selecionar campus para listagem de cursos do SUAP', 
                'moodle/site:config',  
                $this->is_enabled() === false
            );

//$settings->add(new admin_setting_heading('courses_suap_campus', get_string('campus', 'enrol_suap'), ''));

$settings->add(
    new admin_setting_configselect_campus(
        'block_suap_id_campus',
        get_string('campus', 'enrol_suap'),
        get_string('configcampus', 'enrol_suap'),
        7
    )
);    


$ADMIN->add('courses', $settings);

// --------------------------------------------------------------------------------
//  Administração do site > Cursos e Disciplinas > 'Associar ou importar cursos do SUAP'
// --------------------------------------------------------------------------------

$ADMIN->add('courses', 
        new admin_externalpage('enrol_suap_cursos', 
        'Associar ou importar cursos do SUAP',             
        $CFG->wwwroot . '/blocks/suap/listar_cursos.php', array('moodle/site:approvecourse')));



        
$settings = null; 