<?php

declare(strict_types=1);

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A message submitted through the public contact form. Created by the
 * frontend, read in the admin. Mirrors pikionis/cms-core's ContactMessage.
 */
class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'message', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
