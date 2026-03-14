<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gamifikasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GamifikasiController extends Controller
{
    /**
     * Get user's gamification data
     */
    public function show($user_id = null)
    {
        try {
            $userId = $user_id ?? Auth::id();
            
            $gamifikasi = Gamifikasi::where('user_id', $userId)->first();
            
            if (!$gamifikasi) {
                // Create if doesn't exist
                $gamifikasi = Gamifikasi::create([
                    'user_id' => $userId,
                    'poin' => 0,
                    'badge' => json_encode([])
                ]);
            }

            // Get user rank
            $rank = Gamifikasi::where('poin', '>', $gamifikasi->poin)->count() + 1;

            $data = $gamifikasi->toArray();
            $data['rank'] = $rank;
            // Handle badge - could be string (JSON) or already array (from model casting)
            $data['badge'] = is_string($gamifikasi->badge) 
                ? json_decode($gamifikasi->badge, true) ?? [] 
                : ($gamifikasi->badge ?? []);

            return response()->json([
                'status' => true,
                'message' => 'Data gamifikasi berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Award points to user
     */
    public function awardPoints(Request $request)
    {
        try {
            $userId = $request->user_id ?? Auth::id();
            $points = $request->points ?? 0;
            $reason = $request->reason ?? 'activity';

            $gamifikasi = Gamifikasi::firstOrCreate(
                ['user_id' => $userId],
                ['poin' => 0, 'badge' => json_encode([])]
            );

            $gamifikasi->poin += $points;
            
            // Check for new badges
            $badges = is_string($gamifikasi->badge) ? json_decode($gamifikasi->badge, true) ?? [] : ($gamifikasi->badge ?? []);
            $newBadges = $this->checkBadges($gamifikasi->poin, $badges);
            
            if (count($newBadges) > count($badges)) {
                $gamifikasi->badge = json_encode($newBadges);
            }
            
            $gamifikasi->save();

            return response()->json([
                'status' => true,
                'message' => "Berhasil mendapat $points poin!",
                'data' => [
                    'poin' => $gamifikasi->poin,
                    'badge' => $newBadges,
                    'new_badges' => array_diff($newBadges, $badges)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memberikan poin: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leaderboard
     */
    public function leaderboard(Request $request)
    {
        try {
            $limit = $request->limit ?? 10;
            
            $leaderboard = Gamifikasi::with('user:id,nama,email')
                ->orderBy('poin', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item, $index) {
                    return [
                        'rank' => $index + 1,
                        'user_id' => $item->user_id,
                        'nama' => $item->user->nama,
                        'poin' => $item->poin,
                        'badge' => is_string($item->badge) ? json_decode($item->badge, true) ?? [] : ($item->badge ?? [])
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Leaderboard berhasil diambil',
                'data' => $leaderboard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil leaderboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available badges
     */
    public function badges()
    {
        $badges = [
            ['name' => 'Pemula', 'description' => 'Mulai perjalanan belajar', 'requirement' => 0, 'icon' => '🌱'],
            ['name' => 'Pelajar Aktif', 'description' => 'Kumpulkan 100 poin', 'requirement' => 100, 'icon' => '📚'],
            ['name' => 'Bintang Kelas', 'description' => 'Kumpulkan 500 poin', 'requirement' => 500, 'icon' => '⭐'],
            ['name' => 'Master Fisika', 'description' => 'Kumpulkan 1000 poin', 'requirement' => 1000, 'icon' => '🏆'],
            ['name' => 'Einstein Jr', 'description' => 'Kumpulkan 2000 poin', 'requirement' => 2000, 'icon' => '🧠'],
        ];

        return response()->json([
            'status' => true,
            'message' => 'Daftar badge berhasil diambil',
            'data' => $badges
        ]);
    }

    /**
     * Check and award badges based on points
     */
    private function checkBadges($points, $currentBadges)
    {
        $allBadges = ['Pemula', 'Pelajar Aktif', 'Bintang Kelas', 'Master Fisika', 'Einstein Jr'];
        $requirements = [0, 100, 500, 1000, 2000];
        
        $earnedBadges = [];
        foreach ($requirements as $index => $req) {
            if ($points >= $req) {
                $earnedBadges[] = $allBadges[$index];
            }
        }
        
        return $earnedBadges;
    }
}
