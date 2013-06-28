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
 * This file contains the definition for the library class for mobas submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_mobas
 * @copyright 2013 Box Hill Institute 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//todo: is file area required?

defined('MOODLE_INTERNAL') || die();
/**
 * File area for online text submission assignment
 */
define('ASSIGNSUBMISSION_MOBAS_FILEAREA', 'submissions_mobas');

/**
 * library class for mobas  submission plugin extending submission plugin base class
 *
 */
class assign_submission_mobas extends assign_submission_plugin {

    /**
     * Get the name of the mobas submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('mobas', 'assignsubmission_mobas');
    }


   /**
    * Get mobas submission information from the database
    *
    * @param  int $submissionid
    * @return mixed
    */
    private function get_mobas_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_mobas', array('submission'=>$submissionid));
    }

    public function enabled(){
        //todo:It seems like each assignment can return objects for each submission type whether enabled or not. So I'm doing this, but there should be a better way. Similarly for the assignment checks,  submission_open errors with a missing courseid, so I'm not sure what's going on.
        if ($this->get_config('enabled') ) return true;
        
        return false;
    }
/*
* info for external client
*/
    public function get_info(){
        $mobastype=$this->get_config('type');
        $mobascontent=$this->get_config('content');
        return array('mtype'=>$mobastype,'content'=>$mobascontent);

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
        $mobassubmitcode=$this->get_config('submitcode')?$this->get_config('submitcode'):'';
        $mobascontent=$this->get_config('content')?$this->get_config('content'):'';
        // Adding the rest of mobas settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic

        $options = array(1 =>'Work Diary',2=>'Create Procedure/Process',3=>'Job Safety Analysis',4=>'Demonstration Checklist');
        $mform->addElement('select', 'assignsubmission_mobas_type', get_string('lbltype', 'assignsubmission_mobas'), $options);
        $mform->setDefault('assignsubmission_mobas_type',$mobastype);
        $mform->addHelpButton('assignsubmission_mobas_type', 'lbltype', 'assignsubmission_mobas');
        $mform->disabledIf('assignsubmission_mobas_type', 'assignsubmission_mobas_enabled', 'eq', 0);
        $mform->addElement('textarea', 'assignsubmission_mobas_content', get_string('lblcontent','assignsubmission_mobas'),'wrap="virtual" rows="10" cols="50" style="padding:1ex"');
        $mform->setDefault('assignsubmission_mobas_content',$mobascontent);
        $mform->addHelpButton('assignsubmission_mobas_content', 'lblcontent', 'assignsubmission_mobas');
        $mform->disabledIf('assignsubmission_mobas_content', 'assignsubmission_mobas_type', 'neq', 4);
        $mform->disabledIf('assignsubmission_mobas_content', 'assignsubmission_mobas_enabled', 'eq', 0);
        $mform->addElement('passwordunmask','assignsubmission_mobas_submitcode',get_string('lblsubmitcode','assignsubmission_mobas'),array());
        $mform->setDefault('assignsubmission_mobas_submitcode',$mobassubmitcode);
        $mform->addHelpButton('assignsubmission_mobas_submitcode', 'lblsubmitcode', 'assignsubmission_mobas');
        $mform->disabledIf('assignsubmission_mobas_submitcode', 'assignsubmission_mobas_type', 'neq', 4);
        $mform->disabledIf('assignsubmission_mobas_submitcode', 'assignsubmission_mobas_enabled', 'eq', 0);
        $mform->addElement('html','<hr width="75%">');
    }
    
    /**
     * Save the settings for mobas submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        $this->set_config('type', $data->assignsubmission_mobas_type);
        $this->set_config('submitcode', $data->assignsubmission_mobas_submitcode);
        $this->set_config('content', $data->assignsubmission_mobas_content);
        return true;
    }


    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
     //todo: add the nb form setup stuff
     //alter because no choice of format?
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $elements = array();

        //$editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;

        if (!isset($data->content)) {
            $data->content = '';
        }
        if (!isset($data->mobasformat)) {
            $data->mobasformat = 1;
        }

        if ($submission) {
            $mobassubmission = $this->get_mobas_submission($submission->id);
            if ($mobassubmission) {
                $data->mobas = $mobassubmission->mobas;
                $data->mobasformat = 1;
            }

        }


        //$data = file_prepare_standard_editor($data, 'mobas', $editoroptions, $this->assignment->get_context(), 'assignsubmission_mobas', ASSIGNSUBMISSION_ONLINETEXT_FILEAREA, $submissionid);
        $mform->addElement('editor', 'mobas_editor', '', null, $editoroptions);
        return true;
    }

    public function check_submitcode($code){
        if ($this->get_config('type')!=4 || $this->get_config('submitcode')==$code){
            return true;
        }
       return false; 
    }
    /**
     * Editor format options
     *
     * @return array
     */
    private function get_edit_options() {
         $editoroptions = array(
           'noclean' => false,
           'maxfiles' => 0,     //EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->assignment->get_course()->maxbytes,
           'context' => $this->assignment->get_context(),
           'return_types' => FILE_INTERNAL | FILE_EXTERNAL
        );
        return $editoroptions;
    }

     /**
      * Save data to the database and trigger plagiarism plugin, if enabled, to scan the uploaded content via events trigger
      *
      * @param stdClass $submission
      * @param stdClass $data
      * @return bool
      */


   //todo: how do we say assignment is submitted?
     public function save_mobile(stdClass $submission, stdClass $data){
        global $USER, $DB;
        $mobassubmission=$this->get_mobas_submission($submission->id);
         // Let Moodle know that an assessable content was uploaded (eg for plagiarism detection)
        $eventdata = new stdClass();
        $eventdata->modulename = 'assign';
        $eventdata->cmid = $this->assignment->get_course_module()->id;
        $eventdata->itemid = $submission->id;
        $eventdata->courseid = $this->assignment->get_course()->id;
        $eventdata->userid = $USER->id;
        //todo: fix this... don't use format_text...
        $eventdata->content = trim(format_text($data->mobas,1, array('context'=>$this->assignment->get_context())));
        events_trigger('assessable_content_uploaded', $eventdata);

        if ($mobassubmission) {
            $mobassubmission->mobas = $data->mobas;
            $mobassubmission->onlineformat = 1;
            return $DB->update_record('assignsubmission_mobas', $mobassubmission);
        } else {
            $mobassubmission = new stdClass();
            $mobassubmission->mobas = $data->mobas;
            $mobassubmission->onlineformat = 1;
            $mobassubmission->submission = $submission->id;
            $mobassubmission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assignsubmission_mobas', $mobassubmission) > 0;
        }

}

     public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $editoroptions = $this->get_edit_options();

        $data = file_postupdate_standard_editor($data, 'mobas', $editoroptions, $this->assignment->get_context(), 'assignsubmission_mobas', ASSIGNSUBMISSION_ONLINETEXT_FILEAREA, $submission->id);

        $mobassubmission = $this->get_mobas_submission($submission->id);

        //$fs = get_file_storage();
        //$files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_mobas', ASSIGNSUBMISSION_ONLINETEXT_FILEAREA, $submission->id, "id", false);
        // Let Moodle know that an assessable content was uploaded (eg for plagiarism detection)
        $eventdata = new stdClass();
        $eventdata->modulename = 'assign';
        $eventdata->cmid = $this->assignment->get_course_module()->id;
        $eventdata->itemid = $submission->id;
        $eventdata->courseid = $this->assignment->get_course()->id;
        $eventdata->userid = $USER->id;
        $eventdata->content = trim(format_text($data->mobas, $data->mobas_editor['format'], array('context'=>$this->assignment->get_context())));
        if ($files) {
            $eventdata->pathnamehashes = array_keys($files);
        }
        events_trigger('assessable_content_uploaded', $eventdata);

        if ($mobassubmission) {

            $mobassubmission->mobas = $data->mobas;
            $mobassubmission->onlineformat = $data->mobas_editor['format'];


            return $DB->update_record('assignsubmission_mobas', $mobassubmission);
        } else {

            $mobassubmission = new stdClass();
            $mobassubmission->mobas = $data->mobas;
            $mobassubmission->onlineformat = $data->mobas_editor['format'];

            $mobassubmission->submission = $submission->id;
            $mobassubmission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assignsubmission_mobas', $mobassubmission) > 0;
        }


    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('mobas' => get_string('pluginname', 'assignsubmission_comments'));
    }

    /**
     * Get the saved text content from the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return string
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'mobas') {
            $mobassubmission = $this->get_mobas_submission($submissionid);
            if ($mobassubmission) {
                return $mobassubmission->mobas;
            }
        }

        return '';
    }

    /**
     * Get the content format for the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'mobas') {
            $mobassubmission = $this->get_mobas_submission($submissionid);
            if ($mobassubmission) {
                return $mobassubmission->onlineformat;
            }
        }


         return 0;
    }


     /**
      * Display mobas word count in the submission status table
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG;

        $mobassubmission = $this->get_mobas_submission($submission->id);
        // always show the view link
        $showviewlink = true;
        if ($mobassubmission) {
           $plagiarismlinks = '';
            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim(format_text($mobassubmission->mobas, $mobassubmission->onlineformat, array('context'=>$this->assignment->get_context()))),
                    'cmid' => $this->assignment->get_course_module()->id,
                    'course' => $this->assignment->get_course()->id,
                    'assignment' => $submission->assignment));
            }
            return $plagiarismlinks.get_string('numwords', 'assignsubmission_mobas', count_words($mobassubmission->mobas));
        }
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission
     *
     * @param stdClass $submission - For this is the submission data
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        global $DB;
        $files = array();
        $mobassubmission = $this->get_mobas_submission($submission->id);
        if ($mobassubmission) {
            $user = $DB->get_record("user", array("id"=>$submission->userid),'id,username,firstname,lastname', MUST_EXIST);

            if (!$this->assignment->is_blind_marking()) {
                $filename = str_replace('_', '', fullname($user)) . '_' .
                            $this->assignment->get_uniqueid_for_user($submission->userid) . '_' .
                            $this->get_name() . '_';
                $prefix = clean_filename($filename);
            } else {
                $filename = get_string('participant', 'assign') . '_' .
                            $this->assignment->get_uniqueid_for_user($submission->userid) . '_' .
                            $this->get_name() . '_';
                $prefix = clean_filename($filename);
            }

            $finaltext = str_replace('@@PLUGINFILE@@/', $prefix, $mobassubmission->mobas);
            $submissioncontent = "<html><body>". format_text($finaltext, $mobassubmission->onlineformat, array('context'=>$this->assignment->get_context())). "</body></html>";      //fetched from database

            $files[get_string('mobasfilename', 'assignsubmission_mobas')] = array($submissioncontent);

            $fs = get_file_storage();

            $fsfiles = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_mobas', ASSIGNSUBMISSION_ONLINETEXT_FILEAREA, $submission->id, "timemodified", false);

            foreach ($fsfiles as $file) {
                $files[$file->get_filename()] = $file;
            }
        }

        return $files;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    //todo: may want to change this to have link to open in popup? pics through out the view table
    public function view(stdClass $submission) {
        $result = '';

        $mobassubmission = $this->get_mobas_submission($submission->id);


        if ($mobassubmission) {

            // render for portfolio API
            $result .= $mobassubmission->mobas;

        }

        return $result;
    }

     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        return false;
    }



    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $mobassubmission = $this->get_mobas_submission($submission->id);
        $mobasloginfo = '';
        $text = format_text($mobassubmission->mobas,
                            $mobassubmission->onlineformat,
                            array('context'=>$this->assignment->get_context()));
        $mobasloginfo .= get_string('numwordsforlog', 'assignsubmission_mobas', count_words($text));

        return $mobasloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_mobas', array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $mobassubmission = $this->get_mobas_submission($submission->id);

        return empty($mobassubmission->mobas);
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
     //todo: may not need this
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_ONLINETEXT_FILEAREA=>$this->get_name());
    }

}


