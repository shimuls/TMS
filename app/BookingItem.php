<?php

namespace Crater;

use Illuminate\Database\Eloquent\Model;

use Crater\Booking;
use Crater\Tax;
use Crater\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class BookingItem extends Model
{
    //
    protected $fillable = [
        'booking_id',
        'name',
        'item_id',
        'description',
        'company_id',
        'quantity',
        'price',
        'discount_type',
        'discount_val',
        'total',
        'tax',
        'discount'
    ];

    protected $casts = [
        'price' => 'integer',
        'total' => 'integer',
        'discount' => 'float',
        'quantity' => 'float',
        'discount_val' => 'integer',
        'tax' => 'integer'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function scopeWhereCompany($query, $company_id)
    {
        $query->where('company_id', $company_id);
    }

    public function scopeBookingsBetween($query, $start, $end)
    {
        $query->whereHas('booking', function ($query) use ($start, $end) {
            $query->whereBetween(
                'booking_date',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            );
        });
    }

    public function scopeApplyBookingFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('d/m/Y', $filters->get('from_date'));
            $end = Carbon::createFromFormat('d/m/Y', $filters->get('to_date'));
            $query->bookingsBetween($start, $end);
        }
    }

    public function scopeItemAttributes($query)
    {
        $query->select(
            DB::raw('sum(quantity) as total_quantity, sum(total) as total_amount, booking_items.name')
        )->groupBy('booking_items.name');

    }
}
