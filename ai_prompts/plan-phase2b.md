# Lesson Design Templates — Phase 2b Plan (essay, matching, content/branch)

> **Status:** Planned for later. Prerequisite: Phase 1 + Phase 2a are merged
> (multichoice, truefalse, shortanswer, numerical already render via the card
> pipeline). This plan extends the same `question_page` renderable to the remaining
> page types using the hybrid-fallback principle from the spec (§6.3).

**Goal:** Render essay, matching, and content/branch pages with the card design,
or fall back cleanly where a faithful card is impractical.

**Architecture:** Extend `\mod_lesson\output\question_page::export_for_template()`
with new mode branches and add matching template sections to
`templates/pages/card/question.mustache`. The renderer switch in
`mod_lesson_renderer::display_page()` adds the new id-strings to `$cardtypes`.

## Global constraints (carried from Phase 1/2a)
- Each pagetype's `check_answer()` rebuilds its own moodleform and calls
  `get_data()`, so the card form MUST post that form's `_qf__<formname>` marker.
  Confirm each form class name before wiring.
- `default` design must stay byte-identical; unsupported types fall back to legacy.
- Moodle coding style + PHPDoc; `MBS-HACK` markers on core edits; codechecker clean.

---

## Task 1: Content / branch pages (highest value, lowest risk)

**Files:**
- Modify: `classes/output/question_page.php` — add a `content_page` export path OR a
  new `content_page` renderable (branch pages are navigation, not answers).
- Create: `templates/pages/card/content.mustache` — styled nav buttons.
- Modify: `renderer.php` — route `branchtable` id-string to the content template.
- Test: `tests/output/content_page_test.php`, `tests/behat/card_render.feature` (add scenario).

**Notes:**
- `lesson_page_type_branchtable::display()` builds `single_button`s (one per answer)
  posting to `continue.php`. The card version renders each answer as a button
  (`<button name="jumpto" value="…">` or the existing single_button form fields).
- Inspect `branchtable/pagetype.php` `display()` for the exact field names the
  branch navigation posts (jump handling) and replicate them in the template.
- Buttons should be keyboard-accessible and laid out as cards.

**Steps (TDD):** write export test for the button list → implement export → mustache
→ renderer route → Behat (branch page renders as card, clicking a button navigates).

---

## Task 2: Matching pages

**Files:**
- Modify: `classes/output/question_page.php` — add `matching` branch producing a list
  of `{ stemlabel, selectname, options:[{value,label,selected}] }` pairs.
- Modify: `templates/pages/card/question.mustache` — add `{{#ismatching}}` section
  rendering paired `<select>` elements.
- Modify: `renderer.php` — add `matching` to `$cardtypes`.
- Test: `tests/output/question_page_test.php` (matching case), Behat scenario.

**Notes:**
- Form class `lesson_display_answer_form_matching`; marker
  `_qf__lesson_display_answer_form_matching`.
- Field names: each row is a `<select name="response[<answerid>]">` with the
  available response options (see `matching/pagetype.php` lines ~577-595). Replicate
  the `response[id]` naming and the option values exactly so `check_answer()` reads
  `$data->response`.
- Shuffle of response options must match the legacy behaviour (or be acceptable).

---

## Task 3: Essay pages (hybrid fallback decision)

**Files:**
- Modify: `classes/output/question_page.php` — add `essay` branch.
- Modify: `templates/pages/card/question.mustache` — add `{{#isessay}}` section.
- Modify: `renderer.php` — add `essay` to `$cardtypes` (only if implemented).

**Decision required before building:**
- Essay uses an `editor` element (`answer_editor`) — a rich-text widget that cannot
  be faithfully reproduced in mustache. Two options:
  1. **Plain textarea card**: render `<textarea name="answer_editor[text]">` plus the
     hidden `answer_editor[format]`. Loses the toolbar but works; `check_answer()`
     reads `$data->answer_editor['text']`. Verify the exact submitted structure the
     essay `check_answer()` expects (it reads `answer_editor` then strips/saves).
  2. **Keep legacy**: leave essay on the moodleform path (do NOT add to `$cardtypes`).
     Most defensible — the editor is the point of an essay answer.
- Recommendation: **Option 2 (legacy fallback)** unless a plain textarea is
  explicitly acceptable. If Option 1, confirm `answer_editor[text]`/`[format]` names.

---

## Task 4: Quality gates
- `bindev/codechecker.sh <abs path>` and `bindev/moodlecheck.sh <abs path>` clean for
  touched files.
- Full suite: `bindev/phpunit.sh --testsuite mod_lesson_testsuite`.
- Behat: `bindev/behat.sh --tags=@mod_lesson_design` (re-init after adding features).

## Verification checklist
- [ ] Content/branch pages render as card with working navigation buttons.
- [ ] Matching renders paired selects; a submitted match is graded correctly.
- [ ] Essay either renders a working textarea card OR cleanly falls back to legacy.
- [ ] `default` design unchanged for all three types.
- [ ] codechecker clean; full PHPUnit + Behat green.
