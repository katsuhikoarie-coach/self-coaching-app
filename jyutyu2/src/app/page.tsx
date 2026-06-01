'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';

export default function LoginPage() {
  const { user, isLoading, login } = useAuth();
  const [email, setEmail]     = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError]     = useState('');
  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    if (!isLoading && user) window.location.href = '/home/';
  }, [user, isLoading]);

  if (isLoading) return null;

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const res = await fetch('/api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        throw new Error(data.error || 'ログインに失敗しました');
      }

      login({
        id:         String(data.id),
        name:       data.contact_name || data.email,
        email:      data.email,
        fcName:     data.fc_name,
        address:    '',
        isDemo:     false,
        centerType: data.center_type || 'FC',
      });

      window.location.href = '/home/';
    } catch (err) {
      setError(err instanceof Error ? err.message : 'ログインに失敗しました');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-stone-50 flex items-center justify-center p-4">
      <div className="w-full max-w-sm">

        {/* ロゴ */}
        <div className="text-center mb-8 select-none">
          <div className="w-16 h-16 mx-auto mb-3 bg-amber-50 border border-amber-200 rounded-full flex items-center justify-center">
            <span className="text-3xl">🌿</span>
          </div>
          <h1 className="text-2xl font-bold text-stone-800 tracking-widest">朝霧ヤマノ</h1>
          <p className="text-amber-700 font-medium tracking-wider mt-1 text-sm">センター受注システム</p>
        </div>

        {/* ログインカード */}
        <div className="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
          <form onSubmit={handleLogin} className="flex flex-col gap-4">
            <div>
              <label className="block text-xs text-stone-500 mb-1">メールアドレス</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="example@gmail.com"
                required
                autoComplete="email"
                className="w-full border border-stone-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div>
              <label className="block text-xs text-stone-500 mb-1">パスワード</label>
              <div style={{ position: 'relative' }}>
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="パスワードを入力"
                  required
                  autoComplete="current-password"
                  className="w-full border border-stone-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                  style={{ paddingRight: '2.8rem' }}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  style={{
                    position: 'absolute',
                    right: '0.6rem',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    background: 'none',
                    border: 'none',
                    cursor: 'pointer',
                    color: '#999',
                    fontSize: '1.2rem',
                    lineHeight: 1,
                    padding: '0',
                  }}
                  aria-label={showPassword ? 'パスワードを隠す' : 'パスワードを表示'}
                >
                  {showPassword ? '🙈' : '👁️'}
                </button>
              </div>
            </div>

            {error && (
              <p className="text-sm text-red-600 text-center leading-relaxed whitespace-pre-wrap">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white rounded-xl py-3 font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? 'ログイン中...' : 'ログイン'}
            </button>
          </form>
        </div>

        <p className="text-center text-xs text-stone-300 mt-6">
          朝霧ヤマノ © {new Date().getFullYear()}
        </p>
      </div>
    </main>
  );
}
