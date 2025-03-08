<?php



defined('MOODLE_INTERNAL') || die();


require_once dirname(__FILE__) . '/locallib.php';

function echo_cli($var, $force=TRUE){
    if (CLI_SCRIPT && $force){
        echo $var;
    }
}
function echo_html($var, $force=TRUE){
    if (!CLI_SCRIPT && $force){
        echo $var;
    }
}
$_tempo_inicial = 0;
function tempo_inicio(){
    return microtime();
}
function tempo_gasto($t, $desc){
    echo("\n<br><pre>$desc: " . round(microtime() - $t, 3) . '</pre>');
    return microtime();
}

class enrol_suap_plugin extends enrol_plugin {
    protected $enroltype = 'enrol_suap';
    protected $errorlogtag = '[ENROL SUAP] ';
    public function get_name() {
        return 'suap';
    }    

    public function roles_protected() {
        // Users may tweak the roles later.
        return false;
    }

    public function allow_enrol(stdClass $instance) {
        // Users with enrol cap may unenrol other users suaply suaply.
        return true;
    }

    public function allow_unenrol(stdClass $instance) {
        // Users with unenrol cap may unenrol other users suaply suaply.
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // Users with manage cap may tweak period and status.
        return true;
    }
    public function can_add_instance($courseid) {
        global $DB;
        $context = context_course::instance($courseid);
        if (!has_capability('enrol/suap:manage', $context)){
            return false;
        }
        return true;
    }    
    public function can_edit_instance($courseid) {
        return true;
    }
    public function edit_instance_form($instance, MoodleQuickForm $mform, $coursecontext) {
        if (has_any_capability(['enrol/manual:config'], $coursecontext)) {
            $mform->addElement(
                'text',
                'name',
                get_string(
                    identifier: 'config_diario_name',
                    component: 'enrol_suap',
                ),
                1,
                'A'
            );
            $mform->setType('name', PARAM_RAW);
            $mform->addHelpButton(
                elementname: 'name',
                identifier: 'config_diario_name_desc',
                component: 'enrol_suap',
            );

            $mform->addElement(
                'text',
                'customint1',
                get_string(
                    identifier: 'config_diario',
                    component: 'enrol_suap',
                ),
                1,
                'A'
            );
            $mform->setType('customint1', PARAM_INT);
            $mform->addRule('customint1', null, 'required');
            $mform->addHelpButton(
                elementname: 'customint1',
                identifier: 'config_diario_desc',
                component: 'enrol_suap',
            );
            /*$mform->addElement(
                'text',
                'customint2',
                get_string(
                    identifier: 'config_turma',
                    component: 'enrol_suap',
                ),
                1,
                'A'
            );
            $mform->setType('customint2', PARAM_INT);

            $mform->addHelpButton(
                elementname: 'customint2',
                identifier: 'config_turma_desc',
                component: 'enrol_suap',
            );*/            
        }
    }
    public function edit_instance_validation($data, $files, $instance, $context) {
        global $DB;
        $errors = array();
        //$errors['customint1'] = "Não foi possível verificar se o diário existe no SUAP.";    
        return $errors;
    }

    /**
     * Add new instance of enrol plugin.
     * @param stdClass $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, ?array $fields = NULL) {
        global $DB;

        return parent::add_instance($course, $fields);
    }
    public function can_delete_instance($instance) {        
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/suap:manage', $context);
        return true;
    }
    public function get_newinstance_link($courseid) {
        $params = array('type' => 'suap', 'courseid' => $courseid);
        return new moodle_url('/enrol/editinstance.php', $params);
        //// Não permitir a criação de novas instâncias
        //return null;
    }
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/manual:config', $context);    
        //return false;
    }
    /**
     * Returns link to manual enrol UI if exists.
     * Does the access control tests automatically.
     *
     * @param stdClass $instance
     * @return moodle_url
     */
    public function get_manual_enrol_link($instance) {
        $name = $this->get_name();
        if ($instance->enrol !== $name) {
            throw new coding_exception('invalid enrol instance!');
        }

        if (!enrol_is_enabled($name)) {
            return NULL;
        }

        //var_dump($instance);
        $context = context_course::instance($instance->courseid, MUST_EXIST);

        //if (!has_capability('enrol/suap:enrol', $context)) {
            // Note: manage capability not used here because it is used for editing
            // of existing enrolments which is not possible here.
        //    return NULL;
        //}

        return new moodle_url('/enrol/suap/tasks/update.php', array('enrolid'=>$instance->id, 'id'=>$instance->courseid, 'redirect'=>1, 'outras_instancias'=>1));
    }

        
    public function get_bulk_operations(course_enrolment_manager $manager){
        global $CFG;
        require_once(dirname(__FILE__).'/classes/selectedusers_operation.php');
        $context = $manager->get_context();
        debugging(var_export($context, true), DEBUG_DEVELOPER);
        $bulkoperations = array();
        if (has_capability("enrol/suap:unenrol", $context)) {
            $bulkoperations['deleteselectedusers'] = new enrol_suap_deleteselectedusers_operation($manager, $this);
            $bulkoperations['deletesuspendedusers'] = new enrol_suap_deletesuspendedusers_operation($manager, $this);
        }
        return $bulkoperations;
    }

