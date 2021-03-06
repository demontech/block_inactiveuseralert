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

use block_inactiveuseralert\alert;
use block_inactiveuseralert\helper;

/**
 * Inactive user alert block
 *
 * @package    block_inactiveuseralert
 * @copyright  2015 Blackboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_inactiveuseralert extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_inactiveuseralert');
    }

    /**
     * Return contents of the block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config('block_inactiveuseralert');

        $this->content = new stdClass();
        $this->content->footer = '';

        if (!has_capability('block/inactiveuseralert:addinstance', $this->page->context)) {
            return $this->content;
        }

        $renderer = $this->page->get_renderer('block_inactiveuseralert');

        $enabledalerts = helper::get_alerts($this->page->course->id);
        $modinfo = null;
        if (!empty($enabledalerts)) {
            $modinfo = get_fast_modinfo($this->page->course);
        }
        $alerts = [];
        foreach ($enabledalerts as $alert) {
            $trackdata = helper::load_alerts_with_track($alert->id);
            $name = get_string('typelogin', 'block_inactiveuseralert');
            if (!empty($alert->cmid) && $alert->alerttype == 'activity') {
                if (!$DB->record_exists('course_modules', array('id' => $alert->cmid, 'course' => $alert->course))) {
                    $name = get_string('invalidalert', 'block_inactiveuseralert');
                } else {
                    $name = $modinfo->cms[$alert->cmid]->name;
                }
            }

            if (empty($alert->cmid) && $alert->alerttype == 'activity') {
                $name = get_string('invalidalert', 'block_inactiveuseralert');
            }

            $alerts[] = new \block_inactiveuseralert\output\alert\renderable($alert->enabled, $trackdata, $alert->course, $name);
        }

        $this->content->text = $renderer->alerts($alerts);

        return $this->content;
    }

    /**
     * Allow the block to have a configuration page.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function instance_delete() {
        global $DB;
        $coursecontext = $this->context->get_course_context();
        $alerts = $DB->get_records_list('block_inactiveuseralert', 'course', [$coursecontext->instanceid], '', 'id');
        if (!empty($alerts)) {
            $DB->delete_records_list('block_inactiveuseralert_trac', "alertid", array_keys($alerts));
        }
        $DB->delete_records('block_inactiveuseralert', ['course' => $coursecontext->instanceid]);
    }

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => false, 'course' => true);
    }

}
