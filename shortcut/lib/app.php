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
        $iteration->timeInReview();
    }

    public function iterationPointsRank($id)
    {
        $iteration = new Iteration($id);
    }

    public function totalPoints($id)
    {
        $iteration = new Iteration($id);
        $iteration->totalPoints();
    }
}
