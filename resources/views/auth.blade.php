<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AutoSpec - Login</title>
  <style>
    :root {
      --bg: #1E1E1E;
      --card: #27333d;
      --primary: #1B3C53;
      --text: #F7F8F0;
      --muted: #c9d0c5;
      --border: #3d5567;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: radial-gradient(1100px 700px at 20% -10%, #233746 0%, var(--bg) 50%), var(--bg);
      color: var(--text);
      font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif;
      padding: 24px;
    }
    .card {
      width: 100%;
      max-width: 430px;
      background: linear-gradient(165deg, #2f3c47 0%, var(--card) 60%);
      border: 1px solid rgba(247, 248, 240, 0.16);
      border-radius: 16px;
      box-shadow: 0 22px 55px rgba(0, 0, 0, 0.35);
      padding: 28px;
    }
    .brand { text-align: center; margin-bottom: 20px; }
    .brand img { width: 220px; max-width: 100%; height: auto; margin-bottom: 10px; }
    .title { margin: 0; font-size: 28px; font-weight: 700; }
    .subtitle { margin: 8px 0 0; color: var(--muted); font-size: 14px; }
    label { display: block; margin: 12px 0 8px; font-size: 13px; font-weight: 600; }
    input {
      width: 100%;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: #202932;
      color: var(--text);
      padding: 12px;
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease;
    }
    input::placeholder { color: #9da8b0; }
    input:focus {
      border-color: #4f7593;
      box-shadow: 0 0 0 3px rgba(27, 60, 83, 0.35);
    }
    .btn-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
    button {
      border: 0;
      border-radius: 10px;
      padding: 12px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .16s ease, opacity .2s ease, background .2s ease;
    }
    button:hover { transform: translateY(-1px); }
    button:disabled { opacity: .65; cursor: not-allowed; transform: none; }
    .btn-login { background: var(--primary); color: var(--text); }
    .btn-login:hover { background: #234c6a; }
    .btn-signup { background: transparent; color: var(--text); border: 1px solid var(--primary); }
    .btn-signup:hover { background: var(--primary); }
    .divider {
      margin: 14px 0;
      display: flex;
      align-items: center;
      gap: 12px;
      color: rgba(247, 248, 240, .6);
      font-size: 12px;
    }
    .divider::before,
    .divider::after {
      content: '';
      height: 1px;
      flex: 1;
      background: rgba(247, 248, 240, .2);
    }
    .btn-google {
      width: 100%;
      border: 1px solid rgba(247, 248, 240, .75);
      border-radius: 10px;
      padding: 12px;
      background: transparent;
      color: var(--text);
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s ease, color .2s ease, transform .16s ease;
    }
    .btn-google:hover {
      background: #F7F8F0;
      color: #1E1E1E;
      transform: translateY(-1px);
    }
    .msg { min-height: 20px; margin-top: 14px; font-size: 13px; }
    .msg.error { color: #ff9999; }
    .msg.ok { color: #99eab7; }
  </style>
</head>
<body>
  <form
    class="card"
    id="auth-form"
    data-supabase-url="{{ env('VITE_SUPABASE_URL') }}"
    data-supabase-key="{{ env('VITE_SUPABASE_ANON_KEY') }}"
  >
    <div class="brand">
      <img src="/images/autospec-logo.png" alt="AutoSpec" onerror="this.style.display='none'" />
      <h1 class="title">Masuk / Daftar</h1>
      <p class="subtitle">Lanjutkan ke dashboard AutoSpec</p>
    </div>

    <label for="email">Email</label>
    <input id="email" type="email" placeholder="you@example.com" required />

    <label for="password">Password</label>
    <input id="password" type="password" placeholder="Minimal 8 karakter" required />

    <div class="btn-row">
      <button id="btn-login" class="btn-login" type="submit">Login</button>
      <button id="btn-signup" class="btn-signup" type="button">Halaman Daftar</button>
    </div>

    <div class="divider">atau</div>

    <button id="btn-google" class="btn-google" type="button">
      <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#EA4335" d="M24 9.5c3.3 0 6.3 1.2 8.7 3.3l6.5-6.5C35.2 2.7 29.9.5 24 .5 14.8.5 6.9 5.8 3 13.5l7.9 6.1C12.7 13.6 17.8 9.5 24 9.5z" />
        <path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-2.8-.4-4.1H24v7.8h12.7c-.2 1.9-1.5 4.8-4.3 6.7l7.3 5.6c4.4-4.1 6.4-10 6.4-16z" />
        <path fill="#FBBC05" d="M10.9 28.4c-.5-1.3-.8-2.8-.8-4.4s.3-3.1.8-4.4L3 13.5A23.8 23.8 0 0 0 .5 24c0 3.8.9 7.4 2.5 10.5l7.9-6.1z" />
        <path fill="#34A853" d="M24 47.5c6 0 11-2 14.7-5.4l-7.3-5.6c-2 1.4-4.4 2.3-7.4 2.3-6.2 0-11.3-4.1-13.1-10L3 34.9C6.9 42.2 14.8 47.5 24 47.5z" />
      </svg>
      <span>Login with Google</span>
    </button>

    <p id="msg" class="msg" aria-live="polite"></p>
  </form>

  <script type="module">
    import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm';

    const emailEl = document.getElementById('email');
    const passwordEl = document.getElementById('password');
    const formEl = document.getElementById('auth-form');
    const loginBtn = document.getElementById('btn-login');
    const signupBtn = document.getElementById('btn-signup');
    const googleBtn = document.getElementById('btn-google');
    const msgEl = document.getElementById('msg');

    const SUPABASE_URL = formEl.dataset.supabaseUrl || '';
    const SUPABASE_KEY = formEl.dataset.supabaseKey || '';
    const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);

    function setLoading(v) {
      loginBtn.disabled = v;
      signupBtn.disabled = v;
      loginBtn.textContent = v ? 'Memproses...' : 'Login';
      signupBtn.textContent = v ? 'Mohon tunggu...' : 'Halaman Daftar';
    }

    function setMsg(text, isError = false) {
      msgEl.textContent = text || '';
      msgEl.className = `msg ${isError ? 'error' : 'ok'}`;
    }

    function normalizeAuthError(message) {
      const raw = String(message || '');
      if (!raw) return 'Terjadi kesalahan. Coba lagi.';
      const lowered = raw.toLowerCase();

      if (lowered.includes('invalid login credentials')) {
        return 'Email atau password tidak valid.';
      }
      if (lowered.includes('anonymous sign-ins are disabled')) {
        return 'Login anonim nonaktif. Gunakan email dan password yang terdaftar.';
      }
      if (lowered.includes('email not confirmed')) {
        return 'Email belum terverifikasi. Silakan cek inbox untuk verifikasi.';
      }
      if (lowered.includes('too many requests')) {
        return 'Terlalu banyak percobaan. Coba lagi beberapa saat.';
      }

      return raw;
    }

    function validateInput() {
      const email = emailEl.value.trim();
      const password = passwordEl.value;

      if (!email || !password) {
        setMsg('Email dan password wajib diisi.', true);
        return false;
      }

      if (!/^\S+@\S+\.\S+$/.test(email)) {
        setMsg('Format email tidak valid.', true);
        return false;
      }

      if (password.length < 8) {
        setMsg('Password minimal 8 karakter.', true);
        return false;
      }

      return true;
    }

    async function doLogin(e) {
      e.preventDefault();
      setMsg('');
      if (!validateInput()) return;

      setMsg('Memverifikasi akun...', false);
      setLoading(true);
      const { error } = await supabase.auth.signInWithPassword({
        email: emailEl.value.trim(),
        password: passwordEl.value,
      });
      setLoading(false);
      if (error) return setMsg(normalizeAuthError(error.message), true);
      setMsg('Login berhasil. Mengarahkan...');
      window.location.href = '/dashboard';
    }

    formEl.addEventListener('submit', doLogin);
    signupBtn.addEventListener('click', () => {
      window.location.href = '/register';
    });

    googleBtn.addEventListener('click', () => {
      // UI only for now
      console.log('Google Login clicked');
      setMsg('Google Login clicked (UI only).', false);
    });
  </script>
</body>
</html>
