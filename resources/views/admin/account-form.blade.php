@php
    $editing = $account !== null;
    $formId = $editing ? 'account-' . $account->id : 'new-account';
    $formAction = $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store');
    $selectedRoleId = $account?->roles->first()?->id;
    $permissionModes = ['inherit', 'allow', 'deny'];
    $permissionLabels = [
        'create' => 'Insert',
        'view' => 'View',
        'update' => 'Update',
        'delete' => 'Delete',
    ];

    $savedOverrides = $account?->permission_overrides ?? [];
@endphp

<form class="admin-form" method="POST" action="{{ $formAction }}">
    @csrf

    @if ($editing)
        @method('PUT')
    @endif

    {{-- Validation errors --}}
    <div class="form-errors alert alert-danger d-none" role="alert"></div>

    {{-- Account Information --}}
    <div class="row g-3">

        {{-- Name --}}
        <div class="col-md-6">
            <label for="{{ $formId }}-name" class="form-label">
                Name
            </label>

            <input id="{{ $formId }}-name" name="name" type="text" class="form-control"
                value="{{ old('name', $account?->name) }}" maxlength="100" autocomplete="name" required>
        </div>

        {{-- Email --}}
        <div class="col-md-6">
            <label for="{{ $formId }}-email" class="form-label">
                Email
            </label>

            <input id="{{ $formId }}-email" name="email" type="email" class="form-control"
                value="{{ old('email', $account?->email) }}" maxlength="255" autocomplete="email" required>
        </div>

        {{-- Password --}}
        <div class="col-md-6">
            <label for="{{ $formId }}-password" class="form-label">
                Password

                @if ($editing)
                    <span class="text-muted">
                        (leave blank to keep current password)
                    </span>
                @endif
            </label>

            <input id="{{ $formId }}-password" name="password" type="password" class="form-control" minlength="6"
                autocomplete="new-password" @required(!$editing)>
        </div>

        {{-- Password Confirmation --}}
        <div class="col-md-6">
            <label for="{{ $formId }}-password-confirmation" class="form-label">
                Confirm Password
            </label>

            <input id="{{ $formId }}-password-confirmation" name="password_confirmation" type="password"
                class="form-control" minlength="6" autocomplete="new-password" @required(!$editing)>
        </div>

        {{-- Role --}}
        <div class="col-md-6">
            <label for="{{ $formId }}-role" class="form-label">
                Role
            </label>

            <select id="{{ $formId }}-role" name="role_id" class="form-select" required>
                <option value="" disabled @selected(!$selectedRoleId)>
                    Select Role
                </option>

                @foreach ($roles->where('name', '!=', 'Admin') as $availableRole)
                    <option value="{{ $availableRole->id }}" @selected(old('role_id', $selectedRoleId) == $availableRole->id)>
                        {{ $availableRole->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Permission Override Information --}}
    <div class="mt-4">
        <h6 class="mb-2">
            Record Permission Overrides
        </h6>

        <p class="mb-1">
            <strong>Inherit:</strong>
            Use the permission provided by the role.
        </p>

        <p class="mb-1">
            <strong>Allow:</strong>
            Explicitly grant the permission.
        </p>

        <p class="mb-2">
            <strong>Deny:</strong>
            Explicitly block the permission.
        </p>

        <p class="small text-muted mb-0">
            Update and Delete also require View.
            Denying View disables Update and Delete.
            Insert can work without View.
        </p>
    </div>

    {{-- Permission Overrides --}}
    <div class="row g-3 mt-1">

        @foreach ($permissions as $permission)
            @php
                $action = str($permission)->after('records.')->value();
                $mode = array_key_exists($permission, $savedOverrides)
                    ? ($savedOverrides[$permission]
                        ? 'allow'
                        : 'deny')
                    : 'inherit';

                $selectedMode = old('overrides.' . $action, $mode);
                $effectiveAccess = null;
                if ($editing) {
                    $effectiveAccess = $account->allowsRecord($permission);

                    if (in_array($action, ['update', 'delete'], true)) {
                        $effectiveAccess = $effectiveAccess && $account->allowsRecord('records.view');
                    }
                }
            @endphp

            <div class="col-md-3">
                <label for="{{ $formId }}-{{ $action }}" class="form-label">
                    {{ $permissionLabels[$action] ?? ucfirst($action) }}
                </label>

                <select id="{{ $formId }}-{{ $action }}" name="overrides[{{ $action }}]"
                    class="form-select">
                    @foreach ($permissionModes as $modeOption)
                        <option value="{{ $modeOption }}" @selected($selectedMode === $modeOption)>
                            {{ ucfirst($modeOption) }}
                        </option>
                    @endforeach
                </select>

                @if ($editing)
                    <div class="small text-muted mt-1">
                        Saved effective access:
                        <strong>
                            {{ $effectiveAccess ? 'Allowed' : 'Blocked' }}
                        </strong>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Submit --}}
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            {{ $editing ? 'Save Account' : 'Create Account' }}
        </button>
    </div>
</form>
