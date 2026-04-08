import React from 'react';
import Auth from './components/Auth';
import supabase from './lib/supabaseClient';

export default function App() {
  // Debug render log
  // eslint-disable-next-line no-console
  console.log('App is rendering');
  // Runtime check for Vite env vars
  const url = import.meta?.env?.VITE_SUPABASE_URL || process.env.VITE_SUPABASE_URL;
  const key = import.meta?.env?.VITE_SUPABASE_ANON_KEY || process.env.VITE_SUPABASE_ANON_KEY;
  if (!url || !key) {
    // Warn in console for developers
    // eslint-disable-next-line no-console
    console.warn('VITE_SUPABASE_URL and/or VITE_SUPABASE_ANON_KEY are not set. Supabase auth may fail.');
  }

  return (
    <div className="bg-dark-bg text-as-text min-h-screen">
      <Auth />
      <div className="fixed top-4 right-4 bg-primary-blue text-as-text px-3 py-1 rounded shadow-lg z-50">
        APP
      </div>
    </div>
  );
}
