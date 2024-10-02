<?php

namespace App\Http\Controllers;

use App\Models\Notas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class NotaController extends Controller
{
    private function checkAuth()
    {
        if (!Auth::guard('api')->check()) {
            abort(401, 'Unauthorized');
        }
    }

    public function index()
    {
        $this->checkAuth();
        $notas = Notas::where('user_id', Auth::id())->get();
        return response()->json($notas);
    }

    public function store(Request $request)
    {
        $this->checkAuth();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'labels' => 'nullable|string',
            'image_path' => 'nullable|string',
            'expiration_date' => 'nullable|date',
        ]);
        $nota = Notas::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => Auth::id(),
            'labels' => $request->labels,
            'image_path' => $request->image_path,
            'expiration_date' => $request->expiration_date,
        ]);
        return response()->json($nota, 201);
    }

    public function show(Notas $nota)
    {
        $this->checkAuth();
        if ($nota->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($nota);
    }

    public function update(Request $request, Notas $nota)
    {
        $this->checkAuth();
        if ($nota->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'labels' => 'nullable|string',
            'image_path' => 'nullable|string',
            'expiration_date' => 'nullable|date',
        ]);
        $nota->update($request->only('title', 'description', 'labels', 'image_path', 'expiration_date'));
        return response()->json($nota);
    }

    public function destroy(Notas $nota)
    {
        $this->checkAuth();
        if ($nota->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $nota->delete();
        return response()->json(null, 204);
    }
}