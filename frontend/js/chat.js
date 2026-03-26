// ── UUID生成 ──────────────────────────────────────────────
function generateUUID() {
  if (crypto.randomUUID) return crypto.randomUUID();
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random() * 16 | 0;
    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
  });
}

// ── 定数・設定 ────────────────────────────────────────────
const COACH_NAMES = {
  narrative: 'ナラティブコーチ',
  grow: 'GROWコーチ',
  position: 'ポジションチェンジコーチ',
};

const WELCOME_MESSAGES = {
  narrative: 'はじめまして。今日はどんなことをお話ししますか？',
  grow: 'こんにちは。今日のセッションで何を持ち帰りたいですか？',
  position: 'こんにちは。今日はどんなことが気になっていますか？',
};

// ── 状態 ──────────────────────────────────────────────────
const params = new URLSearchParams(window.location.search);
const coachId = params.get('coach') || 'narrative';

let sessionId = generateUUID();
localStorage.setItem('coaching_session_id', sessionId);

let messages = [];

// ── DOM参照 ───────────────────────────────────────────────
const coachNameEl    = document.getElementById('coach-name');
const messagesEl     = document.getElementById('messages');
const userInputEl    = document.getElementById('user-input');
const sendBtn        = document.getElementById('send-btn');
const endBtn         = document.getElementById('end-btn');
const deleteBtn      = document.getElementById('delete-btn');
const crisBanner     = document.getElementById('crisis-banner');
const crisCloseBtn   = document.getElementById('crisis-close-btn');
const summaryModal   = document.getElementById('summary-modal');
const modalOverlay   = document.getElementById('modal-overlay');
const modalCloseBtn  = document.getElementById('modal-close-btn');

// ── ユーティリティ ────────────────────────────────────────
function setLoading(isLoading) {
  sendBtn.disabled = isLoading;
  sendBtn.textContent = isLoading ? '送信中...' : '送信';
}

function appendMessage(role, text) {
  const isUser = role === 'user';
  const wrapper = document.createElement('div');
  wrapper.classList.add('message', isUser ? 'message-user' : 'message-assistant');

  const label = document.createElement('div');
  label.classList.add('message-label');
  label.textContent = isUser ? 'あなた' : 'コーチ';

  const bubble = document.createElement('div');
  bubble.classList.add('message-bubble');
  bubble.textContent = text;

  wrapper.appendChild(label);
  wrapper.appendChild(bubble);
  messagesEl.appendChild(wrapper);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

function updatePhaseBar(phase) {
  const items = document.querySelectorAll('#phase-bar .phase-item');
  items.forEach(item => {
    item.classList.remove('active');
    if (String(item.dataset.phase) === String(phase)) {
      item.classList.add('active');
    }
  });
}

// ── メッセージ送信 ────────────────────────────────────────
async function sendMessage() {
  const userMessage = userInputEl.value.trim();
  if (!userMessage) return;

  userInputEl.value = '';
  messages.push({ role: 'user', content: userMessage });
  appendMessage('user', userMessage);

  setLoading(true);

  try {
    const response = await api.post('/api/chat', {
      session_id: sessionId,
      coach_id: coachId,
      messages: messages,
      user_message: userMessage,
    });

    if (response.is_crisis) {
      messagesEl.style.display = 'none';
      crisBanner.style.display = 'flex';
    } else {
      const aiText = response.reply || '';
      appendMessage('assistant', aiText);
      messages.push({ role: 'assistant', content: aiText });

      if (response.phase !== undefined && response.phase !== 'unknown') {
        updatePhaseBar(response.phase);
      }
    }

    // セッション保存
    await api.post('/api/session/' + sessionId, {
      role: 'user',
      content: userMessage,
      coach_id: coachId,
    });

  } catch (err) {
    console.error('sendMessage error:', err);
    appendMessage('assistant', 'エラーが発生しました。もう一度お試しください。');
  } finally {
    setLoading(false);
  }
}

// ── セッションを終える ────────────────────────────────────
async function endSession() {
  setLoading(true);

  try {
    const response = await api.post('/api/summary', {
      session_id: sessionId,
      coach_id: coachId,
      messages: messages,
    });

    document.getElementById('summary-theme').textContent    = response.theme    || '—';
    document.getElementById('summary-insight').textContent  = response.insight  || '—';
    document.getElementById('summary-obstacle').textContent = response.obstacle || '—';
    document.getElementById('summary-action').textContent   = response.action   || '—';
    document.getElementById('summary-strength').textContent = response.strength || '—';

    summaryModal.style.display = 'flex';
  } catch (err) {
    console.error('endSession error:', err);
    alert('サマリーの取得に失敗しました。');
  } finally {
    setLoading(false);
  }
}

// ── 履歴を削除 ────────────────────────────────────────────
async function deleteHistory() {
  if (!confirm('セッション履歴を削除しますか？')) return;

  try {
    await api.delete('/api/session/' + sessionId);
  } catch (err) {
    console.error('deleteHistory error:', err);
  }

  localStorage.removeItem('coaching_session_id');
  sessionId = generateUUID();
  localStorage.setItem('coaching_session_id', sessionId);

  messages = [];
  messagesEl.innerHTML = '';

  // 危機バナーが表示されていた場合は元に戻す
  crisBanner.style.display = 'none';
  messagesEl.style.display = '';

  appendMessage('assistant', WELCOME_MESSAGES[coachId] || WELCOME_MESSAGES['narrative']);
}

// ── イベントリスナー ──────────────────────────────────────
sendBtn.addEventListener('click', sendMessage);

userInputEl.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

endBtn.addEventListener('click', endSession);

deleteBtn.addEventListener('click', deleteHistory);

crisCloseBtn.addEventListener('click', () => {
  crisBanner.style.display = 'none';
  messagesEl.style.display = '';
});

modalCloseBtn.addEventListener('click', () => {
  summaryModal.style.display = 'none';
});

modalOverlay.addEventListener('click', () => {
  summaryModal.style.display = 'none';
});

// ── BFCache対策 ───────────────────────────────────────────
// ブラウザの戻る/進むでキャッシュから復元されたときは強制リロードして
// 古いsessionId・messagesが使われないようにする
window.addEventListener('pageshow', (event) => {
  if (event.persisted) {
    window.location.reload();
  }
});

// ── 初期化 ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  coachNameEl.textContent = COACH_NAMES[coachId] || COACH_NAMES['narrative'];
  appendMessage('assistant', WELCOME_MESSAGES[coachId] || WELCOME_MESSAGES['narrative']);
});