    public function get_manual_enrol_button(course_enrolment_manager $manager){
        global $PAGE, $CFG;
        static $called = false;
        $instance = null;
        $link = null;        
        
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'suap') {
                if ($instance === null) {
                    $instance = $tempinstance;
                }
            }
        }
        try{
            $context = context_course::instance(@$instance->courseid);
        } catch(Exception $e) {
            return NULL;
        }
        
        if (has_capability('enrol/suap:update', $context)) {
            if (empty($instance)) {
                return false;
            }
            $link = $this->get_manual_enrol_link($instance);
            if (!$link) {
                return false;
            }
            $button = new enrol_user_button($link, get_string('searchenrolusers', 'enrol_suap'), 'get');
            $button->class .= ' enrol_suap_plugin';
            $button->type = single_button::BUTTON_PRIMARY;
            return $button;
        }
    }

    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;
        $context = context_course::instance($instance->courseid);
        $icons = array();

        if (has_capability('enrol/suap:update', $context)) {
            $managelink = new moodle_url("/enrol/suap/tasks/update.php", array('enrolid'=>$instance->id, 'id'=>$instance->courseid, 'returnto'=> 'instances'));            
            $icons[] = $OUTPUT->action_icon(
                        $managelink, 
                        new pix_icon('t/download', get_string('enrolupdate', 'enrol_suap'), 'core', array('class'=>'iconsmall')),
                        NULL
                    );
            $linkparams = [
                'courseid' => $instance->courseid,
                'id' => $instance->id,
                'type' => $instance->enrol,
            ];
            $editlink = new moodle_url('/enrol/editinstance.php', $linkparams);
            $icon = new pix_icon('t/edit', get_string('edit'), 'core', ['class' => 'iconsmall']);
            $icons[] = $OUTPUT->action_icon($editlink, $icon);
        }        
        return $icons;
    }
    /**
     * Forces synchronisation of user enrolments with SUAP.
     * It creates courses if the plugin is configured to do so.
     *
     * @param object $user user record
     * @return void
     */
    public function sync_user_enrolments($user) {

    }
    
    /**
     * Forces synchronisation of all enrolments with SUAP.
     *
     * @param progress_trace $trace
     * @param int|null $onecourse limit sync to one course->id, null if all courses
     * @return void
     */
    public function atualizar_diarios($trace) {
        global $CFG, $DB;
        // echo '<hr><pre>';
        $moodle_diarios = MoodleEnrol::all();
        // echo json_encode($moodle_diarios, JSON_PRETTY_PRINT);
        // echo '</pre>';
        foreach($moodle_diarios as $moodle_diario){ 
            echo_cli("\nenrol_id=$moodle_diario->id\ncourse_id=$moodle_diario->courseid\ndiario_id=$moodle_diario->customint1");
            //echo_cli(json_encode($moodle_diario));           
            
            $moodle_diario->merge(TRUE,
                function($inscrito_encontrado){                    
                    echo_cli("\n - SUAP: Sim - Moodle: Sim = " . $inscrito_encontrado->username . ' ' . $inscrito_encontrado->nome . '. ' );
                },
                function($inscrito_nao_encontrado) {                    
                    echo_cli("\n - SUAP: Sim - Moodle: Não = "  . $inscrito_nao_encontrado->username . ' ' . $inscrito_nao_encontrado->nome . '. ');
                },
                function($inscritos_sobrando) {                    
                    if (CLI_SCRIPT) {
                        if (count($inscritos_sobrando)> 0){
                            foreach($inscritos_sobrando as $inscrito){
                                echo ("\n - SUAP: Não - Moodle: Sim = ". $inscrito->username . ' ' . $inscrito->alternatename . '. '  );                            
                            }            
                        } 
                    }       
                },
                 FALSE,
                 TRUE       
            );
            echo_cli("\n");
            
           
        }


    }

    /**
     * Will create the moodle course from the template
     * course_ext is an array as obtained from ldap -- flattened somewhat
     *
     * @param array $course_ext
     * @param progress_trace $trace
     * @return mixed false on error, id for the newly created course otherwise.
     */
    function create_course($course_ext, progress_trace $trace) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/course/lib.php");
    }
}
