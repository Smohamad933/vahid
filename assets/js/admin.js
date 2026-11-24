/**
 * اسکریپت تعاملی پنل مدیریت: افزودن/حذف/جابه‌جایی ردیف‌های قطعات، نوازندگان، کادر و پیش‌نمایش زنده گوگل
 */
(function () {
  'use strict';

  function bindRepeaterControls(root) {
    if (!root) return;
    root.addEventListener('click', function (e) {
      var delBtn = e.target.closest('[data-remove-row]');
      if (delBtn) {
        var row = delBtn.closest('[data-rep-row]');
        if (row && confirm('آیا از حذف این مورد اطمینان دارید؟')) {
          row.remove();
        }
        return;
      }

      var upBtn = e.target.closest('[data-move-up]');
      if (upBtn) {
        var rUp = upBtn.closest('[data-rep-row]');
        if (rUp && rUp.previousElementSibling) {
          rUp.parentNode.insertBefore(rUp, rUp.previousElementSibling);
        }
        return;
      }

      var downBtn = e.target.closest('[data-move-down]');
      if (downBtn) {
        var rDn = downBtn.closest('[data-rep-row]');
        if (rDn && rDn.nextElementSibling) {
          rDn.parentNode.insertBefore(rDn.nextElementSibling, rDn);
        }
      }
    });
  }

  document.querySelectorAll('.adm-repeater').forEach(bindRepeaterControls);

  // ۱. افزودن قطعه جدید
  var addTrackBtn = document.getElementById('btn-add-track');
  var tracksRep = document.getElementById('tracks-repeater');
  if (addTrackBtn && tracksRep) {
    var nextTrkIdx = tracksRep.querySelectorAll('[data-rep-row]').length + 50;
    addTrackBtn.addEventListener('click', function () {
      var idx = nextTrkIdx++;
      var div = document.createElement('div');
      div.className = 'adm-rep-item';
      div.setAttribute('data-rep-row', '');
      div.innerHTML =
        '<input type="hidden" name="trk_id[' + idx + ']" value="">' +
        '<div class="adm-rep-bar">' +
          '<strong class="adm-rep-num">قطعه جدید</strong>' +
          '<div class="adm-rep-btns">' +
            '<button type="button" class="adm-mini-btn" data-move-up>↑</button>' +
            '<button type="button" class="adm-mini-btn" data-move-down>↓</button>' +
            '<button type="button" class="adm-mini-btn adm-mini-del" data-remove-row>حذف قطعه</button>' +
          '</div>' +
        '</div>' +
        '<div class="adm-grid-3">' +
          '<div class="adm-field"><label>نام قطعه *</label><input type="text" name="trk_title[' + idx + ']" required placeholder="نام قطعه..."></div>' +
          '<div class="adm-field"><label>آهنگساز (اختیاری)</label><input type="text" name="trk_composer[' + idx + ']"></div>' +
          '<div class="adm-field"><label>شاعر / شعر (اختیاری)</label><input type="text" name="trk_poet[' + idx + ']"></div>' +
        '</div>' +
        '<div class="adm-grid-2">' +
          '<div class="adm-field"><label>تنظیم‌کننده (اختیاری)</label><input type="text" name="trk_arranger[' + idx + ']"></div>' +
          '<div class="adm-field"><label>توضیح / نوازنده (مثلاً بی‌کلام یا به نوازندگی ...)</label><input type="text" name="trk_note[' + idx + ']"></div>' +
        '</div>' +
        '<div class="adm-audio-box">' +
          '<div class="adm-grid-2">' +
            '<div class="adm-field"><label>آپلود فایل صوتی قطعه</label><input type="file" name="trk_audio_file[' + idx + ']" accept=".mp3,.m4a,.wav,.ogg,.aac,.flac,audio/*"></div>' +
            '<div class="adm-field"><label>یا آدرس مستقیم صوت</label><input type="text" dir="ltr" name="trk_audio_existing[' + idx + ']"></div>' +
          '</div>' +
          '<label class="adm-check"><input type="checkbox" name="trk_allow_dl[' + idx + ']" value="1" checked><span>نمایش دکمه دانلود کنار نوار پخش صوت</span></label>' +
        '</div>' +
        '<div class="adm-field"><label>متن شعر قطعه (هر مصرع در یک خط مجزا)</label><textarea name="trk_lyrics[' + idx + ']" rows="3"></textarea></div>';
      tracksRep.appendChild(div);
      div.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }

  // ۲. افزودن نوازنده جدید
  var addMusBtn = document.getElementById('btn-add-musician');
  var musRep = document.getElementById('musicians-repeater');
  if (addMusBtn && musRep) {
    var nextMusIdx = musRep.querySelectorAll('[data-rep-row]').length + 50;
    addMusBtn.addEventListener('click', function () {
      var idx = nextMusIdx++;
      var div = document.createElement('div');
      div.className = 'adm-rep-item';
      div.setAttribute('data-rep-row', '');
      div.innerHTML =
        '<div class="adm-grid-3">' +
          '<div class="adm-field"><label>نام هنرمند</label><input type="text" name="mus_name[' + idx + ']" placeholder="نام هنرمند..."></div>' +
          '<div class="adm-field"><label>ساز / نقش</label><input type="text" name="mus_role[' + idx + ']" placeholder="نام ساز..."></div>' +
          '<div class="adm-field adm-field-inline">' +
            '<label class="adm-check"><input type="checkbox" name="mus_hl[' + idx + ']" value="1"><span>برجسته (طلایی)</span></label>' +
            '<button type="button" class="adm-mini-btn adm-mini-del" data-remove-row>حذف</button>' +
          '</div>' +
        '</div>';
      musRep.appendChild(div);
    });
  }

  // ۳. افزودن عضو کادر اجرایی
  var addCrewBtn = document.getElementById('btn-add-crew');
  var crewRep = document.getElementById('crew-repeater');
  if (addCrewBtn && crewRep) {
    var nextCrewIdx = crewRep.querySelectorAll('[data-rep-row]').length + 50;
    addCrewBtn.addEventListener('click', function () {
      var idx = nextCrewIdx++;
      var div = document.createElement('div');
      div.className = 'adm-rep-item';
      div.setAttribute('data-rep-row', '');
      div.innerHTML =
        '<div class="adm-grid-3">' +
          '<div class="adm-field"><label>سمت / مسئولیت</label><input type="text" name="crew_role[' + idx + ']"></div>' +
          '<div class="adm-field"><label>نام شخص یا مجموعه</label><input type="text" name="crew_name[' + idx + ']"></div>' +
          '<div class="adm-field adm-field-inline"><button type="button" class="adm-mini-btn adm-mini-del" data-remove-row>حذف ردیف</button></div>' +
        '</div>';
      crewRep.appendChild(div);
    });
  }

  // ۴. پیش‌نمایش زنده SERP گوگل در تب سئو
  var tInput = document.getElementById('seo-tab-title');
  var sInput = document.getElementById('seo-title-suffix');
  var dInput = document.getElementById('seo-desc');
  var cInput = document.getElementById('seo-canonical');
  var pTitle = document.getElementById('serp-title');
  var pDesc = document.getElementById('serp-desc');
  var pUrl = document.getElementById('serp-url');

  function syncSerp() {
    if (pTitle && tInput) {
      var base = tInput.value.trim() || 'بروشور اجرای دیجیتال';
      var suf = sInput ? sInput.value.trim() : '';
      pTitle.textContent = suf ? (base + ' | ' + suf) : base;
    }
    if (pDesc && dInput) {
      pDesc.textContent = dInput.value.trim();
    }
    if (pUrl && cInput) {
      pUrl.textContent = cInput.value.trim() || 'https://negahm.ir/vahid-taj/';
    }
  }

  [tInput, sInput, dInput, cInput].forEach(function (el) {
    if (el) el.addEventListener('input', syncSerp);
  });
})();
