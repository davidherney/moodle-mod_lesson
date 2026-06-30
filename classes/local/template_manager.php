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

namespace mod_lesson\local;

/**
 * Resolves and lists lesson design templates.
 *
 * @package    mod_lesson
 * @copyright  2026 mebis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class template_manager {
    /**
     * Get the template for a lesson, falling back to 'default'.
     *
     * @param \stdClass $lesson Lesson record (needs ->design).
     * @return template
     */
    public static function get_for_lesson(\stdClass $lesson): template {
        $idnumber = $lesson->design ?? 'default';
        $tpl = template::get_record(['idnumber' => $idnumber, 'enabled' => 1]);
        if (!$tpl) {
            $tpl = template::get_record(['idnumber' => 'default']);
        }
        return $tpl;
    }

    /**
     * Enabled templates as idnumber => name for form menus.
     *
     * @return array<string, string>
     */
    public static function menu_options(): array {
        $options = [];
        foreach (template::get_records(['enabled' => 1], 'sortorder, name') as $tpl) {
            $options[$tpl->get('idnumber')] = format_string($tpl->get('name'));
        }
        return $options;
    }

    /**
     * Decoded config merged over the base skin defaults.
     *
     * @param template $tpl
     * @return array
     */
    public static function resolved_config(template $tpl): array {
        $decoded = json_decode((string) $tpl->get('config'), true) ?: [];
        return array_replace_recursive(config_schema::defaults($tpl->get('baseskin')), $decoded);
    }

    /**
     * Duplicate a template, generating a unique idnumber.
     *
     * @param template $tpl The template to duplicate.
     * @return template The newly created copy.
     */
    public static function duplicate(template $tpl): template {
        $base = $tpl->get('idnumber');
        $i = 1;
        do {
            $newidnumber = $base . '_copy' . ($i > 1 ? $i : '');
            $i++;
        } while (template::record_exists_select('idnumber = ?', [$newidnumber]));

        $copy = new template(0, (object) [
            'name' => $tpl->get('name') . ' (copy)',
            'idnumber' => $newidnumber,
            'baseskin' => $tpl->get('baseskin'),
            'config' => $tpl->get('config'),
            'enabled' => $tpl->get('enabled'),
            'sortorder' => $tpl->get('sortorder'),
        ]);
        $copy->create();
        return $copy;
    }

    /**
     * Move a template up or down in the sort order.
     *
     * @param template $tpl The template to move.
     * @param int $direction -1 to move up, +1 to move down.
     * @return void
     */
    public static function move(template $tpl, int $direction): void {
        $all = array_values(template::get_records([], 'sortorder, name'));
        $idx = null;
        foreach ($all as $i => $row) {
            if ((int) $row->get('id') === (int) $tpl->get('id')) {
                $idx = $i;
            }
        }
        if ($idx === null) {
            return;
        }
        $swap = $idx + $direction;
        if ($swap < 0 || $swap >= count($all)) {
            return;
        }
        $tmp = $all[$idx];
        $all[$idx] = $all[$swap];
        $all[$swap] = $tmp;
        foreach ($all as $pos => $row) {
            if ((int) $row->get('sortorder') !== $pos) {
                $row->set('sortorder', $pos);
                $row->update();
            }
        }
    }
}
