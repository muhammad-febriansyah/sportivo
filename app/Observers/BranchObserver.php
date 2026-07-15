<?php

namespace App\Observers;

use App\Models\Branch;

class BranchObserver
{
    /**
     * Setiap cabang wajib punya satu baris pengaturan, dibuat otomatis di sini.
     * Lihat docs/02-erd.md tabel branch_settings.
     */
    public function created(Branch $branch): void
    {
        $branch->setting()->create();
    }
}
