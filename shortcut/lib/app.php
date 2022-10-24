<?php
namespace shortcut;
class App
{
    /**
     * Print out some basic info about the script.
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
    public function iterationReviewTime($id)
    {
        $iteration = new Iteration($id);
        echo $iteration->timeInReview();
    }

    /**
     * Genrate and print out an iteration scoreboard of developer points.
     *
     * @param integer $id
     */
    public function iterationScoreboard($id)
    {
        $iteration = new Iteration($id);
        echo $iteration->developerScoreboard();
    }

    /**
     * Generate and print out a human readable string showing total points
     * completed in a sprint.
     *
     * @param integer id
     */
    public function iterationTotalPoints($id)
    {
        $iteration = new Iteration($id);
        echo $iteration->totalPoints();
    }

    /**
     * Generate a comprehensive stats report for a specified iteration and
     * append it to the iteration's description.
     */
    public function iterationReport($id)
    {
        $iteration = new Iteration($id);
        $report = $iteration->generateReportDescription();
        echo $report;
        $result = $iteration->uploadReport($report);
        if (!$result) {
            echo "\nThere was a problem uploading the report.";
        } else if (!empty($result->errors)) {
            print_r($result->errors);
        } else {
            echo "\nReport uploaded successfully.";
        }
    }

    /**
     * Print out a list of valid commands and a description of what they do.
     */
    public function help()
    {
        echo "\nCommands:\n";
        echo "------------------------------------------------------------------------------------------------\n";
        echo "iterationReviewTime - Calculate average time in review\n";
        echo "iterationScoreboard - Show a scoreboard of developer points per sprint\n";
        echo "iterationTotalPoints - Show total points in an iteration\n";
        echo "iterationReport - Does all of the above and pushes it to the iteration's description in Shortcut\n";
        echo "------------------------------------------------------------------------------------------------\n";
    }
}
