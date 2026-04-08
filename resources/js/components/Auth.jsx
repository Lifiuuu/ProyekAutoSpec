import { useState } from 'react';
import { supabase } from '../lib/supabaseClient';

export default function Auth() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [logoError, setLogoError] = useState(false);

  const handleResponse = (resError, successText) => {
    setLoading(false);
    if (resError) {
      setError(resError.message || 'Terjadi kesalahan');
      setMessage('');
    } else {
      setError('');
      setMessage(successText);
    }
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');
    setError('');
    try {
      const { error } = await supabase.auth.signInWithPassword({ email, password });
      handleResponse(error, 'Login berhasil.');
    } catch (err) {
      handleResponse(err, '');
    }
  };

  const handleSignup = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');
    setError('');
    try {
      const { error } = await supabase.auth.signUp({ email, password });
      handleResponse(error, 'Pendaftaran berhasil. Silakan cek email untuk verifikasi (jika diaktifkan).');
    } catch (err) {
      handleResponse(err, '');
    }
  };

  const handleGoogleUiOnly = () => {
    // UI only for now (no auth flow call yet)
    // eslint-disable-next-line no-console
    console.log('Google Login clicked');
  };

  return (
    <div className="min-h-screen bg-[#1E1E1E] text-[#F7F8F0] grid place-items-center px-4 py-8">
      <form
        className="w-full max-w-md rounded-xl border border-[#2a455a] bg-[#25323d] p-7 shadow-[0_18px_50px_rgba(0,0,0,0.35)]"
        onSubmit={handleLogin}
      >
        <div className="mb-6 flex flex-col items-center text-center">
          {!logoError ? (
            <img
              src="/images/autospec-logo.png"
              alt="AutoSpec"
              className="mb-3 h-12 w-auto object-contain"
              onError={() => setLogoError(true)}
            />
          ) : (
            <div className="mb-3 grid h-12 w-12 place-items-center rounded-full border border-[#36546c] bg-[#1B3C53] text-xl font-bold">
              A
            </div>
          )}
          <h2 className="text-2xl font-semibold tracking-tight">Masuk / Daftar</h2>
          <p className="mt-1 text-sm text-[#F7F8F0]/75">Lanjutkan ke dashboard AutoSpec</p>
        </div>

        <label className="mb-2 block text-sm font-medium text-[#F7F8F0]">Email</label>
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          className="mb-4 w-full rounded-lg border border-[#4c6577] bg-[#1f2a33] px-3 py-2.5 text-[#F7F8F0] placeholder:text-[#F7F8F0]/45 focus:outline-none focus:ring-2 focus:ring-[#1B3C53]"
          placeholder="you@example.com"
        />

        <label className="mb-2 block text-sm font-medium text-[#F7F8F0]">Password</label>
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          className="mb-5 w-full rounded-lg border border-[#4c6577] bg-[#1f2a33] px-3 py-2.5 text-[#F7F8F0] placeholder:text-[#F7F8F0]/45 focus:outline-none focus:ring-2 focus:ring-[#1B3C53]"
          placeholder="Masukkan password"
        />

        <div className="grid grid-cols-2 gap-3">
          <button
            type="submit"
            onClick={handleLogin}
            disabled={loading}
            className="rounded-lg bg-[#1B3C53] px-4 py-2.5 font-medium text-[#F7F8F0] transition hover:bg-[#234C6A] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? 'Memproses...' : 'Login'}
          </button>

          <button
            type="button"
            onClick={handleSignup}
            disabled={loading}
            className="rounded-lg border border-[#1B3C53] bg-transparent px-4 py-2.5 font-medium text-[#F7F8F0] transition hover:bg-[#1B3C53] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? 'Memproses...' : 'Daftar'}
          </button>
        </div>

        <div className="my-4 flex items-center gap-3" aria-hidden="true">
          <span className="h-px flex-1 bg-[#F7F8F0]/20" />
          <span className="text-xs text-[#F7F8F0]/60">atau</span>
          <span className="h-px flex-1 bg-[#F7F8F0]/20" />
        </div>

        <button
          type="button"
          onClick={handleGoogleUiOnly}
          className="flex w-full items-center justify-center gap-3 rounded-lg border border-[#F7F8F0]/70 bg-transparent px-4 py-2.5 font-medium text-[#F7F8F0] transition hover:bg-[#F7F8F0] hover:text-[#1E1E1E]"
        >
          <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.3 0 6.3 1.2 8.7 3.3l6.5-6.5C35.2 2.7 29.9.5 24 .5 14.8.5 6.9 5.8 3 13.5l7.9 6.1C12.7 13.6 17.8 9.5 24 9.5z" />
            <path fill="#4285F4" d="M46.1 24.5c0-1.6-.1-2.8-.4-4.1H24v7.8h12.7c-.2 1.9-1.5 4.8-4.3 6.7l7.3 5.6c4.4-4.1 6.4-10 6.4-16z" />
            <path fill="#FBBC05" d="M10.9 28.4c-.5-1.3-.8-2.8-.8-4.4s.3-3.1.8-4.4L3 13.5A23.8 23.8 0 0 0 .5 24c0 3.8.9 7.4 2.5 10.5l7.9-6.1z" />
            <path fill="#34A853" d="M24 47.5c6 0 11-2 14.7-5.4l-7.3-5.6c-2 1.4-4.4 2.3-7.4 2.3-6.2 0-11.3-4.1-13.1-10L3 34.9C6.9 42.2 14.8 47.5 24 47.5z" />
          </svg>
          <span>Login with Google</span>
        </button>

        <div className="mt-4 min-h-6 text-sm" aria-live="polite">
          {message && <p className="text-[#95e6b8]">{message}</p>}
          {error && <p className="text-[#ff9a9a]">{error}</p>}
        </div>
      </form>
    </div>
  );
}
