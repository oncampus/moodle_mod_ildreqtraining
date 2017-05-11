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

$settings->add(new admin_setting_heading(            
	'headerconfig',            
	get_string('headerconfig', 'mod_ildreqtraining'),            
	get_string('descconfig', 'mod_ildreqtraining'),''    
));

$settings->add(new admin_setting_configtext(            
	'ildreqtraining/first_mail_subject',            
	get_string('configlabel_first_mail_subject', 'mod_ildreqtraining'),            
	get_string('configdesc_first_mail_subject', 'mod_ildreqtraining'),''
));

$settings->add(new admin_setting_configtextarea(            
	'ildreqtraining/first_mail_content',            
	get_string('configlabel_first_mail_content', 'mod_ildreqtraining'),            
	get_string('configdesc_first_mail_content', 'mod_ildreqtraining'),''
));

$settings->add(new admin_setting_configtext(            
	'ildreqtraining/second_mail_subject',            
	get_string('configlabel_second_mail_subject', 'mod_ildreqtraining'),            
	get_string('configdesc_second_mail_subject', 'mod_ildreqtraining'),''
));

$settings->add(new admin_setting_configtextarea(            
	'ildreqtraining/second_mail_content',            
	get_string('configlabel_second_mail_content', 'mod_ildreqtraining'),            
	get_string('configdesc_second_mail_content', 'mod_ildreqtraining'),''
));

$settings->add(new admin_setting_configtext(            
	'ildreqtraining/responsible_mail_subject',            
	get_string('configlabel_responsible_mail_subject', 'mod_ildreqtraining'),            
	get_string('configdesc_responsible_mail_subject', 'mod_ildreqtraining'),''
));

$settings->add(new admin_setting_configtextarea(            
	'ildreqtraining/responsible_mail_content',            
	get_string('configlabel_responsible_mail_content', 'mod_ildreqtraining'),            
	get_string('configdesc_responsible_mail_content', 'mod_ildreqtraining'),''
));