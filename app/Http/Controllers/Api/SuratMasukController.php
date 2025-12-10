<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuratMasukController extends Controller
{
    /**
     * Display a listing of surat masuk
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $prioritas = $request->get('prioritas');
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $suratMasuk = SuratMasuk::with(['user', 'disposisi.kepadaUser', 'latestDisposisi'])
            ->when($status, function ($query, $status) {
                return $query->byStatus($status);
            })
            ->when($prioritas, function ($query, $prioritas) {
                return $query->byPrioritas($prioritas);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('nomor_surat', 'like', "%{$search}%")
                      ->orWhere('pengirim', 'like', "%{$search}%")
                      ->orWhere('perihal', 'like', "%{$search}%");
                });
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->dateRange($startDate, $endDate);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $suratMasuk
        ], 200);
    }

    /**
     * Store a newly created surat masuk
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_surat' => 'required|string|max:255|unique:surat_masuk',
            'tanggal_surat' => 'required|date',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string|max:255',
            'isi' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'prioritas' => 'sometimes|in:rendah,sedang,tinggi,urgent',
            'status' => 'sometimes|in:pending,diproses,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $filePath = null;
        if ($request->hasFile('file_surat')) {
            $filePath = $request->file('file_surat')->store('surat_masuk', 'public');
        }

        $suratMasuk = SuratMasuk::create([
            'user_id' => auth()->id(),
            'nomor_surat' => $request->nomor_surat,
            'tanggal_surat' => $request->tanggal_surat,
            'pengirim' => $request->pengirim,
            'perihal' => $request->perihal,
            'isi' => $request->isi,
            'file_surat' => $filePath,
            'prioritas' => $request->get('prioritas', 'sedang'),
            'status' => $request->get('status', 'pending'),
            'catatan' => $request->catatan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Surat masuk created successfully',
            'data' => $suratMasuk->load('user')
        ], 201);
    }

    /**
     * Display the specified surat masuk
     */
    public function show($id)
    {
        $suratMasuk = SuratMasuk::with(['user', 'disposisi.dariUser', 'disposisi.kepadaUser'])->find($id);

        if (!$suratMasuk) {
            return response()->json([
                'success' => false,
                'message' => 'Surat masuk not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $suratMasuk
        ], 200);
    }

    /**
     * Update the specified surat masuk
     */
    public function update(Request $request, $id)
    {
        $suratMasuk = SuratMasuk::find($id);

        if (!$suratMasuk) {
            return response()->json([
                'success' => false,
                'message' => 'Surat masuk not found'
            ], 404);
        }

        // Check permission (owner or admin)
        if ($suratMasuk->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nomor_surat' => 'sometimes|string|max:255|unique:surat_masuk,nomor_surat,' . $id,
            'tanggal_surat' => 'sometimes|date',
            'pengirim' => 'sometimes|string|max:255',
            'perihal' => 'sometimes|string|max:255',
            'isi' => 'nullable|string',
            'file_surat' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'prioritas' => 'sometimes|in:rendah,sedang,tinggi,urgent',
            'status' => 'sometimes|in:pending,diproses,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('file_surat')) {
            if ($suratMasuk->file_surat) {
                \Storage::disk('public')->delete($suratMasuk->file_surat);
            }
            $suratMasuk->file_surat = $request->file('file_surat')->store('surat_masuk', 'public');
        }

        $suratMasuk->fill($request->except('file_surat'));
        $suratMasuk->save();

        return response()->json([
            'success' => true,
            'message' => 'Surat masuk updated successfully',
            'data' => $suratMasuk->load('user')
        ], 200);
    }

    /**
     * Remove the specified surat masuk
     */
    public function destroy($id)
    {
        $suratMasuk = SuratMasuk::find($id);

        if (!$suratMasuk) {
            return response()->json([
                'success' => false,
                'message' => 'Surat masuk not found'
            ], 404);
        }

        // Only owner or admin can delete
        if ($suratMasuk->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($suratMasuk->file_surat) {
            \Storage::disk('public')->delete($suratMasuk->file_surat);
        }

        $suratMasuk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Surat masuk deleted successfully'
        ], 200);
    }

    /**
     * Update status surat masuk
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,diproses,selesai',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $suratMasuk = SuratMasuk::find($id);

        if (!$suratMasuk) {
            return response()->json([
                'success' => false,
                'message' => 'Surat masuk not found'
            ], 404);
        }

        $suratMasuk->update([
            'status' => $request->status,
            'catatan' => $request->catatan ?? $suratMasuk->catatan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $suratMasuk->load('user')
        ], 200);
    }
}
