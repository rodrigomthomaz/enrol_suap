<?php


defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/../../config.php';
function render_cursos($exibir_cursos){
    global $CFG;
    $return = '';
    if ($exibir_cursos){        
        $ano_letivo = $CFG->block_suap_auto_semestre_ano;
        $periodo_letivo = $CFG->block_suap_auto_semestre_semestre;
        $campus = $CFG->block_suap_id_campus;
        $exibir_turmas = FALSE;
        try{
            $cursos = SuapCurso::obter_todos($campus, $ano_letivo, $periodo_letivo, TRUE);
        } catch (Exception $e) {
            $cursos = [];
        }     
        $return .= html_writer::start_tag('div', array('style' =>''));
        $return .= html_writer::start_tag('table', array('class' => 'generaltable generalbox groupmanagementtable boxaligncenter'));
        foreach ($cursos as $curso){  
            $turmas = $curso->get_turmas();
            $exibir_turmas = $exibir_turmas && $curso->moodle_info;
            if ($exibir_turmas) {
                $rowspan_curso = array('rowspan'=> count($turmas) + 1);
            } else {
                $rowspan_curso = array();
            }
            $return .= html_writer::start_tag('tr');
            $return .= html_writer::tag('td', $curso->id_suap, $rowspan_curso);
            $return .= html_writer::tag('td', $curso->codigo);
            $return .= html_writer::tag('td', '<strong>' . $curso->nome . '</strong>');
            if ($curso->moodle_info) {
                $importar_link = html_writer::link(new moodle_url("/blocks/suap/importar_diario.php?id_curso={$curso->id_suap}&ano={$ano_letivo}&periodo={$periodo_letivo}"),'Importar');
                $return .= html_writer::tag('td', $importar_link);
                $auto_associar_link = html_writer::link(new moodle_url("/blocks/suap/auto_associar.php?id_curso={$curso->id_suap}"), 'Auto associar');
                $return .= html_writer::tag('td', $auto_associar_link);
                $turmas_link = html_writer::link(new moodle_url("/blocks/suap/listar_turmas.php?id_curso={$curso->id_suap}&codigo={$curso->codigo}&ano={$ano_letivo}&periodo={$periodo_letivo}"),'Turmas');
                $return .= html_writer::tag('td', $turmas_link);
                
        } else {
                $associar_link = html_writer::link(new moodle_url("/blocks/suap/associar_curso.php?id_curso={$curso->id_suap}"), 'Associar a uma categoria');
                $return .= html_writer::tag('td', $associar_link);
                $return .= html_writer::tag('td', '');
                $return .= html_writer::tag('td', ''); 
            }        
            $componentes_link = html_writer::link(new moodle_url("/blocks/suap/listar_componentes.php?id_curso={$curso->id_suap}&ano={$ano_letivo}&periodo={$periodo_letivo}"), 'Componentes');
            $return .= html_writer::tag('td', $componentes_link);
            $return .= html_writer::end_tag('tr');
            if ($exibir_turmas) {
                foreach($turmas as $turma){
                    $return .= html_writer::start_tag('tr');
                    $return .= html_writer::tag('td', $turma->id_suap, array());
                    $return .= html_writer::tag('td', 'Turma '. $turma->suap_info['codigo']  );
                    $return .= html_writer::tag('td', '<pre>' . json_encode($turma->moodle_info). '</pre>', array('colspan'=> '4') );
                    $return .= html_writer::end_tag('tr');
                }
            }
            
        }
        $return .= html_writer::end_tag('table');
        $return .= html_writer::end_tag('div');
}
    //$return .= var_export(core_course_category::make_categories_list('', 0, ' / '),true);/**/
    return $return;
}


class enrol_suap_admin_setting_category extends admin_setting_configselect {
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, 1, null);
    }

    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = core_course_category::make_categories_list('', 0, ' / ');
        return true;
    }
}