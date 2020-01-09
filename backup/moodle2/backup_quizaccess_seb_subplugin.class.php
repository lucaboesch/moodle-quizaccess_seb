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
 * Backup instructions for the seb (Safe Exam Browser) quiz access subplugin.
 *
 * @package    quizaccess_seb
 * @category   backup
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/backup_mod_quiz_access_subplugin.class.php');

class backup_quizaccess_seb_subplugin extends backup_mod_quiz_access_subplugin {

    /**
     * Stores the data related to the Safe Exam Browser quiz settings and management for a particular quiz.
     *
     * @return backup_subplugin_element
     *
     * @throws DOMException
     * @throws \CFPropertyList\IOException
     * @throws \CFPropertyList\PListException
     * @throws base_element_struct_exception
     * @throws coding_exception
     */
    protected function define_quiz_subplugin_structure() {
        parent::define_quiz_subplugin_structure();
        $quizid = backup::VAR_ACTIVITYID;

        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());

        // TODO: Define the templates table structure once implemented.

        // Get quiz settings keys to save.
        $settings = new \quizaccess_seb\quiz_settings();
        $blanksettingsarray = (array) $settings->to_record();
        unset($blanksettingsarray['id']); // We don't need to save reference to settings record in current instance.
        // We don't need to save the data about who last modified the settings as they will be overwritten on restore. Also
        // means we don't have to think about user data for the backup.
        unset($blanksettingsarray['usermodified']);
        unset($blanksettingsarray['timemodified']);

        $settingskeys = array_keys($blanksettingsarray);

        // Save the settings.
        $subpluginquizsettings = new backup_nested_element('quizaccess_seb_quizsettings', null,
                $settingskeys);

        // Connect XML elements into the tree.
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginquizsettings);

        // Set source to populate the settings data by referencing the ID of quiz being backed up.
        $subpluginquizsettings->set_source_table('quizaccess_seb_quizsettings',
            ['quizid' => $quizid]);

        return $subplugin;
    }
}