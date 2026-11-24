/**
 * کنترلر پخش‌کننده صوتی اختصاصی قطعات، آکاردئون متن اشعار و اشتراک‌گذاری بروشور
 */
(function () {
  'use strict';

  var useFaDigits = window.BROCHURE_FA_DIGITS !== false;
  var faMap = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

  function toFaDigits(str) {
    if (!useFaDigits) return String(str);
    return String(str).replace(/\d/g, function (d) { return faMap[Number(d)]; });
  }

  function formatTime(sec) {
    if (!isFinite(sec) || sec < 0) sec = 0;
    var total = Math.floor(sec);
    var m = Math.floor(total / 60);
    var s = total % 60;
    var mm = (m < 10 ? '0' : '') + m;
    var ss = (s < 10 ? '0' : '') + s;
    return toFaDigits(mm + ':' + ss);
  }

  /* ================== ۱. پلیر صوتی هر قطعه (مطابق image-2.png) ================== */
  var trackCards = Array.prototype.slice.call(document.querySelectorAll('[data-track-card]'));
  var activeAudio = null;
  var activeCard = null;

  trackCards.forEach(function (card) {
    var playBtn = card.querySelector('[data-audio-toggle]');
    var playerRow = card.querySelector('[data-audio-player]');
    var lyricsBtn = card.querySelector('[data-lyrics-toggle]');

    // الف) مدیریت باز/بسته‌شدن کشوی «متن قطعه» (مطابق image-3.png)
    if (lyricsBtn) {
      var drawerId = lyricsBtn.getAttribute('data-lyrics-toggle');
      var drawer = drawerId ? document.getElementById(drawerId) : null;
      lyricsBtn.addEventListener('click', function () {
        if (!drawer) return;
        var isOpen = !drawer.hasAttribute('hidden');
        if (isOpen) {
          drawer.setAttribute('hidden', '');
          card.classList.remove('is-lyrics-open');
          lyricsBtn.setAttribute('aria-expanded', 'false');
        } else {
          drawer.removeAttribute('hidden');
          card.classList.add('is-lyrics-open');
          lyricsBtn.setAttribute('aria-expanded', 'true');
        }
      });
    }

    // ب) مدیریت پخش صوت و نوار زمان
    if (!playBtn || !playerRow) return;
    var audio = playerRow.querySelector('audio');
    var currEl = playerRow.querySelector('[data-curr-time]');
    var durEl = playerRow.querySelector('[data-dur-time]');
    var fillEl = playerRow.querySelector('[data-progress-fill]');
    var thumbEl = playerRow.querySelector('[data-progress-thumb]');
    var rangeEl = playerRow.querySelector('[data-progress-range]');
    if (!audio) return;

    var isSeeking = false;

    function syncProgressUI(ratio) {
      var clamped = Math.max(0, Math.min(1, ratio || 0));
      var pct = (clamped * 100).toFixed(2) + '%';
      if (fillEl) fillEl.style.width = pct;
      if (thumbEl) thumbEl.style.left = pct;
      if (rangeEl && !isSeeking) {
        rangeEl.value = String(Math.round(clamped * 1000));
      }
    }

    audio.addEventListener('loadedmetadata', function () {
      if (durEl && isFinite(audio.duration)) {
        durEl.textContent = formatTime(audio.duration);
      }
    });

    audio.addEventListener('timeupdate', function () {
      if (currEl) currEl.textContent = formatTime(audio.currentTime);
      if (!isSeeking && isFinite(audio.duration) && audio.duration > 0) {
        syncProgressUI(audio.currentTime / audio.duration);
      }
    });

    audio.addEventListener('play', function () {
      if (activeAudio && activeAudio !== audio) {
        activeAudio.pause();
      }
      if (activeCard && activeCard !== card) {
        activeCard.classList.remove('is-playing');
      }
      activeAudio = audio;
      activeCard = card;
      card.classList.add('is-playing');
    });

    audio.addEventListener('pause', function () {
      card.classList.remove('is-playing');
    });

    audio.addEventListener('ended', function () {
      card.classList.remove('is-playing');
      syncProgressUI(0);
      if (currEl) currEl.textContent = formatTime(0);
    });

    playBtn.addEventListener('click', function () {
      if (audio.paused) {
        audio.play().catch(function () {});
      } else {
        audio.pause();
      }
    });

    if (rangeEl) {
      rangeEl.addEventListener('input', function () {
        isSeeking = true;
        var r = Number(rangeEl.value) / 1000;
        if (fillEl) fillEl.style.width = (r * 100).toFixed(2) + '%';
        if (thumbEl) thumbEl.style.left = (r * 100).toFixed(2) + '%';
        if (currEl && isFinite(audio.duration) && audio.duration > 0) {
          currEl.textContent = formatTime(r * audio.duration);
        }
      });

      rangeEl.addEventListener('change', function () {
        var r = Number(rangeEl.value) / 1000;
        if (isFinite(audio.duration) && audio.duration > 0) {
          audio.currentTime = r * audio.duration;
        }
        isSeeking = false;
      });
    }
  });

  /* ================== ۲. اشتراک‌گذاری بروشور ================== */
  var shareModal = document.getElementById('share-modal');
  var shareTriggers = document.querySelectorAll('[data-share-trigger]');
  var shareCloseBtns = document.querySelectorAll('[data-share-close]');
  var copyBtn = document.getElementById('share-copy-btn');
  var urlInput = document.getElementById('share-url-input');
  var tgLink = document.getElementById('share-tg');
  var waLink = document.getElementById('share-wa');

  function openShareFallback() {
    if (!shareModal) return;
    var shareUrl = window.location.href;
    var shareTitle = window.BROCHURE_SHARE_TITLE || document.title;
    if (urlInput) urlInput.value = shareUrl;
    if (tgLink) tgLink.href = 'https://t.me/share/url?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareTitle);
    if (waLink) waLink.href = 'https://wa.me/?text=' + encodeURIComponent(shareTitle + '\n' + shareUrl);
    shareModal.removeAttribute('hidden');
  }

  shareTriggers.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var shareData = {
        title: window.BROCHURE_SHARE_TITLE || document.title,
        text: document.title,
        url: window.location.href
      };
      if (navigator.share) {
        navigator.share(shareData).catch(function (err) {
          if (err && err.name !== 'AbortError') openShareFallback();
        });
      } else {
        openShareFallback();
      }
    });
  });

  shareCloseBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (shareModal) shareModal.setAttribute('hidden', '');
    });
  });

  if (shareModal) {
    shareModal.addEventListener('click', function (e) {
      if (e.target === shareModal) shareModal.setAttribute('hidden', '');
    });
  }

  if (copyBtn && urlInput) {
    copyBtn.addEventListener('click', function () {
      urlInput.select();
      var copied = false;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(urlInput.value).then(function () {
          copyBtn.textContent = 'کپی شد ✓';
          setTimeout(function () { copyBtn.textContent = 'کپی لینک'; }, 2000);
        });
        copied = true;
      }
      if (!copied) {
        document.execCommand('copy');
        copyBtn.textContent = 'کپی شد ✓';
        setTimeout(function () { copyBtn.textContent = 'کپی لینک'; }, 2000);
      }
    });
  }
})();
