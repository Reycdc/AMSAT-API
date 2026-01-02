<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuratKeluarController extends Controller
{
    /**
     * Display a listing of surat keluar
     */
    /**
     * Display a listing of surat keluar
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $search = $request->get('search');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $suratKeluar = SuratKeluar::with(['creator', 'approver'])
                ->when($status, function ($query, $status) {
                    return $query->byStatus($status);
                })
                ->when($search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('nomor_surat', 'like', "%{$search}%")
                            ->orWhere('tujuan_surat', 'like', "%{$search}%")
                            ->orWhere('isi', 'like', "%{$search}%");
                    });
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->dateRange($startDate, $endDate);
                })
                ->latest()
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $suratKeluar
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve surat keluar',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Store a newly created surat keluar
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_surat' => 'required|string|max:255|unique:surat_keluar',
            'tanggal_surat' => 'required|date',
            'tujuan_surat' => 'required|string|max:255',
            'isi' => 'required|string',
            'file_surat' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'status' => 'sometimes|in:draft,pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = null;
            if ($request->hasFile('file_surat')) {
                $filePath = $request->file('file_surat')->store('surat_keluar', 'public');
            }

            $suratKeluar = SuratKeluar::create([
                'nomor_surat' => $request->nomor_surat,
                'tanggal_surat' => $request->tanggal_surat,
                'tujuan_surat' => $request->tujuan_surat,
                'isi' => $request->isi,
                'created_by' => auth()->id(),
                'file_surat' => $filePath,
                'status' => $request->get('status', 'draft'),
                'catatan' => $request->catatan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surat keluar created successfully',
                'data' => $suratKeluar->load(['creator', 'approver'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create surat keluar',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Display the specified surat keluar
     */
    public function show($id)
    {
        try {
            $suratKeluar = SuratKeluar::with(['creator', 'approver'])->find($id);

            if (!$suratKeluar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surat keluar not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $suratKeluar
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve surat keluar',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update the specified surat keluar
     */
    public function update(Request $request, $id)
    {
        try {
            $suratKeluar = SuratKeluar::find($id);

            if (!$suratKeluar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surat keluar not found'
                ], 404);
            }

            // Check permission
            if (!$suratKeluar->canBeEditedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to edit this surat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nomor_surat' => 'sometimes|string|max:255|unique:surat_keluar,nomor_surat,' . $id,
                'tanggal_surat' => 'sometimes|date',
                'tujuan_surat' => 'sometimes|string|max:255',
                'isi' => 'sometimes|string',
                'file_surat' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
                'status' => 'sometimes|in:draft,pending',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('file_surat')) {
                if ($suratKeluar->file_surat) {
                    \Storage::disk('public')->delete($suratKeluar->file_surat);
                }
                $suratKeluar->file_surat = $request->file('file_surat')->store('surat_keluar', 'public');
            }

            $suratKeluar->fill($request->except(['file_surat', 'created_by', 'approved_by']));
            $suratKeluar->save();

            return response()->json([
                'success' => true,
                'message' => 'Surat keluar updated successfully',
                'data' => $suratKeluar->load(['creator', 'approver'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update surat keluar',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Remove the specified surat keluar
     */
    public function destroy($id)
    {
        try {
            $suratKeluar = SuratKeluar::find($id);

            if (!$suratKeluar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surat keluar not found'
                ], 404);
            }

            // Only creator or admin can delete
            if ($suratKeluar->created_by !== auth()->id() && !auth()->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this surat'
                ], 403);
            }

            if ($suratKeluar->file_surat) {
                \Storage::disk('public')->delete($suratKeluar->file_surat);
            }

            $suratKeluar->delete();

            return response()->json([
                'success' => true,
                'message' => 'Surat keluar deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete surat keluar',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Submit surat for approval
     */
    public function submit($id)
    {
        try {
            $suratKeluar = SuratKeluar::find($id);

            if (!$suratKeluar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surat keluar not found'
                ], 404);
            }

            if ($suratKeluar->created_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($suratKeluar->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft surat can be submitted'
                ], 422);
            }

            $suratKeluar->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Surat submitted for approval',
                'data' => $suratKeluar->load(['creator', 'approver'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit surat',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Approve surat keluar (admin/redaktur only)
     */
    public function approve(Request $request, $id)
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

        try {
            $suratKeluar = SuratKeluar::find($id);

            if (!$suratKeluar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surat keluar not found'
                ], 404);
            }

            if (!$suratKeluar->canBeApprovedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or surat not in pending status'
                ], 403);
            }

            $suratKeluar->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'catatan' => $request->catatan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surat keluar approved successfully',
                'data' => $suratKeluar->load(['creator', 'approver'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve surat',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Reject surat keluar (admin/redaktur only)
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'catatan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $suratKeluar = SuratKeluar::find($id);

            if (!$suratKeluar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surat keluar not found'
                ], 404);
            }

            if (!$suratKeluar->canBeApprovedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or surat not in pending status'
                ], 403);
            }

            $suratKeluar->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'catatan' => $request->catatan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surat keluar rejected',
                'data' => $suratKeluar->load(['creator', 'approver'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject surat',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
