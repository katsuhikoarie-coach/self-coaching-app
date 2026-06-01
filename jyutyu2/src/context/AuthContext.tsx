'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';

export interface FcUser {
  id: string;
  name: string;
  email: string;
  fcName: string;
  address: string;
  isDemo: boolean;
  centerType: string;  // 'FC' | 'BC' | '販社'
}

interface AuthContextType {
  user: FcUser | null;
  isLoading: boolean;
  login: (user: FcUser) => void;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<FcUser | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetch('/api/get_session.php', { credentials: 'include' })
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data?.ok) {
          setUser({
            id:         String(data.id),
            name:       data.contact_name || data.email,
            email:      data.email,
            fcName:     data.fc_name,
            address:    '',
            isDemo:     false,
            centerType: data.center_type || 'FC',
          });
        }
      })
      .catch(() => {})
      .finally(() => setIsLoading(false));
  }, []);

  const login = useCallback((u: FcUser) => {
    setUser(u);
  }, []);

  const logout = useCallback(async () => {
    await fetch('/api/logout.php', { credentials: 'include', method: 'POST' }).catch(() => {});
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
