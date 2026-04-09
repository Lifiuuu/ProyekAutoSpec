import React, { useMemo, useState } from 'react';
import { useAuth } from '../../contexts/AuthContext';

function GoogleIcon() {
  return (
    <svg viewBox="0 0 48 48" className="h-5 w-5" aria-hidden="true">
      <path fill="#EA4335" d="M24 9.5c3.3 0 6.3 1.2 8.6 3.1l6.4-6.4C34.9 2.5 29.8 0 24 0 14.6 0 6.5 5.4 2.6 13.3l7.4 5.7C11.9 13.2 17.4 9.5 24 9.5z" />
      <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-2.8-.4-4.2H24v8h12.9c-.3 2-1.8 5.1-5.1 7.2l7.9 6.1c4.7-4.4 6.8-10.8 6.8-17.1z" />
      <path fill="#FBBC05" d="M10 28.6c-.6-1.8-.9-3.1-.9-4.6s.3-2.8.8-4.1l-7.5-5.8C.9 17.2 0 20.5 0 24c0 3.5.9 6.8 2.5 9.8l7.5-5.2z" />
      <path fill="#34A853" d="M24 48c6.5 0 12-2.1 16-5.8l-7.9-6.1c-2.1 1.5-4.9 2.5-8.1 2.5-6.6 0-12.1-3.7-14.1-9l-7.6 5.2C6.2 42.6 14.4 48 24 48z" />
    </svg>
  );
}

function Toast({ message, tone, onClose }) {
  const tones = {
    error: 'border-red-400/40 bg-red-500/20 text-red-100',
    success: 'border-green-400/40 bg-green-500/20 text-green-100',
    info: 'border-cyan-400/40 bg-cyan-500/20 text-cyan-100',
  };

  return (
    <div className={`pointer-events-auto flex items-start justify-between gap-4 rounded-xl border px-4 py-3 shadow-xl ${tones[tone] || tones.info}`}>
      <p className="text-sm font-medium">{message}</p>
      <button type="button" onClick={onClose} className="text-xs font-semibold opacity-80 hover:opacity-100">Tutup</button>
    </div>
  );
}

function validate(email, password) {
  if (!email.trim()) {
    return 'Email wajib diisi.';
  }

  if (!/^\S+@\S+\.\S+$/.test(email)) {
    return 'Format email tidak valid.';
  }

  if (!password.trim()) {
    return 'Password wajib diisi.';
  }

  if (password.length < 6) {
    return 'Password minimal 6 karakter.';
  }

  return '';
}

