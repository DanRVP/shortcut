<?php
namespace shortcut;
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
    public function iterationReviewTime($id)
    {
        $iteration = new Iteration($id);
        echo $iteration->timeInReview();
    }

    public function iterationScoreboard($id)
    {
        $iteration = new Iteration($id);
        echo $iteration->developerScoreboard();
    }

    public function iterationTotalPoints($id)
    {
        $iteration = new Iteration($id);
        echo $iteration->totalPoints();
    }

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

    public function help()
    {
        echo("\nCommands:\n");
        echo("------------------------------------------------------------------------------------------------\n");
        echo("iterationReviewTime - Calculate average time in review\n");
        echo("iterationScoreboard - Show a scoreboard of developer points per sprint\n");
        echo("iterationTotalPoints - Show total points in an iteration\n");
        echo("iterationReport - Does all of the above and pushes it to the iteration's description in Shortcut\n");
        echo("------------------------------------------------------------------------------------------------\n");
    }
}
