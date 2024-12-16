<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\ChatSession;
use App\Models\Consultation;
use Illuminate\Http\Request;
use App\Models\PsikologReview;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends BaseController
{

    /**
     * Get dashboard data for admin
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardAdmin(){
        $totalUser = User::whereIn('role', ['M', 'P'])->count();
        $totalPsikolog = User::where('role', 'P')->count();
        $totalConsultation = ChatSession::count();
        $totlCourse = 0;

        // Data grafik konsultasi dalam 6 bulan terakhir
        $consultationData = ChatSession::select(
                DB::raw('DATE_FORMAT(created_at, "%M") as month'), 
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy(DB::raw('MIN(created_at)'), 'ASC') // Menggunakan MIN untuk menghindari error
            ->get()
            ->pluck('total', 'month');  

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $months[] = now()->subMonths(5 - $i)->format('F');
        }

        // Gabungkan data dari database dengan bulan-bulan yang kosong
        $chartData = [
            'months' => $months,
            'totals' => array_map(function ($month) use ($consultationData) {
                return $consultationData[$month] ?? 0; // Jika tidak ada data, isi 0
            }, $months),
        ];

        return $this->sendResponse('Dashboard admin berhasil diambil.', [
            'totalUser' => $totalUser,
            'totalPsikolog' => $totalPsikolog,
            'totalConsultation' => $totalConsultation,
            'totalCourse' => $totlCourse,
            'consultationChart' => $chartData
        ]);
    }

    /**
     * Get dashboard data for psikolog
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboardPsikolog()
    {
        $user = Auth::user();

        // Pastikan user adalah psikolog
        if ($user->role !== 'P') {
            return $this->sendError('Anda bukan psikolog', [], 404);
        }

        // Total client (unik berdasarkan user_id) untuk psikolog ini
        $totalClients = Consultation::where('psi_id', $user->id)
            ->whereIn('consul_status', ['completed', 'ongoing', 'scheduled'])
            ->distinct('user_id')
            ->count('user_id');

        // Total konsultasi dengan status 'completed', 'ongoing', dan 'scheduled'
        $totalCompletedConsultations = Consultation::where('psi_id', $user->id)
            ->whereIn('consul_status', ['completed', 'ongoing', 'scheduled'])
            ->count();

        // Total konsultasi dengan status 'scheduled'
        $totalScheduledConsultations = Consultation::where('psi_id', $user->id)
            ->whereIn('consul_status', ['ongoing', 'scheduled'])
            ->count();
        
        $topics = Consultation::select('topic_id', DB::raw('COUNT(*) as total'))
            ->where('psi_id', $user->id)
            ->whereIn('consul_status', ['completed', 'ongoing', 'scheduled'])
            ->groupBy('topic_id')
            ->with('topic') 
            ->get()
            ->map(function ($item) use ($totalCompletedConsultations) {
                return [
                    'topicName' => $item->topic->topic_name ?? 'Unknown', // Pastikan ada relasi `topic` untuk mendapatkan nama
                    'total' => $item->total,
                    'percentage' => round(($item->total / $totalCompletedConsultations) * 100, 2),
                ];
            });

        // Ambil data konsultasi dalam 6 bulan terakhir dari database
        $consultationData = Consultation::select(
            DB::raw('DATE_FORMAT(created_at, "%M") as month'),
            DB::raw('COUNT(*) as total')
        )
        ->where('psi_id', $user->id)
        ->where('created_at', '>=', now()->subMonths(6))
        ->groupBy('month')
        ->orderBy(DB::raw('MIN(created_at)'), 'ASC') // Menggunakan MIN untuk menghindari error
        ->get()
        ->pluck('total', 'month');        

        // Generate array semua bulan dalam 6 bulan terakhir
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $months[] = now()->subMonths(5 - $i)->format('F');
        }

        // Gabungkan data dari database dengan bulan-bulan yang kosong
        $chartData = [
            'months' => $months,
            'totals' => array_map(function ($month) use ($consultationData) {
                return $consultationData[$month] ?? 0; // Jika tidak ada data, isi 0
            }, $months),
        ];

        // Rata-rata rating psikolog
        $averageRating = PsikologReview::where('psi_id', $user->id)
            ->avg('rating');

        // Kembalikan data sebagai JSON
        return $this->sendResponse('Dashboard psikolog berhasil diambil.', [
            'totalClients' => $totalClients,
            'totalConsultations' => $totalCompletedConsultations,
            'totalScheduledConsultations' => $totalScheduledConsultations,
            'averageRating' => round($averageRating, 2),
            'consultationChart' => $chartData,
            'topicChart' => $topics,
        ]);
    }

}