export default function AuthPage() {
  const { login, register, googleSignIn, isLoading } = useAuth();
  const [mode, setMode] = useState('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [toasts, setToasts] = useState([]);

  const title = mode === 'login' ? 'Masuk ke AutoSpec' : 'Buat Akun AutoSpec';
  const subtitle = mode === 'login'
    ? 'Lanjutkan prototyping database berbasis bahasa alami.'
    : 'Daftarkan akun untuk mengelola skema database Anda.';

  const submitLabel = useMemo(() => (mode === 'login' ? 'Login' : 'Registrasi'), [mode]);

  const pushToast = (message, tone = 'info') => {
    const id = `toast_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    setToasts((prev) => [...prev, { id, message, tone }]);

    window.setTimeout(() => {
      setToasts((prev) => prev.filter((item) => item.id !== id));
    }, 3500);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    const validationError = validate(email, password);
    if (validationError) {
      pushToast(validationError, 'error');
      return;
    }

    try {
      if (mode === 'login') {
        await login({ email, password });
      } else {
        await register({ email, password });
      }

      pushToast('Autentikasi berhasil, mengarahkan ke Dashboard...', 'success');
      if (window.location.pathname !== '/main-dashboard') {
        window.location.href = '/main-dashboard';
      }
    } catch (error) {
      const message = error?.response?.data?.message || 'Autentikasi gagal. Coba lagi.';
      pushToast(message, 'error');
    }
  };

  const handleGoogleSignIn = async () => {
    try {
      await googleSignIn({
        email: 'google-user@autospec.local',
        name: 'Google AutoSpec User',
      });
      pushToast('Google Sign-In berhasil, mengarahkan ke Dashboard...', 'success');
      if (window.location.pathname !== '/main-dashboard') {
        window.location.href = '/main-dashboard';
      }
    } catch (error) {
      const message = error?.response?.data?.message || 'Google Sign-In gagal. Coba lagi.';
      pushToast(message, 'error');
    }
  };

  return (
    <div className="min-h-screen bg-[#1E1E1E] px-4 py-8 text-[#F7F8F0]">
      <div className="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-5xl items-center justify-center">
        <div className="w-full max-w-md rounded-3xl border border-[#234C6A] bg-[#141414] p-7 shadow-[0_20px_60px_rgba(0,0,0,0.45)] sm:p-8">
          <div className="mb-7 text-center">
            <img src="/images/autospec-logo.svg" alt="AutoSpec Logo" className="mx-auto h-16 w-auto" />
            <h1 className="mt-4 text-2xl font-bold">{title}</h1>
            <p className="mt-2 text-sm text-[#F7F8F0]/75">{subtitle}</p>
          </div>

          <div className="mb-5 grid grid-cols-2 gap-2 rounded-2xl border border-[#234C6A]/60 bg-[#1E1E1E] p-1">
            <button
              type="button"
              onClick={() => setMode('login')}
              className={`rounded-xl px-3 py-2 text-sm font-semibold transition-all ${mode === 'login' ? 'bg-[#456882] text-white shadow-md' : 'text-[#F7F8F0]/70 hover:bg-[#234C6A]/35'}`}
            >
              Login
            </button>
            <button
              type="button"
              onClick={() => setMode('register')}
              className={`rounded-xl px-3 py-2 text-sm font-semibold transition-all ${mode === 'register' ? 'bg-[#456882] text-white shadow-md' : 'text-[#F7F8F0]/70 hover:bg-[#234C6A]/35'}`}
            >
              Registrasi
            </button>
          </div>

          <form className="space-y-4" onSubmit={handleSubmit}>
            <div>
              <label className="mb-2 block text-sm font-semibold text-[#F7F8F0]">Email</label>
              <input
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="w-full rounded-xl border border-[#234C6A]/70 bg-[#1E1E1E] px-4 py-3 text-[#F7F8F0] outline-none transition-all placeholder:text-[#F7F8F0]/35 focus:border-[#456882] focus:ring-2 focus:ring-[#456882]/30"
                placeholder="you@example.com"
                autoComplete="email"
              />
            </div>

            <div>
              <label className="mb-2 block text-sm font-semibold text-[#F7F8F0]">Password</label>
              <input
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                className="w-full rounded-xl border border-[#234C6A]/70 bg-[#1E1E1E] px-4 py-3 text-[#F7F8F0] outline-none transition-all placeholder:text-[#F7F8F0]/35 focus:border-[#456882] focus:ring-2 focus:ring-[#456882]/30"
                placeholder="Masukkan password"
                autoComplete={mode === 'login' ? 'current-password' : 'new-password'}
              />
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full rounded-xl bg-[#456882] px-4 py-3 text-sm font-bold text-white shadow-lg transition-all hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-55"
            >
              {isLoading ? 'Memproses...' : submitLabel}
            </button>
          </form>

          <div className="my-5 flex items-center gap-3 text-xs text-[#F7F8F0]/45">
            <div className="h-px flex-1 bg-[#234C6A]/70" />
            <span>atau</span>
            <div className="h-px flex-1 bg-[#234C6A]/70" />
          </div>

          <button
            type="button"
            onClick={handleGoogleSignIn}
            disabled={isLoading}
            className="flex w-full items-center justify-center gap-3 rounded-xl border border-[#234C6A] bg-white px-4 py-3 text-sm font-bold text-[#1f1f1f] shadow-md transition-all hover:shadow-xl hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-70"
          >
            <GoogleIcon />
            Sign in with Google
          </button>
        </div>
      </div>

      <div className="pointer-events-none fixed right-4 top-4 z-[70] flex w-full max-w-sm flex-col gap-2">
        {toasts.map((toast) => (
          <Toast
            key={toast.id}
            message={toast.message}
            tone={toast.tone}
            onClose={() => setToasts((prev) => prev.filter((item) => item.id !== toast.id))}
          />
        ))}
      </div>
    </div>
  );
}
