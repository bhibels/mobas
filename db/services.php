<?php
$functions = array( 'mod_assign_submission_mobas_get_mobas' =>array(
        'classname' => 'mod_assign_submission_mobas_external',
        'methodname' => 'get_mobas',
        'classpath' =>'mod/assign/submission/mobas/externallib.php',
        'description' => 'get list of mobas activities for given list of course ids',
        'type'  =>'read',
    ),

'mod_assign_submission_mobas_uploadhtml'=>array(
        'classname' => 'mod_assign_submission_mobas_external',
        'methodname' => 'uploadhtml',
        'classpath' =>'mod/assign/submission/mobas/externallib.php',
        'description' => 'upload to online html component of given assignment',
        'type'  =>'write',
    ),


);

$services = array(
      'Mobas Submission' => array(      //the name of the web service
          'functions' => array ( 'mod_assign_submission_mobas_get_mobas',
                        'mod_assign_submission_mobas_uploadhtml',
                        'core_enrol_get_users_courses',
                        'core_course_get_categories',
                        'core_webservice_get_site_info',
                        ), //web service functions of this service
         // 'requiredcapability' => '',                //if set, the web service user need this capability to access 
                                                     //any function of this service. For example: 'some/capability:specified'                 
          'restrictedusers' =>0,                   //if enabled, the Moodle administrator must link some user to this service
                                                   //into the administration
          'enabled'=>1,                            //if enabled, the service can be reachable on a default installation
          'shortname'=>'mobas',
       )
  );



