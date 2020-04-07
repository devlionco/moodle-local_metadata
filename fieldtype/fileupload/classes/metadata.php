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
 * Strings for component 'profilefield_fileupload', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   profilefield_fileupload
 * @copyright  2008 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace metadatafieldtype_fileupload;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Class local_metadata_field_fileupload
 *
 * @copyright  2008 onwards Shane Elliot {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata extends \local_metadata\fieldtype\metadata {

    /** @var array $options */
    public $options;

    public $draftitem_id;

    /**
     * Constructor method.
     * Pulls out the options for the fileupload from the database and sets the
     * the corresponding key for the data if it exists
     *
     * @param int $fieldid
     * @param int $instanceid
     */
    public function __construct($fieldid = 0, $instanceid = 0) {
        global $DB;
        // First call parent constructor.
        parent::__construct($fieldid, $instanceid);

        $this->draftitem_id = $this->data;

        // Set the name for display; will need to be a language string.
        $this->name = 'File Upload';
    }

    /**
     * Add elements for editing the profile field value.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
        global $CFG;

        // SG - prepare draft area for fileupload
        $context = $this->get_proper_context($this->field->contextlevel, $this->instanceid);
        $fileuploaddraftid = file_get_submitted_draft_itemid($this->inputname);
        file_prepare_draft_area($fileuploaddraftid, $context->id, 'local_metadata', 'image', $this->data);

        $mform->addElement('filemanager', $this->inputname, format_string($this->field->name), null, // format_string($this->field->name)
            array('subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'areamaxbytes' => FILE_AREA_MAX_BYTES_UNLIMITED, 'maxfiles' => 1,
                'accepted_types' => '*', 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL));

        $mform->setDefault($this->inputname, $fileuploaddraftid);
    }

    /**
     * Display the data for this field
     *
     * @return string HTML.
     */
    public function display_data() {
        global $DB;

        $context = $this->get_proper_context($this->field->contextlevel, $this->instanceid);

        $sql = "
            SELECT * 
            FROM {files}
            WHERE filename != '.' AND contextid = ?
        
        ";

        $file = $DB->get_record_sql($sql, array($context->id));

        if(!empty($file)){
            $fileurl = \moodle_url::make_pluginfile_url($context->id, $file->component, $file->filearea, $this->data, $file->filepath, $file->filename);
            return \html_writer::img($fileurl, '');
        }else{
            return '';
        }
    }

    /**
     * Return file url.
     *
     * @return string HTML.
     */
    public function get_file_url() {
        global $DB;

        $context = $this->get_proper_context($this->field->contextlevel, $this->instanceid);

        if(empty($this->data)) return false;

        $sql = "
            SELECT * 
            FROM {files}
            WHERE filename != '.' AND contextid = ? AND itemid = ?
        
        ";

        $file = $DB->get_record_sql($sql, array($context->id, $this->data));

        if(!empty($file)){
            $fileurl = \moodle_url::make_pluginfile_url($context->id, $file->component, $file->filearea, $this->data, $file->filepath, $file->filename);
            return $fileurl;
        }else{
            return false;
        }
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method.
     * @param moodleform $mform Moodle form instance
     */
    public function edit_field_set_default($mform) {

        // TODO: Remove this?

        // Set form data
        //$mform->setDefaults(array($this->inputname => $this->draftitem_id));
        //$mform->setDefault($this->inputname, $this->data_url);
    }

    public function edit_save_data($entry) {
        global $DB, $CFG;

        if (!isset($entry->{$this->inputname})) {
            // Field not present in form, probably locked and invisible - skip it.
            return;
        }

        if (empty($entry->id)) {
            $entry = new \stdClass;
            $entry->id = null;
        }

        $context = $this->get_proper_context($entry->contextlevel, $entry->id);

        $this->draftitem_id = file_get_submitted_draft_itemid($this->inputname);

        file_prepare_draft_area($this->draftitem_id, $context->id, 'local_metadata', 'image', 0);
        file_save_draft_area_files($this->draftitem_id, $context->id, 'local_metadata', 'image', $this->draftitem_id);

        $data = new \stdClass();
        $data->instanceid = $entry->id;
        $data->fieldid = $this->field->id;
        $data->data = $this->draftitem_id;

        if ($dataid = $DB->get_field('local_metadata', 'id', ['instanceid' => $data->instanceid, 'fieldid' => $data->fieldid])) {
            $data->id = $dataid;
            $DB->update_record('local_metadata', $data);
        } else {
            $DB->insert_record('local_metadata', $data);
        }
    }


    /**
     * When passing the instance object to the form class for the edit page
     * we should load the key for the saved data
     *
     * Overwrites the base class method.
     *
     * @param stdClass $instance Instance object.
     */
    public function edit_load_instance_data($instance) {
        $context = $this->get_proper_context($this->field->contextlevel, $instance->id);
        $draftitemid = file_get_submitted_draft_itemid($this->inputname);
        file_prepare_draft_area($draftitemid, $context->id, 'local_metadata', 'image', $this->data, array(), null);
        $instance->{$this->inputname} = $draftitemid;
    }

    /**
     * HardFreeze the field if locked.
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() && !has_capability('moodle/user:update', context_system::instance())) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, format_string($this->datakey));
        }
    }

    /**
     * Convert external data (csv file) from value to key for processing later by edit_save_data_preprocess
     *
     * @param string $value one of the values in fileupload options.
     * @return int options key for the fileupload
     */
    public function convert_external_data($value) {
        if (isset($this->options[$value])) {
            $retval = $value;
        } else {
            $retval = array_search($value, $this->options);
        }

        // If value is not found in options then return null, so that it can be handled
        // later by edit_save_data_preprocess.
        if ($retval === false) {
            $retval = null;
        }
        return $retval;
    }

    /**
     * Return the field type and null properties.
     * This will be used for validating the data submitted by a user.
     *
     * @return array the param type and null property
     * @since Moodle 3.2
     */
    public function get_field_properties() {
        return [PARAM_TEXT, NULL_NOT_ALLOWED];
    }

    private function get_proper_context($contextlevel, $instanceid) {
        switch ($contextlevel) {
            case CONTEXT_MODULE:
                $context = \context_module::instance($instanceid);
                break;
            case CONTEXT_COURSE:
                $context = \context_course::instance($instanceid);
                break;
            case CONTEXT_COURSECAT:
                $context = \context_coursecat::instance($instanceid);
                break;
            default:
                $context = null;
        }
        return $context;
    }
}


