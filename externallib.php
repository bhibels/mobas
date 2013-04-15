<?php

/**
 * PLUGIN external file
 *
 * @package    local_PLUGIN
 * @copyright  2013 Box Hill Institute
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot.'/calendar/lib.php');

 
class mod_assign_submission_mobas_external extends external_api {
 
  public static function get_mobas_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters(). 
        // The external_function_parameters constructor expects an array of external_description.
                // a external_description can be: external_value, external_single_structure or external_multiple structure
        return new external_function_parameters(
         array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'course id'),
                    '0 or more course ids',
                    VALUE_DEFAULT, array()
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
        global $USER,$CFG;
        //Note: don't forget to validate the context and check capabilities

     $params = self::validate_parameters(
            self::get_mobas(),
            array('courseids' => $courseids)
        );

        $warnings = array();
        $fields = 'sortorder,shortname,fullname,timemodified';
        $courses = enrol_get_users_courses($USER->id, true, $fields);
 // Used to test for ids that have been requested but can't be returned.
        if (count($params['courseids']) > 0) {
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

//should I swap to assignment subtype right now???

   $extrafields='m.id as mobasid, m.course, m.nosubmissions, m.submissiondrafts, m.sendnotifications, '.
                     'm.sendlatenotifications, m.duedate, m.allowsubmissionsfromdate, m.grade, m.timemodified, '.
                     'm.completionsubmit, m.cutoffdate, m.teamsubmission, m.requireallteammemberssubmit, '.
                     'm.teamsubmissiongroupingid, m.blindmarking, m.revealidentities, m.requiresubmissionstatement';
        $coursearray = array();




        return array();
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_mobas_returns() {
        return new external_single_structure(
            array(
            'calendarurl'=>new external_value(PARAM_TEXT, 'export link for calendar')
            ));
    }


 
}
                     
