<?php

/**
 * PLUGIN external file
 *
 * @package   mod_assign_submission_mobas 
 * @copyright  2013 Box Hill Institute
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot.'/mod/assign/locallib.php');

 
class mod_assign_submission_mobas_external extends external_api {
 
  public static function get_mobas_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters(). 
        // The external_function_parameters constructor expects an array of external_description.
                // a external_description can be: external_value, external_single_structure or external_multiple structure
        return new external_function_parameters(
         array()
        );
    }
 
   /**
     * Returns an array of courses the user is enrolled in, and for each course all of the mobas activitites that the user can
     * view within that course.
     *
     * will be returned. If the user is not enrolled in a given course a warning will be generated and returned.
     This is mostly copied from mod_assign_get_assignments
     */
 
  public static function get_mobas(){
        //Parameters validation
        global $USER,$DB;
        $mobasar=array();
        $fields = 'sortorder,shortname,fullname,timemodified';
        $courses = enrol_get_users_courses($USER->id, true, $fields);

        //this is from assign/externallib, but not so robust
        foreach ($courses as $id => $course) {
            if ($modules = get_coursemodules_in_course('assign', $courses[$id]->id)) {
                foreach ($modules as $module) {
                   $context = context_module::instance($module->id); //the assign id
                   if (has_capability('mod/assign:submit', $context)){
                       //$submission = $assign->get_user_submission($USER->id, true);
                       $cm = get_coursemodule_from_id('assign', $module->id, 0, false, MUST_EXIST);
                       $assign = new assign($context,$cm,$courses[$id]->id);
                       $mobas=$assign->get_submission_plugin_by_type('mobas');
                       if ($mobas->enabled()){
                            $a=$assign->get_instance();
                            $m=array('id'=>$a->id,
                            'name'=>$a->name,
                            'intro'=>$a->intro,
                            'course'=>$a->course,
                            'duedate'=>$a->duedate,
                            'allowsubmissionsfromdate'=>$a->allowsubmissionsfromdate
                            );
                            $mobasar[]=array_merge($m,$mobas->get_info());
                       }
                    }
                }
            }
        }


        return $mobasar;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_mobas_returns() {

        return new external_multiple_structure(
            new external_single_structure(
                    array('id'=> new external_value(PARAM_INT,'id'),
                          'name'=>new external_value(PARAM_TEXT,'name'),
                          'intro'=>new external_value(PARAM_RAW,'intro'),
                          'course'=>new external_value(PARAM_INT,'courseid'),
                          'duedate'=>new external_value(PARAM_INT,'due date'),
                          'allowsubmissionsfromdate'=>new external_value(PARAM_INT,'submissions from'),
                          'mtype'=>new external_value(PARAM_TEXT,'mobas type'), //is an int but stored as text in db
                          'submitcode'=>new external_value(PARAM_TEXT,'mobas submit code'),
                          'content'=>new external_value(PARAM_RAW,'mobas content')
                )
            )
            );
    }

    public static function uploadhtml_parameters(){
        return new external_function_parameters(
                     array( 'assignmentid' => new external_value(PARAM_INT,'assignmentid',VALUE_REQUIRED,'',NULL_NOT_ALLOWED),
                                    'content'   =>new external_value(PARAM_RAW,'content',VALUE_DEFAULT,null,NULL_ALLOWED), 
                                    'submitcode'=>new external_value(PARAM_TEXT,'submitcode',VALUE_DEFAULT,null,NULL_ALLOWED)
                            ));
    }

    /*
       upload html from mobile device to the onlinetext component of the assignment
        key behaviour is from assign->process_save_submission(), whcih is depending on an existing form data.
    */
    public static function uploadhtml($assignmentid,$content,$submitcode){
          global $USER,$DB;
          //validate user in course
         $params = self::validate_parameters(self::uploadhtml_parameters(), 
         array('assignmentid'=>$assignmentid, 'content'=>$content,'submitcode'=>$submitcode));
       //find html content item for assignment
          
       $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
       $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
       $context = context_module::instance($cm->id);

       $assign = new assign($context,$cm,$course);
       require_capability('mod/assign:submit', $context);
       if (!$assign->submissions_open()) {
           $error= get_string('submissionsclosed','assign');
       } else {
           $submission = $assign->get_user_submission($USER->id, true);
           $mobas=$assign->get_submission_plugin_by_type('mobas');
           if ($mobas->check_submitcode($submitcode)){
                $data=new stdClass;
                $data->mobas=$content;
               $mobas->save_mobile($submission,$data);
               //private method
               //$assign->update_submission($submission, $USER->id, true, $assign->get_instance()->teamsubmission);
               add_to_log($cm->course,'assign', 'submit', '', $info='mobas', $cm->id, $USER->id);
               $submission->status="submitted";
               $result=$DB->update_record('assign_submission', $submission);
               $error=$result?"":'database error on submit';
           } else { //submit code incorrect
            $result=0;
            $error='incorrect assessor code for Demonstration Checklist';
           }

        }
        return array('assignmentid'=>$assignmentid,
                    'result'=>$result,
                    'error'=>$error);




/*
 from process_save_submission
 $this->update_submission($submission, $USER->id, true, $this->get_instance()->teamsubmission);
            /*

            $complete = COMPLETION_INCOMPLETE;
            if ($submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                $complete = COMPLETION_COMPLETE;
            }
            $completion = new completion_info($this->get_course());
            if ($completion->is_enabled($this->get_course_module()) && $this->get_instance()->completionsubmit) {
                $completion->update_state($this->get_course_module(), $complete, $USER->id);
            }

*/
    }

    public static function uploadhtml_returns(){
        return new external_single_structure(
        array('assignmentid' => new external_value(PARAM_INT,'assignment id'),
                'result' => new external_value(PARAM_INT,'result'),
                'error' => new external_value(PARAM_RAW,'error')
        ));
    }
    
 
}
                     
