import React, { useState } from 'react';
import supabase from '@/lib/supabaseClient';

export default function Auth() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

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

  return (
    <div className="min-h-screen flex items-center justify-center bg-[#1E1E1E] p-6">
      <form className="w-full max-w-md bg-[#1E1E1E] text-[#F7F8F0] p-6 rounded-lg shadow-md" onSubmit={handleLogin}>
        <h2 className="text-2xl font-semibold mb-4 text-[#F7F8F0]">Masuk / Daftar</h2>

        <label className="block mb-2 text-sm">Email</label>
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          className="w-full mb-4 px-3 py-2 rounded border border-gray-700 bg-transparent text-[#F7F8F0] focus:outline-none focus:ring-2 focus:ring-[#1B3C53]"
        />

        <label className="block mb-2 text-sm">Password</label>
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          className="w-full mb-4 px-3 py-2 rounded border border-gray-700 bg-transparent text-[#F7F8F0] focus:outline-none focus:ring-2 focus:ring-[#1B3C53]"
        />

        <div className="flex gap-3">
          <button
            type="submit"
            onClick={handleLogin}
            disabled={loading}
            className="flex-1 bg-[#1B3C53] hover:opacity-90 text-[#F7F8F0] font-medium py-2 px-4 rounded"
          >
            {loading ? 'Memproses...' : 'Login'}
          </button>

          <button
            type="button"
            onClick={handleSignup}
            disabled={loading}
            className="flex-1 bg-transparent border border-[#1B3C53] hover:bg-[#1B3C53] hover:text-[#F7F8F0] text-[#F7F8F0] font-medium py-2 px-4 rounded"
          >
            {loading ? 'Memproses...' : 'Daftar'}
          </button>
        </div>

        <div className="mt-4" aria-live="polite">
          {message && <p className="text-green-400">{message}</p>}
          {error && <p className="text-red-400">{error}</p>}
        </div>
      </form>
    </div>
  );
}
