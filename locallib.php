<?php 
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for file submission plugin
 * 
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_onlineaudio
 * @copyright 2012 Paul Nicholls
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Include eventslib.php */
//require_once($CFG->libdir.'/eventslib.php');

defined('MOODLE_INTERNAL') || die();
/**
 * File areas for file submission assignment
 */

//define('ASSIGNSUBMISSION_ONLINEAUDIO_MAX_SUMMARY_FILES', 5);
define('ASSIGNSUBMISSION_MOBAS_FILEAREA', 'submission_mobas');

/*
 * library class for online audio recording submission plugin extending submission plugin base class
 * 
 * @package   assignsubmission_onlineaudio
 * @copyright 2012 Paul Nicholls
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_mobas extends assign_submission_plugin {
    
    /**
     * Get the name of the file submission plugin
     * @return string 
     */
    public function get_name() {
        return get_string('mobas', 'assignsubmission_mobas');
    }

    /**
     * Load the submission object for a particular user, optionally creating it if required
     * I don't want to have to do this, but it's private on the assign() class, so can't be used!
     *
     * @param int $userid The id of the user whose submission we want or 0 in which case USER->id is used
     * @param bool $create optional Defaults to false. If set to true a new submission object will be created in the database
     * @return stdClass The submission
     */
    public function get_user_submission_record($userid, $create) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }
        // if the userid is not null then use userid
        $submission = $DB->get_record('assign_mobas', array('assignment'=>$this->assignment->get_instance()->id, 'userid'=>$userid));

        if ($submission) {
            return $submission;
        }
        if ($create) {
            $submission = new stdClass();
            $submission->assignment   = $this->assignment->get_instance()->id;
            $submission->userid       = $userid;
            $submission->timecreated = time();
            $submission->timemodified = $submission->timecreated;

            if ($this->assignment->get_instance()->submissiondrafts) {
                $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
            } else {
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            }
            $sid = $DB->insert_record('assign_submission', $submission);
            $submission->id = $sid;
            return $submission;
        }
        return false;
    }
    
    /**
     * Get file submission information from the database
     * 
     * @global moodle_database $DB
     * @param int $submissionid
     * @return mixed 
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_mobas', array('submission'=>$submissionid));
    }
    
    /**
     * Get the default setting for mobas submission plugin
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $mobastype=$this->get_config('type');

        // Adding the rest of mobas settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic

        $mform->addElement('html','<p>These settings are for students using the mobas app to submit different types of assignment</p>');

        $mform->addElement('editor', 'mobascontent', get_string('lblcontent','assignsubmission_mobas'));
        $mform->setType('contenteditor', PARAM_RAW); // no XSS prevention here, users must be trusted
        $mform->disabledIf('assignsubmission_mobascontent', 'assignsubmission_mobas_enabled', 'eq', 0);
        $options = array(5 =>'Work Diary',8=>'Digital Story',9=>'Job Safety Analysis');
        $mform->addElement('select', 'assignsubmission_mobas_type', get_string('lbltype', 'assignsubmission_mobas'), $options);
        $mform->setDefault('assignsubmission_mobas_type',$mobastype);

        $mform->addHelpButton('assignsubmission_mobas_type', 'lbltype', 'assignsubmission_mobas');
        $mform->disabledIf('assignsubmission_mobas_type', 'assignsubmission_mobas_enabled', 'eq', 0);
    }
    
    /**
     * Save the settings for mobas submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        $this->set_config('type', $data->assignsubmission_mobas_type);
        return true;
    }


   
    /**
     * Add elements to submission form
     * 
     * @param mixed stdClass|null $submission
     * @param MoodleQuickForm $submission
     * @param stdClass $data
     * @return bool 
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $CFG, $USER;
        $submissionid = $submission ? $submission->id : 0;
        $maxfiles = $this->get_config('maxfilesubmissions');
        $defaultname = $this->get_config('defaultname');
        $allownameoverride = $this->get_config('nameoverride');
        if ($maxfiles <= 0) {
            return false;
        }
        $count = $this->count_files($submissionid, ASSIGN_FILEAREA_SUBMISSION_ONLINEAUDIO);
        /*
        if($count < $maxfiles) {
            $url='submission/onlineaudio/assets/recorder.swf?gateway='.$CFG->wwwroot.'/mod/assign/submission/onlineaudio/upload.php';

            $flashvars="&filefield=assignment_file&id={$this->assignment->get_course_module()->id}&sid={$submissionid}";

            if($defaultname) {
                $field=($allownameoverride)?'filename':'forcename';
                $filename=($defaultname==2)?fullname($USER):$USER->username;
                $filename=clean_filename($filename);
                $assignname=clean_filename($this->assignment->get_instance()->name);
                $coursename=clean_filename($this->assignment->get_course()->shortname);
                $filename.='_-_'.substr($assignname,0,20).'_-_'.$coursename.'_-_'.date('Y-m-d');
                $filename=str_replace(' ', '_', $filename);
                $flashvars .= "&$field=$filename";
            }

            $html = '<script type="text/javascript" src="submission/onlineaudio/assets/swfobject.js"></script>
                <script type="text/javascript">
                swfobject.registerObject("onlineaudiorecorder", "10.1.0", "submission/onlineaudio/assets/expressInstall.swf");
                </script>';

            $html .= '<div id="onlineaudiorecordersection" style="float:left">
                <object id="onlineaudiorecorder" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="215" height="138">
                        <param name="movie" value="'.$url.$flashvars.'" />
                        <!--[if !IE]>-->
                        <object type="application/x-shockwave-flash" data="'.$url.$flashvars.'" width="215" height="138">
                        <!--<![endif]-->
                        <div>
                                <p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
                        </div>
                        <!--[if !IE]>-->
                        </object>
                        <!--<![endif]-->
                </object></div>';
            $mform->addElement('html', $html);
        } else {
            $mform->addElement('html', '<p>'.get_string('maxfilesreached', 'assignsubmission_onlineaudio').'</p>');
        }
        */
        $mform->addElement('html', $this->print_user_files($submissionid));
        
        return true;
    }


    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     * 
     * @param stdClass $submission
     * @return string 
     */
    public function view(stdClass $submission) {
        return $this->assignment->render_area_files('assignsubmission_mobas', ASSIGN_FILEAREA_SUBMISSION_MOBAS, $submission->id);
    }
    


    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     * 
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        return false;
    }
  
    

    /**
     * The assignment has been deleted - cleanup
     * 
     * @global moodle_database $DB
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_mobas', array('assignment'=>$this->assignment->get_instance()->id));
        
        return true;
    }
    
    /**
     * Formatting for log info
     * 
     * @param stdClass $submission The submission
     * 
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $filecount = $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_MOBAS);
        $fileloginfo = '';
        $fileloginfo .= ' the number of file(s) : ' . $filecount . " file(s).<br>";

        return $fileloginfo;
    }

    /**
     * Return true if there are no submission files
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, ASSIGN_FILEAREA_SUBMISSION_MOBAS) == 0;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGN_FILEAREA_SUBMISSION_MOBAS=>$this->get_name());
    }

    //GM had to add
    public function portfolio_exportable(){
        return true;
    }
}
