<?php

namespace Crater\Http\Controllers;

use Illuminate\Support\Facades\DB;

use Crater\Booking;
use Crater\BookingItem;
use Illuminate\Http\Request;


use Crater\CompanySetting;
use Crater\Company;
use Illuminate\Support\Collection;
use Crater\Currency;
use Crater\InvoiceTemplate;
use Crater\Http\Requests;

use Carbon\Carbon;
use Crater\Item;
use Crater\Mail\InvoicePdf;
use function MongoDB\BSON\toJSON;
use Illuminate\Support\Facades\Log;
use Crater\User;
use Mailgun\Mailgun;
use PDF;
use Validator;
use Crater\TaxType;
use Crater\Tax;


class BookingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {   
        
        $limit = $request->has('limit') ? $request->limit : 10;

        $bookings = Booking::with(['items', 'user'])
            ->join('users', 'users.id', '=', 'bookings.user_id')
            ->applyFilters($request->only([
                'status',
                'paid_status',
                'customer_id',
                'booking_number',
                'from_date',        
                'to_date',
                'orderByField',
                'orderBy',
                'search',
            ]))
            ->whereCompany($request->header('company'))
            ->select('bookings.*', 'users.name')
            ->latest()
            ->paginate($limit);
            
        return response()->json([
            'bookings' => $bookings,
            'bookingTotalCount' => Booking::count()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        //$tax_per_item = CompanySetting::getSetting('tax_per_item', $request->header('company'));
        //$discount_per_item = CompanySetting::getSetting('discount_per_item', $request->header('company'));
        $booking_prefix = "BK";
        //CompanySetting::getSetting('booking_prefix', $request->header('company'));
        $booking_num_auto_generate ="YES";//= CompanySetting::getSetting('booking_auto_generate', $request->header('company'));

        $nextBookingNumberAttribute = null;
        $nextBookingNumber = Booking::getNextBookingNumber($booking_prefix);

        if ($booking_num_auto_generate == "YES") {
            $nextBookingNumberAttribute = $nextBookingNumber;
        }

        return response()->json([
            'nextBookingNumberAttribute' => $nextBookingNumberAttribute,
            'nextBookingNumber' => $booking_prefix.'-'.$nextBookingNumber,
            'items' => Item::with('taxes')->whereCompany($request->header('company'))->get(),
            //'bookingTemplates' => InvoiceTemplate::all(),
            //'tax_per_item' => $tax_per_item,
            //'discount_per_item' => $discount_per_item,
            'booking_prefix' => $booking_prefix
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\BookingsRequest $request)
    {
        $booking_number = explode("-",$request->booking_number);
        $number_attributes['booking_number'] = $booking_number[0].'-'.sprintf('%06d', intval($booking_number[1]));

        Validator::make($number_attributes, [
            'booking_number' => 'required|unique:bookings,booking_number'
        ])->validate();

        $booking_date = Carbon::createFromFormat('d/m/Y', $request->booking_date);
        $due_date = Carbon::createFromFormat('d/m/Y', $request->due_date);
        $status = Booking::STATUS_DRAFT;

       // $tax_per_item = CompanySetting::getSetting('tax_per_item', $request->header('company')) ?? 'NO';
       // $discount_per_item = CompanySetting::getSetting('discount_per_item', $request->header('company')) ?? 'NO';

        if ($request->has('bookingSend')) {
            $status = Booking::STATUS_SENT;
        }

        $booking = Booking::create([
            'booking_date' => $booking_date,
            'due_date' => $due_date,
            'booking_number' => $number_attributes['booking_number'],
            'reference_number' => $request->reference_number,
            'user_id' => $request->user_id,
            'company_id' => $request->header('company'),
            //'booking_template_id' => $request->booking_template_id, 
            'status' => $status,
            'paid_status' => Booking::STATUS_UNPAID,
            'sub_total' => $request->sub_total,
            //'discount' => $request->discount,
            //'discount_type' => $request->discount_type,
            //'discount_val' => $request->discount_val,
            'total' => $request->total,
            'due_amount' => $request->total,
            //'tax_per_item' => $tax_per_item,
            //'discount_per_item' => $discount_per_item,
            //'tax' => $request->tax,
            'notes' => $request->notes,
            'unique_hash' => str_random(60)
        ]);

        $bookingItems = $request->items;

        foreach ($bookingItems as $bookingItem) {
            $bookingItem['company_id'] = $request->header('company');
            $item = $booking->items()->create($bookingItem);
        }

/*         if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $tax['company_id'] = $request->header('company');

                if (gettype($tax['amount']) !== "NULL") {
                    $booking->taxes()->create($tax);
                }
            }
        } */

/*         if ($request->has('bookingSend')) {
            $data['booking'] = Booking::findOrFail($booking->id)->toArray();
            $data['user'] = User::find($request->user_id)->toArray();
            $data['company'] = Company::find($booking->company_id);

            $email = $data['user']['email'];

            if (!$email) {
                return response()->json([
                    'error' => 'user_email_does_not_exist'
                ]);
            }

            \Mail::to($email)->send(new InvoicePdf($data));
        }
 */
        $booking = Booking::with(['items', 'user'])->find($booking->id);

        return response()->json([
            'url' => url('/bookings/pdf/'.$booking->unique_hash),
            'booking' => $booking
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $booking = Booking::with([
            'items',
        //    'items.taxes',
            'user',
        //    'bookingTemplate',
        //    'taxes.taxType'
        ])->find($id);

        $siteData = [
            'booking' => $booking,
            'shareable_link' => url('/bookings/pdf/' . $booking->unique_hash)
        ];

        return response()->json($siteData);
    }


        public function find(Request $request, $id)
    {
        $booking = Booking::with([
            'items',
       //     'items.taxes',
            'user',
       //     'bookingTemplate',
       //     'taxes.taxType'
        ])->find($id);

        $siteData = [
            'booking' => $booking,
            'shareable_link' => url('/bookings/pdf/' . $booking->unique_hash)
        ];

        return response()->json($siteData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request,$id)
    {
        DB::enableQueryLog();

        $booking = Booking::with([
            'items',
           // 'items.taxes',
            'user',
           // 'bookingTemplate',
           // 'taxes.taxType'
        ])->find($id);
       // dd($booking);

        return response()->json([
             'nextBookingNumber' => $booking->getBookingNumAttribute(),
             'booking' => $booking,
            // 'bookingTemplates' => InvoiceTemplate::all(),
            // 'tax_per_item' => $booking->tax_per_item,
            // 'discount_per_item' => $booking->discount_per_item,
            // 'shareable_link' => url('/bookings/pdf/'.$booking->unique_hash),
             'booking_prefix' => 'BK'// $booking->getBookingPrefixAttribute()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\BookingsRequest $request, $id)
    {
        //DB::enableQueryLog() ;

        

        $booking_number = explode("-",$request->booking_number);
        $number_attributes['booking_number'] = $booking_number[0].'-'.sprintf('%06d', intval($booking_number[1]));

        Validator::make($number_attributes, [
            'booking_number' => 'required|unique:bookings,booking_number'.','.$id
        ])->validate();

        $booking_date = Carbon::createFromFormat('d/m/Y', $request->booking_date);
        $due_date = Carbon::createFromFormat('d/m/Y', $request->due_date);

        $booking = Booking::find($id);
        $oldAmount = $booking->total;

        if ($oldAmount != $request->total) {
            $oldAmount = (int)round($request->total) - (int)$oldAmount;
        } else {
            $oldAmount = 0;
        }

        $booking->due_amount = ($booking->due_amount + $oldAmount);

        if ($booking->due_amount == 0 && $booking->paid_status != Booking::STATUS_PAID) {
            $booking->status = Booking::STATUS_COMPLETED;
            $booking->paid_status = Booking::STATUS_PAID;
        } elseif ($booking->due_amount < 0 && $booking->paid_status != Booking::STATUS_UNPAID) {
            return response()->json([
                'error' => 'invalid_due_amount'
            ]);
        } elseif ($booking->due_amount != 0 && $booking->paid_status == Booking::STATUS_PAID) {
            $booking->status = $booking->getPreviousStatus();
            $booking->paid_status = Booking::STATUS_PARTIALLY_PAID;
        }

        $booking->booking_date = $booking_date;
        $booking->due_date = $due_date;
        //$booking->booking_number =  $number_attributes['booking_number'];
        $booking->reference_number = $request->reference_number;
        $booking->user_id = $request->user_id;
        //$booking->booking_template_id = $request->booking_template_id;
        $booking->sub_total = $request->sub_total;
        $booking->total = $request->total;
        //$booking->discount =0;// $request->discount;
        //$booking->discount_type =0;// $request->discount_type;
        //$booking->discount_val =0;// $request->discount_val;
        //$booking->tax = $request->tax;
        $booking->notes = $request->notes;
        $booking->save();
        //dd(DB::getQueryLog());

        $oldItems = $booking->items->toArray();
//         $oldTaxes = $booking->taxes->toArray();
        $bookingItems = $request->items;

        // foreach ($oldItems as $oldItem) {
        //     BookingItem::destroy($oldItem['id']);
        // }

//         foreach ($oldTaxes as $oldTax) {
//             Tax::destroy($oldTax['id']);
//         }
        foreach ($bookingItems as $bookingItem) {
            $bookingItem['company_id'] = $request->header('company');
            $item = $booking->items()->create($bookingItem);

            
        }


//         

//         /* if ($request->has('taxes')) {
//             foreach ($request->taxes as $tax) {
//                 $tax['company_id'] = $request->header('company');

//                 if (gettype($tax['amount']) !== "NULL") {
//                     $booking->taxes()->create($tax);
//                 }
//             }
//         }
//  */
        $booking = Booking::with(['items', 'user',])->find($booking->id);

        return response()->json([
            'url' => url('/bookings/pdf/' . $booking->unique_hash),
            'booking' => $booking,
            'success' => true
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $booking = Booking::find($id);

        if ($booking->payments()->exists() && $booking->payments()->count() > 0) {
            return response()->json([
                'error' => 'payment_attached'
            ]);
        }

        $booking = Booking::destroy($id);

        return response()->json([
            'success' => true
        ]);
    }

    public function delete(Request $request)
    {
        foreach ($request->id as $id) {
            $booking = Booking::find($id);

            if ($booking->payments()->exists() && $booking->payments()->count() > 0) {
                return response()->json([
                    'error' => 'payment_attached'
                ]);
            }
        }

        $booking = Booking::destroy($request->id);

        return response()->json([
            'success' => true
        ]);
    }



     /**
     * Mail a specific booking to the correponding cusitomer's email address.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBooking(Request $request)
    {
        $booking = Booking::findOrFail($request->id);

        $data['booking'] = $booking->toArray();
        $userId = $data['booking']['user_id'];
        $data['user'] = User::find($userId)->toArray();
        $data['company'] = Company::find($booking->company_id);
        $email = $data['user']['email'];

        if (!$email) {
            return response()->json([
                'error' => 'user_email_does_not_exist'
            ]);
        }

        \Mail::to($email)->send(new InvoicePdf($data));

        if ($booking->status == Booking::STATUS_DRAFT) {
            $booking->status = Booking::STATUS_SENT;
            $booking->sent = true;
            $booking->save();
        }


        return response()->json([
            'success' => true
        ]);
    }


     /**
     * Mark a specific booking as sent.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsSent(Request $request)
    {
        $booking = Booking::findOrFail($request->id);
        $booking->status = Booking::STATUS_SENT;
        $booking->sent = true;
        $booking->save();

        return response()->json([
            'success' => true
        ]);
    }


     /**
     * Mark a specific booking as paid.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsPaid(Request $request)
    {
        $booking = Booking::findOrFail($request->id);
        $booking->status = Booking::STATUS_COMPLETED;
        $booking->paid_status = Booking::STATUS_PAID;
        $booking->due_amount = 0;
        $booking->save();

        return response()->json([
            'success' => true
        ]);
    }


     /**
     * Retrive a specified user's unpaid bookings from storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomersUnpaidBookings(Request $request, $id)
    {
        $bookings = Booking::where('paid_status', '<>', Booking::STATUS_PAID)
            ->where('user_id', $id)->where('due_amount', '>', 0)
            ->whereCompany($request->header('company'))
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    public function cloneBooking(Request $request)
    {
        $oldBooking = Booking::with([
            'items.taxes',
            'user',
            'bookingTemplate',
            'taxes.taxType'
        ])
        ->find($request->id);

        $date = Carbon::now();
        $booking_prefix = "BK";        //CompanySetting::getSetting('booking_prefix', $request->header('company')        );
        
        $tax_per_item = CompanySetting::getSetting(
                'tax_per_item',
                $request->header('company')
            ) ? CompanySetting::getSetting(
                'tax_per_item',
                $request->header('company')
            ) : 'NO';
        $discount_per_item = CompanySetting::getSetting(
                'discount_per_item',
                $request->header('company')
            ) ? CompanySetting::getSetting(
                'discount_per_item',
                $request->header('company')
            ) : 'NO';

        $booking = Booking::create([
            'booking_date' => $date,
            'due_date' => $date,
            'booking_number' => $booking_prefix."-".Booking::getNextBookingNumber($booking_prefix),
            'reference_number' => $oldBooking->reference_number,
            'user_id' => $oldBooking->user_id,
            'company_id' => $request->header('company'),
            'booking_template_id' => 1,
            'status' => Booking::STATUS_DRAFT,
            'paid_status' => Booking::STATUS_UNPAID,
            'sub_total' => $oldBooking->sub_total,
            'discount' => $oldBooking->discount,
            'discount_type' => $oldBooking->discount_type,
            'discount_val' => $oldBooking->discount_val,
            'total' => $oldBooking->total,
            'due_amount' => $oldBooking->total,
            'tax_per_item' => $oldBooking->tax_per_item,
            'discount_per_item' => $oldBooking->discount_per_item,
            'tax' => $oldBooking->tax,
            'notes' => $oldBooking->notes,
            'unique_hash' => str_random(60)
        ]);

        $bookingItems = $oldBooking->items->toArray();

        foreach ($bookingItems as $bookingItem) {
            $bookingItem['company_id'] = $request->header('company');
            $bookingItem['name'] = $bookingItem['name'];
            $item = $booking->items()->create($bookingItem);

            if (array_key_exists('taxes', $bookingItem) && $bookingItem['taxes']) {
                foreach ($bookingItem['taxes'] as $tax) {
                    $tax['company_id'] = $request->header('company');

                    if ($tax['amount']) {
                        $item->taxes()->create($tax);
                    }
                }
            }
        }

        if ($oldBooking->taxes) {
            foreach ($oldBooking->taxes->toArray() as $tax) {
                $tax['company_id'] = $request->header('company');
                $booking->taxes()->create($tax);
            }
        }

        $booking = Booking::with([
            'items',
            'user',
            'bookingTemplate',
            'taxes'
        ])->find($booking->id);

        return response()->json([
            'booking' => $booking
        ]);
    }
}
