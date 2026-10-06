<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\ListFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $search = ListFilter::searchTerm($request);
        $logAction = trim((string) $request->query('log_action', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $filtersActive = ListFilter::isActive($request, ['q', 'log_action', 'date_from', 'date_to']);

        $logs = ActivityLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('action', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($user) use ($search): void {
                            $user->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($logAction !== '', fn ($query) => $query->where('action', $logAction))
            ->when($dateFrom !== '', function ($query) use ($dateFrom): void {
                $start = Carbon::parse($dateFrom, 'Asia/Manila')->startOfDay();
                $query->where('created_at', '>=', $start);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo): void {
                $end = Carbon::parse($dateTo, 'Asia/Manila')->endOfDay();
                $query->where('created_at', '<=', $end);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();

        $logActionOptions = ActivityLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action', 'action')
            ->all();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'filtersActive' => $filtersActive,
            'search' => $search,
            'logActionFilter' => $logAction !== '' ? $logAction : null,
            'logActionOptions' => $logActionOptions,
            'dateFrom' => $dateFrom !== '' ? $dateFrom : null,
            'dateTo' => $dateTo !== '' ? $dateTo : null,
            'subjectUrls' => $this->subjectUrls($logs->getCollection()),
        ]);
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @return array<string, string>
     */
    private function subjectUrls($logs): array
    {
        $urls = [];

        foreach ($logs as $log) {
            $key = $log->subject_type.'#'.$log->subject_id;

            if (isset($urls[$key]) || $log->subject_id === null || $log->subject_type === null) {
                continue;
            }

            $urls[$key] = match ($log->subject_type) {
                Booking::class => route('admin.bookings.show', $log->subject_id),
                User::class => route('admin.users.show', $log->subject_id),
                Vehicle::class => route('admin.fleet.edit', $log->subject_id),
                default => null,
            };
        }

        return array_filter($urls);
    }
}
