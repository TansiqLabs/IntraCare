<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class SetupController extends Controller
{
    /**
     * Show the setup wizard form.
     */
    public function index()
    {
        // If already installed, redirect to admin
        if (User::count() > 0) {
            return redirect('/admin');
        }

        return view('setup.index');
    }

    /**
     * Process the setup: create admin account, seed roles.
     */
    public function store(Request $request)
    {
        // Prevent running setup twice
        if (User::count() > 0) {
            return redirect('/admin');
        }

        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Update app name (skip touching .env in tests)
        if (! app()->environment('testing')) {
            $this->updateEnv('APP_NAME', $validated['hospital_name']);
        }

        // Seed roles and permissions
        if (app()->environment('testing')) {
            (new \Database\Seeders\RolesAndPermissionsSeeder())->run();
        } else {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder',
                '--force' => true,
            ]);
        }

        // Create admin user inside a transaction to avoid partial state.
        $admin = DB::transaction(function () use ($validated) {
            // NOTE: Do NOT wrap in Hash::make() — the User model's 'hashed' cast
            // on the password attribute already handles hashing automatically.
            $admin = User::create([
                'employee_id' => 'EMP-0001',
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => true,
            ]);

            $admin->forceFill(['email_verified_at' => now()])->save();

            $admin->assignRole('Admin');

            return $admin;
        });

        // Create storage symlink (skip in tests)
        if (! app()->environment('testing')) {
            Artisan::call('storage:link');
        }

        // Clear installation cache
        cache()->forget('intracare.installed');

        // Log the admin in
        auth()->login($admin);

        return redirect('/admin')->with('success', __('setup.complete'));
    }

    /**
     * Update a single .env key.
     */
    protected function updateEnv(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        // Sanitize value: strip newlines and carriage returns to prevent .env injection.
        $sanitized = str_replace(["\n", "\r", "\0"], '', $value);

        // Wrap in quotes if value has spaces or special characters
        $escapedValue = (str_contains($sanitized, ' ') || str_contains($sanitized, '"'))
            ? '"' . addcslashes($sanitized, '"') . '"'
            : $sanitized;

        if (str_contains($content, "{$key}=")) {
            $content = preg_replace(
                "/^" . preg_quote($key, '/') . "=.*/m",
                "{$key}={$escapedValue}",
                $content
            );
        } else {
            $content .= "\n{$key}={$escapedValue}\n";
        }

        file_put_contents($envPath, $content);
    }
}
