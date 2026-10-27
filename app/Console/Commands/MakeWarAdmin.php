<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeWarAdmin extends Command
{
    protected $signature = 'war:make-admin {email : Email akun yang akan dijadikan admin}';

    protected $description = 'Memberikan akses admin war tiket kepada akun terdaftar';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error('Akun dengan email tersebut tidak ditemukan. Daftarkan akun terlebih dahulu.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true, 'role' => 'admin'])->save();

        $this->components->info("{$user->email} sekarang memiliki akses admin war tiket.");

        return self::SUCCESS;
    }
}
