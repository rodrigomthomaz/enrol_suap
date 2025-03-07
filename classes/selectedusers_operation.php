<?php

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . "/../../bulkchange_forms.php");

class enrol_suap_deleteselectedusers_form extends enrol_bulk_enrolment_confirm_form {}
class enrol_suap_deleteselectedusers_operation extends enrol_bulk_enrolment_operation {

    /**
     * Returns the title to display for this bulk operation.
     *
     * @return string
     */
    public function get_identifier() {
        return 'deleteselectedusers';
    }

    /**
     * Returns the identifier for this bulk operation. This is the key used when the plugin
     * returns an array containing all of the bulk operations it supports.
     *
     * @return string
     */
    public function get_title() {
        return get_string('deleteselectedusers', 'enrol_suap');
    }
    public function get_message(){
        return get_string('confirmbulkdeleteenrolment', 'enrol_suap');
    }
    public function get_button_label(){
        return get_string('unenrolusers', 'enrol_suap');
    }
    /**
     * Returns a enrol_bulk_enrolment_operation extension form to be used
     * in collecting required information for this operation to be processed.
     *
     * @param string|moodle_url|null $defaultaction
     * @param mixed $defaultcustomdata
     * @return enrol_suap_deleteselectedusers_form
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        if (!array($defaultcustomdata)) {
            $defaultcustomdata = array();
        }
        $defaultcustomdata['title'] = $this->get_title();
        $defaultcustomdata['message'] = $this->get_message();
        $defaultcustomdata['button'] = $this->get_button_label();    
        //exit('<pre>' . json_encode($defaultcustomdata, JSON_PRETTY_PRINT) . '</pre>');
        return new enrol_suap_deleteselectedusers_form($defaultaction, $defaultcustomdata);
    }


    public function process(course_enrolment_manager $manager, array $users, stdClass $properties) {
        if (!has_capability("enrol/suap:unenrol", $manager->get_context())) {
            return false;
        }

        foreach ($users as $user) {
            foreach ($user->enrolments as $enrolment) {
                $plugin = $enrolment->enrolmentplugin;
                $instance = $enrolment->enrolmentinstance;
                if ($plugin->allow_unenrol_user($instance, $enrolment)) {
                    $plugin->unenrol_user($instance, $user->id);
                }
            }
        }

        return true;
    }
}


class enrol_suap_deletesuspendedusers_operation extends enrol_suap_deleteselectedusers_operation {

    public function get_title() {
        return get_string('deletesuspendedusers', 'enrol_suap');
    }
    public function get_message(){
        return get_string('confirmbulkdeleteenrolment', 'enrol_suap');
    }    
    public function get_button_label(){
        return get_string('unenrolusers', 'enrol_suap');
    }
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        if (!array($defaultcustomdata)) {
            $defaultcustomdata = array();
        }
        $defaultcustomdata['title'] = $this->get_title();
        $defaultcustomdata['message'] = $this->get_message();
        $defaultcustomdata['button'] = $this->get_button_label();
        foreach ($defaultcustomdata['users'] as $u_id => $user) {
            foreach ($user->enrolments as $enrolment) {
                if ($enrolment->status == ENROL_INSTANCE_ENABLED) {
                    unset($defaultcustomdata['users'][$u_id]);
                }
            }
        }
        //exit('<pre>' . json_encode($defaultcustomdata, JSON_PRETTY_PRINT) . '</pre>');
        return new enrol_suap_deleteselectedusers_form($defaultaction, $defaultcustomdata);
    }

}