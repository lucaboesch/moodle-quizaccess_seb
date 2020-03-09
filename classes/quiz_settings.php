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
 * Entity model representing quiz settings for the seb plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_seb;

use CFPropertyList\CFArray;
use CFPropertyList\CFBoolean;
use CFPropertyList\CFDictionary;
use CFPropertyList\CFNumber;
use CFPropertyList\CFString;
use core\persistent;
use lang_string;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Entity model representing quiz settings for the seb plugin.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_settings extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'quizaccess_seb_quizsettings';

    /** @var property_list $plist The SEB config represented as a Property List object. */
    private $plist;

    /**
     * Create an instance of this class.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param \stdClass $record If set will be passed to {@link self::from_record()}.
     */
    public function __construct($id = 0, \stdClass $record = null) {
        parent::__construct($id, $record);
        // Get existing config.
        $config = $this->get('config');
        // Parse basic settings into a property list.
        $this->plist = new property_list($config);
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'quizid' => [
                'type' => PARAM_INT,
            ],
            'cmid' => [
                'type' => PARAM_INT,
            ],
            'templateid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'requiresafeexambrowser' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'showsebtaskbar' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'showwificontrol' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'showreloadbutton' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'showtime' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'showkeyboardlayout' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'allowuserquitseb' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'quitpassword' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'linkquitseb' => [
                'type' => PARAM_URL,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'userconfirmquit' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'enableaudiocontrol' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'muteonstartup' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'allowspellchecking' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'allowreloadinexam' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
            'activateurlfiltering' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'filterembeddedcontent' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'expressionsallowed' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'regexallowed' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'expressionsblocked' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'regexblocked' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'suppresssebdownloadlink' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'allowedbrowserexamkeys' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'configkey' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'config' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Validate the browser exam keys string.
     *
     * @param string $keys Newline separated browser exam keys.
     * @return true|lang_string If there is an error, an error string is returned.
     */
    protected function validate_allowedbrowserexamkeys($keys) {
        $keys = $this->split_keys($keys);
        foreach ($keys as $i => $key) {
            if (!preg_match('~^[a-f0-9]{64}$~', $key)) {
                return new lang_string('allowedbrowserkeyssyntax', 'quizaccess_seb');
            }
        }
        if (count($keys) != count(array_unique($keys))) {
            return new lang_string('allowedbrowserkeysdistinct', 'quizaccess_seb');
        }
        return true;
    }

    /**
     * Get the browser exam keys as a pre-split array instead of just as a string.
     *
     * @return array
     */
    protected function get_allowedbrowserexamkeys() : array {
        $keysstring = $this->raw_get('allowedbrowserexamkeys');
        $keysstring = empty($keysstring) ? '' : $keysstring;
        return $this->split_keys($keysstring);
    }

    /**
     * Hook to execute before an update.
     *
     * Please note that at this stage the data has already been validated and therefore
     * any new data being set will not be validated before it is sent to the database.
     */
    protected function before_update() {
        $this->before_save();
    }

    /**
     * Hook to execute before a create.
     *
     * Please note that at this stage the data has already been validated and therefore
     * any new data being set will not be validated before it is sent to the database.
     */
    protected function before_create() {
        $this->before_save();
    }

    /**
     * As there is no hook for before both create and update, this function is called by both hooks.
     */
    private function before_save() {
        // Recalculate config and config key.
        $this->compute_config();
        $this->compute_config_key();
    }

    /**
     * Generate the config key from the config string.
     */
    private function compute_config_key() {
        $config = $this->get('config');
        $configkey = config_key::generate($config)->get_hash();
        $this->set('configkey', $configkey);
    }

    /**
     * Create or update the config string based on the current quiz settings.
     */
    private function compute_config() {
        switch ($this->get('requiresafeexambrowser')) {
            case settings_provider::USE_SEB_NO:
                $this->process_seb_config_no();
                break;

            case settings_provider::USE_SEB_CONFIG_MANUALLY:
                $this->process_seb_config_manually();
                break;

            case settings_provider::USE_SEB_TEMPLATE:
                $this->process_seb_template();
                break;

            case settings_provider::USE_SEB_UPLOAD_CONFIG:
                $this->process_seb_upload_config();
                break;

            default: // Also settings_provider::USE_SEB_CLIENT_CONFIG.
                $this->process_seb_client_config();
        }

        // Export and save the config, ready for DB.
        $this->set('config', $this->plist->to_xml());
    }

    /**
     * Case for USE_SEB_NO.
     */
    private function process_seb_config_no() {
        $this->set('templateid', 0);
    }

    /**
     * Case for USE_SEB_CONFIG_MANUALLY. This creates a plist and applies all settings from the posted form, along with
     * some defaults.
     */
    private function process_seb_config_manually() {
        $this->set('templateid', 0);

        // If at any point a configuration file has been uploaded and parsed, clear the settings.
        $this->plist = new property_list();

        $this->process_bool_settings();
        $this->process_quit_password_settings();
        $this->process_quit_url_from_settings();
        $this->process_url_filters();
        $this->process_required_enforced_settings();

        // One of the requirements for USE_SEB_CONFIG_MANUALLY is setting examSessionClearCookiesOnStart to false.
        $this->plist->set_or_update_value('examSessionClearCookiesOnStart', new CFBoolean(false));
    }

    /**
     * Case for USE_SEB_TEMPLATE. This creates a plist from the template uploaded, then applies the quit password
     * setting and some defaults.
     */
    private function process_seb_template() {
        $template = template::get_record(['id' => $this->get('templateid')]);
        $this->plist = new property_list($template->get('content'));

        $this->process_quit_password_settings();
        $this->process_quit_url_from_template_or_config();
        $this->process_required_enforced_settings();
    }

    /**
     * Case for USE_SEB_UPLOAD_CONFIG. This creates a plist from an uploaded configuration file, then applies the quiz
     * password settings and some defaults.
     */
    private function process_seb_upload_config() {
        $this->set('templateid', 0);

        $file = settings_provider::get_module_context_sebconfig_file($this->get('cmid'));

        // If there was no file, create an empty plist so the rest of this wont explode.
        if (empty($file)) {
            throw new moodle_exception('noconfigfilefound', 'quizaccess_seb', '', $this->get('cmid'));
        } else {
            $this->plist = new property_list($file->get_content());
        }

        $this->process_quit_password_settings();
        $this->process_quit_url_from_template_or_config();
        $this->process_required_enforced_settings();
    }

    /**
     * Case for USE_SEB_CLIENT_CONFIG. This creates an empty plist to remove the config stored.
     */
    private function process_seb_client_config() {
        $this->set('templateid', 0);

        // Just setup an empty plist.
        $this->plist = new property_list();
    }

    /**
     * Sets or updates some sensible default settings, these are the items 'startURL' and 'sendBrowserExamKey'.
     */
    private function process_required_enforced_settings() {
        global $CFG;

        $quizurl = new moodle_url($CFG->wwwroot . "/mod/quiz/view.php", ['id' => $this->get('cmid')]);
        $this->plist->set_or_update_value('startURL', new CFString($quizurl->out(true)));
        $this->plist->set_or_update_value('sendBrowserExamKey', new CFBoolean(true));
    }

    /**
     * Use the boolean map to add Moodle boolean setting to config PList.
     */
    private function process_bool_settings() {
        $settings = $this->to_record();
        $map = $this->get_bool_seb_setting_map();
        foreach ($settings as $setting => $value) {
            if (isset($map[$setting])) {
                $enabled = $value == 1 ? true : false;
                $this->plist->add_element_to_root($map[$setting], new CFBoolean($enabled));
            }
        }
    }

    /**
     * Turn hashed quit password and quit link into PList strings and add to config PList.
     */
    private function process_quit_password_settings() {
        $settings = $this->to_record();
        if (!empty($settings->quitpassword) && is_string($settings->quitpassword)) {
            // Hash quit password.
            $hashedpassword = hash('SHA256', $settings->quitpassword);
            $this->plist->add_element_to_root('hashedQuitPassword', new CFString($hashedpassword));
        }
    }

    /**
     * Sets the quitURL if found in the quiz_settings.
     */
    private function process_quit_url_from_settings() {
        $settings = $this->to_record();
        if (!empty($settings->linkquitseb) && is_string($settings->linkquitseb)) {
            $this->plist->set_or_update_value('quitURL', new CFString($settings->linkquitseb));
        }
    }

    /**
     * Sets the quiz_setting's linkquitseb if a quitURL value was found in a template or uploaded config.
     */
    private function process_quit_url_from_template_or_config() {
        // Does the plist (template or config file) have an existing quitURL?
        $quiturl = $this->plist->get_element_value('quitURL');
        if (!empty($quiturl)) {
            $this->set('linkquitseb', $quiturl);
        }
    }

    /**
     * Turn return separated strings for URL filters into a PList array and add to config PList.
     */
    private function process_url_filters() {
        $settings = $this->to_record();
        // Create rules to each expression provided and add to config.
        $urlfilterrules = [];
        // Get all rules separated by newlines and remove empty rules.
        $expallowed = array_filter(explode(PHP_EOL, $settings->expressionsallowed));
        $expblocked = array_filter(explode(PHP_EOL, $settings->expressionsblocked));
        $regallowed = array_filter(explode(PHP_EOL, $settings->regexallowed));
        $regblocked = array_filter(explode(PHP_EOL, $settings->regexblocked));
        foreach ($expallowed as $rulestring) {
            $urlfilterrules[] = $this->create_filter_rule($rulestring, true, false);
        }
        foreach ($expblocked as $rulestring) {
            $urlfilterrules[] = $this->create_filter_rule($rulestring, false, false);
        }
        foreach ($regallowed as $rulestring) {
            $urlfilterrules[] = $this->create_filter_rule($rulestring, true, true);
        }
        foreach ($regblocked as $rulestring) {
            $urlfilterrules[] = $this->create_filter_rule($rulestring, false, true);
        }
        $this->plist->add_element_to_root('URLFilterRules', new CFArray($urlfilterrules));
    }

    /**
     * Create a CFDictionary represeting a URL filter rule.
     *
     * @param string $rulestring The expression to filter with.
     * @param bool $allowed Allowed or blocked.
     * @param bool $isregex Regex or simple.
     * @return CFDictionary A PList dictionary.
     */
    private function create_filter_rule(string $rulestring, bool $allowed, bool $isregex) : CFDictionary {
        $action = $allowed ? 1 : 0;
        return new CFDictionary([
                    'action' => new CFNumber($action),
                    'active' => new CFBoolean(true),
                    'expression' => new CFString(trim($rulestring)),
                    'regex' => new CFBoolean($isregex),
                    ]);
    }

    /**
     * Map the settings that are booleans to the Safe Exam Browser config keys.
     *
     * @return array Moodle setting as key, SEB setting as value.
     */
    private function get_bool_seb_setting_map() : array {
        return [
            'activateurlfiltering' => 'URLFilterEnable',
            'allowspellchecking' => 'allowSpellCheck',
            'allowreloadinexam' => 'browserWindowAllowReload',
            'allowuserquitseb' => 'allowQuit',
            'enableaudiocontrol' => 'audioControlEnabled',
            'filterembeddedcontent' => 'URLFilterEnableContentFilter',
            'muteonstartup' => 'audioMute',
            'showkeyboardlayout' => 'showInputLanguage',
            'showreloadbutton' => 'showReloadButton',
            'showsebtaskbar' => 'showTaskBar',
            'showtime' => 'showTime',
            'showwificontrol' => 'allowWlan',
            'userconfirmquit' => 'quitURLConfirm',
        ];
    }

    /**
     * This helper method takes list of browser exam keys in a string and splits it into an array of separate keys.
     *
     * @param string|null $keys the allowed keys.
     * @return array of string, the separate keys.
     */
    private function split_keys($keys) : array {
        $keys = preg_split('~[ \t\n\r,;]+~', $keys, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($keys as $i => $key) {
            $keys[$i] = strtolower($key);
        }
        return $keys;
    }
}
