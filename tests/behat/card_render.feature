@mod @mod_lesson @mod_lesson_design
Feature: Card design renders lesson question pages
  In order to present questions with a different look
  As a learner
  I see the card layout when a lesson uses a card-based template

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Sam       | Student  |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activity" exists:
      | activity | lesson      |
      | course   | C1          |
      | idnumber | 0001        |
      | name     | Card lesson |
      | design   | monsterwelt |
    And the following "mod_lesson > pages" exist:
      | lesson      | qtype       | title | content        |
      | Card lesson | multichoice | Q1    | Pick the right |
    And the following "mod_lesson > answers" exist:
      | page | answer | response | jumpto    | score |
      | Q1   | Right  | Yes      | Next page | 1     |
      | Q1   | Wrong  | No       | This page | 0     |

  Scenario: Multichoice question renders as a card
    When I am on the "Card lesson" "lesson activity" page logged in as student1
    Then ".lesson-design-card" "css_element" should exist
    And I should see "Right"
    And I should see "Wrong"

  Scenario: Progress bar shows on the card when enabled
    Given the following "activity" exists:
      | activity    | lesson      |
      | course      | C1          |
      | idnumber    | 0006        |
      | name        | Prog lesson |
      | design      | monsterwelt |
      | progressbar | 1           |
    And the following "mod_lesson > pages" exist:
      | lesson      | qtype       | title | content        |
      | Prog lesson | multichoice | PQ    | Pick the right |
    And the following "mod_lesson > answers" exist:
      | page | answer | response | jumpto    | score |
      | PQ   | Right  | Yes      | Next page | 1     |
      | PQ   | Wrong  | No       | This page | 0     |
    When I am on the "Prog lesson" "lesson activity" page logged in as student1
    Then ".lesson-design-card__progress" "css_element" should exist

  Scenario: Default design keeps the standard look
    Given the following "activity" exists:
      | activity | lesson         |
      | course   | C1             |
      | idnumber | 0002           |
      | name     | Plain lesson   |
      | design   | default        |
    And the following "mod_lesson > pages" exist:
      | lesson       | qtype       | title | content        |
      | Plain lesson | multichoice | PQ1   | Pick the right |
    And the following "mod_lesson > answers" exist:
      | page | answer | response | jumpto    | score |
      | PQ1  | Right  | Yes      | Next page | 1     |
      | PQ1  | Wrong  | No       | This page | 0     |
    When I am on the "Plain lesson" "lesson activity" page logged in as student1
    Then ".lesson-design-card" "css_element" should not exist
    And I should see "Right"

  Scenario: True/false question renders as a card
    Given the following "activity" exists:
      | activity | lesson      |
      | course   | C1          |
      | idnumber | 0003        |
      | name     | TF lesson   |
      | design   | monsterwelt |
    And the following "mod_lesson > pages" exist:
      | lesson    | qtype     | title | content           |
      | TF lesson | truefalse | TF1   | The earth is round |
    And the following "mod_lesson > answers" exist:
      | page | answer | response | jumpto    | score |
      | TF1  | True   | Yes      | Next page | 1     |
      | TF1  | False  | No       | This page | 0     |
    When I am on the "TF lesson" "lesson activity" page logged in as student1
    Then ".lesson-design-card" "css_element" should exist
    And I should see "True"
    And I should see "False"

  Scenario: Short answer question renders as a card with a text input and accepts an answer
    Given the following "activity" exists:
      | activity | lesson      |
      | course   | C1          |
      | idnumber | 0004        |
      | name     | SA lesson   |
      | design   | monsterwelt |
    And the following "mod_lesson > pages" exist:
      | lesson    | qtype       | title | content              |
      | SA lesson | shortanswer | SA1   | Capital of France?   |
    And the following "mod_lesson > answers" exist:
      | page | answer | response | jumpto    | score |
      | SA1  | Paris  | Correct  | Next page | 1     |
      | SA1  | Lyon   | Wrong    | This page | 0     |
    When I am on the "SA lesson" "lesson activity" page logged in as student1
    Then ".lesson-design-card__textinput" "css_element" should exist
    And I set the field "answer" to "Paris"
    And I press "Submit"
    Then I should see "Correct"

  Scenario: Numerical question renders as a card with a number input
    Given the following "activity" exists:
      | activity | lesson       |
      | course   | C1           |
      | idnumber | 0005         |
      | name     | Num lesson   |
      | design   | monsterwelt  |
    And the following "mod_lesson > pages" exist:
      | lesson     | qtype     | title | content   |
      | Num lesson | numeric   | N1    | 1 plus 1? |
    And the following "mod_lesson > answers" exist:
      | page | answer | response | jumpto    | score |
      | N1   | 2      | Correct  | Next page | 1     |
      | N1   | 3      | Wrong    | This page | 0     |
    When I am on the "Num lesson" "lesson activity" page logged in as student1
    Then ".lesson-design-card__textinput" "css_element" should exist
