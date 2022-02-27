#!/usr/bin/php
<?php

namespace shortcut;

use shortcut\Api;

class App 
{
    private const API_KEY = '621b5b1d-719d-461f-a212-a4731bbd4668';

    public function main()
    {
        $api = new Api(self::API_KEY);
        $response = $api->get('https://api.app.shortcut.com/api/v3/stories/10235/history');
        $actions = $this->extractWorkFlowActions($response);
        echo json_encode($actions, JSON_PRETTY_PRINT);
    }

    private function extractWorkFlowActions($story_history)
    {
        $workflow_actions = [];
        foreach ($story_history as $event) {
            if (property_exists($event, 'references')) {
                foreach ($event->references as $reference) {
                    if ($reference->entity_type == "workflow-state") {
                        $workflow_actions[] = $reference;
                    }
                }
            }
        }

        return $workflow_actions;
    }
}