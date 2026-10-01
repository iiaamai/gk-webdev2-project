<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\ListFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $search = ListFilter::searchTerm($request);
        $logAction = trim((string) $request->query('log_action', ''));
        $filtersActive = ListFilter::isActive($request, ['q', 'log_action']);

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
        ]);
    }
}
