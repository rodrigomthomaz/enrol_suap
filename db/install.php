<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_suap_install() {
    global $CFG, $DB;
    require_once($CFG->dirroot .'/enrol/suap/db/upgrade.php');
    xmldb_enrol_suap_upgrade(0);
}
