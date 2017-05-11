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
 * @package    mod_ildreqtraining
 * @copyright  2016 Fachhochschule Lübeck ILD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/ildreqtraining/lib.php');
require_once($CFG->libdir . '/filelib.php');

class mod_ildreqtraining_mod_form extends moodleform_mod {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('date_time_selector', 'startdate', get_string('from'), array('startyear' => 2016));
        $mform->addRule('startdate', null, 'required', null, 'client');

        $options = ildreqtraining_get_trainings();
        $mform->addElement('select', 'training', get_string('training', 'ildreqtraining'), $options);
        $mform->setDefault('training', 0);
        $mform->addRule('training', null, 'required', null, 'client');

        $options = array('yearly' => get_string('yearly', 'ildreqtraining'), 'twoyears' => get_string('twoyears', 'ildreqtraining'));
        $mform->addElement('select', 'trainingperiod', get_string('trainingperiod', 'ildreqtraining'), $options);
        $mform->setDefault('trainingperiod', 'yearly');
        $mform->addRule('trainingperiod', null, 'required', null, 'client');

        $options = array('weekly' => get_string('weekly', 'ildreqtraining'), 'fortnight' => get_string('fortnight', 'ildreqtraining'), 'monthly' => get_string('monthly', 'ildreqtraining'));
        $mform->addElement('select', 'notificationperiod', get_string('notificationperiod', 'ildreqtraining'), $options);
        $mform->setDefault('notificationperiod', 'weekly');
        $mform->addRule('notificationperiod', null, 'required', null, 'client');

        $mform->addElement('text', 'maxnotifications', get_string('maxnotifications', 'ildreqtraining'), array('size' => 3));
        $mform->setType('maxnotifications', PARAM_INT);
        $mform->addRule('maxnotifications', null, 'required', null, 'client');
		
		/* first mail group */
        $mform->addElement('header', 'firstmailgroup', get_string('firstmailgroup', 'ildreqtraining'));
        $mform->addElement('text', 'firstmailsubject', get_string('configlabel_first_mail_subject', 'ildreqtraining'), 'size="150"');
        $mform->addHelpButton('firstmailsubject', 'mailsubject', 'mod_ildreqtraining');
        $mform->addElement('textarea', 'firstmailcontent', get_string('configlabel_first_mail_content', 'ildreqtraining'), 'rows="10"');
        $mform->addHelpButton('firstmailcontent', 'mailcontent', 'mod_ildreqtraining');

        /* second mail group */
        $mform->addElement('header', 'secondmailgroup', get_string('secondmailgroup', 'ildreqtraining'));
        $mform->addElement('text', 'secondmailsubject', get_string('configlabel_second_mail_subject', 'ildreqtraining'), 'size="150"');
        $mform->addHelpButton('secondmailsubject', 'mailsubject', 'mod_ildreqtraining');
        $mform->addElement('textarea', 'secondmailcontent', get_string('configlabel_second_mail_content', 'ildreqtraining'), 'rows="10"');
        $mform->addHelpButton('secondmailcontent', 'mailcontent', 'mod_ildreqtraining');

        /* responsible mail group */
        $mform->addElement('header', 'responsiblemailgroup', get_string('responsiblemailgroup', 'ildreqtraining'));
        $mform->addElement('text', 'responsiblemailsubject', get_string('configlabel_responsible_mail_subject', 'ildreqtraining'), 'size="150"');
        $mform->addHelpButton('responsiblemailsubject', 'mailsubject', 'mod_ildreqtraining');
        $mform->addElement('textarea', 'responsiblemailcontent', get_string('configlabel_responsible_mail_content', 'ildreqtraining'), 'rows="10"');
        $mform->addHelpButton('responsiblemailcontent', 'responsiblemailcontent', 'mod_ildreqtraining');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
