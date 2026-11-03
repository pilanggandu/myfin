<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

class SpacePolicy
{
    /**
     * Memastikan user adalah anggota aktif dari space untuk dapat melihat atau memilihnya.
     */
    public function view(User $user, Space $space): bool
    {
        return $space->hasMember($user);
    }

    /**
     * Hanya pemilik (owner) ruang yang dapat memperbarui pengaturan ruang.
     */
    public function update(User $user, Space $space): bool
    {
        return $space->isOwner($user);
    }

    /**
     * Hanya pemilik yang dapat menghapus ruang, dan ruang personal tidak boleh dihapus.
     */
    public function delete(User $user, Space $space): bool
    {
        if ($space->isPersonal()) {
            return false;
        }

        return $space->isOwner($user);
    }

    /**
     * Hanya pemilik yang dapat mengelola anggota ruang bersama.
     */
    public function manageMembers(User $user, Space $space): bool
    {
        if ($space->isPersonal()) {
            return false;
        }

        return $space->isOwner($user);
    }
}
