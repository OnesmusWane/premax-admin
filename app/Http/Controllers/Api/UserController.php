<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
 
class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at'])
        );
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:staff,admin,super_admin',
        ]);
 
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);
 
        return response()->json($user->only(['id','name','email','role','created_at']), 201);
    }
 
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'sometimes|string|max:150',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role'     => 'sometimes|in:staff,admin,super_admin',
        ]);
 
        $data = $request->only(['name', 'email', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
 
        $user->update($data);
 
        return response()->json($user->fresh()->only(['id','name','email','role','created_at']));
    }
 
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }
 
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }
}
 
