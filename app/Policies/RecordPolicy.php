<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\User;

class RecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->allowsRecord('records.view');
    }

    public function create(User $user): bool
    {
        return $user->allowsRecord('records.create');
    }

    public function view(User $user, Record $record): bool
    {
        return $this->owns($user, $record)
            && $user->allowsRecord('records.view');
    }

    public function update(User $user, Record $record): bool
    {
        return $this->view($user, $record)
            && $user->allowsRecord('records.update');
    }

    public function delete(User $user, Record $record): bool
    {
        return $this->view($user, $record)
            && $user->allowsRecord('records.delete');
    }

    private function owns(User $user, Record $record): bool
    {
        return $user->isAdmin()
            || (int) $record->created_by === (int) $user->id;
    }
}