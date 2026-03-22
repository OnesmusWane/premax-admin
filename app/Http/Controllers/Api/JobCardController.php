<?php

namespace App\Http\Controllers\Api;
use App\Models\{JobCard, Vehicle, Customer};
use Illuminate\Http\Request;
 
class JobCardController extends \App\Http\Controllers\Controller {
 
    public function index() {
        $cards = JobCard::with(['vehicle','customer'])
            ->whereNotIn('stage',['done'])
            ->get()
            ->groupBy('stage');
 
        $stages = ['waiting','washing','repair','quality_check'];
        return response()->json(
            collect($stages)->mapWithKeys(fn($s)=>[$s => $cards[$s] ?? collect()])
        );
    }
 
    public function store(Request $request) {
        $data = $request->validate(['vehicle_id'=>'required|exists:vehicles,id','customer_id'=>'required|exists:customers,id','service_name'=>'required','service_id'=>'nullable|exists:services,id','estimated_minutes'=>'nullable|integer','notes'=>'nullable']);
        $data['reference'] = JobCard::generateReference();
        $data['stage']     = 'waiting';
        return response()->json(JobCard::create($data)->load(['vehicle','customer']), 201);
    }
 
    public function updateStage(Request $request, JobCard $jobCard) {
        $request->validate(['stage'=>'required|in:waiting,washing,repair,quality_check,done']);
        $update = ['stage' => $request->stage];
        if ($request->stage !== 'waiting' && !$jobCard->started_at) $update['started_at'] = now();
        if ($request->stage === 'done') $update['completed_at'] = now();
        $jobCard->update($update);
        return response()->json($jobCard->fresh(['vehicle','customer']));
    }
 
    public function destroy(JobCard $jobCard) {
        $jobCard->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
