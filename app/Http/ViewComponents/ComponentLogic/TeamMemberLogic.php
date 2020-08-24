<?php

namespace App\Http\ViewComponents\ComponentLogic;

class TeamMemberLogic implements ComponentLogic
{
    public function load($attributes)
    {
        // TODO: Replace these with actual data
        $teamMembers[] = [
            'name' => 'Fred Bloggs',
            'subtitle' => 'Editor',
            'thumbnail' => 'http://via.placeholder.com/150',
            'profile_url' => 'http://gdr.localhost:8080/author/profile/fred-bloggs'
        ];

        $teamMembers[] = [
            'name' => 'John Smith',
            'subtitle' => 'Journalist',
            'thumbnail' => 'http://via.placeholder.com/150',
            'profile_url' => 'http://gdr.localhost:8080/author/profile/john-smith'
        ];

        $items = collect($teamMembers);

        return $items;
    }
}
