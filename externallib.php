<?php

/**
 * PLUGIN external file
 *
 * @package   mod_assign_submission_mobas 
 * @copyright  2013 Box Hill Institute
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');

 
class mod_assign_submission_mobas_external extends external_api {
 
  public static function get_mobas_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters(). 
        // The external_function_parameters constructor expects an array of external_description.
                // a external_description can be: external_value, external_single_structure or external_multiple structure
        return new external_function_parameters(
         array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'course id')
                )
            )
        );
    }
 
   /**
     * Returns an array of courses the user is enrolled in, and for each course all of the mobas activitites that the user can
     * view within that course.
     *
     * @param array $courseids An optional array of course ids. If provided only assignments within the given course
     * will be returned. If the user is not enrolled in a given course a warning will be generated and returned.
     * @return An array of courses and warnings.
     * @since  Moodle 2.4

     This is mostly copied from mod_assign_get_assignments
     */
 
  public static function get_mobas($courseids=array()){
        //Parameters validation
        global $USER,$DB;
        //Note: don't forget to validate the context and check capabilities
      $params = self::validate_parameters(self::get_mobas_parameters(),
            array('courseids'=>$courseids)
        );

 // Used to test for ids that have been requested but can't be returned.
        $warnings = array();
        if (count($params['courseids']) > 0) {
            $fields = 'sortorder,shortname,fullname,timemodified';
            $courses = enrol_get_users_courses($USER->id, true, $fields);
            foreach ($params['courseids'] as $courseid) {
                if (!in_array($courseid, array_keys($courses))) {
                    unset($courses[$courseid]);
                    $warnings[] = array(
                        'item' => 'course',
                        'itemid' => $courseid,
                        'warningcode' => '2',
                        'message' => 'User is not enrolled or does not have requested capability'
                    );
                }
            }
        }
/*
         foreach ($courses as $id => $course) {
            if (count($params['courseids']) > 0 && !in_array($id, $params['courseids'])) {
                unset($courses[$id]);
            }
            $context = context_course::instance($id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                unset($courses[$id]);
                $warnings[] = array(
                    'item' => 'course',
                    'itemid' => $id,
                    'warningcode' => '1',
                    'message' => 'No access rights in course context '.$e->getMessage().$e->getTraceAsString()
                );
                continue;
            }
        }

*/
        $courselist=join(',',array_keys($courses));


//todo: replace direct query with proper oop info gathering
//$mobas=new assign_submission_mobas()
//$mobas->get_config("type");

   $sql="select a.id,a.name,intro,course,duedate, allowsubmissionsfromdate,
c.value as enabled,c2.value as mtype from {assign} a inner join 
(select assignment,value from {assign_plugin_config} where plugin='mobas' and  name='enabled') c on a.id=c.assignment
inner join
(select assignment,value from {assign_plugin_config} where plugin='mobas' and  name='type') c2 on a.id=c2.assignment
        where a.course in ($courselist)";

/*
        $sql="select a.id,a.name,intro,course,duedate,allowsubmissionsfromdate from {assign} a 
        inner join {assign_plugin_config} c on a.id=c.assignment
        where c.plugin='mobas' and c.name='enabled' 
        and cast(c.value as nvarchar(1))=1 and a.course in ($courselist)";
        */
        $result=$DB->get_records_sql($sql, array('courselist'=>$courselist));
        $resultar=array();
        foreach ($result as $id=>$row){
          $resultar[]=(array) $row; //convert each to array
        }
        return $resultar;
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
                          'mtype'=>new external_value(PARAM_INT,'mobas type'),
                          'duedate'=>new external_value(PARAM_INT,'due date'),
                          'allowsubmissionsfromdate'=>new external_value(PARAM_INT,'submissions from')
                )
            )
            );
    }

    public static function uploadhtml_parameters(){
        return new external_function_parameters(
                     array( 'assignmentid' => new external_value(PARAM_INT,'assignmentid',VALUE_REQUIRED,'',NULL_NOT_ALLOWED),
                                    'content'   =>new external_value(PARAM_RAW,'content',VALUE_DEFAULT,null,NULL_ALLOWED) //todo:is this right for long html?
                            ));
    }

    /*
       upload html from mobile device to the onlinetext component of the assignment
        key behaviour is from assign->process_save_submission(), whcih is depending on an existing form data.
    */
    public static function uploadhtml($assignmentid,$content){
          global $USER,$DB;
          //validate user in course
         $params = self::validate_parameters(self::uploadhtml_parameters(), 
         array('assignmentid'=>$assignmentid, 'content'=>$content));
       //find html content item for assignment
          
    $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);

   $assign = new assign($context,$cm,$course);
   require_capability('mod/assign:submit', $context);
   $onlinetext=$assign->get_submission_plugin_by_type('onlinetext');
   $submission = $assign->get_user_submission($USER->id, true);
