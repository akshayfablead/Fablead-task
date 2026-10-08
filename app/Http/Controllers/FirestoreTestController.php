<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirestoreTestController extends Controller
{
    public function store()
    {
        try {
            $db = Firebase::firestore()->database();

            $db->collection('users')->document('user_1')->set([
                'name'  => 'Test User',
                'email' => 'test@example.com',
                'time'  => now()->toDateTimeString(),
            ]);

            return response()->json([
                'status'  => 'inserted',
                'message' => 'Firestore में data insert हो गया ✅',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }
    }
}
