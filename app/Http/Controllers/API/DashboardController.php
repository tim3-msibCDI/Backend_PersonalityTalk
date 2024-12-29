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
use Carbon\Carbon;

class DashboardController extends BaseController
{
    // Properti untuk menyimpan bulan dalam bahasa Indonesia
    protected $monthsInIndonesian = [];
    
    // Constructor untuk set locale dan bulan dalam bahasa Indonesia
    public function __construct()
    {
        // Set locale ke Indonesia
        Carbon::setLocale('id');
        
        // Menyimpan bulan dalam bahasa Indonesia
        $this->monthsInIndonesian = [
            'January' => 'Januari', 
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];
    }

    // Fungsi untuk mendapatkan bulan dalam bahasa Indonesia
    protected function getMonthsInIndonesian($monthsBack = 6)
    {
        $months = [];
        for ($i = 0; $i < $monthsBack; $i++) {
            $months[] = now()->subMonths($monthsBack - 1 - $i)->locale('id')->isoFormat('MMMM'); // Menggunakan isoFormat untuk bulan Indonesia
        }
        return $months;
    }

    // Fungsi untuk mendapatkan data konsultasi dalam bulan-bulan tertentu
    protected function getConsultationData($userId, $months)
    {
        // Ambil data konsultasi dalam 6 bulan terakhir dari database
        $consultationData = Consultation::select(
                DB::raw('DATE_FORMAT(created_at, "%M") as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('psi_id', $userId)
            ->where('created_at', '>=', now()->subMonths(count($months)))
            ->whereIn('consul_status', ['completed', 'ongoing', 'scheduled'])
            ->groupBy('month')
            ->orderBy(DB::raw('MIN(created_at)'), 'ASC')
            ->get()
            ->pluck('total', 'month');
        
        // Mengonversi bulan ke bahasa Indonesia
        return $consultationData->mapWithKeys(function ($total, $month) {
            return [$this->monthsInIndonesian[$month] ?? $month => $total]; // Mencocokkan bulan dengan bahasa Indonesia
        });
    }

    // Dashboard untuk Admin
    public function dashboardAdmin(){
        $totalUser = User::whereIn('role', ['M', 'P'])->count();
        $totalPsikolog = User::where('role', 'P')->count();
        $totalConsultation = ChatSession::count();
        $totalCourse = 0;

        // Mendapatkan bulan-bulan dalam bahasa Indonesia
        $months = $this->getMonthsInIndonesian();

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

        // Gabungkan data dari database dengan bulan-bulan yang kosong
        $chartConsultation = [
            'months' => $months,
            'totals' => array_map(function ($month) use ($consultationData) {
                return $consultationData[$month] ?? 0; // Jika tidak ada data, isi 0
            }, $months),
        ];

        // Data sementara untuk course
        $chartCourse = [
            'months' => $months,
            'totals' => [15, 10, 23, 18, 30, 33],
        ];

        return $this->sendResponse('Dashboard admin berhasil diambil.', [
            'totalUser' => $totalUser,
            'totalPsikolog' => $totalPsikolog,
            'totalConsultation' => $totalConsultation,
            'totalCourse' => $totalCourse,
            'consultationChart' => $chartConsultation,
            'courseChart' => $chartCourse
        ]);
    }

    // Dashboard untuk Psikolog
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

        // Mendapatkan bulan-bulan dalam bahasa Indonesia
        $months = $this->getMonthsInIndonesian();

        // Mengambil data konsultasi dan bulan-bulan dalam bahasa Indonesia
        $consultationData = $this->getConsultationData($user->id, $months);

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
