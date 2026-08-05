<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MediaCleanupQueue extends Model
{
    protected $table = 'media_cleanup_queue';

    protected $guarded = [];
}
