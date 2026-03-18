/**
 * coach_select.js
 * コーチ選択画面のインタラクションを管理する
 */

'use strict';

// 選択中のコーチID（null = 未選択）
let selectedCoachId = null;

document.addEventListener('DOMContentLoaded', () => {
  initMoodChips();
  initCoachCards();
  initStartButton();
});

/**
 * 気分チップの初期化
 * クリックで selected トグル + 関連カードへ highlighted を付与/除去
 */
function initMoodChips() {
  const chips = document.querySelectorAll('.chip');

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      chip.classList.toggle('selected');
      updateCardHighlights();
    });
  });
}

/**
 * 選択中の気分チップから関連するコーチIDを集め、
 * 各コーチカードの highlighted クラスを更新する
 */
function updateCardHighlights() {
  const selectedChips = document.querySelectorAll('.chip.selected');

  // 選択中チップに紐づくコーチIDを収集（重複なし）
  const highlightedCoachIds = new Set();
  selectedChips.forEach((chip) => {
    const moods = chip.dataset.moods ? chip.dataset.moods.split(' ') : [];
    moods.forEach((id) => highlightedCoachIds.add(id));
  });

  // 全カードの highlighted を更新
  const cards = document.querySelectorAll('.coach-card');
  cards.forEach((card) => {
    const coachId = getCoachIdFromCard(card);
    if (highlightedCoachIds.has(coachId)) {
      card.classList.add('highlighted');
    } else {
      card.classList.remove('highlighted');
    }
  });
}

/**
 * コーチカードの初期化
 * 「このコーチと話す」ボタンクリックで selected を排他的に付与
 */
function initCoachCards() {
  const selectButtons = document.querySelectorAll('.btn-select');

  selectButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const coachId = btn.dataset.coachId;
      selectCoach(coachId);
    });
  });
}

/**
 * 指定したコーチIDのカードを selected 状態にする（他は解除）
 * @param {string} coachId
 */
function selectCoach(coachId) {
  // 全カードの selected を除去
  document.querySelectorAll('.coach-card').forEach((card) => {
    card.classList.remove('selected');
  });

  // 対象カードに selected を付与
  const targetCard = document.getElementById('card-' + coachId);
  if (targetCard) {
    targetCard.classList.add('selected');
  }

  selectedCoachId = coachId;
}

/**
 * 「セッションを始める」ボタンの初期化
 */
function initStartButton() {
  const startBtn = document.getElementById('start-btn');
  if (!startBtn) return;

  startBtn.addEventListener('click', () => {
    if (!selectedCoachId) {
      alert('コーチを選んでください');
      return;
    }
    window.location.href = 'chat.html?coach=' + selectedCoachId;
  });
}

/**
 * カード要素からコーチIDを取得するユーティリティ
 * カードのidは "card-{coachId}" の形式
 * @param {HTMLElement} card
 * @returns {string}
 */
function getCoachIdFromCard(card) {
  return card.id.replace('card-', '');
}
