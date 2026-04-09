<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $databaseUnavailable = $this->isDatabaseUnavailable();

        $emailRules = ['required', 'email', 'max:255'];
        if (!$databaseUnavailable) {
            $emailRules[] = 'unique:users,email';
        }

        $payload = $request->validate([
            'email' => $emailRules,
            'password' => 'required|string|min:6|max:255',
        ]);

        $name = Str::before($payload['email'], '@');

        if ($databaseUnavailable) {
            return $this->registerWithFileStore($payload['email'], $payload['password'], $name);
        }

        try {
            $user = User::create([
                'name' => $name,
                'email' => $payload['email'],
                'password' => $payload['password'],
            ]);

            return $this->successAuthResponse($user, 'Registrasi berhasil.', 201);
        } catch (\Throwable $e) {
            Log::warning('Auth register fallback activated: '.$e->getMessage());
            return $this->registerWithFileStore($payload['email'], $payload['password'], $name);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        if ($this->isDatabaseUnavailable()) {
            return $this->loginWithFileStore($payload['email'], $payload['password']);
        }

        try {
            $user = User::where('email', $payload['email'])->first();

            if (!$user || !Hash::check($payload['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password tidak valid.',
                ], 401);
            }

            return $this->successAuthResponse($user, 'Login berhasil.');
        } catch (\Throwable $e) {
            Log::warning('Auth login fallback activated: '.$e->getMessage());
            return $this->loginWithFileStore($payload['email'], $payload['password']);
        }
    }

    public function google(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $email = $payload['email'] ?? 'google-user@autospec.local';
        $name = $payload['name'] ?? Str::before($email, '@');

        if ($this->isDatabaseUnavailable()) {
            return $this->googleWithFileStore($email, $name);
        }

        try {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Str::random(32),
                ]
            );

            return $this->successAuthResponse($user, 'Google Sign-In berhasil.');
        } catch (\Throwable $e) {
            Log::warning('Auth google fallback activated: '.$e->getMessage());
            return $this->googleWithFileStore($email, $name);
        }
    }

    private function buildTokenForUser($user): string
    {
        $authUser = $this->normalizeUser($user);
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $authUser['id'],
            'email' => $authUser['email'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
        ]));

        $secret = (string) config('app.key', 'autospec-demo-secret');
        $signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);

        return $header . '.' . $payload . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function normalizeUser($user): array
    {
        if ($user instanceof User) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return [
            'id' => $user['id'] ?? 0,
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
        ];
    }

    private function successAuthResponse($user, string $message, int $status = 200): JsonResponse
    {
        $payload = $this->normalizeUser($user);

        return response()->json([
            'success' => true,
            'message' => $message,
            'token' => $this->buildTokenForUser($payload),
            'user' => $payload,
        ], $status);
    }

    private function isDatabaseUnavailable(): bool
    {
        try {
            DB::connection()->getPdo();
            return false;
        } catch (\Throwable $e) {
            Log::warning('Auth DB unavailable, using file fallback: '.$e->getMessage());
            return true;
        }
    }

    private function authStorePath(): string
    {
        return 'auth/users.json';
    }

    private function readAuthStore(): array
    {
        try {
            if (!Storage::disk('local')->exists($this->authStorePath())) {
                return [];
            }

            $raw = Storage::disk('local')->get($this->authStorePath());
            $parsed = json_decode($raw, true);
            return is_array($parsed) ? $parsed : [];
        } catch (\Throwable $e) {
            Log::warning('Failed reading auth store: '.$e->getMessage());
            return [];
        }
    }

    private function writeAuthStore(array $users): void
    {
        Storage::disk('local')->put($this->authStorePath(), json_encode(array_values($users), JSON_PRETTY_PRINT));
    }

    private function findUserByEmail(array $users, string $email): ?array
    {
        foreach ($users as $user) {
            if (isset($user['email']) && strcasecmp((string) $user['email'], $email) === 0) {
                return $user;
            }
        }

        return null;
    }

    private function registerWithFileStore(string $email, string $password, string $name): JsonResponse
    {
        $users = $this->readAuthStore();
        if ($this->findUserByEmail($users, $email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email sudah terdaftar.',
            ], 422);
        }

        $nextId = 1;
        foreach ($users as $existing) {
            $nextId = max($nextId, ((int) ($existing['id'] ?? 0)) + 1);
        }

        $user = [
            'id' => $nextId,
            'name' => $name,
            'email' => $email,
            'password_hash' => Hash::make($password),
            'provider' => 'password',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $users[] = $user;
        $this->writeAuthStore($users);

        return $this->successAuthResponse($user, 'Registrasi berhasil.', 201);
    }

    private function loginWithFileStore(string $email, string $password): JsonResponse
    {
        $users = $this->readAuthStore();
        $user = $this->findUserByEmail($users, $email);

        if (!$user || !isset($user['password_hash']) || !Hash::check($password, (string) $user['password_hash'])) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password tidak valid.',
            ], 401);
        }

        return $this->successAuthResponse($user, 'Login berhasil.');
    }

    private function googleWithFileStore(string $email, string $name): JsonResponse
    {
        $users = $this->readAuthStore();
        $user = $this->findUserByEmail($users, $email);

        if (!$user) {
            $nextId = 1;
            foreach ($users as $existing) {
                $nextId = max($nextId, ((int) ($existing['id'] ?? 0)) + 1);
            }

            $user = [
                'id' => $nextId,
                'name' => $name,
                'email' => $email,
                'password_hash' => Hash::make(Str::random(32)),
                'provider' => 'google',
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];
            $users[] = $user;
            $this->writeAuthStore($users);
        }

        return $this->successAuthResponse($user, 'Google Sign-In berhasil.');
    }
}
