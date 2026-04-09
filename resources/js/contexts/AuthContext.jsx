import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { loginWithPassword, registerWithPassword, signInWithGoogle } from '../services/authService';

const AUTH_SESSION_KEY = 'autospec-auth-session';
const AUTH_TOKEN_KEY = 'autospec-auth-token';

const AuthContext = createContext(null);

function readPersistedSession() {
  if (typeof window === 'undefined') {
    return { token: '', user: null };
  }

  try {
    const raw = window.localStorage.getItem(AUTH_SESSION_KEY);
    if (!raw) {
      return { token: '', user: null };
    }

    const parsed = JSON.parse(raw);
    return {
      token: parsed?.token || '',
      user: parsed?.user || null,
    };
  } catch {
    return { token: '', user: null };
  }
}

function persistSession(session) {
  if (typeof window === 'undefined') {
    return;
  }

  if (!session?.token) {
    window.localStorage.removeItem(AUTH_SESSION_KEY);
    window.localStorage.removeItem(AUTH_TOKEN_KEY);
    return;
  }

  window.localStorage.setItem(AUTH_SESSION_KEY, JSON.stringify(session));
  window.localStorage.setItem(AUTH_TOKEN_KEY, session.token);
}

export function AuthProvider({ children }) {
  const initial = readPersistedSession();
  const [token, setToken] = useState(initial.token);
  const [user, setUser] = useState(initial.user);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    persistSession({ token, user });
  }, [token, user]);

  const applySession = (session) => {
    setToken(session?.token || '');
    setUser(session?.user || null);
  };

  const login = async ({ email, password }) => {
    setIsLoading(true);
    try {
      const session = await loginWithPassword({ email, password });
      applySession(session);
      return session;
    } finally {
      setIsLoading(false);
    }
  };

  const register = async ({ email, password }) => {
    setIsLoading(true);
    try {
      const session = await registerWithPassword({ email, password });
      applySession(session);
      return session;
    } finally {
      setIsLoading(false);
    }
  };

  const googleSignIn = async (payload = {}) => {
    setIsLoading(true);
    try {
      const session = await signInWithGoogle(payload);
      applySession(session);
      return session;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    setToken('');
    setUser(null);
  };

  const value = useMemo(() => ({
    user,
    token,
    isAuthenticated: Boolean(token),
    isLoading,
    login,
    register,
    googleSignIn,
    logout,
  }), [user, token, isLoading]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
