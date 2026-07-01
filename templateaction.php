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
 * Row actions (enable, disable, duplicate, move) for lesson design templates.
 *
 * @package    mod_lesson
 * @copyright  2026 mebis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('lessontemplates');
require_capability('mod/lesson:managetemplates', context_system::instance());

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
require_sesskey();

$template = new \mod_lesson\local\template($id);
$returnurl = new moodle_url('/mod/lesson/templates.php');

switch ($action) {
    case 'enable':
        $template->set('enabled', 1);
        $template->update();
        break;
    case 'disable':
        // The built-in default must remain selectable.
        if ($template->get('idnumber') !== 'default') {
            $template->set('enabled', 0);
            $template->update();
        }
        break;
    case 'duplicate':
        \mod_lesson\local\template_manager::duplicate($template);
        break;
    case 'moveup':
        \mod_lesson\local\template_manager::move($template, -1);
        break;
    case 'movedown':
        \mod_lesson\local\template_manager::move($template, 1);
        break;
}

redirect($returnurl);
