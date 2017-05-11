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

/**
 * @param $feature
 * @return bool|null
 */
function ildreqtraining_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return false;
        default:
            return null;
    }
}

/**
 * @param $data
 * @return mixed
 */
function ildreqtraining_add_instance($data) {
    global $DB;

    $data->timemodified = time();
    $cmid = $data->coursemodule;
    $data->id = $DB->insert_record('ildreqtraining', $data);

    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));

    return $data->id;
}

/**
 * @param $data
 * @return bool
 */
function ildreqtraining_update_instance($data) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('ildreqtraining', $data);

    return true;
}

/**
 * @param $id
 * @return bool
 */
function ildreqtraining_delete_instance($id) {
    global $DB;

    if (!$ildreqtraining = $DB->get_record('ildreqtraining', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('ildreqtraining', array('id' => $ildreqtraining->id));

    return true;
}

/**
 * Return available trainings in course (quiz & scorm)
 *
 * @return array
 */
function ildreqtraining_get_trainings() {
    global $DB, $COURSE;
    $trainings = array();

    $quiz = $DB->get_record('modules', array('name' => 'quiz'));
    $scrom = $DB->get_record('modules', array('name' => 'scorm'));

    $quiz_modules = $DB->get_records_sql('SELECT cm.*, m.name FROM {course_modules} AS cm LEFT JOIN {quiz} AS m ON cm.instance = m.id WHERE cm.course = ? AND cm.module = ?', array($COURSE->id, $quiz->id));
    $scorm_modules = $DB->get_records_sql('SELECT cm.*, m.name FROM {course_modules} AS cm LEFT JOIN {scorm} AS m ON cm.instance = m.id WHERE cm.course = ? AND cm.module = ?', array($COURSE->id, $scrom->id));

    $course_modules = array_merge($quiz_modules, $scorm_modules);

    foreach ($course_modules as $course_module) {
        $trainings[$course_module->id] = $course_module->name;
    }

    return $trainings;
}

/**
 * Check the requirements and send mail if needed
 */
function ildreqtraining_notify_participants() {
    global $DB, $CFG;
    require_once($CFG->libdir . '/grouplib.php');

    $reqtrainings = $DB->get_records('ildreqtraining');

    foreach ($reqtrainings as $training) {
        $context = context_course::instance($training->course);

        $cm = $DB->get_record('course_modules', array('id' => $training->training));

        $training_module = $DB->get_record('modules', array('name' => 'ildreqtraining'));
        $training_cm = $DB->get_record('course_modules', array('course' => $training->course, 'module' => $training_module->id, 'instance' => $training->id));
        $participants = array();

        if ($training_cm->visible == 1 && time() >= $training->startdate) {
            if (!empty($cm->availability)) {
                $groups = json_decode($cm->availability)->c;

                foreach ($groups as $group) {
                    if ($group->type == 'group') {
                        $group_participants = $DB->get_records('groups_members', array('groupid' => $group->id));

                        foreach ($group_participants as $group_participant) {
                            $participant = $DB->get_record('user', array('id' => $group_participant->userid));

                            if (has_capability('mod/ildreqtraining:participant', $context, $participant)) {
                                array_push($participants, $participant);
                            }
                        }
                    }
                }
            }

            $responsible = get_enrolled_users($context, 'mod/ildreqtraining:responsible');
            $completion_reset_time = 0;

            foreach ($participants as $participant) {
                $completion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $training->training, 'userid' => $participant->id));

                if ($completion) {
                    if ($completion->completionstate != 2) {
                        ildreqtraining_notify($participant, $responsible, $training);
                    } else {
                        switch ($training->trainingperiod) {
                            case 'yearly':
                                $completion_reset_time = strtotime('+2 minutes', $completion->timemodified);
                                break;
                            case 'twoyears':
                                $completion_reset_time = strtotime('+2 years', $completion->timemodified);
                                break;
                        }

                        if (time() >= $completion_reset_time) {
                            $quiz = $DB->get_record('modules', array('name' => 'quiz'));
                            $scrom = $DB->get_record('modules', array('name' => 'scorm'));

                            // archive completion
                            $arch_completion = new stdClass();
                            $arch_completion->userid = $participant->id;
                            $arch_completion->trainingdate = $completion->timemodified;
                            $arch_completion->coursemodule = $cm->id;

                            $DB->insert_record('ildreqtraining_archive', $arch_completion);

                            if ($cm->module == $quiz->id) {
                                ildreqtraining_reset_quiz($cm, $participant);
                            } else if ($cm->module == $scrom->id) {
                                ildreqtraining_reset_scorm($cm, $participant);
                            }

                            // delete user_pref
                            $DB->delete_records('user_preferences', array('userid' => $participant->id, 'name' => 'ildreqtraining_notification_' . $cm->id));

                            ildreqtraining_notify($participant, $responsible, $training);
                        }
                    }
                } else {
                    ildreqtraining_notify($participant, $responsible, $training);
                }
            }
        }
    }
}

