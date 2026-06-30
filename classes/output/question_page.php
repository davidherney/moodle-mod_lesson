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

namespace mod_lesson\output;

use mod_lesson\local\template_manager;

/**
 * Renderable for a lesson question page under a non-default design skin.
 *
 * @package    mod_lesson
 * @copyright  2026 mebis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_page implements \renderable, \templatable {
    /** @var \lesson The lesson. */
    protected $lesson;

    /** @var \lesson_page The question page. */
    protected $page;

    /** @var object|null The current attempt, if any. */
    protected $attempt;

    /** @var int The course module id. */
    protected $cmid;

    /**
     * Constructor.
     *
     * @param \lesson $lesson The lesson.
     * @param \lesson_page $page The question page.
     * @param object|null $attempt The current attempt, if any.
     * @param int $cmid The course module id.
     */
    public function __construct(\lesson $lesson, \lesson_page $page, $attempt, int $cmid) {
        $this->lesson = $lesson;
        $this->page = $page;
        $this->attempt = $attempt;
        $this->cmid = $cmid;
    }

    /**
     * Export data for the mustache template.
     *
     * @param \renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        global $CFG, $USER;

        $tpl = template_manager::get_for_lesson($this->lesson->properties());
        $config = template_manager::resolved_config($tpl);
        $lessonprops = $this->lesson->properties();
        $pageprops = $this->page->properties();

        $hasattempt = isset($USER->modattempts[$this->lesson->id])
            && !empty($USER->modattempts[$this->lesson->id]);

        $backurl = (new \moodle_url('/mod/lesson/view.php', ['id' => $this->cmid]))->out(false);
        $retryparams = ['id' => $this->cmid, 'pageid' => $pageprops->id];
        $retryurl = (new \moodle_url('/mod/lesson/view.php', $retryparams))->out(false);

        $data = [
            'formaction' => $CFG->wwwroot . '/mod/lesson/continue.php',
            'sesskey' => sesskey(),
            'cmid' => $this->cmid,
            'pageid' => $pageprops->id,
            'contents' => $this->page->get_contents(),
            'answercolumns' => (int) ($config['answercolumns'] ?? 2),
            'showprogress' => !empty($config['showprogress']),
            'progresshtml' => '',
            'nav' => [
                'showback' => !empty($config['nav']['showback']),
                'showretry' => !empty($config['nav']['showretry']) && !empty($lessonprops->retake),
                'shownext' => !empty($config['nav']['shownext']),
                'backurl' => $backurl,
                'retryurl' => $retryurl,
                'backlabel' => get_string('design_back', 'lesson'),
                'retrylabel' => get_string('design_retry', 'lesson'),
            ],
            'palette' => $config['palette'] ?? [],
            'submitlabel' => get_string('submit', 'lesson'),
            'choosearialabel' => get_string('design_chooseanswer', 'lesson'),
            // Mode flags (mutually exclusive); default all false.
            'ischoice' => false,
            'istext' => false,
            'ismatching' => false,
            'iscontent' => false,
            'multiple' => false,
        ];

        $idstring = $this->page->get_idstring();
        switch ($idstring) {
            case 'multichoice':
                $multiple = !empty($pageprops->qoption);
                $formname = $multiple
                    ? 'lesson_display_answer_form_multichoice_multianswer'
                    : 'lesson_display_answer_form_multichoice_singleanswer';
                $data['ischoice'] = true;
                $data['multiple'] = $multiple;
                $data['answers'] = $this->build_choices($this->page->get_used_answers(), $multiple, $hasattempt);
                break;
            case 'truefalse':
                $formname = 'lesson_display_answer_form_truefalse';
                $data['ischoice'] = true;
                $answers = $this->page->get_answers();
                foreach ($answers as $key => $answer) {
                    $answers[$key] = \lesson_page::rewrite_answers_urls($answer);
                }
                $data['answers'] = $this->build_choices($answers, false, $hasattempt);
                break;
            case 'shortanswer':
                $formname = 'lesson_display_answer_form_shortanswer';
                $data['istext'] = true;
                $data += $this->build_textinput('text', $hasattempt);
                break;
            case 'numerical':
                $formname = 'lesson_display_answer_form_numerical';
                $data['istext'] = true;
                $data += $this->build_textinput('number', $hasattempt);
                break;
            case 'matching':
                $formname = 'lesson_display_answer_form_matching';
                $data['ismatching'] = true;
                $data['matchrows'] = $this->build_matching();
                break;
            case 'branchtable':
                // Content page: navigation buttons, no answer form marker.
                $formname = '';
                $data['iscontent'] = true;
                $data['title'] = format_string($pageprops->title);
                $data['buttons'] = $this->build_content_buttons();
                break;
            default:
                // Should not happen: the renderer only routes supported types here.
                $formname = 'lesson_display_answer_form_' . $idstring;
                break;
        }

        $data['qfmarkername'] = '_qf__' . $formname;
        return $data;
    }

    /**
     * Build the answer choices for radio/checkbox based question types.
     *
     * @param array $answers The answers to render.
     * @param bool $multiple Whether multiple answers are allowed (checkboxes).
     * @param bool $hasattempt Whether the user already has an attempt (review mode).
     * @return array[] List of answer rows for the template.
     */
    protected function build_choices(array $answers, bool $multiple, bool $hasattempt): array {
        global $USER;

        shuffle($answers);
        $textoptions = ['para' => false, 'noclean' => true];
        $useransrid = $hasattempt ? ($USER->modattempts[$this->lesson->id]->answerid ?? 0) : 0;

        $rows = [];
        foreach ($answers as $answer) {
            $rows[] = [
                'label' => format_text($answer->answer, $answer->answerformat, $textoptions),
                'value' => $multiple ? 1 : $answer->id,
                'name' => $multiple ? 'answer[' . $answer->id . ']' : 'answerid',
                'type' => $multiple ? 'checkbox' : 'radio',
                'checked' => (!$multiple && $answer->id == $useransrid),
                'disabled' => $hasattempt,
            ];
        }
        return $rows;
    }

    /**
     * Build the single text/number input for shortanswer and numerical types.
     *
     * @param string $inputtype The HTML input type ('text' or 'number').
     * @param bool $hasattempt Whether the user already has an attempt (review mode).
     * @return array Template fields for the text input.
     */
    protected function build_textinput(string $inputtype, bool $hasattempt): array {
        global $USER;

        $value = '';
        if ($hasattempt && isset($USER->modattempts[$this->lesson->id]->useranswer)) {
            $value = (string) $USER->modattempts[$this->lesson->id]->useranswer;
        }
        return [
            'inputname' => 'answer',
            'inputtype' => $inputtype,
            'inputvalue' => $value,
            'inputreadonly' => $hasattempt,
        ];
    }

    /**
     * Build the navigation buttons for a content (branch table) page.
     *
     * Each branch answer becomes a button posting its jump target to continue.php.
     *
     * @return array[] List of buttons with jumpto and label.
     */
    protected function build_content_buttons(): array {
        $textoptions = ['para' => false, 'noclean' => true];
        $buttons = [];
        foreach ($this->page->get_answers() as $answer) {
            if ($answer->answer === '') {
                // Not a branch.
                continue;
            }
            $buttons[] = [
                'jumpto' => $answer->jumpto,
                'label' => strip_tags(format_text($answer->answer, FORMAT_MOODLE, $textoptions)),
            ];
        }
        return $buttons;
    }

    /**
     * Build the matching rows (stem label + a select of shuffled responses).
     *
     * Mirrors lesson_page_type_matching::make_answer_form(): the first two answers
     * hold the correct/wrong response feedback, the rest are matchable stems.
     *
     * @return array[] List of rows with stemlabel, selectname and options.
     */
    protected function build_matching(): array {
        $answers = array_slice($this->page->get_answers(), 2);
        $textoptions = ['para' => false, 'noclean' => true];

        // Collect the available responses (right-hand side), keyed by trimmed text.
        $responses = [];
        foreach ($answers as $answer) {
            if ($answer->response !== null) {
                $responses[trim($answer->response)] = format_text(trim($answer->response));
            }
        }
        $keys = array_keys($responses);
        shuffle($keys);

        $rows = [];
        foreach ($answers as $answer) {
            if ($answer->response === null) {
                continue;
            }
            $answer = \lesson_page::rewrite_answers_urls($answer);
            $options = [['value' => '', 'label' => get_string('choosedots'), 'selected' => true]];
            foreach ($keys as $key) {
                $options[] = ['value' => $key, 'label' => $responses[$key], 'selected' => false];
            }
            $rows[] = [
                'stemlabel' => format_text($answer->answer, $answer->answerformat, $textoptions),
                'selectname' => 'response[' . $answer->id . ']',
                'options' => $options,
            ];
        }
        return $rows;
    }
}
