<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Notas;

class NotaController extends Controller
{
    /**
     * Verifica si el usuario está autenticado.
     *
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException si el usuario no está autenticado.
     */
    private function checkAuth()
    {
        if (!Auth::guard('api')->check()) {
            abort(401, 'Unauthorized');
        }
    }

    /**
     * Obtiene todas las notas del usuario autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $this->checkAuth();
        $notas = Notas::where('user_id', Auth::id())->get();
        return response()->json($notas);
    }

    /**
     * Crea una nueva nota para el usuario autenticado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->checkAuth();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_path' => 'nullable|string',
            'labels' => 'nullable|string',
            'expiration_date' => 'nullable|date',
        ]);

        $path = null; // Inicializa la variable para la ruta de la imagen
        $directory = 'images'; // Ruta de la carpeta donde se guardarán las imágenes

        // Verifica si la carpeta pública es escribible y crea la carpeta si no existe
        $publicPath = storage_path('app/public/' . $directory);
        if (!is_writable($publicPath)) {
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true); // Crea la carpeta con permisos adecuados
            } elseif (!is_writable($publicPath)) {
                return response()->json(['error' => 'No se puede escribir en la carpeta de imágenes.'], 403);
            }
        }

        // Verifica si se proporciona una imagen en base64
        if ($request->has('image_path')) {
            $imageData = $request->input('image_path');
            $imageData = explode(',', $imageData); // Separa el tipo y el contenido
            $base64String = isset($imageData[1]) ? $imageData[1] : $imageData[0]; // Toma el contenido base64

            // Decodifica la imagen
            $image = base64_decode($base64String);
            $imageName = uniqid() . '.png'; // Genera un nombre único para la imagen

            // Almacena la imagen en el almacenamiento público
            Storage::disk('public')->put($directory . '/' . $imageName, $image);

            // Asigna la ruta de la imagen como URL pública
            $path = url('storage/' . $directory . '/' . $imageName);
        }

        // Crea la nota
        $nota = Notas::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => Auth::id(),
            'labels' => $request->labels,
            'image_path' => $path,
            'expiration_date' => $request->expiration_date,
        ]);

        return response()->json($nota, 201);
    }

    /**
     * Muestra una nota específica del usuario autenticado.
     *
     * @param Notas $nota
     * @return \Illuminate\Http\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException si el usuario no tiene permiso.
     */
    public function show(Notas $nota)
    {
        $this->checkAuth();
        if ($nota->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($nota);
    }

    /**
     * Actualiza una nota existente del usuario autenticado.
     *
     * @param Request $request
     * @param Notas $nota
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException si la validación falla.
     */
    public function update(Request $request, Notas $nota)
    {
        $this->checkAuth();

        // Verifica si el usuario tiene permisos para actualizar la nota
        if ($nota->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Valida los datos de la solicitud
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'labels' => 'nullable|string',
                'image_path' => 'nullable|string', // Se espera un string base64
                'expiration_date' => 'nullable|date',
            ]);

            // Verifica si se proporciona una nueva imagen en base64
            if ($request->has('image_path')) {
                $imageData = $request->input('image_path');
                $imageData = explode(',', $imageData); // Separa el tipo y el contenido
                $base64String = isset($imageData[1]) ? $imageData[1] : $imageData[0]; // Toma el contenido base64

                // Decodifica la imagen
                $image = base64_decode($base64String);
                $imageName = uniqid() . '.png'; // Genera un nombre único para la imagen

                // Almacena la imagen en el almacenamiento público
                Storage::disk('public')->put('images/' . $imageName, $image);

                // Asigna la ruta de la imagen como URL pública
                $path = url('storage/images/' . $imageName);

                // Actualiza el campo de la imagen en la nota
                $nota->image_path = $path;
            }

            // Actualiza la nota si la validación pasa
            $nota->update($request->only('title', 'description', 'labels', 'expiration_date'));

            return response()->json($nota);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Devuelve los errores de validación
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->validator->errors(),
            ], 422);
        }
    }

    /**
     * Elimina una nota del usuario autenticado.
     *
     * @param Notas $nota
     * @return \Illuminate\Http\JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException si el usuario no tiene permiso.
     */
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
