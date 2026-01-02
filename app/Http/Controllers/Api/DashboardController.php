<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
  public function stats()
  {
    $userCount = User::count();
    $contentCount = Content::count();
    $commentCount = Comment::count();
    $likeCount = Like::count();

    // Potential donation/income data from memberships
    // For now, let's just sum a column 'biaya' if it exists, or 0
    $totalIncome = 0;
    try {
      $totalIncome = DB::table('memberships')->sum('biaya');
    } catch (\Exception $e) {
      // Table might not have 'biaya' column
    }

    $recentContent = Content::with(['user', 'menu'])
      ->latest('date')
      ->limit(5)
      ->get();

    return response()->json([
      'success' => true,
      'data' => [
        'counts' => [
          'users' => $userCount,
          'contents' => $contentCount,
          'comments' => $commentCount,
          'likes' => $likeCount,
        ],
        'income' => $totalIncome,
        'recent_content' => $recentContent
      ]
    ]);
  }
}
