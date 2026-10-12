<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminImportedUsersController extends Controller
{
    public function index(): View
    {
        return view('admin.imported-users.index', [
            'testUserCount' => $this->testUsersQuery()->count(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $draw = max(0, (int) $request->input('draw', 0));
        $start = min(10_000_000, max(0, (int) $request->input('start', 0)));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $baseQuery = $this->testUsersQuery();
        $recordsTotal = (clone $baseQuery)->count();
        $query = clone $baseQuery;
        $search = mb_substr(trim((string) $request->input('search.value', '')), 0, 100);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumn = (int) $request->input('order.0.column', 0);
        $columns = ['id', 'name', 'email', 'id', 'id', 'created_at', 'id'];
        $orderBy = $columns[$orderColumn] ?? 'id';
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $users = $query
            ->orderBy($orderBy, $direction)
            ->orderBy('id')
            ->offset($start)
            ->limit($length)
            ->with([
                'records' => fn (HasMany $query) => $query
                    ->select(['id', 'created_by', 'title', 'status'])
                    ->orderBy('id'),
            ])
            ->withCount('records')
            ->get(['id', 'name', 'email', 'phone', 'created_at', 'import_batch_id']);

        $users->each(function (User $user): void {
            $user->setAttribute('is_imported_user', $user->import_batch_id !== null);
            $user->makeHidden('import_batch_id');
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $users,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_if($user->import_batch_id === null, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->ignore($user->getKey()),
            ],
        ]);

        DB::transaction(function () use ($user, $data): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->whereNotNull('import_batch_id')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill($data)->save();
        });

        return response()->json([
            'message' => 'Imported test user updated.',
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->import_batch_id === null, 404);

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->whereNotNull('import_batch_id')
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $lockedUser->records()->exists(),
                422,
                'This imported user has CRM records. Delete those records first.'
            );

            DB::table('sessions')
                ->where('user_id', $lockedUser->getKey())
                ->delete();
            DB::table('oauth_access_tokens')
                ->where('user_id', $lockedUser->getKey())
                ->delete();
            DB::table('oauth_auth_codes')
                ->where('user_id', $lockedUser->getKey())
                ->delete();
            DB::table('oauth_device_codes')
                ->where('user_id', $lockedUser->getKey())
                ->delete();
            DB::table('password_reset_tokens')
                ->where('email', $lockedUser->email)
                ->delete();
            $lockedUser->notifications()->delete();
            $lockedUser->delete();
        });

        return response()->json([
            'message' => 'Imported test user deleted.',
        ]);
    }

    private function testUsersQuery(): Builder
    {
        return User::query()
            ->where(function (Builder $query): void {
                $query->whereNotNull('import_batch_id')
                    ->orWhereNotNull('load_test_batch_id');
            })
            ->whereHas('records');
    }
}