/**
 * Reset quiz gradebook entry
 *
 * @param $cm
 * @param $participant
 */
function ildreqtraining_reset_quiz($cm, $participant) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    $attempts = $DB->get_records('quiz_attempts', array('userid' => $participant->id, 'quiz' => $cm->instance));
    $quiz = $DB->get_record('quiz', array('id' => $cm->instance, 'course' => $cm->course));

    foreach ($attempts as $attempt) {
        quiz_delete_attempt($attempt, $quiz);
    }
}

/**
 * Reset scorm gradebook entry
 *
 * @param $cm
 * @param $participant
 */
function ildreqtraining_reset_scorm($cm, $participant) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/scorm/locallib.php');

    $attempts = $DB->get_records_sql('SELECT * FROM {scorm_scoes_track} WHERE userid = ? AND scormid = ? GROUP BY attempt', array($participant->id, $cm->instance));
    $scorm = $DB->get_record('scorm', array('id' => $cm->instance, 'course' => $cm->course));

    foreach ($attempts as $attempt) {
        scorm_delete_attempt($participant->id, $scorm, $attempt->attempt);
    }
}

/**
 * Check trys and notify participants
 *
 * @param $participant
 * @param $responsible
 * @param $training
 */
function ildreqtraining_notify($participant, $responsible, $training) {
    global $DB;

    $user_pref = $DB->get_record('user_preferences', array('userid' => $participant->id, 'name' => 'ildreqtraining_notification_' . $training->training));

    if ($user_pref) {
        $pref = unserialize($user_pref->value);

        if ($pref['attempt'] < $training->maxnotifications) {
            $last_try = $pref['last_try'];
            $next_try = 0;

            switch ($training->notificationperiod) {
                case 'weekly':
                    $next_try = strtotime('+1 week', $last_try);
                    break;
                case 'fortnight':
                    $next_try = strtotime('+2 weeks', $last_try);
                    break;
                case 'monthly':
                    $next_try = strtotime('+1 month', $last_try);
                    break;
            }

            if (time() >= $next_try) {
                if (empty($training->secondmailsubject)) {
                    $subject = ildreqtraining_parse_placeholder(get_config('ildreqtraining', 'second_mail_subject'), $training);
                } else {
                    $subject = ildreqtraining_parse_placeholder($training->secondmailsubject, $training);
                }

                if (empty($training->secondmailcontent)) {
                    $content = ildreqtraining_parse_placeholder(get_config('ildreqtraining', 'second_mail_content'), $training, $participant);
                } else {
                    $content = ildreqtraining_parse_placeholder($training->secondmailcontent, $training, $participant);
                }

                $sent = ildreqtraining_send_message($participant, $subject, $content);

                if ($sent) {
                    $attempt = $pref['attempt'];

                    $record = new stdClass();
                    $record->id = $user_pref->id;
                    $record->userid = $participant->id;
                    $record->name = 'ildreqtraining_notification_' . $training->training;
                    $record->value = serialize(array('last_try' => time(), 'attempt' => ($attempt + 1), 'responsible_notified' => 0));
                    $DB->update_record('user_preferences', $record, false);
                }
            }
        } else {
            if ($pref['responsible_notified'] == '0') {
                if (empty($training->responsiblemailsubject)) {
                    $subject = ildreqtraining_parse_placeholder(get_config('ildreqtraining', 'responsible_mail_subject'), $training);
                } else {
                    $subject = ildreqtraining_parse_placeholder($training->responsiblemailsubject, $training);
                }

                if (empty($training->responsiblemailcontent)) {
                    $content = ildreqtraining_parse_placeholder(get_config('ildreqtraining', 'responsible_mail_content'), $training, $participant);
                } else {
                    $content = ildreqtraining_parse_placeholder($training->responsiblemailcontent, $training, $participant);
                }

                foreach ($responsible as $userto) {
                    ildreqtraining_send_message($userto, $subject, $content);
                }

                $attempt = $pref['attempt'];

                $record = new stdClass();
                $record->id = $user_pref->id;
                $record->userid = $participant->id;
                $record->name = 'ildreqtraining_notification_' . $training->training;
                $record->value = serialize(array('last_try' => time(), 'attempt' => $attempt, 'responsible_notified' => 1));
                $DB->update_record('user_preferences', $record, false);
            }
        }
    } else {
        if (empty($training->firstmailsubject)) {
            $subject = ildreqtraining_parse_placeholder(get_config('ildreqtraining', 'first_mail_subject'), $training);
        } else {
            $subject = ildreqtraining_parse_placeholder($training->firstmailsubject, $training);
        }

        if (empty($training->firstmailcontent)) {
            $content = ildreqtraining_parse_placeholder(get_config('ildreqtraining', 'first_mail_content'), $training, $participant);
        } else {
            $content = ildreqtraining_parse_placeholder($training->firstmailcontent, $training, $participant);
        }

        $sent = ildreqtraining_send_message($participant, $subject, $content);

        if ($sent) {
            $record = new stdClass();
            $record->userid = $participant->id;
            $record->name = 'ildreqtraining_notification_' . $training->training;
            $record->value = serialize(array('last_try' => time(), 'attempt' => 1, 'responsible_notified' => 0));
            $DB->insert_record('user_preferences', $record, false);
        }
    }
}

