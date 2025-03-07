<?php
defined('MOODLE_INTERNAL') || die();

function configure_endpoint($issuer_id, $endpoint_name, $url) {
    global $DB, $CFG, $USER;
    $endpoint = $DB->get_record_sql('SELECT * FROM {oauth2_endpoint} WHERE issuerid = ? AND name = ?', [$issuer_id, $endpoint_name]);
    if (!$endpoint) {
        $endpointdata = [
            'name' => $endpoint_name,
            'url'=> $url,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $USER->id,
            'issuerid' => $issuer_id,
        ];        
        $DB->insert_record('oauth2_endpoint', $endpointdata);
    }
}
function configure_userfieldmappings($issuer_id, $externalfield, $internalfield) {
    global $DB, $CFG, $USER;
    $userfield = $DB->get_record_sql('SELECT * FROM {oauth2_user_field_mapping} WHERE issuerid = ? AND internalfield = ?', [$issuer_id, $internalfield]);
    if (!$userfield) {
        $endpointdata = [
            'externalfield' => $externalfield,
            'internalfield'=> $internalfield,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $USER->id,
            'issuerid' => $issuer_id,
        ];
        $DB->insert_record('oauth2_user_field_mapping', $endpointdata);
    }
}

function xmldb_enrol_suap_upgrade($oldversion){
    global $DB, $CFG, $USER;
    $enabled = enrol_get_plugins(true);
    $enabled['suap'] = true;
    $enabled = array_keys($enabled);
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    $issuerdata = $DB->get_record_sql('SELECT * FROM {oauth2_issuer} WHERE name LIKE ? ', ['%SUAP%']);
    if (!$issuerdata){
        $issuer = [
            'name'=> 'Autenticação SUAP', 
            'image' => '',
            'baseurl'=> '',
            'clientid'  => '',
            'clientsecret' => '',   
            'loginscopes' => '',
            'loginscopesoffline' => '',
            'enabled' => 0,
            'showonloginpage' => 1,
            'basicauth' => 1,
            'requireconfirmation' => 0,
            'loginpage' => 'SUAP',
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $USER->id,
            'loginparams' => '',
            'loginparamsoffline' => '',
            'alloweddomains' => '',
            'sortorder' => 0
        ];
        $issuer_id = $DB->insert_record('oauth2_issuer', $issuer);
    } else {
        $issuer_id = $issuerdata->id;
    }
    configure_endpoint($issuer_id, 'authorization_endpoint', '' );
    configure_endpoint($issuer_id, 'token_endpoint', '');
    configure_endpoint($issuer_id, 'userinfo_endpoint', '');

    configure_userfieldmappings($issuer_id, 'alternatename', 'alternatename');
    configure_userfieldmappings($issuer_id, 'user_role', 'department');
    configure_userfieldmappings($issuer_id, 'first_name', 'firstname');
    configure_userfieldmappings($issuer_id, 'username', 'username');
    configure_userfieldmappings($issuer_id, 'last_name', 'lastname');    
    configure_userfieldmappings($issuer_id, 'matricula_prontuario', 'idnumber');
    configure_userfieldmappings($issuer_id, 'email', 'email');
    configure_userfieldmappings($issuer_id, 'middle_name', 'middlename');


    if ($oldversion < 2025050000) {
        
    }
    return true;

}