<?php
require_once dirname(__FILE__) . '/../../../config.php';

require_once dirname(__FILE__) . '/../locallib.php';
require_once $CFG->dirroot . '/enrol/locallib.php';

set_time_limit($CFG->enrol_suap_update_set_time_limit);
$enrolid = required_param('enrolid', PARAM_INT);
$course_id = optional_param('id', null, PARAM_INT);
$returnto = optional_param('returnto', null, PARAM_TEXT);
$redirect = optional_param('redirect', 0, PARAM_INT);
$outras_instancias = optional_param('outras_instancias', 0, PARAM_INT);

$no_redirect = ! $redirect;
if (!CLI_SCRIPT) :
    require_login();

    $context = context_course::instance($course_id);
    if (!has_capability('enrol/suap:update', $context)) {
        throw new \moodle_exception(get_string('notallowed', 'enrol_suap'));
    }
    $context = context_system::instance();
    if ($no_redirect){
        $PAGE->set_cacheable(false);
        $PAGE->set_context($context);
        $PAGE->set_pagelayout('maintenance');
        $PAGE->set_title(get_string('pluginname', 'enrol_suap'));
        $PAGE->set_url(new moodle_url('/suap/index.php'));
        $PAGE->set_heading(get_string('pluginname', 'enrol_suap'));
        echo $OUTPUT->header();
    }
    
    

endif;
$moodle_diario_main = MoodleEnrol::instance($enrolid);

if (!$outras_instancias){    
    $moodle_diarios = array($moodle_diario_main);
} else {
    $courseid = $moodle_diario_main->courseid;
    $moodle_diarios = MoodleEnrol::instance(NULL, $courseid);
}
foreach($moodle_diarios as $moodle_diario){ 
    $moodle_diario->merge(TRUE,
        function($inscrito_encontrado) use ($OUTPUT, $no_redirect){        
            echo_html($OUTPUT->notification("Usuário " . $inscrito_encontrado->username . ' (' . $inscrito_encontrado->nome . ') já estava cadastrado.', 'info', false), $no_redirect); 
        },
        function($inscrito_nao_encontrado) use ($OUTPUT, $no_redirect){        
            echo_html($OUTPUT->notification("Usuário " . $inscrito_nao_encontrado->username . ' (' . $inscrito_nao_encontrado->nome . ') não estava cadastrado.', 'warning', false), $no_redirect);        
        },
        function($inscritos_sobrando) use ($OUTPUT, $no_redirect){
            if (!CLI_SCRIPT && !$no_redirect) {
                if (count($inscritos_sobrando)> 0){
                    foreach($inscritos_sobrando as $inscrito){
                        echo $OUTPUT->notification("Usuário " . $inscrito->username . ' (' . $inscrito->nome . ') está sobrando.', 'warning', false);
                    }            
                }       
            }
        },    
    ); 
}


    
echo_html($OUTPUT->notification("Atualizado.", 'success', false), $no_redirect);





if ($course_id){
    switch ($returnto){
        case 'instances':
            $linkback = new moodle_url("/enrol/instances.php", array('id'=>$course_id));
            break;
        default:
            $linkback = new moodle_url("/user/index.php", array('id'=>$course_id , 'page'=> 0, 'perpage'=> 5000, 'tsort' => 'firstname'));
    }      
    echo_html($OUTPUT->continue_button($linkback), $no_redirect);
} else {
    echo_html('<div class="continuebutton"><a class="btn btn-primary" href="javascript:window.close()">Fechar</a></div>', $no_redirect);  
}


if ($redirect){
    redirect($linkback);
} else {
    echo_html($OUTPUT->footer(), $no_redirect);
}



