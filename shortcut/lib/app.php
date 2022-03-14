<?php
namespace shortcut;

use DateTime;
use shortcut\Api;
use shortcut\Secrets;

class App
{
    /**
     * Just echos out some garabage idk.
     *
     * @return void
     */
    public function main()
    {
        echo "\nInfo: CLI Script to find average time in review for all stories in a Shortcut Iteration.\n";
        echo "\nAuthor: Dan Rogers\n";
    }

    /**
     * Finds the average time a story sits in review within a specified iteration.
     *
     * @param int $id Iteration ID.
     *
     * @return void
     */
    public function iteration($id)
    {
        if (empty($id)) {
            echo "\nAn iteration ID is required.\n";
            return;
        }

        echo "\nRunning...\n";
        $api = new Api(Secrets::API_KEY);
        if (!$stories = $api->getStoriesFromIteration($id)) {
            echo "\nRequest failed - no error.";
            return;
        }

        $total_review_time = 0;
        $number_of_stories = 0;
        foreach ($stories as $story) {
            if ($story->completed === true) {
                $story_id = $this->getStoryIdFromUrl($story->app_url);
                $story_history = $api->getStoryHistory($story_id);
                $actions = $this->extractWorkFlowActions($story_history);
                $start = $this->getFirstReviewDateTime($actions);
                $end = $this->getLastDeployDateTime($actions);

                $total_review_time += $this->calculateTimeInReview($start, $end);
                $number_of_stories ++;
            }
        }

        $average_mins = $total_review_time / $number_of_stories;
        $average_days = round($average_mins / 1440, 2);

        echo "\nTotal time in review (mins): $total_review_time\n";
        echo "Total number of stories: $number_of_stories\n";
        echo "\nAverage time in review per story: $average_days days\n";
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
}
