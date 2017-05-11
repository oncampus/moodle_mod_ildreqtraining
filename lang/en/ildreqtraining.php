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

$string['modulename'] = 'Required activity';
$string['pluginname'] = 'Required activity';
$string['pluginadministration'] = 'Required activity';
$string['modulenameplural'] = 'Required activities';
$string['ildreqtraining:addinstance'] = 'Add new required activity';

$string['headerconfig'] = 'Required training e-mail configuration';
$string['descconfig'] = 'Global e-mail template configuration for required trainings';

$string['mailsubject'] = 'Subject';
$string['mailsubject_help'] = '<b>Placeholder:</b><br/>{DOCNAME} - Documentname';
$string['mailcontent'] = 'Content';
$string['mailcontent_help'] = '<b>Placeholder:</b><br/>{DOCNAME} - Documentname<br/>{FIRSTNAME} - Firstname<br/>{LASTNAME} - Lastanem<br/>{COURSELINK} - Courselink';
$string['responsiblewmailcontent'] = 'Content';
$string['responsiblemailcontent_help'] = '<b>Placholder:</b><br/>{DOCNAME} - Documentname<br/>{FIRSTNAME} - Firstname<br/>{LASTNAME} - Lastname<br/>{COURSELINK} - Courselink<br/>{OVERVIEWLINK} - Overviewlink';

$string['configlabel_first_mail_subject'] = 'Subject first e-mail';
$string['configdesc_first_mail_subject'] = 'Subject template for the first notification e-mail';
$string['configlabel_first_mail_content'] = 'Content first e-mail';
$string['configdesc_first_mail_content'] = 'Content template for first e-mail';

$string['configlabel_second_mail_subject'] = 'Subject further e-mail';
$string['configdesc_second_mail_subject'] = 'Subject template for further notification e-mails';
$string['configlabel_second_mail_content'] = 'Content further e-mails';
$string['configdesc_second_mail_content'] = 'Content template for further e-mails';

$string['configlabel_responsible_mail_subject'] = 'Subject for e-mail to responsible';
$string['configdesc_responsible_mail_subject'] = 'Subject template for e-mail to responsible person';
$string['configlabel_responsible_mail_content'] = 'Content for e-mail to responsible';
$string['configdesc_responsible_mail_content'] = 'Content template for e-mail to responsible person';

$string['firstmailgroup'] = 'E-Mail template for the first notification e-mail';
$string['secondmailgroup'] = 'E-Mail template for further e-mails';
$string['responsiblemailgroup'] = 'E-Mail template for the responsible person';

$string['training'] = 'Training';
$string['trainingperiod'] = 'Training period';
$string['notificationperiod'] = 'Notification period';
$string['maxnotifications'] = 'max. notifications';

$string['weekly'] = 'weekly';
$string['monthly'] = 'monthly';
$string['fortnight'] = 'fortnight';
$string['yearly'] = 'yearly';
$string['twoyears'] = 'every 2 years';

$string['notify-participants'] = 'Notify participant about required training';
$string['messageprovider:notify'] = 'Message about required training';