/**
 * Send email with given template
 *
 * @param $recipient
 * @param $subject
 * @param $content
 * @return mixed
 */
function ildreqtraining_send_message($recipient, $subject, $content) {
    global $USER;

    $message = new \core\message\message();
    $message->component = 'mod_ildreqtraining';
    $message->name = 'notify';
    $message->userfrom = $USER;
    $message->userto = $recipient;
    $message->subject = $subject;
    $message->fullmessage = $content;
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml = '<p>' . $content . '</p>';
    $message->smallmessage = '';
    $message->notification = '0';

    $messageid = message_send($message);

    return $messageid;
}

/**
 * Parse placeholders - add more placeholder if required
 *
 * @param $text
 * @param $training
 * @param null $participant
 * @return mixed
 */
function ildreqtraining_parse_placeholder($text, $training, $participant = null) {
    global $DB;

    $courselink = new moodle_url('/course/view.php', array('id' => $training->course));
    $overviewlink = new moodle_url('/report/progress/index.php', array('course' => $training->course));
    $course_module = $DB->get_record('course_modules', array('id' => $training->training));
    $module = $DB->get_record('modules', array('id' => $course_module->module));
    $instance = $DB->get_record($module->name, array('id' => $course_module->instance));

    if ($participant == null) {
        $placeholder = array('{DOCNAME}', '{COURSELINK}', '{OVERVIEWLINK}');
        $items = array($instance->name, $courselink, $overviewlink);
    } else {
        $placeholder = array('{FIRSTNAME}', '{LASTNAME}', '{DOCNAME}', '{COURSELINK}', '{OVERVIEWLINK}');
        $items = array($participant->firstname, $participant->lastname, $instance->name, $courselink, $overviewlink);
    }

    $text = str_replace($placeholder, $items, $text);

    return $text;
}

/**
 * Hide activity on course page if participant
 *
 * @param cm_info $cm
 */
function ildreqtraining_cm_info_dynamic(cm_info $cm) {
    $context = context_module::instance($cm->id);

    if (!has_capability('mod/ildreqtraining:addinstance', $context)) {
        $cm->set_user_visible(false);
    }
}