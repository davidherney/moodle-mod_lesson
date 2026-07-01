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
 * Admin list page for lesson design templates.
 *
 * @package    mod_lesson
 * @copyright  2026 mebis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('lessontemplates');
require_capability('mod/lesson:managetemplates', context_system::instance());

$PAGE->set_url(new moodle_url('/mod/lesson/templates.php'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('design_managetemplates', 'lesson'));

echo $OUTPUT->single_button(
    new moodle_url('/mod/lesson/templateedit.php'),
    get_string('design_addtemplate', 'lesson'),
    'get'
);

$templates = \mod_lesson\local\template::get_records([], 'sortorder, name');
$table = new html_table();
$table->head = [
    get_string('design_name', 'lesson'),
    get_string('design_idnumber', 'lesson'),
    get_string('design_baseskin', 'lesson'),
    get_string('design_enabled', 'lesson'),
    get_string('actions'),
];
$table->data = [];
$lastindex = count($templates) - 1;
$index = 0;
$actionbase = '/mod/lesson/templateaction.php';
foreach ($templates as $tpl) {
    $id = $tpl->get('id');
    $editurl = new moodle_url('/mod/lesson/templateedit.php', ['id' => $id]);
    $actions = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));

    // Enable / disable toggle (default template stays enabled).
    if ($tpl->get('idnumber') !== 'default') {
        if ($tpl->get('enabled')) {
            $disableurl = new moodle_url($actionbase, ['id' => $id, 'action' => 'disable', 'sesskey' => sesskey()]);
            $actions .= ' ' . html_writer::link($disableurl, $OUTPUT->pix_icon('t/hide', get_string('disable')));
        } else {
            $enableurl = new moodle_url($actionbase, ['id' => $id, 'action' => 'enable', 'sesskey' => sesskey()]);
            $actions .= ' ' . html_writer::link($enableurl, $OUTPUT->pix_icon('t/show', get_string('enable')));
        }
    }

    // Duplicate.
    $dupurl = new moodle_url($actionbase, ['id' => $id, 'action' => 'duplicate', 'sesskey' => sesskey()]);
    $actions .= ' ' . html_writer::link($dupurl, $OUTPUT->pix_icon('t/copy', get_string('design_duplicate', 'lesson')));

    // Move up / down.
    if ($index > 0) {
        $upurl = new moodle_url($actionbase, ['id' => $id, 'action' => 'moveup', 'sesskey' => sesskey()]);
        $actions .= ' ' . html_writer::link($upurl, $OUTPUT->pix_icon('t/up', get_string('moveup')));
    }
    if ($index < $lastindex) {
        $downurl = new moodle_url($actionbase, ['id' => $id, 'action' => 'movedown', 'sesskey' => sesskey()]);
        $actions .= ' ' . html_writer::link($downurl, $OUTPUT->pix_icon('t/down', get_string('movedown')));
    }

    // Delete (not the built-in default).
    if ($tpl->get('idnumber') !== 'default') {
        $delurl = new moodle_url('/mod/lesson/templatedelete.php', ['id' => $id, 'sesskey' => sesskey()]);
        $actions .= ' ' . html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
    }

    $table->data[] = [
        format_string($tpl->get('name')),
        s($tpl->get('idnumber')),
        s($tpl->get('baseskin')),
        $tpl->get('enabled') ? get_string('yes') : get_string('no'),
        $actions,
    ];
    $index++;
}
echo html_writer::table($table);
echo $OUTPUT->footer();
