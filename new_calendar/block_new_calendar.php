<?php
/**
 * Created by PhpStorm.
 * User: oemer
 * Date: 31.05.15
 * Time: 09:31
 */

class block_new_calendar extends block_base {

    /**
     * Initialise the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_new_calendar');
    }

    /**
     * Return preferred_width.
     *
     * @return int
     */
    public function preferred_width() {
        return 210;
    }

    /**
     * Return the content of this block.
     *
     * @return stdClass the content
     */
    public function get_content() {
        global $CFG;
        global $eventsbyday;
        global $display;
        global $PAGE;
        $url = new moodle_url('/calendar/event.php');

        $PAGE->requires->css('/blocks/new_calendar/new_calendar.css');

        $calm = optional_param('cal_m', 0, PARAM_INT);
        $caly = optional_param('cal_y', 0, PARAM_INT);
        $time = optional_param('time', 0, PARAM_INT);

        require_once($CFG->dirroot.'/calendar/lib.php');


        $filtercourse = calendar_get_default_courses();
        list($courses, $group, $user) = calendar_set_filters($filtercourse);
        $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
        $lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);
        $events = calendar_get_upcoming($courses, $group, $user, $lookahead, 5);


        if ($this->content !== null) {
            return $this->content;
        }

        // If a day, month and year were passed then convert it to a timestamp. If these were passed then we can assume
        // the day, month and year are passed as Gregorian, as no where in core should we be passing these values rather
        // than the time. This is done for BC.
        if (!empty($calm) && (!empty($caly))) {
            $time = make_timestamp($caly, $calm, 1);
        } else if (empty($time)) {
            $time = time();
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $this->content->footer .= calendar_get_block_upcoming($events, $linkhref = NULL);
        $this->content->footer .= '<a href='.$url.'><div class="add-event-icon"></div></a>';

        // [pj] To me it looks like this if would never be needed, but Penny added it
        // when committing the /my/ stuff. Reminder to discuss and learn what it's about.
        // It definitely needs SOME comment here!
        $courseid = $this->page->course->id;
        $issite = ($courseid == SITEID);

        if ($issite) {
            // Being displayed at site level. This will cause the filter to fall back to auto-detecting
            // the list of courses it will be grabbing events from.
            $filtercourse = calendar_get_default_courses();
        } else {
            // Forcibly filter events to include only those from the particular course we are in.
            $filtercourse = array($courseid => $this->page->course);
        }

        list($courses, $group, $user) = calendar_set_filters($filtercourse);
        if ($issite) {
            // For the front page.
            $this->content->text .= calendar_get_mini($courses, $group, $user, false, false, 'frontpage', $courseid, $time);
            // No filters for now.
        } else {
            // For any other course.s
            $this->content->text .= calendar_get_mini($courses, $group, $user, false, false, 'course', $courseid, $time);
            $this->content->text .= '<h3 class="eventskey">'.get_string('eventskey', 'calendar').'</h3>';
            $this->content->text .= '<div class="filters calendar_filters">'.calendar_filter_controls($this->page->url).'</div>';
        }

        return $this->content;
    }
}
