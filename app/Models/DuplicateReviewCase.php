<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuplicateReviewCase extends Model
{
    protected $fillable = ['fingerprint', 'rfc_emisor', 'amount', 'invoice_date', 'status', 'note', 'reviewed_by_id', 'reviewed_at'];

    protected $casts = ['amount' => 'decimal:2', 'invoice_date' => 'date', 'reviewed_at' => 'datetime'];

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
