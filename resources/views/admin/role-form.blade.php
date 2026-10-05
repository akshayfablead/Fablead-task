<form
    class="admin-form"
    method="POST"
    action="{{ $role
        ? route('admin.roles.update', $role)
        : route('admin.roles.store') }}"
>
    @csrf

    @if($role)
        @method('PUT')
    @endif

    <div
        class="form-errors alert alert-danger d-none"
        role="alert"
    ></div>

    <label
        class="form-label"
        for="role-name-{{ $role?->id ?? 'new' }}"
    >
        Role name
    </label>

    <input
        class="form-control mb-3"
        id="role-name-{{ $role?->id ?? 'new' }}"
        name="name"
        value="{{ $role?->name }}"
        required
        maxlength="100"
        @readonly(
            $role && in_array($role->name, ['User', 'Manager'], true)
        )
    >

    <div class="d-flex flex-wrap gap-3">
        @foreach($permissions as $permission)
            <label class="form-check-label">
                <input
                    class="form-check-input me-1"
                    type="checkbox"
                    name="permissions[]"
                    value="{{ $permission }}"
                    @checked(
                        $role?->permissions->contains('name', $permission)
                    )
                >

                {{ $permission }}
            </label>
        @endforeach
    </div>

    <p class="small text-muted mt-2">
        Update and Delete also require View.
        Account overrides can change these defaults.
    </p>

    <button type="submit" class="btn btn-primary mt-2">
        {{ $role ? 'Save Role' : 'Create Role' }}
    </button>
</form>