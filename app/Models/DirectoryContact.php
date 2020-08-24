<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DirectoryContact extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'position',
        'email'
    ];

    protected $visible = [
        'name',
        'position',
        'email'
    ];

    public function firm()
    {
        return $this->hasOne(DirectoryFirm::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Set the cardinality if it hasn't been provided.
        static::saving(function ($contact) {
            if (is_null($contact->cardinality)) {
                $contact->cardinality = static::where('directory_firm_id', $contact->directory_firm_id)->count();
            }
        });
    }
}
