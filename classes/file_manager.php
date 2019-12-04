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
 * Class to manage seb files, used in part to abstract some of the Moodle file manager logic.
 *
 * @package    quizaccess_safeexambrowser
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_safeexambrowser;

use stored_file;

defined('MOODLE_INTERNAL') || die();

class file_manager {

    /** Component the file will be stored in.  */
    const COMPONENT = 'mod_quiz';

    /** File area in the module to store files.  */
    const FILE_AREA = 'quizaccess_seb_quizsettings';

    /**
     * Get a stored file object representing a draft file saved from a form.
     *
     * @param string $itemid
     * @return stored_file|null
     * @throws \coding_exception
     */
    public function get_form_file_by_itemid(string $itemid) {
        global $USER; // Uploaded files initially have a user context.
        $file  = null;

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        $areafiles = $fs->get_area_files($context->id, 'user', 'draft', $itemid, 'id DESC', false);
        if (count($areafiles) === 1) {
            $file = reset($areafiles);
        }
        return $file;
    }

    /**
     * Get a saved file from the safe exam browser file area, for a specific quiz.
     *
     * @param string $itemid
     * @param string $cmid
     * @return stored_file|null
     * @throws \coding_exception
     */
    public function get_module_file_by_itemid(string $itemid, string $cmid) {
        $file  = null;
        $fs = get_file_storage();

        if (get_coursemodule_from_id('quiz', $cmid)) { // Validate course module id.
            $context = \context_module::instance($cmid);
            $areafiles = $fs->get_area_files($context->id, self::COMPONENT, self::FILE_AREA, $itemid, 'id DESC', false);
            if (count($areafiles) === 1) {
                $file = reset($areafiles);
            }
        }
        return $file;
    }

    /**
     * Delete a saved file from the safe exam browser file area, for a specific quiz.
     *
     * @param string $itemid
     * @param string $cmid
     * @return bool
     * @throws \coding_exception
     */
    public function delete_module_file_by_itemid(string $itemid, string $cmid) : bool {
        $file = $this->get_module_file_by_itemid($itemid, $cmid);
        if (!empty($file)) {
            return $file->delete();
        }
        return false;
    }

    /**
     * Check if a file is a valid seb config file. The file may be unencrypted xml or encryped binary. If encrypted,
     * the encryption password is required to decrypt it.
     *
     * @param stored_file $uploadedconfig
     * @param string $password
     * @return bool
     */
    public function is_file_valid_seb_config(stored_file $uploadedconfig, string $password = '') : bool {
        $content = $uploadedconfig->get_content();
        // Attempt to decrypt file contents.
        $content = seb_cipher::decrypt($content, $password);
        // Validate file contents, including checking it is an seb config xml file.
        return $this->validate_seb_contents($content);
    }

    /**
     * Create a new file with the same file name and contents of input, in the safe exam browser file area.
     *
     * @param stored_file $file
     * @param string $cmid
     * @throws \coding_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function save_file_in_module(stored_file $file, string $cmid) {
        $fs = get_file_storage();
        if (get_coursemodule_from_id('quiz', $cmid)) { // Validate course module id.
            $context = \context_module::instance($cmid);
            $newfile = $fs->create_file_from_storedfile([
                'contextid' => $context->id,
                'component' => self::COMPONENT,
                'filearea' => self::FILE_AREA,
            ], $file);
        }
    }

    /**
     * Check that it is a valid seb config file.
     *
     * @param string $content
     * @return bool
     */
    private function validate_seb_contents(string $content) : bool {
        $valid = true;
        $lines = explode(PHP_EOL, $content);

        // Make sure file length is at least two lines, otherwise exit early.
        if (count($lines) < 2) {
            return false;
        }

        // Check first line declares file as xml.
        if (strpos($lines[0], '<?xml') !== 0) {
            $valid = false;
        }

        // Check second line declares file as plist.
        if (strpos($lines[1], '<!DOCTYPE plist') !== 0) {
            $valid = false;
        }

        return $valid;
    }
}
