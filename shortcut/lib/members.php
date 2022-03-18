<?php

namespace shortcut;

use shortcut\Api;
use shortcut\Secrets;

class Members
{
    public function getActiveDevs()
    {
        $api = new Api(Secrets::API_KEY);
        $members = $api->getMembers();

        $active_members = [];
        foreach($members as $member) {
            if (!$member->disabled && $member->role !== 'observer') {
                $active_members[$member->id] = $member->profile->name;
            }
        }

        return $active_members;
    }
}
