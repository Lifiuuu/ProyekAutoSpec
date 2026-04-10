<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $databaseUnavailable = $this->isDatabaseUnavailable();

        $emailRules = ['required', 'email', 'max:255'];
        if (! $databaseUnavailable) {
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

            if (! $user || ! Hash::check($payload['password'], $user->password)) {
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
        $googleClientId = (string) env('GOOGLE_CLIENT_ID', '');
        $googleClientSecret = (string) env('GOOGLE_CLIENT_SECRET', '');
        if ($googleClientId === '' || $googleClientSecret === '') {
            return response()->json([
                'success' => false,
                'message' => 'Google Sign-In belum dikonfigurasi pada environment.',
            ], 503);
        }

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

    public function googleRedirect(Request $request): RedirectResponse|JsonResponse
    {
        [$googleClientId, $googleClientSecret, $redirectUri] = $this->googleOAuthConfig();
        if ($googleClientId === '' || $googleClientSecret === '') {
            return response()->json([
                'success' => false,
                'message' => 'Google OAuth belum dikonfigurasi pada environment.',
            ], 503);
        }

        $redirectPath = $this->sanitizeFrontendRedirectPath((string) $request->query('redirect', '/main-dashboard'));
        $state = $this->buildGoogleState($redirectPath);

        $query = http_build_query([
            'client_id' => $googleClientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function googleCallback(Request $request): RedirectResponse|JsonResponse
    {
        [$googleClientId, $googleClientSecret, $redirectUri] = $this->googleOAuthConfig();
        if ($googleClientId === '' || $googleClientSecret === '') {
            return response()->json([
                'success' => false,
                'message' => 'Google OAuth callback belum dikonfigurasi pada environment.',
            ], 503);
        }

        $statePayload = $this->verifyGoogleState((string) $request->query('state', ''));
        $redirectPath = $this->sanitizeFrontendRedirectPath((string) ($statePayload['redirect'] ?? '/main-dashboard'));

        $oauthError = (string) $request->query('error', '');
        if ($oauthError !== '') {
            return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                'auth_error' => $oauthError,
            ]));
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                'auth_error' => 'missing_authorization_code',
            ]));
        }

        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $googleClientId,
                'client_secret' => $googleClientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                Log::warning('Google OAuth token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);

                return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                    'auth_error' => 'token_exchange_failed',
                ]));
            }

            $accessToken = (string) ($tokenResponse->json('access_token') ?? '');
            if ($accessToken === '') {
                return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                    'auth_error' => 'missing_access_token',
                ]));
            }

            $googleUserResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (! $googleUserResponse->successful()) {
                Log::warning('Google OAuth userinfo failed', [
                    'status' => $googleUserResponse->status(),
                    'body' => $googleUserResponse->body(),
                ]);

                return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                    'auth_error' => 'userinfo_fetch_failed',
                ]));
            }

            $googleUser = $googleUserResponse->json();
            $email = strtolower(trim((string) ($googleUser['email'] ?? '')));
            $name = trim((string) ($googleUser['name'] ?? ''));
            $googleId = trim((string) ($googleUser['sub'] ?? ''));

            if ($email === '') {
                return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                    'auth_error' => 'missing_google_email',
                ]));
            }

            if ($name === '') {
                $name = Str::before($email, '@');
            }

            if ($this->isDatabaseUnavailable()) {
                $response = $this->googleWithFileStore($email, $name, $googleId);
                $data = $response->getData(true);

                return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                    'auth_token' => (string) ($data['token'] ?? ''),
                    'auth_user' => $this->base64UrlEncode(json_encode($data['user'] ?? [])),
                    'auth_provider' => 'google',
                ]));
            }

            $user = User::where('email', $email)->first();
            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Str::random(32),
                    'provider' => 'google',
                    'google_id' => $googleId !== '' ? $googleId : null,
                ]);
            } else {
                $user->name = $user->name ?: $name;
                $user->provider = 'google';
                if ($googleId !== '') {
                    $user->google_id = $googleId;
                }
                $user->save();
            }

            $payload = $this->normalizeUser($user);
            $token = $this->buildTokenForUser($payload);

            return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                'auth_token' => $token,
                'auth_user' => $this->base64UrlEncode(json_encode($payload)),
                'auth_provider' => 'google',
            ]));
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect($this->buildFrontendRedirectUrl($redirectPath, [
                'auth_error' => 'oauth_callback_failed',
            ]));
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
        $signature = hash_hmac('sha256', $header.'.'.$payload, $secret, true);

        return $header.'.'.$payload.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $base64 = strtr($value, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);

        return $decoded === false ? '' : $decoded;
    }

    private function googleOAuthConfig(): array
    {
        $clientId = trim((string) env('GOOGLE_CLIENT_ID', ''));
        $clientSecret = trim((string) env('GOOGLE_CLIENT_SECRET', ''));
        $redirectUri = trim((string) env('GOOGLE_REDIRECT_URI', ''));

        if ($redirectUri === '') {
            $redirectUri = url('/api/auth/google/callback');
        }

        return [$clientId, $clientSecret, $redirectUri];
    }

    private function buildGoogleState(string $redirectPath): string
    {
        $payload = $this->base64UrlEncode(json_encode([
            'redirect' => $redirectPath,
            'nonce' => Str::random(24),
            'ts' => time(),
        ]));

        $secret = (string) config('app.key', 'autospec-oauth-secret');
        $signature = hash_hmac('sha256', $payload, $secret);

        return $payload.'.'.$signature;
    }

    private function verifyGoogleState(string $state): array
    {
        if ($state === '' || ! str_contains($state, '.')) {
            return [];
        }

        [$payload, $signature] = explode('.', $state, 2);
        $secret = (string) config('app.key', 'autospec-oauth-secret');
        $expected = hash_hmac('sha256', $payload, $secret);
        if (! hash_equals($expected, $signature)) {
            return [];
        }

        $decoded = json_decode($this->base64UrlDecode($payload), true);
        if (! is_array($decoded)) {
            return [];
        }

        $issuedAt = (int) ($decoded['ts'] ?? 0);
        if ($issuedAt <= 0 || (time() - $issuedAt) > 900) {
            return [];
        }

        return $decoded;
    }

    private function sanitizeFrontendRedirectPath(string $path): string
    {
        $candidate = trim($path);
        if ($candidate === '' || ! str_starts_with($candidate, '/') || str_starts_with($candidate, '//')) {
            return '/main-dashboard';
        }

        return $candidate;
    }

    private function buildFrontendRedirectUrl(string $path, array $query): string
    {
        $sanitizedPath = $this->sanitizeFrontendRedirectPath($path);

        return $sanitizedPath.(str_contains($sanitizedPath, '?') ? '&' : '?').http_build_query($query);
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
            if (! Storage::disk('local')->exists($this->authStorePath())) {
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

        if (! $user || ! isset($user['password_hash']) || ! Hash::check($password, (string) $user['password_hash'])) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password tidak valid.',
            ], 401);
        }

        return $this->successAuthResponse($user, 'Login berhasil.');
    }

    private function googleWithFileStore(string $email, string $name, string $googleId = ''): JsonResponse
    {
        $users = $this->readAuthStore();
        $user = $this->findUserByEmail($users, $email);

        if (! $user) {
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
                'google_id' => $googleId !== '' ? $googleId : null,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];
            $users[] = $user;
            $this->writeAuthStore($users);
        } else {
            $user['provider'] = 'google';
            if ($googleId !== '') {
                $user['google_id'] = $googleId;
            }

            foreach ($users as $index => $existing) {
                if ((int) ($existing['id'] ?? 0) === (int) ($user['id'] ?? 0)) {
                    $users[$index] = array_merge($existing, $user, [
                        'updated_at' => now()->toIso8601String(),
                    ]);
                    break;
                }
            }

            $this->writeAuthStore($users);
        }

        return $this->successAuthResponse($user, 'Google Sign-In berhasil.');
    }
}
