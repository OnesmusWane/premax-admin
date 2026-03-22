<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\ContactInformation;
use App\Models\Setting;
use Illuminate\Http\Request;
 
class SettingsController extends Controller
{
    /**
     * GET /api/admin/settings
     * Returns all key/value settings as a flat object.
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json($settings);
    }
 
    /**
     * POST /api/admin/settings
     * Upserts key/value pairs.
     */
    public function store(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return response()->json(['message' => 'Settings saved.']);
    }
 
    /**
     * GET /api/admin/settings/contact
     */
    public function contact()
    {
        $contact = ContactInformation::where('is_primary', true)->first();
        return response()->json($contact);
    }
 
    /**
     * PUT /api/admin/settings/contact
     */
    public function updateContact(Request $request)
    {
        $contact = ContactInformation::where('is_primary', true)->first();
 
        if (!$contact) {
            $contact = ContactInformation::create(array_merge(
                $request->except(['_token']),
                ['is_primary' => true, 'is_active' => true]
            ));
        } else {
            $contact->update($request->except(['_token']));
        }
 
        return response()->json($contact->fresh());
    }
}