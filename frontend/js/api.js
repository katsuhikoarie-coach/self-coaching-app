/**
 * api.js - バックエンドAPIとの通信ユーティリティ
 */

const API_BASE = '';  // 同一オリジンで配信するため空文字

function getApiHeaders(extra) {
  return {
    'Content-Type': 'application/json',
    'X-API-Key': window.APP_SECRET_KEY || '',
    ...extra,
  };
}

const api = {
  /**
   * GET リクエスト
   */
  async get(path) {
    const res = await fetch(API_BASE + path, {
      method: 'GET',
      headers: getApiHeaders(),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.detail || err.error || `HTTP ${res.status}`);
    }
    return res.json();
  },

  /**
   * POST リクエスト
   */
  async post(path, body) {
    const res = await fetch(API_BASE + path, {
      method: 'POST',
      headers: getApiHeaders(),
      body: JSON.stringify(body),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.detail || err.error || `HTTP ${res.status}`);
    }
    return res.json();
  },

  /**
   * DELETE リクエスト
   */
  async delete(path) {
    const res = await fetch(API_BASE + path, {
      method: 'DELETE',
      headers: getApiHeaders(),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.detail || err.error || `HTTP ${res.status}`);
    }
    return res.json();
  },
};