//this is in private function in onlinetext unfortunately...
$onlinetextsubmission= $DB->get_record('assignsubmission_onlinetext', array('submission'=>$submission->id));

$ot="";
/*
 from process_save_submission
 $this->update_submission($submission, $USER->id, true, $this->get_instance()->teamsubmission);

            // Logging
            if (isset($data->submissionstatement)) {
                $this->add_to_log('submission statement accepted', get_string('submissionstatementacceptedlog', 'mod_assign', fullname($USER)));
            }
            $this->add_to_log('submit', $this->format_submission_for_log($submission));

            $complete = COMPLETION_INCOMPLETE;
            if ($submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                $complete = COMPLETION_COMPLETE;
            }
            $completion = new completion_info($this->get_course());
            if ($completion->is_enabled($this->get_course_module()) && $this->get_instance()->completionsubmit) {
                $completion->update_state($this->get_course_module(), $complete, $USER->id);
            }

            if (!$this->get_instance()->submissiondrafts) {
                $this->notify_student_submission_receipt($submission);
                $this->notify_graders($submission);
                // Trigger assessable_submitted event on submission.
                $eventdata = new stdClass();
                $eventdata->modulename   = 'assign';
                $eventdata->cmid         = $this->get_course_module()->id;
                $eventdata->itemid       = $submission->id;
                $eventdata->courseid     = $this->get_course()->id;
                $eventdata->userid       = $USER->id;
                $eventdata->params       = array(
                    'submission_editable' => true,
                );
                events_trigger('assessable_submitted', $eventdata);
            }
            return true;
        }
        return false;
    }





    //$onlinetext->submit();
ob_start();
print_object($submission);
$ot=ob_get_contents();
ob_end_clean();
   foreach ($this->submissionplugins as $plugin) {
                if ($plugin->is_enabled()) {
                    if (!$plugin->save($submission, $data)) {
                        $notices[] = $plugin->get_error();
                        $pluginerror = true;
                    }
                    if (!$allempty || !$plugin->is_empty($submission)) {
                        $allempty = false;
                    }
                }
            }

            //see assign/process_save_submission; trigger logging and completion data as well
 //adapted from locallib for online text (which depends on editor, which we're not using?
        $assign=new assign_submission_onlinetext;
        $onlinetextsubmission = $assign->get_onlinetext_submission($submission->id);

        /* Let Moodle know that an assessable content was uploaded (eg for plagiarism detection)
        $eventdata = new stdClass();
        $eventdata->modulename = 'assign';
        $eventdata->cmid = $this->assignment->get_course_module()->id;
        $eventdata->itemid = $submission->id;
        $eventdata->courseid = $this->assignment->get_course()->id;
        $eventdata->userid = $USER->id;
        $eventdata->content = trim(format_text($data->onlinetext, 1, array('context'=>$this->assignment->get_context())));
        events_trigger('assessable_content_uploaded', $eventdata);
     */
//online text uses the editor to save, so we're hackily writing direct to db (not ideal).
        if ($onlinetextsubmission) {
            $onlinetextsubmission->onlinetext = $content;
            $onlinetextsubmission->onlineformat = 1;
            $result= $DB->update_record('assignsubmission_onlinetext', $onlinetextsubmission);
        } else {
            $onlinetextsubmission = new stdClass();
            $onlinetextsubmission->onlinetext = $content;
            $onlinetextsubmission->onlineformat = 1;
            $onlinetextsubmission->submission = $submission->id;
            $onlinetextsubmission->assignment = $assignmentid; //$this->assignment->get_instance()->id;
            $result= $DB->insert_record('assignsubmission_onlinetext', $onlinetextsubmission) > 0;
        }

        //todo:all should be done through proper methods;
        //currently no triggering of any events, notifications, etc
        $submission->status="submitted";
        $DB->update_record('assign_submission', $submission);
        $error=$result?"":'database error on submit';
        return array('assignmentid'=>$assignmentid,
                    'result'=>$result,
                    'error'=>$error);

    }

    public static function uploadhtml_returns(){
        return new external_single_structure(
        array('assignmentid' => new external_value(PARAM_INT,'assignment id'),
                'result' => new external_value(PARAM_INT,'result'),
                'error' => new external_value(PARAM_RAW,'error')
        ));
    }
    
 
}
                     
