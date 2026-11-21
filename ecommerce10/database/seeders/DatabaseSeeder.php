<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            foreach (['demo1@ecommerce.test', 'demo2@ecommerce.test'] as $email) {
                User::query()->firstOrCreate(
                    ['email' => $email],
                    User::factory()->raw(['email' => $email]),
                );
            }

            $this->call(ProductSeeder::class);
        }

        $adminEmail = trim((string) env('ADMIN_EMAIL'));
        $adminPassword = (string) env('ADMIN_PASSWORD');

        if ($adminEmail === '' || $adminPassword === '') {
            return;
        }

        $admin = User::query()->firstOrNew([
            'email' => $adminEmail,
        ]);

        if (! $admin->exists) {
            $admin->name = env('ADMIN_NAME', 'Administrator');
            $admin->password = $adminPassword;
        }

        // Re-running the seeder must not reset an existing administrator's password.
        $admin->role = 'admin';
        $admin->email_verified_at = $admin->email_verified_at ?? now();
        $admin->save();
    }
}
