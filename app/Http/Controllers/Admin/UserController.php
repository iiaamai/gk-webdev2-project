<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SyncVehicleDriverAssignment;
use App\Actions\UploadUserAvatar;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\ListFilter;
use App\Support\MailIntegration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = ListFilter::searchTerm($request);
        $role = UserRole::tryFrom((string) $request->query('role', ''));
        $filtersActive = ListFilter::isActive($request, ['q', 'role']);

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(ListFilter::PER_PAGE)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filtersActive' => $filtersActive,
            'search' => $search,
            'roleFilter' => $request->query('role'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'vehicles' => $this->assignableVehicles(),
        ]);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('assignedVehicle.pricing');

        return view('admin.users.show', compact('user'));
    }

    public function store(
        StoreUserRequest $request,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
        UploadUserAvatar $uploadUserAvatar,
    ): RedirectResponse {
        $data = $request->safe()->only([
            'name',
            'email',
            'mobile',
            'password',
            'role',
        ]);

        $role = $data['role'] instanceof UserRole
            ? $data['role']
            : UserRole::from((string) $data['role']);

        $data['role'] = $role;
        $data['email_verified_at'] = MailIntegration::isEnabled() ? null : now();

        $user = User::query()->create($data);

        if ($request->hasFile('avatar')) {
            $uploadUserAvatar->execute($user, $request->file('avatar'));
        }

        if ($role === UserRole::Driver) {
            $vehicleId = $request->validated('vehicle_id');
            $syncVehicleDriverAssignment->forDriver(
                $user,
                $vehicleId !== null ? (int) $vehicleId : null,
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('assignedVehicle.pricing');

        return view('admin.users.edit', [
            'user' => $user,
            'vehicles' => $this->assignableVehicles($user),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
        UploadUserAvatar $uploadUserAvatar,
    ): RedirectResponse {
        $data = $request->safe()->only([
            'name',
            'email',
            'mobile',
            'role',
            'password',
        ]);

        $role = $data['role'] instanceof UserRole
            ? $data['role']
            : UserRole::from((string) $data['role']);

        $data['role'] = $role;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->hasFile('avatar')) {
            $uploadUserAvatar->execute($user->fresh(), $request->file('avatar'));
        }

        $vehicleId = $role === UserRole::Driver
            ? $request->validated('vehicle_id')
            : null;

        $syncVehicleDriverAssignment->forDriver(
            $user->fresh(),
            $vehicleId !== null ? (int) $vehicleId : null,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated.');
    }

    public function destroy(
        User $user,
        SyncVehicleDriverAssignment $syncVehicleDriverAssignment,
    ): RedirectResponse {
        $this->authorize('delete', $user);

        if ($user->isDriver()) {
            $syncVehicleDriverAssignment->forDriver($user, null);
        }

        $user->archive();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User archived.');
    }

    /**
     * @return Collection<int, Vehicle>
     */
    private function assignableVehicles(?User $user = null)
    {
        return Vehicle::query()
            ->with('pricing')
            ->where(function ($query) use ($user): void {
                $query->whereNull('driver_id');

                if ($user?->id) {
                    $query->orWhere('driver_id', $user->id);
                }
            })
            ->orderBy('plate_number')
            ->get();
    }
}
