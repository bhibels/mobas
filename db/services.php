<?php
$functions = array( 'mod_assign_submission_mobas_get_mobas' =>array(
        'classname' => 'mod_assign_submission_mobas_external',
        'methodname' => 'get_mobas',
        'classpath' =>'mod/assign/submission/mobas/externallib.php',
        'description' => 'get list of mobas activities for given list of course ids',
        'type'  =>'read',
    ),


);

$services = array(
      'Mobas Submission' => array(                                                //the name of the web service
          'functions' => array ( 'mod_assign_submission_mobas_get_mobas',
                        'core_files_upload',
                        'core_enrol_get_users_courses',
                        'core_course_get_contents',
                        'core_course_get_courses',
                        'core_course_get_categories',
                        'core_webservice_get_site_info',
                        'mod_assign_get_grades',
                        'mod_assign_get_assignments'
                        ), //web service functions of this service
         // 'requiredcapability' => '',                //if set, the web service user need this capability to access 
                                                     //any function of this service. For example: 'some/capability:specified'                 
          'restrictedusers' =>0,                   //if enabled, the Moodle administrator must link some user to this service
                                                   //into the administration
          'enabled'=>1,                            //if enabled, the service can be reachable on a default installation
       )
  );



