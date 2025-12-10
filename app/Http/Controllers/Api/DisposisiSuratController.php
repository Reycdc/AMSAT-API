<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DisposisiSurat;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisposisiSuratController extends Controller
{
    /**
     * Display a listing of disposisi
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $userId = $request->get('user_id');

        $disposisi = DisposisiSurat::with(['suratMasuk', 'dariUser', 'kepadaUser'])
            ->when($status, function ($query, $status) {
                return $query->byStatus($status);
            })
            ->when($userId, function ($query, $userId) {
                return $query->forUser($userId);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $disposisi
        ], 200);
    }

    /**
     * Create disposisi for surat masuk
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_surat_masuk' => 'required|exists:surat_masuk,id',
            'kepada_user_id' => 'required|exists:users,id',
            'instruksi' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $suratMasuk = SuratMasuk::find($request->id_surat_masuk);

        if (!$suratMasuk->canBeDispositioned()) {
            return response()->json([
                'success' => false,
                'message' => 'Surat sudah selesai, tidak bisa didisposisikan'
            ], 422);
        }

        $disposisi = DisposisiSurat::create([
            'id_surat_masuk' => $request->id_surat_masuk,
            'dari_user_id' => auth()->id(),
            'kepada_user_id' => $request->kepada_user_id,
            'instruksi' => $request->instruksi,
            'catatan' => $request->catatan,
            'status' => 'pending',
        ]);

        // Update surat masuk status
        $suratMasuk->update(['status' => 'diproses']);

        return response()->json([
            'success' => true,
            'message' => 'Disposisi created successfully',
            'data' => $disposisi->load(['suratMasuk', 'dariUser', 'kepadaUser'])
        ], 201);
    }

    /**
     * Display the specified disposisi
     */
    public function show($id)
    {
        $disposisi = DisposisiSurat::with(['suratMasuk', 'dariUser', 'kepadaUser'])->find($id);

        if (!$disposisi) {
            return response()->json([
                'success' => false,
                'message' => 'Disposisi not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $disposisi
        ], 200);
    }

    /**
     * Update the specified disposisi
     */
    public function update(Request $request, $id)
    {
        $disposisi = DisposisiSurat::find($id);

        if (!$disposisi) {
            return response()->json([
                'success' => false,
                'message' => 'Disposisi not found'
            ], 404);
        }

        // Only creator can update
        if ($disposisi->dari_user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'kepada_user_id' => 'sometimes|exists:users,id',
            'instruksi' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $disposisi->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Disposisi updated successfully',
            'data' => $disposisi->load(['suratMasuk', 'dariUser', 'kepadaUser'])
        ], 200);
    }

    /**
     * Remove the specified disposisi
     */
    public function destroy($id)
    {
        $disposisi = DisposisiSurat::find($id);

        if (!$disposisi) {
            return response()->json([
                'success' => false,
                'message' => 'Disposisi not found'
            ], 404);
        }

        // Only creator or admin can delete
        if ($disposisi->dari_user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $disposisi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Disposisi deleted successfully'
        ], 200);
    }

    /**
     * Get my dispositions (as recipient)
     */
    public function myDispositions(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');

        $disposisi = DisposisiSurat::with(['suratMasuk', 'dariUser'])
            ->forUser(auth()->id())
            ->when($status, function ($query, $status) {
                return $query->byStatus($status);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $disposisi
        ], 200);
    }

    /**
     * Mark disposisi as read
     */
    public function markAsRead($id)
    {
        $disposisi = DisposisiSurat::find($id);

        if (!$disposisi) {
            return response()->json([
                'success' => false,
                'message' => 'Disposisi not found'
            ], 404);
        }

        // Only recipient can mark as read
        if ($disposisi->kepada_user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $disposisi->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Disposisi marked as read',
            'data' => $disposisi->load(['suratMasuk', 'dariUser', 'kepadaUser'])
        ], 200);
    }

    /**
     * Mark disposisi as completed
     */
    public function markAsCompleted(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $disposisi = DisposisiSurat::find($id);

        if (!$disposisi) {
            return response()->json([
                'success' => false,
                'message' => 'Disposisi not found'
            ], 404);
        }

        // Only recipient can mark as completed
        if ($disposisi->kepada_user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($request->has('catatan')) {
            $disposisi->catatan = $request->catatan;
        }

        $disposisi->markAsCompleted();

        // Check if all dispositions are completed, update surat masuk status
        $suratMasuk = $disposisi->suratMasuk;
        $allCompleted = $suratMasuk->disposisi()->where('status', '!=', 'selesai')->count() === 0;

        if ($allCompleted) {
            $suratMasuk->update(['status' => 'selesai']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Disposisi marked as completed',
            'data' => $disposisi->load(['suratMasuk', 'dariUser', 'kepadaUser'])
        ], 200);
    }

    /**
     * Get dispositions by surat masuk ID
     */
    public function bySurat($suratId)
    {
        $disposisi = DisposisiSurat::with(['dariUser', 'kepadaUser'])
            ->where('id_surat_masuk', $suratId)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $disposisi
        ], 200);
    }
}
