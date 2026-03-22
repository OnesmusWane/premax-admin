<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
 
class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
 
        $request->validate([
            'name'                  => 'required|string|max:150',
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'current_password'      => 'required_with:password|string',
            'password'              => 'nullable|string|min:8|confirmed',
        ]);
 
        // Verify current password if changing password
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.'
                ], 422);
            }
        }
 
        $user->name  = $request->name;
        $user->email = $request->email;
 
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
 
        $user->save();
 
        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }
}