<?php

namespace shortcut;

use shortcut\Api;
use shortcut\Secrets;

class Members
{
    const DEV_IDS = [
        '6140b419-a375-430b-9c82-12fd40d0a284',
        '60b4a871-33b6-41ce-b4ed-14f4e052f270',
        '612f526e-28da-4491-898f-893ddf78b862',
        '598dd0a2-b885-4ab6-837e-0cda534c5dd0',
    ];

    public function getActiveDevs()
    {
        $api = new Api(Secrets::API_KEY);
        $members = $api->getMembers();

        $active_members = [];
        foreach($members as $member) {
            if (
                !$member->disabled 
                && $member->role !== 'observer' 
                && in_array($member->profile->id, self::DEV_IDS)
            ) {
                $active_members[$member->id] = $member->profile->name;
            }
        }

        return $active_members;
    }
}
