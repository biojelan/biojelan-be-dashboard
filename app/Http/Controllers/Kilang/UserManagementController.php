<?php

namespace App\Http\Controllers\Kilang;

use App\Actions\Kilang\ManageUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kilang\SaveUserRequest;
use App\Models\AgentApplication;
use App\Models\Pickup;
use App\Models\TransactionAgent;
use App\Models\TransactionClient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255'], 'role' => ['nullable', 'integer', 'between:1,7']]);
        $users = User::query()->with(['agen', 'driver', 'admin'])->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%")))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role_id', $role))->latest('id')->paginate(15)->withQueryString();

        return Inertia::render('kilang/users', ['users' => $users, 'filters' => $filters,
            'applications' => AgentApplication::query()->with('user:id,name,email,phone')->where('status', 'PENDING')->latest('id')->get()]);
    }

    public function store(SaveUserRequest $request, ManageUser $manage): RedirectResponse
    {
        /** @var User $actor */ $actor = $request->user();
        $manage->save($actor, $request->validated());

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(SaveUserRequest $request, User $user, ManageUser $manage): RedirectResponse
    {
        /** @var User $actor */ $actor = $request->user();
        $this->ensureManageable($actor, $user);
        $manage->save($actor, $request->validated(), $user);

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        /** @var User $actor */ $actor = $request->user();
        $this->ensureManageable($actor, $user);
        if ($user->id === $actor->id || $user->role_id === User::SUPERADMIN_ROLE_ID) {
            throw ValidationException::withMessages(['user' => 'Akun sendiri dan Superadmin tidak dapat dihapus.']);
        }
        DB::transaction(function () use ($user): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if (TransactionAgent::query()->where('agent_id', $user->id)->orWhere('driver_id', $user->id)->exists()
                || TransactionClient::query()->where('agent_id', $user->id)->orWhere('client_id', $user->id)->exists()
                || Pickup::query()->where('agent_id', $user->id)->orWhere('driver_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user' => 'Akun memiliki riwayat transaksi. Nonaktifkan akun untuk menjaga riwayat.']);
            }
            $user->tokens()->delete();
            $user->driver()->delete();
            $user->agen()->delete();
            $user->delete();
        });

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function review(Request $request, AgentApplication $application, ManageUser $manage): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['APPROVED', 'REJECTED'])], 'review_note' => ['required_if:decision,REJECTED', 'nullable', 'string', 'max:1000']]);
        /** @var User $actor */ $actor = $request->user();
        DB::transaction(function () use ($application, $data, $actor, $manage): void {
            $application = AgentApplication::query()->lockForUpdate()->findOrFail($application->id);
            if ($application->status !== 'PENDING') {
                throw ValidationException::withMessages(['decision' => 'Permintaan sudah ditinjau.']);
            }
            $user = User::query()->lockForUpdate()->findOrFail($application->user_id);
            if ($user->role_id !== User::CLIENT_ROLE_ID || ! $user->is_active) {
                throw ValidationException::withMessages(['decision' => 'Pemohon harus berupa Client aktif.']);
            }
            if ($data['decision'] === 'APPROVED') {
                $manage->save($actor, ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
                    'role_id' => User::AGEN_ROLE_ID, 'is_active' => true, 'profile' => $application->profile], $user);
            }
            $application->update(['status' => $data['decision'], 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);
        });

        return back()->with('success', 'Permintaan Agent berhasil ditinjau.');
    }

    private function ensureManageable(User $actor, User $target): void
    {
        abort_if($actor->role_id !== User::SUPERADMIN_ROLE_ID && in_array($target->role_id, [1, 2], true), 403);
        if (in_array($target->role_id, User::KILANG_ROLE_IDS, true) && $target->admin !== null) {
            abort_unless($actor->role_id === User::SUPERADMIN_ROLE_ID || $actor->admin->factory_id === $target->admin->factory_id, 404);
        }
    }
}
