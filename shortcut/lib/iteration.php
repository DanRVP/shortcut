<?php

namespace shortcut;

use DateTime;
use shortcut\Api;
use shortcut\Secrets;

class Iteration
{
    /**
     * @var integer ID of the iteration we are looking at.
     */
    protected $id = null;

    /**
     * @var Api $api
     */
    protected $api = null;

    /**
     * @param null|integer $id
     */
    public function __construct($id = null) {
        $this->api = new Api(Secrets::API_KEY);
        if (empty($id)) {
            $id = $this->getCurrentIteration();
        }

        $this->id = $id;
    }

    /**
     * Get the average TIR (days) and total TIR (mins) in a human readable string.
     *
     * @return string
     */
    public function timeInReview()
    {
        if (empty($this->id)) {
            echo "\nAn iteration ID is required.\n";
            return;
        }

        echo "\nRunning...\n";
        if (!$stories = $this->api->getStoriesFromIteration($this->id)) {
            echo "\nRequest failed - no error.";
            return;
        }

        $total_review_time = 0;
        $number_of_stories = 0;
        foreach ($stories as $story) {
            if ($story->completed === true) {
                $story_id = $this->getStoryIdFromUrl($story->app_url);
                $story_history = $this->api->getStoryHistory($story_id);
                $actions = $this->extractWorkFlowActions($story_history);
                $start = $this->getFirstReviewDateTime($actions);
                $end = $this->getLastDeployDateTime($actions);

                $total_review_time += $this->calculateTimeInReview($start, $end);
                $number_of_stories ++;
            }
        }

        $average_mins = $total_review_time / $number_of_stories;
        $average_days = round($average_mins / 1440, 2);

        $report = "\nTotal time in review (mins): $total_review_time\n";
        $report .= "Total number of stories: $number_of_stories\n";
        $report .= "Average time in review per story: $average_days days\n";

        return $report;
    }

    /**
     * Get an ID from a shortcut app url.
     *
     * @param string $url
     *
     * @return string|int
     */
    private function getStoryIdFromUrl($url)
    {
        $parts = explode('/', $url);
        return end($parts);
    }

    /**
     * Gets workflow actions out of a story history object.
     *
     * @param object $story_history A story history object from Shortcut.
     *
     * @return object A workflow action object with datetime string attached.
     */
    private function extractWorkFlowActions($story_history)
    {
        $workflow_actions = [];
        foreach ($story_history as $event) {
            if (property_exists($event, 'references')) {
                foreach ($event->references as $reference) {
                    if ($reference->entity_type == "workflow-state") {
                        $reference->changed_at = $event->changed_at;
                        $workflow_actions[] = $reference;
                    }
                }
            }
        }

        return $workflow_actions;
    }

    /**
     * Finds the first time a story was put into 'Ready for Review'.
     *
     * @param array $actions An array of action objects.
     *
     * @return string Datetime string.
     */
    private function getFirstReviewDateTime($actions)
    {
        $review_states = [];
        foreach ($actions as $action) {
            if ($action->name === 'Ready for Review') {
                $review_states[] = $action;
            }
        }

        if (empty($review_states)) {
            return;
        }

        usort($review_states, [$this, 'compareDates']);
        return $review_states[0]->changed_at;
    }

    /**
     * Finds the last time a story was put into 'Ready for Deploy'.
     *
     * @param array $actions An array of action objects.
     *
     * @return string Datetime string.
     */
    private function getLastDeployDateTime($actions)
    {
        $deploy_states = [];
        foreach ($actions as $action) {
            if ($action->name === 'Ready for Deploy') {
                $deploy_states[] = $action;
            }
        }

        if (empty($deploy_states)) {
            return;
        }

        usort($deploy_states, [$this, 'compareDates']);
        return end($deploy_states)->changed_at;
    }

    /**
     * Calculate a time difference in minutes.
     *
     * @param string $start Datetime string.
     * @param string $end Datetime string.
     *
     * @return integer Interval in minutes.
     */
    private function calculateTimeInReview($start, $end)
    {
        if (empty($start) || empty($end)) {
            return;
        }

        $start_object = new DateTime($start);
        $end_object = new DateTime($end);
        $diff = $start_object->diff($end_object);

        $minutes = 0;
        $minutes += $diff->d * 1440;
        $minutes += $diff->h * 60;
        $minutes += $diff->i;

        // Calculate and subtract weekend hours
        $weekend_days = 0;
        for($i = $start_object; $i <= $end_object; $i->modify('+1 day')){
            if (in_array($i->format('N'), [6, 7])) {
                $weekend_days ++;
            }
        }

        $minutes -= ($weekend_days * 1440);
        return $minutes;
    }

