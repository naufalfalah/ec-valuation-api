<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadDetail;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    protected $leadService;

    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    /**
     * Display a listing of leads.
     *
     * @group Leads
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "form_type": "condo",
     *       "source_url": "https://example.com",
     *       "ip": "192.168.1.1",
     *       "name": "John Doe",
     *       "phone_number": "123456789",
     *       "email": "johndoe@example.com",
     *       "created_at": "2025-04-30T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index()
    {
        $leads = Lead::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $leads
        ]);
    }

    /**
     * Store a new lead.
     *
     * @group Leads
     *
     * @bodyParam form_type string required The type of the form. Example: "condo"
     * @bodyParam source_url string required The URL source of the lead. Example: "https://example.com"
     * @bodyParam ip string required The IP address of the lead. Example: "192.168.1.1"
     * @bodyParam name string required The name of the lead. Example: "John Doe"
     * @bodyParam ph_number string required The phone number of the lead. Example: "123456789"
     * @bodyParam email string required The email of the lead. Example: "johndoe@example.com"
     * @bodyParam project string optional The project name, if applicable. Example: "Project A"
     * @bodyParam block string optional The block number, if applicable. Example: "10"
     * @bodyParam floor string optional The floor number, if applicable. Example: "3"
     * @bodyParam unit string optional The unit number, if applicable. Example: "03-01"
     * @bodyParam flat_type string optional The HDB flat type, if applicable. Example: "3-room"
     * @bodyParam town string optional The town, if applicable. Example: "Tampines"
     * @bodyParam street string optional The street name, if applicable. Example: "Tampines Street 21"
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Lead stored successfully.",
     *   "data": {
     *     "id": 1,
     *     "form_type": "condo",
     *     "source_url": "https://example.com",
     *     "ip": "192.168.1.1",
     *     "firstname": "John Doe",
     *     "ph_number": "123456789",
     *     "email": "johndoe@example.com"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Validation error.",
     *   "errors": {
     *     "ph_number": [
     *       "The phone number field is required."
     *     ]
     *   }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_type' => 'required|string',
            'source_url' => 'required|url',
            'ip' => 'required|ip',
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email',
        ]);

        $phone = $validated['phone_number'];

        $existingLead = Lead::where('phone_number', $phone)->first();

        DB::beginTransaction();

        try {
            if (!$existingLead) {
                $lead = Lead::create([
                    'form_type' => $validated['form_type'],
                    'source_url' => $validated['source_url'],
                    'ip' => $validated['ip'],
                    'name' => $validated['name'],
                    'phone_number' => $validated['phone_number'],
                    'email' => $validated['email'],
                ]);

                $additionalFields = $request->except(array_keys($validated));
                unset($additionalFields['user_otp'], $additionalFields['wp_otp'], $additionalFields['lead_id']);

                foreach ($additionalFields as $key => $value) {
                    LeadDetail::create([
                        'lead_id' => $lead->id,
                        'lead_form_key' => $key,
                        'lead_form_value' => is_array($value) ? implode('| ', $value) : $value,
                    ]);
                }

                DB::commit();

                $leadWithDetails = $this->fetchLeadWithDetails($lead->id);
                $this->leadService->sendLeadToDiscord($leadWithDetails);

                $leadId = $lead->id;
            } else {
                $leadId = $existingLead->id;
            }

            return [
                'lead_id' => $leadId,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'error' => 'An error occurred while processing the lead.'
            ];
        }
    }

    /**
     * Display the specified lead.
     *
     * @group Leads
     *
     * @urlParam id integer required The ID of the lead. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "form_type": "condo",
     *     "source_url": "https://example.com",
     *     "ip": "192.168.1.1",
     *     "name": "John Doe",
     *     "phone_number": "123456789",
     *     "email": "johndoe@example.com",
     *     "project": "Project A",
     *     "block": "10",
     *     ...
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Lead not found."
     * }
     */
    public function show($id)
    {
        try {
            $leadWithDetails = $this->fetchLeadWithDetails($id);
            return response()->json([
                'success' => true,
                'data' => $leadWithDetails
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found.'
            ], 404);
        }
    }

    private function fetchLeadWithDetails($leadId)
    {
        $lead = Lead::findOrFail($leadId)->toArray();
        $details = LeadDetail::where('lead_id', $leadId)->get();

        foreach ($details as $detail) {
            $lead[$detail->lead_form_key] = $detail->lead_form_value;
        }

        return $lead;
    }
}
