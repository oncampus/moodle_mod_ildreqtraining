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

require('../../config.php');

define('ARCHIVE_PAGE', 25);

$id = optional_param('id', 0, PARAM_INT); // Course Module ID

if (!$cm = get_coursemodule_from_id('ildreqtraining', $id)) {
    print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
}

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('course is misconfigured');  // NOTE As above
}

$ildreqtraining = $DB->get_record('ildreqtraining', array('id' => $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/ildreqtraining:view', $context);

// Sort (default lastname, optionally firstname)
$sort = optional_param('sort', '', PARAM_ALPHA);
$firstnamesort = $sort == 'firstname';

// CSV format
$format = optional_param('format', '', PARAM_ALPHA);
$excel = $format == 'excelcsv';
$csv = $format == 'csv' || $excel;

// Paging
$start = optional_param('start', 0, PARAM_INT);
$sifirst = optional_param('sifirst', 'all', PARAM_NOTAGS);
$silast = optional_param('silast', 'all', PARAM_NOTAGS);
$start = optional_param('start', 0, PARAM_INT);

// Whether to show extra user identity information
$extrafields = get_extra_user_fields($context);
$leftcols = 1 + count($extrafields);

function csv_quote($value) {
    global $excel;
    if ($excel) {
        return core_text::convert('"' . str_replace('"', "'", $value) . '"', 'UTF-8', 'UTF-16LE');
    } else {
        return '"' . str_replace('"', "'", $value) . '"';
    }
}

$url = new moodle_url('/mod/ildreqtraining/view.php', array('id' => $cm->id));
if ($sort !== '') {
    $url->param('sort', $sort);
}
if ($format !== '') {
    $url->param('format', $format);
}
if ($start !== 0) {
    $url->param('start', $start);
}

$PAGE->set_url($url);

$completions = $DB->get_records('ildreqtraining_archive', array('coursemodule' => $ildreqtraining->training));
$user_completions = array();

foreach ($completions as $completion) {
    $sql = '';
    $params = array();

    if (array_key_exists($completion->userid, $user_completions)) {
        array_push($user_completions[$completion->userid], $completion->trainingdate);
    } else {
        if ($sifirst !== 'all' && $silast !== 'all') {
            $sql = 'SELECT * FROM {user} WHERE id = :id AND ' . $DB->sql_like('firstname', ':sifirst') . ' AND ' . $DB->sql_like('lastname', ':silast');
            $params = array('id' => $completion->userid, 'sifirst' => $sifirst . '%', 'silast' => $silast . '%');
        } else if ($sifirst !== 'all') {
            $sql = 'SELECT * FROM {user} WHERE id = :id AND ' . $DB->sql_like('firstname', ':sifirst');
            $params = array('id' => $completion->userid, 'sifirst' => $sifirst . '%');
        } else if ($silast !== 'all') {
            $sql = 'SELECT * FROM {user} WHERE id = :id AND ' . $DB->sql_like('lastname', ':silast');
            $params = array('id' => $completion->userid, 'silast' => $silast . '%');
        } else {
            $sql = 'SELECT * FROM {user} WHERE id = ?';
            $params = array($completion->userid);
        }

        $found_user = $DB->get_record_sql($sql, $params);

        if (!empty($found_user)) {
            $user_completions[$completion->userid] = array($completion->trainingdate);
        }
    }
}

$total = count($user_completions);

if ($csv && count($user_completions) > 0) { // Only show CSV if there are some users/actvs
    header('Content-Disposition: attachment; filename=progress.' .
        preg_replace('/[^a-z0-9-]/', '_', core_text::strtolower(strip_tags($ildreqtraining->name))) . '.csv');
    // Unicode byte-order mark for Excel
    if ($excel) {
        header('Content-Type: text/csv; charset=UTF-16LE');
        print chr(0xFF) . chr(0xFE);
        $sep = "\t" . chr(0);
        $line = "\n" . chr(0);
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        $sep = ";";
        $line = "\n";
    }
} else {
    // Navigation and header
    $strcompletion = get_string('activitycompletion', 'completion');

    $PAGE->set_title($strcompletion);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
}

// Build link for paging
$link = $CFG->wwwroot . '/mod/ildreqtraining/view.php?id=' . $cm->id;
if (strlen($sort)) {
    $link .= '&amp;sort=' . $sort;
}
$link .= '&amp;start=';

// Build the the page by Initial bar
$initials = array('first', 'last');
$alphabet = explode(',', get_string('alphabet', 'langconfig'));

$pagingbar = '';
foreach ($initials as $initial) {
    $var = 'si' . $initial;

    $othervar = $initial == 'first' ? 'silast' : 'sifirst';
    $othervar = $$othervar != 'all' ? "&amp;{$othervar}={$$othervar}" : '';

    $pagingbar .= ' <div class="initialbar ' . $initial . 'initial">';
    $pagingbar .= get_string($initial . 'name') . ':&nbsp;';

    if ($$var == 'all') {
        $pagingbar .= '<strong>' . get_string('all') . '</strong> ';
    } else {
        $pagingbar .= "<a href=\"{$link}{$othervar}\">" . get_string('all') . '</a> ';
    }

    foreach ($alphabet as $letter) {
        if ($$var === $letter) {
            $pagingbar .= '<strong>' . $letter . '</strong> ';
        } else {
            $pagingbar .= "<a href=\"$link&amp;$var={$letter}{$othervar}\">$letter</a> ";
        }
    }

    $pagingbar .= '</div>';
}

// Do we need a paging bar?
if ($total > ARCHIVE_PAGE) {

    // Paging bar
    $pagingbar .= '<div class="paging">';
    $pagingbar .= get_string('page') . ': ';

    $sistrings = array();
    if ($sifirst != 'all') {
        $sistrings[] = "sifirst={$sifirst}";
    }
    if ($silast != 'all') {
        $sistrings[] = "silast={$silast}";
    }
    $sistring = !empty($sistrings) ? '&amp;' . implode('&amp;', $sistrings) : '';

    // Display previous link
    if ($start > 0) {
        $pstart = max($start - ARCHIVE_PAGE, 0);
        $pagingbar .= "(<a class=\"previous\" href=\"{$link}{$pstart}{$sistring}\">" . get_string('previous') . '</a>)&nbsp;';
    }

    // Create page links
    $curstart = 0;
    $curpage = 0;
    while ($curstart < $total) {
        $curpage++;

        if ($curstart == $start) {
            $pagingbar .= '&nbsp;' . $curpage . '&nbsp;';
        } else {
            $pagingbar .= "&nbsp;<a href=\"{$link}{$curstart}{$sistring}\">$curpage</a>&nbsp;";
        }

        $curstart += ARCHIVE_PAGE;
    }

    // Display next link
    $nstart = $start + ARCHIVE_PAGE;
    if ($nstart < $total) {
        $pagingbar .= "&nbsp;(<a class=\"next\" href=\"{$link}{$nstart}{$sistring}\">" . get_string('next') . '</a>)';
    }

    $pagingbar .= '</div>';
}

// Start of table
if (!$csv) {
    print '<br class="clearer"/>'; // ugh

    print $pagingbar;

    if (!$total) {
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
        echo $OUTPUT->footer();
        exit;
    }

    print '<div id="completion-progress-wrapper" class="no-overflow">';
    print '<table id="completion-progress" class="generaltable flexible boxaligncenter" style="text-align:left"><thead><tr style="vertical-align:top">';

    // User heading / sort option
    print '<th scope="col" class="completion-sortchoice">';

    $sistring = "&amp;silast={$silast}&amp;sifirst={$sifirst}";

    if ($firstnamesort) {
        print
            get_string('firstname') . " / <a href=\"./view.php?id={$cm->id}{$sistring}\">" .
            get_string('lastname') . '</a>';
    } else {
        print "<a href=\"./view.php?id={$cm->id}&amp;sort=firstname{$sistring}\">" .
            get_string('firstname') . '</a> / ' .
            get_string('lastname');
    }
    print '</th>';

    // Print user identity columns
    foreach ($extrafields as $field) {
        echo '<th scope="col" class="completion-identifyfield">' .
            get_user_field_name($field) . '</th>';
    }
} else {
    foreach ($extrafields as $field) {
        echo $sep . csv_quote(get_user_field_name($field));
    }
}


// Some names (labels) come URL-encoded and can be very long, so shorten them
$displayname = 'Zuletzt erfolgreich bestanden';

if ($csv) {
    print $sep . csv_quote($displayname);
    print $line;
} else {
    print '<th scope="col" class="completion-identifyfield">' . $displayname;
    print '</th>';
    print '</tr></thead><tbody>';
}

// Row for each user
foreach ($user_completions as $userid => $completions) {
    $user = $DB->get_record('user', array('id' => $userid));

    if ($csv) {
        print csv_quote(fullname($user));
        foreach ($extrafields as $field) {
            echo $sep . csv_quote($user->{$field});
        }
        foreach ($completions as $completion) {
            print $sep . csv_quote(date('Y-m-d H:i:s', $completion));
        }
        print $line;
    } else {
        print '<tr><th scope="row"><a href="' . $CFG->wwwroot . '/user/view.php?id=' .
            $user->id . '&amp;course=' . $course->id . '">' . fullname($user) . '</a></th>';
        foreach ($extrafields as $field) {
            echo '<td>' . s($user->{$field}) . '</td>';
        }

        print '<td>';
        foreach ($completions as $completion) {
            print date('Y-m-d H:i:s', $completion) . '</br>';
        }
        print '</td></tr>';
    }
}

if ($csv) {
    exit;
}

print '</tbody></table>';
print '</div>';
print $pagingbar;

print '<ul class="progress-actions"><li><a href="view.php?id=' . $cm->id .
    '&amp;format=csv">' . get_string('csvdownload', 'completion') . '</a></li>
    <li><a href="view.php?id=' . $cm->id . '&amp;format=excelcsv">' .
    get_string('excelcsvdownload', 'completion') . '</a></li></ul>';


echo $OUTPUT->footer();