    /**
     * Callback function for sorting objects in 'changed_at' in order ASC.
     * @param object $a First object.
     * @param object $b Second object.
     *
     * @return int
     */
    private function compareDates($a, $b)
    {
        $first_date = strtotime($a->changed_at);
        $second_date = strtotime($b->changed_at);

        if ($first_date == $second_date) {
            return 0;
        }

        return $first_date < $second_date ? -1 : 1;
    }

    /**
     * Get the total points completed in an iteration
     *
     * @return string
     */
    public function totalPoints()
    {
        if (empty($this->id)) {
            return "\nAn iteration ID is required.\n";
        }

        if (empty($iteration_data = $this->api->getIteration($this->id))) {
            return "\nThere was a problem finding an iteration with an ID of: ." . $this->id . "\n";
        }

        return "\n" . $iteration_data->stats->num_points_done . ' points completed in ' . $iteration_data->name . "\n";
    }

    /**
     * Generate a developer 'scoreboard' as a string.
     *
     * @return string
     */
    public function developerScoreboard()
    {
        if (empty($this->id)) {
            echo "\nAn iteration ID is required.\n";
            return;
        }

        echo "\nRunning...\n";
        if (!$stories = $this->api->getStoriesFromIteration($this->id)) {
            echo "\nRequest failed - no error.";
            return;
        }

        $members = new Members();
        if (empty($devs = $members->getActiveDevs())) {
            echo "\nRequest failed - There was a problem finding devs in your workspace";
            return;
        }

        $dev_points = [];
        foreach ($devs as $key => $value) {
            $dev_points[$value] = $this->getDevPoints($stories, $key);
        }

        arsort($dev_points);
        $scoreboard = "\n### Total Number of points in iteration per team member\n";
        $position = 1;
        foreach($dev_points as $key => $value) {
            $string = $position . '. ' . $key . ': ' . $value . " points\n";
            $scoreboard .= $string;
            $position ++;
        }

        return $scoreboard;
    }

    /**
     * Get the total points per dev.
     *
     * @param mixed $stories
     * @param integer $dev_id
     * @return integer
     */
    public function getDevPoints($stories, $dev_id)
    {
        $points = 0;
        foreach ($stories as $story) {
            if (in_array($dev_id, $story->owner_ids) && !is_null($story->completed_at)) {
                $points += ($story->estimate / count($story->owner_ids));
            }
        }

        return $points;
    }

    /**
     * Generate the body of the report as a string.
     *
     * @return string
     */
    public function generateReportDescription()
    {
        $iteration = $this->api->getIteration($this->id);
        $description = $iteration->description;
        $title = '### Shortcut Stats Report';
        $report = "\n\n" . $title;
        $report .= $this->timeInReview();
        $report .= $this->totalPoints();
        $report .= $this->developerScoreboard();

        $parts = explode($title, $description);
        return $parts[0] . $report;
    }

    /**
     * Upload a generated iteration report to Shortcut
     *
     * @param string $report
     */
    public function uploadReport($report)
    {
        $description = [
            'description' => $report,
        ];

        return $this->api->updateIteration($this->id, $description);
    }

    /**
     * Find the current iteration.
     *
     * @return null|integer
     */
    private function getCurrentIteration()
    {
        $current_date = new DateTime();
        $iterations = (array) $this->api->listIterations();

        // Go by newest first to shorten time iterating over result set.
        usort($iterations, function ($x, $y) {
            $x_date = new DateTime($x->end_date);
            $y_date = new DateTime($y->end_date);

            if ($x_date == $y_date) {
                return 0;
            }

            return $x_date < $y_date ? 1 : -1;
        });

        foreach ($iterations as $iteration) {
            $end = new DateTime($iteration->end_date);
            $start = new DateTime($iteration->start_date);

            if ($current_date <= $end && $current_date >= $start) {
                echo "Using $iteration->id as the current iteration...\n";
                return $iteration->id;
            }
        }

        return null;
    }
}
