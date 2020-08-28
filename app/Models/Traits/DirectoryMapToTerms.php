<?php

namespace App\Models\Traits;

use App\Models\Topic;
use App\Models\Sector;

trait DirectoryMapToTerms
{

    public function getMapSectorsAttribute()
    {
        return $this->getTermMapping('sectors', 'directorySectors');
    }

    public function getMapTopicsAttribute()
    {
        return $this->getTermMapping('topics', 'directorySectors');
    }

    public function getMapRegionsAttribute()
    {
        return $this->getTermMapping('regions', 'jurisdictions');
    }

    public function getTermMapping($area, $tobemapped)
    {
        static $regions;
        static $topics;
        static $sectors;
        static $brands;

        if (! $regions) {
            $topics  = Topic::pluck('id', 'name');
            $sectors = Sector::pluck('id', 'name');
            $brands  = \App\Models\Brand::pluck('machine_name', 'id');
        }
        $list     = collect();
        $mappings = config('term-mapping.' . $brands[$this->directory->brand_id]);
        $this->load($tobemapped);

        if ($this->$tobemapped) {
            foreach ($this->$tobemapped()->get() as $jurisdiction) {
                if (isset($mappings[$area])) {
                    $values = $mappings[$area];
                    foreach ($values as $group) {
                        if (isset($group[$jurisdiction->name])) {
                            if (! isset($$area[$group[$jurisdiction->name]])) {
                                // dump('No database entry for '.$group[$jurisdiction->name]);
                            } else {
                                $list->push([
                                    'id' => $$area[$group[$jurisdiction->name]],
                                    'name' => $group[$jurisdiction->name]
                                ]);
                            }
                        } else {
                            //dump($area.' '.$jurisdiction->name);
                        }
                    }
                }
            }
        } else {
            //dump($tobemapped);
            //dump($this->$tobemapped()->get());
        }
        return $list;
    }
}
