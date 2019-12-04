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
 * PHPUnit tests for plugin file manager.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\file_manager;

defined('MOODLE_INTERNAL') || die();

class file_manager_testcase extends advanced_testcase {

    /**
     * Called before every test.
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that the user draft file is returned.
     */
    public function test_get_form_file() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Search is based on current user.
        $fs = get_file_storage();
        $filemanager = new file_manager();

        $context = context_user::instance($user->id);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Hello World!');

        $file = $filemanager->get_form_file_by_itemid(999);
        $this->assertEquals($createdfile, $file);
    }

    /**
     * Test that if the user has multiple draft files, that only a single matching file is returned.
     */
    public function test_get_form_file_from_form_with_multiple_files() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Search is based on current user.
        $fs = get_file_storage();
        $filemanager = new file_manager();

        $context = context_user::instance($user->id);

        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Hello World!');

        $createdfile2 = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 888,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Hello World!'); // Same content and file name.

        $createdfile3 = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 888,
            'filepath' => '/',
            'filename' => 'test2.txt',
        ], 'Hello Again!'); // Different content and file name.

        $file = $filemanager->get_form_file_by_itemid(999);
        $this->assertEquals($createdfile, $file);
    }

    /**
     * Test that attempting to retrieve a file in user draft area that doesn't exist will not return a file.
     */
    public function test_get_form_file_if_no_file() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Search is based on current user.
        $filemanager = new file_manager();
        $file = $filemanager->get_form_file_by_itemid(999);
        $this->assertNull($file);
    }

    /**
     * Test that module file is retrieved successfully.
     */
    public function test_get_module_file() {
        $fs = get_file_storage();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $context = context_module::instance($quiz->cmid);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_quiz',
            'filearea' => 'quizaccess_seb_quizsettings',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Hello World!');

        $filemanager = new file_manager();
        $file = $filemanager->get_module_file_by_itemid(999, $quiz->cmid);
        $this->assertEquals($createdfile, $file);
    }

    /**
     * Test that trying to get a module file that doesn't exist will not return a file.
     */
    public function test_get_module_file_if_no_file() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $filemanager = new file_manager();
        $file = $filemanager->get_module_file_by_itemid(999, $quiz->cmid);
        $this->assertNull($file);
    }

    /**
     * Test that a file is not returned if module file exists but different itemid.
     */
    public function test_get_module_file_if_itemid_incorrect() {
        $fs = get_file_storage();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $context = context_module::instance($quiz->cmid);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_quiz',
            'filearea' => 'quizaccess_seb_quizsettings',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Hello World!');

        $filemanager = new file_manager();
        $file = $filemanager->get_module_file_by_itemid(888, $quiz->cmid);
        $this->assertNull($file);
    }

    /**
     * Test that trying to get a module file with and incorrect coursemodule id won't throw an error and just fail returning
     * a file.
     */
    public function test_get_module_file_if_incorrect_cmid() {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $filemanager = new file_manager();
        $file = $filemanager->get_module_file_by_itemid(999, 999);
        $this->assertNull($file);
    }

    /**
     * Test that a file can be created and deleted successfully in the module context.
     */
    public function test_delete_module_file() {
        $fs = get_file_storage();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        $context = context_module::instance($quiz->cmid);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'mod_quiz',
            'filearea' => 'quizaccess_seb_quizsettings',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Hello World!');

        // Check that file was created.
        $filemanager = new file_manager();
        $file = $filemanager->get_module_file_by_itemid(999, $quiz->cmid);
        $this->assertEquals($createdfile, $file);

        // Delete the file and try and retrieve it.
        $filemanager->delete_module_file_by_itemid(999, $quiz->cmid);
        $file = $filemanager->get_module_file_by_itemid(999, $quiz->cmid);
        $this->assertNull($file);
    }

    /**
     * Test that calling delete on a non-existent file won't throw any errors.
     */
    public function test_delete_module_file_if_not_exists() {
        $fs = get_file_storage();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);

        // Delete the non-existent file and try and retrieve it.
        $filemanager = new file_manager();
        $filemanager->delete_module_file_by_itemid(999, $quiz->cmid);
        $file = $filemanager->get_module_file_by_itemid(999, $quiz->cmid);
        $this->assertNull($file);
    }

    /**
     * Test that an unencrypted seb file is validated as a valid seb config file.
     */
    public function test_is_valid_seb_config_unencrypted() {
        $unencryptedcontents = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Draft file is based on current user.
        $fs = get_file_storage();

        $context = context_user::instance($user->id);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.seb',
        ], $unencryptedcontents);

        $filemanager = new file_manager();
        $result = $filemanager->is_file_valid_seb_config($createdfile);
        $this->assertTrue($result);
    }

    /**
     * Test that an encrypted seb file is validated as a valid seb config file.
     */
    public function test_is_valid_seb_config_encrypted() {
        $encryptedcontents = file_get_contents(__DIR__ . '/sample_data/unencrypted.seb');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Draft file is based on current user.
        $fs = get_file_storage();

        $context = context_user::instance($user->id);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.seb',
        ], $encryptedcontents);

        $filemanager = new file_manager();
        $result = $filemanager->is_file_valid_seb_config($createdfile, 'test');
        $this->assertTrue($result);
    }

    /**
     * Test files that are not seb configs are not validated as seb config files.
     *
     * @dataProvider invalid_seb_contents_provider
     */
    public function test_is_invalid_seb_config_unencrypted($content) {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Draft file is based on current user.
        $fs = get_file_storage();

        $context = context_user::instance($user->id);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.seb',
        ], $content);

        $filemanager = new file_manager();
        $result = $filemanager->is_file_valid_seb_config($createdfile);
        $this->assertFalse($result);
    }

    /**
     * Test that user draft file is saved successfully to module area.
     */
    public function test_file_saved_in_module() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Draft file is based on current user.
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $fs = get_file_storage();

        // Create file in user draft area to simulate uploading file to form.
        $context = context_user::instance($user->id);
        $createdfile = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'test.seb',
        ], 'Hello World!');

        // Save file from draft area to module area.
        $filemanager = new file_manager();
        $filemanager->save_file_in_module($createdfile, $quiz->cmid);

        // Test that it was saved correctly in new area.
        $savedfile = $filemanager->get_module_file_by_itemid(999, $quiz->cmid);
        $this->assertEquals('test.seb', $savedfile->get_filename());
        $this->assertEquals('/', $savedfile->get_filepath());
        $this->assertEquals('Hello World!', $savedfile->get_content());
        $this->assertEquals('mod_quiz', $savedfile->get_component());
        $this->assertEquals('quizaccess_seb_quizsettings', $savedfile->get_filearea());
        $context = context_module::instance($quiz->cmid);
        $this->assertEquals($context->id, $savedfile->get_contextid());
    }

    /**
     * Data provider for invalid seb content.
     *
     * @return array
     */
    public function invalid_seb_contents_provider() : array {
        return [
            'Plain string' => ['Not valid seb contents'],
            'XML but not PLIST' => ["<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root>\n</root>"],
            'Empty' => [""],
            'Plain string multiline' => ["Not\nValis\nSeb\nConfig"],
        ];
    }
}


