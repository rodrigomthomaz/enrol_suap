<?php


namespace enrol_suap\task;

class atualizar_diarios extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('atualizardiariostask', 'enrol_suap');
    }

    /**
     * Run task for synchronising users.
     */
    public function execute() {

        if (!enrol_is_enabled('suap')) {
            mtrace(get_string('pluginnotenabled', 'enrol_suap'));
            exit(0); // Note, exit with success code, this is not an error - it's just disabled.
        }

        /** @var \enrol_suap_plugin $enrol */
        $enrol = enrol_get_plugin('suap');

        $trace = new \text_progress_trace();

        // Update enrolments -- these handlers should autocreate courses if required.
        $enrol->atualizar_diarios($trace);
    }
}