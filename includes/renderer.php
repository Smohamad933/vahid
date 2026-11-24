<?php
/**
 * موتور رندر HTML5 بروشور اجرای دیجیتال + متاتگ‌های کامل سئو و اسکیما
 */

if (!defined('SITE_ROOT')) {
    exit('Direct access denied.');
}

function brochure_badge_svg($icon)
{
    switch ($icon) {
        case 'calendar':
            return '<svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><rect x="2.25" y="3.25" width="13.5" height="12.5" rx="2.2" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 1.75V4.5M12.5 1.75V4.5M2.25 6.75H15.75" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="6.25" cy="10" r="0.9" fill="currentColor"/><circle cx="9" cy="10" r="0.9" fill="currentColor"/><circle cx="11.75" cy="10" r="0.9" fill="currentColor"/></svg>';
        case 'clock':
            return '<svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><circle cx="9" cy="9" r="6.75" stroke="currentColor" stroke-width="1.4"/><path d="M9 5.25V9.25L11.6 10.9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'pin':
            return '<svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="M9 15.75C9 15.75 14.25 11.15 14.25 7.25C14.25 4.35 11.9 2 9 2C6.1 2 3.75 4.35 3.75 7.25C3.75 11.15 9 15.75 9 15.75Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><circle cx="9" cy="7.25" r="2" stroke="currentColor" stroke-width="1.4"/></svg>';
        case 'share':
            return '<svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><circle cx="13.25" cy="4.25" r="2" stroke="currentColor" stroke-width="1.4"/><circle cx="4.75" cy="9" r="2" stroke="currentColor" stroke-width="1.4"/><circle cx="13.25" cy="13.75" r="2" stroke="currentColor" stroke-width="1.4"/><path d="M6.55 8.05L11.45 5.2M6.55 9.95L11.45 12.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
        default:
            return '<svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="M2 6.75C2 5.65 2.9 4.75 4 4.75H14C15.1 4.75 16 5.65 16 6.75V7.9C15.05 7.9 14.3 8.65 14.3 9.6C14.3 10.55 15.05 11.3 16 11.3V12.25C16 13.35 15.1 14.25 14 14.25H4C2.9 14.25 2 13.35 2 12.25V11.3C2.95 11.3 3.7 10.55 3.7 9.6C3.7 8.65 2.95 7.9 2 7.9V6.75Z" stroke="currentColor" stroke-width="1.4"/></svg>';
    }
}

function build_brochure_font_css(array $typo)
{
    $defaultFontUrl = asset_url('assets/fonts/default-farsi-vf.woff2');
    $css = array();
    $css[] = "@font-face {\n  font-family: 'BrochureDefaultVF';\n  src: url('{$defaultFontUrl}') format('woff2');\n  font-weight: 100 900;\n  font-style: normal;\n  font-display: swap;\n}";

    $activeStack = "'BrochureDefaultVF', Tahoma, sans-serif";
    $mode = isset($typo['mode']) ? $typo['mode'] : 'default';

    if ($mode === 'variable' && !empty($typo['variableFontUrl'])) {
        $src = asset_url($typo['variableFontUrl']);
        $fmt = detect_font_format($typo['variableFontUrl']);
        $css[] = "@font-face {\n  font-family: 'BrochureUploadedFont';\n  src: url('{$src}') format('{$fmt}');\n  font-weight: 100 900;\n  font-style: normal;\n  font-display: swap;\n}";
        $activeStack = "'BrochureUploadedFont', 'BrochureDefaultVF', Tahoma, sans-serif";
    } elseif ($mode === 'weights' && !empty($typo['weights']) && is_array($typo['weights'])) {
        $map = array('regular' => 400, 'semiBold' => 600, 'bold' => 700, 'extraBold' => 800);
        $hasAny = false;
        foreach ($map as $key => $weightNum) {
            if (!empty($typo['weights'][$key])) {
                $hasAny = true;
                $src = asset_url($typo['weights'][$key]);
                $fmt = detect_font_format($typo['weights'][$key]);
                $css[] = "@font-face {\n  font-family: 'BrochureUploadedFont';\n  src: url('{$src}') format('{$fmt}');\n  font-weight: {$weightNum};\n  font-style: normal;\n  font-display: swap;\n}";
            }
        }
        if ($hasAny) {
            $activeStack = "'BrochureUploadedFont', 'BrochureDefaultVF', Tahoma, sans-serif";
        }
    }

    $scale = isset($typo['baseScale']) ? (float)$typo['baseScale'] : 1.0;
    if ($scale < 0.85 || $scale > 1.25) $scale = 1.0;
    $ls = isset($typo['headingLetterSpacingEm']) ? (float)$typo['headingLetterSpacingEm'] : -0.045;
    $fnum = !empty($typo['forcePersianDigits']) ? "'ss01' 1, 'tnum' 0" : "normal";

    $css[] = ":root {\n  --font-brochure: {$activeStack};\n  --font-scale: {$scale};\n  --heading-ls: {$ls}em;\n  --font-feature-fa: {$fnum};\n}";
    return implode("\n", $css);
}

function build_brochure_schema(array $data, $canonicalUrl, $ogImageAbs)
{
    $seo = isset($data['seo']) ? $data['seo'] : array();
    if (empty($seo['schemaEnabled'])) {
        return '';
    }

    $tracks = (!empty($data['tracksSection']['items']) && is_array($data['tracksSection']['items']))
        ? $data['tracksSection']['items']
        : array();

    $works = array();
    foreach ($tracks as $idx => $t) {
        if (empty($t['title'])) continue;
        $work = array(
            '@type'    => 'MusicComposition',
            'position' => $idx + 1,
            'name'     => (string)$t['title']
        );
        if (!empty($t['composer'])) {
            $work['composer'] = array('@type' => 'Person', 'name' => (string)$t['composer']);
        }
        if (!empty($t['poet'])) {
            $work['lyricist'] = array('@type' => 'Person', 'name' => (string)$t['poet']);
        }
        $works[] = $work;
    }

    $tabTitle = !empty($seo['tabTitle']) ? $seo['tabTitle'] : 'بروشور اجرای دیجیتال';
    $fullTitle = !empty($seo['titleSuffix']) ? ($tabTitle . ' | ' . $seo['titleSuffix']) : $tabTitle;

    $eventNode = array(
        '@type'               => 'MusicEvent',
        '@id'                 => rtrim($canonicalUrl, '/') . '/#concert',
        'name'                => !empty($data['hero']['mainTitle']) ? $data['hero']['mainTitle'] : $fullTitle,
        'description'         => isset($seo['description']) ? $seo['description'] : '',
        'inLanguage'          => 'fa-IR',
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'startDate'           => !empty($seo['eventStartDateIso']) ? $seo['eventStartDateIso'] : '2026-11-25T18:30:00+03:30',
        'image'               => array($ogImageAbs),
        'url'                 => $canonicalUrl,
        'location'            => array(
            '@type'   => 'Place',
            'name'    => !empty($seo['eventVenueName']) ? $seo['eventVenueName'] : 'هتل پرستیژ اهواز',
            'address' => array(
                '@type'           => 'PostalAddress',
                'addressLocality' => !empty($seo['eventCity']) ? $seo['eventCity'] : 'اهواز',
                'addressCountry'  => 'IR',
                'streetAddress'   => !empty($seo['eventAddress']) ? $seo['eventAddress'] : ''
            )
        ),
        'performer'           => array(
            '@type' => 'MusicGroup',
            'name'  => !empty($seo['performerName']) ? $seo['performerName'] : 'وحید تاج'
        ),
        'organizer'           => array(
            '@type' => 'Organization',
            'name'  => !empty($seo['organizerName']) ? $seo['organizerName'] : 'آژانس تبلیغاتی نگاه',
            'url'   => !empty($seo['organizerUrl']) ? $seo['organizerUrl'] : 'https://negahm.ir'
        )
    );

    if (!empty($works)) {
        $eventNode['workPerformed'] = $works;
    }
    if (!empty($seo['ticketUrl'])) {
        $eventNode['offers'] = array(
            '@type'        => 'Offer',
            'url'          => $seo['ticketUrl'],
            'availability' => 'https://schema.org/InStock'
        );
    }

    $graph = array(
        '@context' => 'https://schema.org',
        '@graph'   => array(
            $eventNode,
            array(
                '@type'       => 'WebPage',
                '@id'         => rtrim($canonicalUrl, '/') . '/#webpage',
                'url'         => $canonicalUrl,
                'name'        => $fullTitle,
                'description' => isset($seo['description']) ? $seo['description'] : '',
                'inLanguage'  => 'fa-IR',
                'isPartOf'    => array(
                    '@type' => 'WebSite',
                    'name'  => !empty($seo['siteName']) ? $seo['siteName'] : 'نگاه مدیا',
                    'url'   => !empty($seo['organizerUrl']) ? $seo['organizerUrl'] : 'https://negahm.ir'
                ),
                'about'       => array('@id' => rtrim($canonicalUrl, '/') . '/#concert')
            ),
            array(
                '@type'           => 'BreadcrumbList',
                'itemListElement' => array(
                    array(
                        '@type'    => 'ListItem',
                        'position' => 1,
                        'name'     => 'نگاه مدیا',
                        'item'     => 'https://negahm.ir/'
                    ),
                    array(
                        '@type'    => 'ListItem',
                        'position' => 2,
                        'name'     => $tabTitle,
                        'item'     => $canonicalUrl
                    )
                )
            )
        )
    );

    return '<script type="application/ld+json">' . json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

function render_brochure_section($key, array $data, $faDigits)
{
    $preserveMinH = !empty($data['theme']['preserveFigmaMinHeights']);

    /* --------- ۱. درباره این اجرا --------- */
    if ($key === 'about') {
        $ab = isset($data['about']) ? $data['about'] : array();
        if (empty($ab['enabled'])) return '';
        $paragraphs = preg_split('/\R+/', trim((string)(isset($ab['body']) ? $ab['body'] : '')));
        $pHtml = '';
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p !== '') {
                $pHtml .= '<p>' . e(fa_num($p, $faDigits)) . '</p>';
            }
        }
        $alignCls = (isset($ab['textAlign']) && $ab['textAlign'] === 'right') ? 'align-right' : 'align-justify';
        $divHtml = !empty($ab['showDivider']) ? '<div class="card-divider"></div>' : '';

        return '
        <section id="section-about" class="b-card b-card-about" aria-labelledby="h-about">
          <div class="card-head">
            <span class="card-accent-bar" aria-hidden="true"></span>
            <h2 id="h-about" class="card-title">' . e(fa_num(isset($ab['title']) ? $ab['title'] : 'درباره این اجرا', $faDigits)) . '</h2>
          </div>
          ' . $divHtml . '
          <div class="about-body ' . $alignCls . '">' . $pHtml . '</div>
        </section>';
    }

    /* --------- ۲. فهرست قطعات اجرایی (مطابق دقیق رفرنس‌های ارسالی) --------- */
    if ($key === 'tracks') {
        $tr = isset($data['tracksSection']) ? $data['tracksSection'] : array();
        if (empty($tr['enabled'])) return '';
        $items = (!empty($tr['items']) && is_array($tr['items'])) ? $tr['items'] : array();

        $badgeHtml = '';
        $mode = isset($tr['countBadgeMode']) ? $tr['countBadgeMode'] : 'auto';
        if ($mode !== 'hidden') {
            $badgeTxt = ($mode === 'custom' && !empty($tr['customCountText']))
                ? $tr['customCountText']
                : (count($items) . ' قطعه');
            $badgeHtml = '<span class="card-count-pill">' . e(fa_num($badgeTxt, $faDigits)) . '</span>';
        }

        $listHtml = '';
        if (!empty($items)) {
            $rows = '';
            foreach ($items as $idx => $item) {
                $numFa     = fa_num($idx + 1, $faDigits);
                $title     = fa_num(isset($item['title']) ? $item['title'] : '', $faDigits);
                $composer  = trim((string)(isset($item['composer']) ? $item['composer'] : ''));
                $poet      = trim((string)(isset($item['poet']) ? $item['poet'] : ''));
                $arranger  = trim((string)(isset($item['arranger']) ? $item['arranger'] : ''));
                $note      = trim((string)(isset($item['performerNote']) ? $item['performerNote'] : ''));
                $audioRaw  = trim((string)(isset($item['audioUrl']) ? $item['audioUrl'] : ''));
                $lyricsRaw = trim((string)(isset($item['lyrics']) ? $item['lyrics'] : ''));
                $allowDl   = !isset($item['allowDownload']) || !empty($item['allowDownload']);

                $hasAudio  = ($audioRaw !== '');
                $hasLyrics = ($lyricsRaw !== '');
                $audioHref = $hasAudio ? asset_url($audioRaw) : '';

                // ساخت خط متادیتای زیر عنوان قطعه دقیقاً با برچسب‌های رفرنس (آهنگساز / شعر / تنظیم / بی‌کلام / به نوازندگی)
                $metaParts = array();
                if ($composer !== '') {
                    $metaParts[] = '<span class="trk-meta-item"><span class="trk-lbl">آهنگساز</span> <strong class="trk-val">' . e(fa_num($composer, $faDigits)) . '</strong></span>';
                }
                if ($poet !== '') {
                    $metaParts[] = '<span class="trk-meta-item"><span class="trk-lbl">شعر</span> <strong class="trk-val">' . e(fa_num($poet, $faDigits)) . '</strong></span>';
                }
                if ($arranger !== '') {
                    $metaParts[] = '<span class="trk-meta-item"><span class="trk-lbl">تنظیم</span> <strong class="trk-val">' . e(fa_num($arranger, $faDigits)) . '</strong></span>';
                }
                if ($note !== '') {
                    $metaParts[] = '<span class="trk-meta-item trk-note">' . e(fa_num($note, $faDigits)) . '</span>';
                }
                $subHtml = !empty($metaParts)
                    ? '<div class="trk-sub">' . implode('', $metaParts) . '</div>'
                    : '';

                // دکمه‌های دایره‌ای سمت چپ کارت (پخش صوت + بازکردن متن قطعه)
                $ctrlBtns = '';
                if ($hasAudio) {
                    $ctrlBtns .= '
                    <button type="button" class="trk-circle-btn trk-play-toggle" data-audio-toggle aria-label="پخش یا توقف قطعه ' . e($title) . '" title="پخش قطعه">
                      <svg class="ico-play" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M7.2 5.4L14.8 10L7.2 14.6V5.4Z" fill="currentColor"/>
                      </svg>
                      <svg class="ico-pause" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <rect x="6.2" y="5.2" width="2.6" height="9.6" rx="1" fill="currentColor"/>
                        <rect x="11.2" y="5.2" width="2.6" height="9.6" rx="1" fill="currentColor"/>
                      </svg>
                    </button>';
                }
                if ($hasLyrics) {
                    $drawerId = 'lyrics-drawer-' . ($idx + 1);
                    $ctrlBtns .= '
                    <button type="button" class="trk-circle-btn trk-lyrics-toggle" data-lyrics-toggle="' . e($drawerId) . '" aria-expanded="false" aria-controls="' . e($drawerId) . '" aria-label="نمایش متن قطعه ' . e($title) . '" title="متن قطعه">
                      <svg class="ico-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M5.5 7.75L10 12.25L14.5 7.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>';
                }
                $ctrlWrap = ($ctrlBtns !== '') ? '<div class="trk-actions">' . $ctrlBtns . '</div>' : '';

                // نوار پلیر صوتی در صورت وجود فایل صوتی (دقیقاً مطابق image-2.png با چیدمان چپ‌به‌راست زمان، نوار پیشرفت حلقه‌دار و دکمه دانلود)
                $playerHtml = '';
                if ($hasAudio) {
                    $dlBtn = $allowDl ? '
                      <a class="trk-dl-btn" href="' . e($audioHref) . '" download aria-label="دانلود فایل صوتی ' . e($title) . '" title="دانلود فایل صوتی">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                          <path d="M10 4.2V12.4M10 12.4L6.8 9.2M10 12.4L13.2 9.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M4.8 14.8H15.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                      </a>' : '';

                    $playerHtml = '
                    <div class="trk-player-row" dir="ltr" data-audio-player>
                      <audio preload="metadata" src="' . e($audioHref) . '"></audio>
                      <span class="trk-time" data-curr-time>۰۰:۰۰</span>
                      <div class="trk-progress-shell">
                        <div class="trk-progress-rail">
                          <div class="trk-progress-fill" data-progress-fill style="width:0%"></div>
                          <span class="trk-progress-thumb" data-progress-thumb style="left:0%"></span>
                        </div>
                        <input type="range" class="trk-progress-input" data-progress-range min="0" max="1000" value="0" step="1" aria-label="نوار زمان قطعه">
                      </div>
                      <span class="trk-time" data-dur-time>۰۰:۰۰</span>
                      ' . $dlBtn . '
                    </div>';
                }

                // کشوی متن قطعه با خط‌چین جداکننده بالا و مصرع‌های یکی‌درمیان مطابق image-3.png
                $lyricsHtml = '';
                if ($hasLyrics) {
                    $drawerId = 'lyrics-drawer-' . ($idx + 1);
                    $rawLines = preg_split('/\R+/', $lyricsRaw);
                    $versesHtml = '';
                    $lineIndex = 0;
                    foreach ($rawLines as $ln) {
                        $ln = trim($ln);
                        if ($ln === '') continue;
                        $lineIndex++;
                        $toneClass = ($lineIndex % 2 === 1) ? 'lyric-line-soft' : 'lyric-line-bright';
                        $versesHtml .= '<p class="trk-lyric-line ' . $toneClass . '">' . e(fa_num($ln, $faDigits)) . '</p>';
                    }
                    $lyricsHtml = '
                    <div id="' . e($drawerId) . '" class="trk-lyrics-drawer" hidden>
                      <div class="trk-lyrics-label">متن قطعه</div>
                      <div class="trk-lyrics-verses">' . $versesHtml . '</div>
                    </div>';
                }

                $rows .= '
                <li class="trk-card' . ($hasAudio ? ' has-audio' : '') . ($hasLyrics ? ' has-lyrics' : '') . '" data-track-card>
                  <div class="trk-top">
                    <div class="trk-right">
                      <span class="trk-badge" aria-hidden="true">' . e($numFa) . '</span>
                      <div class="trk-titles">
                        <h3 class="trk-name">' . e($title) . '</h3>
                        ' . $subHtml . '
                      </div>
                    </div>
                    ' . $ctrlWrap . '
                  </div>
                  ' . $playerHtml . '
                  ' . $lyricsHtml . '
                </li>';
            }
            $listHtml = '<ol class="trk-list">' . $rows . '</ol>';
        } elseif (!empty($tr['emptyPlaceholder'])) {
            $listHtml = '<p class="card-placeholder">' . e(fa_num($tr['emptyPlaceholder'], $faDigits)) . '</p>';
        }

        $minHCls = ($preserveMinH && empty($items)) ? ' figma-min-tracks' : '';
        return '
        <section id="section-tracks" class="b-card b-card-tracks' . $minHCls . '" aria-labelledby="h-tracks">
          <div class="card-head card-head-between">
            <div class="card-head-title-group">
              <span class="card-accent-bar" aria-hidden="true"></span>
              <h2 id="h-tracks" class="card-title">' . e(fa_num(isset($tr['title']) ? $tr['title'] : 'فهرست قطعات اجرایی', $faDigits)) . '</h2>
            </div>
            ' . $badgeHtml . '
          </div>
          <div class="card-divider"></div>
          <div class="card-content">' . $listHtml . '</div>
        </section>';
    }

    /* --------- ۳. نوازندگان --------- */
    if ($key === 'musicians') {
        $ms = isset($data['musiciansSection']) ? $data['musiciansSection'] : array();
        if (empty($ms['enabled'])) return '';
        $items = (!empty($ms['items']) && is_array($ms['items'])) ? $ms['items'] : array();

        $gridHtml = '';
        if (!empty($items)) {
            $cards = '';
            foreach ($items as $m) {
                $hl = !empty($m['highlight']) ? ' is-lead' : '';
                $role = !empty($m['role']) ? '<span class="person-role">' . e(fa_num($m['role'], $faDigits)) . '</span>' : '';
                $cards .= '
                <div class="person-box' . $hl . '">
                  <span class="person-name">' . e(fa_num(isset($m['name']) ? $m['name'] : '', $faDigits)) . '</span>
                  ' . $role . '
                </div>';
            }
            $gridHtml = '<div class="people-grid">' . $cards . '</div>';
        } elseif (!empty($ms['emptyPlaceholder'])) {
            $gridHtml = '<p class="card-placeholder">' . e(fa_num($ms['emptyPlaceholder'], $faDigits)) . '</p>';
        }

        $minHCls = ($preserveMinH && empty($items)) ? ' figma-min-musicians' : '';
        return '
        <section id="section-musicians" class="b-card b-card-musicians' . $minHCls . '" aria-labelledby="h-musicians">
          <div class="card-head">
            <span class="card-accent-bar" aria-hidden="true"></span>
            <h2 id="h-musicians" class="card-title">' . e(fa_num(isset($ms['title']) ? $ms['title'] : 'نوازندگان', $faDigits)) . '</h2>
          </div>
          <div class="card-divider"></div>
          <div class="card-content">' . $gridHtml . '</div>
        </section>';
    }

    /* --------- ۴. کادر اجرایی --------- */
    if ($key === 'crew') {
        $cr = isset($data['crewSection']) ? $data['crewSection'] : array();
        if (empty($cr['enabled'])) return '';
        $items = (!empty($cr['items']) && is_array($cr['items'])) ? $cr['items'] : array();

        $listHtml = '';
        if (!empty($items)) {
            $rows = '';
            foreach ($items as $c) {
                $rows .= '
                <div class="crew-row">
                  <span class="crew-role">' . e(fa_num(isset($c['role']) ? $c['role'] : '', $faDigits)) . '</span>
                  <span class="crew-dots" aria-hidden="true"></span>
                  <strong class="crew-name">' . e(fa_num(isset($c['name']) ? $c['name'] : '', $faDigits)) . '</strong>
                </div>';
            }
            $listHtml = '<div class="crew-list">' . $rows . '</div>';
        } elseif (!empty($cr['emptyPlaceholder'])) {
            $listHtml = '<p class="card-placeholder">' . e(fa_num($cr['emptyPlaceholder'], $faDigits)) . '</p>';
        }

        $minHCls = ($preserveMinH && empty($items)) ? ' figma-min-crew' : '';
        return '
        <section id="section-crew" class="b-card b-card-crew' . $minHCls . '" aria-labelledby="h-crew">
          <div class="card-head">
            <span class="card-accent-bar" aria-hidden="true"></span>
            <h2 id="h-crew" class="card-title">' . e(fa_num(isset($cr['title']) ? $cr['title'] : 'کادر اجرایی', $faDigits)) . '</h2>
          </div>
          <div class="card-divider"></div>
          <div class="card-content">' . $listHtml . '</div>
        </section>';
    }

    /* --------- ۵. مکان اجرایی و راهنمای ورود --------- */
    if ($key === 'venue') {
        $vn = isset($data['venueSection']) ? $data['venueSection'] : array();
        if (empty($vn['enabled'])) return '';

        $hasAnyContent = !empty($vn['venueName']) || !empty($vn['address']) || !empty($vn['doorsOpenText']) || !empty($vn['guidelines']);
        $body = '';
        if ($hasAnyContent) {
            if (!empty($vn['venueName'])) {
                $body .= '<h3 class="venue-title">' . e(fa_num($vn['venueName'], $faDigits)) . '</h3>';
            }
            if (!empty($vn['address'])) {
                $body .= '<p class="venue-addr">' . e(fa_num($vn['address'], $faDigits)) . '</p>';
            }
            if (!empty($vn['doorsOpenText']) || !empty($vn['phone'])) {
                $pills = '';
                if (!empty($vn['doorsOpenText'])) {
                    $pills .= '<span class="venue-chip">' . e(fa_num($vn['doorsOpenText'], $faDigits)) . '</span>';
                }
                if (!empty($vn['phone'])) {
                    $pills .= '<a class="venue-chip venue-chip-link" href="tel:' . e($vn['phone']) . '">تلفن هماهنگی: ' . e(fa_num($vn['phone'], $faDigits)) . '</a>';
                }
                $body .= '<div class="venue-chips">' . $pills . '</div>';
            }
            if (!empty($vn['guidelines']) && is_array($vn['guidelines'])) {
                $lis = '';
                foreach ($vn['guidelines'] as $g) {
                    $g = trim((string)$g);
                    if ($g !== '') {
                        $lis .= '<li>' . e(fa_num($g, $faDigits)) . '</li>';
                    }
                }
                if ($lis !== '') {
                    $body .= '<ul class="venue-rules">' . $lis . '</ul>';
                }
            }
            $mapBtns = '';
            if (!empty($vn['googleMapsUrl'])) {
                $mapBtns .= '<a class="map-btn" href="' . e($vn['googleMapsUrl']) . '" target="_blank" rel="noopener noreferrer">مسیریابی در گوگل‌مپ</a>';
            }
            if (!empty($vn['neshanUrl'])) {
                $mapBtns .= '<a class="map-btn" href="' . e($vn['neshanUrl']) . '" target="_blank" rel="noopener noreferrer">مسیریابی در نشان</a>';
            }
            if (!empty($vn['baladUrl'])) {
                $mapBtns .= '<a class="map-btn" href="' . e($vn['baladUrl']) . '" target="_blank" rel="noopener noreferrer">مسیریابی در بلد</a>';
            }
            if ($mapBtns !== '') {
                $body .= '<div class="venue-map-btns">' . $mapBtns . '</div>';
            }
            if (!empty($vn['mapEmbedUrl'])) {
                $body .= '<div class="venue-iframe-wrap"><iframe src="' . e($vn['mapEmbedUrl']) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="نقشه محل برگزاری"></iframe></div>';
            }
        }

        $minHCls = ($preserveMinH && !$hasAnyContent) ? ' figma-min-venue' : '';
        return '
        <section id="section-venue" class="b-card b-card-venue' . $minHCls . '" aria-labelledby="h-venue">
          <div class="card-head">
            <span class="card-accent-bar" aria-hidden="true"></span>
            <h2 id="h-venue" class="card-title">' . e(fa_num(isset($vn['title']) ? $vn['title'] : 'مکان اجرایی و راهنمای ورود', $faDigits)) . '</h2>
          </div>
          <div class="card-divider"></div>
          <div class="card-content venue-body">' . $body . '</div>
        </section>';
    }

    /* --------- ۶. بخش‌های سفارشی اضافه شده از پنل --------- */
    if (!empty($data['customSections']) && is_array($data['customSections'])) {
        foreach ($data['customSections'] as $cs) {
            if (isset($cs['id']) && $cs['id'] === $key && !empty($cs['enabled'])) {
                $paras = preg_split('/\R+/', trim((string)(isset($cs['body']) ? $cs['body'] : '')));
                $pOut = '';
                foreach ($paras as $p) {
                    $p = trim($p);
                    if ($p !== '') $pOut .= '<p>' . e(fa_num($p, $faDigits)) . '</p>';
                }
                return '
                <section id="section-' . e($cs['id']) . '" class="b-card">
                  <div class="card-head">
                    <span class="card-accent-bar" aria-hidden="true"></span>
                    <h2 class="card-title">' . e(fa_num(isset($cs['title']) ? $cs['title'] : '', $faDigits)) . '</h2>
                  </div>
                  <div class="card-divider"></div>
                  <div class="about-body align-justify">' . $pOut . '</div>
                </section>';
            }
        }
    }

    return '';
}

function render_brochure_page(array $data)
{
    $seo   = isset($data['seo']) ? $data['seo'] : array();
    $typo  = isset($data['typography']) ? $data['typography'] : array();
    $theme = isset($data['theme']) ? $data['theme'] : array();
    $hero  = isset($data['hero']) ? $data['hero'] : array();
    $foot  = isset($data['footer']) ? $data['footer'] : array();

    $faDigits = !isset($typo['forcePersianDigits']) || !empty($typo['forcePersianDigits']);

    // عنوان تب مرورگر: شروع دقیق با «بروشور اجرای دیجیتال» + پسوند سئوی کنسرت در صورت فعال بودن
    $tabTitle = !empty($seo['tabTitle']) ? trim((string)$seo['tabTitle']) : 'بروشور اجرای دیجیتال';
    $titleSuffix = isset($seo['titleSuffix']) ? trim((string)$seo['titleSuffix']) : '';
    $fullPageTitle = $titleSuffix !== '' ? ($tabTitle . ' | ' . $titleSuffix) : $tabTitle;

    $canonicalUrl = !empty($seo['canonicalUrl'])
        ? rtrim((string)$seo['canonicalUrl'], '/') . '/'
        : (SITE_FULL_BASE . '/');

    $ogImgRel = !empty($seo['ogImage']) ? $seo['ogImage'] : 'assets/images/og-cover.jpg';
    $ogImgAbs = full_url($ogImgRel);

    $faviconRel = !empty($seo['favicon']) ? $seo['favicon'] : 'assets/images/favicon.png';
    $faviconUrl = asset_url($faviconRel);
    $faviconIco = asset_url('favicon.ico');
    $appleTouch = asset_url('assets/images/apple-touch-icon.png');

    $fontCss   = build_brochure_font_css($typo);
    $schemaTag = build_brochure_schema($data, $canonicalUrl, $ogImgAbs);

    // متغیرهای رنگی کالیبره‌شده دقیق با فایل فیگما (2.png)
    $bgCenter       = !empty($theme['bgCenter']) ? $theme['bgCenter'] : '#244460';
    $bgEdge         = !empty($theme['bgEdge']) ? $theme['bgEdge'] : '#16324B';
    $cardBg         = !empty($theme['cardBg']) ? $theme['cardBg'] : '#23435F';
    $accentGold     = !empty($theme['accentGold']) ? $theme['accentGold'] : '#C9AD1E';
    $heroGoldCenter = !empty($theme['heroGoldCenter']) ? $theme['heroGoldCenter'] : '#C3A925';
    $heroGoldEdge   = !empty($theme['heroGoldEdge']) ? $theme['heroGoldEdge'] : '#BAA12B';
    $textPrimary    = !empty($theme['textPrimary']) ? $theme['textPrimary'] : '#FFFFFF';
    $textMuted      = !empty($theme['textMuted']) ? $theme['textMuted'] : '#A9A9A9';

    $themeCss = ":root {
      --bg-center: {$bgCenter};
      --bg-edge: {$bgEdge};
      --card-bg: {$cardBg};
      --accent-gold: {$accentGold};
      --hero-gold-center: {$heroGoldCenter};
      --hero-gold-edge: {$heroGoldEdge};
      --text-primary: {$textPrimary};
      --text-muted: {$textMuted};
    }";

    // بج‌های هدر (تاریخ، ساعت، مکان، اشتراک‌گذاری)
    $badgesHtml = '';
    if (!empty($hero['badges']) && is_array($hero['badges'])) {
        foreach ($hero['badges'] as $b) {
            if (empty($b['enabled'])) continue;
            $iconSvg = brochure_badge_svg(isset($b['icon']) ? $b['icon'] : 'calendar');
            $lbl     = e(fa_num(isset($b['text']) ? $b['text'] : '', $faDigits));
            $action  = isset($b['action']) ? $b['action'] : 'none';

            if ($action === 'share') {
                $badgesHtml .= '<button type="button" class="hero-pill hero-pill-btn" data-share-trigger>' . $iconSvg . '<span>' . $lbl . '</span></button>';
            } elseif ($action === 'scroll-venue') {
                $badgesHtml .= '<a href="#section-venue" class="hero-pill hero-pill-link">' . $iconSvg . '<span>' . $lbl . '</span></a>';
            } elseif ($action === 'link' && !empty($b['url'])) {
                $badgesHtml .= '<a href="' . e($b['url']) . '" target="_blank" rel="noopener noreferrer" class="hero-pill hero-pill-link">' . $iconSvg . '<span>' . $lbl . '</span></a>';
            } else {
                $badgesHtml .= '<div class="hero-pill">' . $iconSvg . '<span>' . $lbl . '</span></div>';
            }
        }
    }

    // رندر بخش‌ها به ترتیب دلخواه مدیر
    $order = (!empty($data['sectionOrder']) && is_array($data['sectionOrder']))
        ? $data['sectionOrder']
        : array('about', 'tracks', 'musicians', 'crew', 'venue');
    if (!empty($data['customSections']) && is_array($data['customSections'])) {
        foreach ($data['customSections'] as $cs) {
            if (!empty($cs['id']) && !in_array($cs['id'], $order, true)) {
                $order[] = $cs['id'];
            }
        }
    }
    $sectionsHtml = '';
    foreach ($order as $secKey) {
        $sectionsHtml .= render_brochure_section($secKey, $data, $faDigits);
    }

    // مقیاس تصاویر هدر
    $subjScale  = isset($hero['subjectScale']) ? ((float)$hero['subjectScale'] / 100.0) : 1.0;
    $leftScale  = isset($hero['leftLogoScale']) ? ((float)$hero['leftLogoScale'] / 100.0) : 1.0;
    $rightScale = isset($hero['rightLogoScale']) ? ((float)$hero['rightLogoScale'] / 100.0) : 1.0;

    // لینک‌های فوتر به Negahm.ir
    $agencyText   = !empty($foot['agencyText']) ? $foot['agencyText'] : 'آژانس تبلیغاتی نگاه';
    $agencyUrl    = !empty($foot['agencyUrl']) ? $foot['agencyUrl'] : 'https://negahm.ir';
    $poweredText  = !empty($foot['poweredByText']) ? $foot['poweredByText'] : 'قدرت گرفته از نگاه مدیا';
    $poweredUrl   = !empty($foot['poweredByUrl']) ? $foot['poweredByUrl'] : 'https://negahm.ir';
    $copyPrefix   = isset($foot['copyrightPrefix']) ? $foot['copyrightPrefix'] : 'بروشور دیجیتال اختصاصی تور کنسرت سرزمین پدری';
    $copyYear     = isset($foot['copyrightYear']) ? $foot['copyrightYear'] : '© ۱۴۰۵';

    $sideRailsHtml = (!isset($theme['showSideLines']) || !empty($theme['showSideLines']))
        ? '<div class="brochure-rails" aria-hidden="true">
             <span class="rail rail-r-inner"></span>
             <span class="rail rail-r-mid"></span>
             <span class="rail rail-r-outer"></span>
             <span class="rail rail-l-inner"></span>
             <span class="rail rail-l-mid"></span>
             <span class="rail rail-l-outer"></span>
           </div>'
        : '';

    $customHead = isset($seo['customHeadHtml']) ? (string)$seo['customHeadHtml'] : '';
    $customBody = isset($seo['customBodyEndHtml']) ? (string)$seo['customBodyEndHtml'] : '';

    return '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>' . e(fa_num($fullPageTitle, $faDigits)) . '</title>
  <meta name="description" content="' . e(isset($seo['description']) ? $seo['description'] : '') . '">
  <meta name="keywords" content="' . e(isset($seo['keywords']) ? $seo['keywords'] : '') . '">
  <meta name="robots" content="' . e(!empty($seo['robots']) ? $seo['robots'] : 'index, follow') . '">
  <meta name="author" content="' . e($agencyText) . '">
  <link rel="canonical" href="' . e($canonicalUrl) . '">
  <link rel="alternate" hreflang="fa-IR" href="' . e($canonicalUrl) . '">
  <meta name="theme-color" content="' . e($bgEdge) . '">

  <!-- فاوآیکون تصویر استاد وحید تاج با کادر مستطیل طلایی دور تصویر -->
  <link rel="icon" type="image/png" sizes="192x192" href="' . e($faviconUrl) . '">
  <link rel="shortcut icon" href="' . e($faviconIco) . '">
  <link rel="apple-touch-icon" sizes="180x180" href="' . e($appleTouch) . '">

  <!-- Open Graph / شبکه‌های اجتماعی و پیام‌رسان‌ها -->
  <meta property="og:locale" content="fa_IR">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="' . e(!empty($seo['siteName']) ? $seo['siteName'] : $tabTitle) . '">
  <meta property="og:title" content="' . e(!empty($seo['ogTitle']) ? $seo['ogTitle'] : $fullPageTitle) . '">
  <meta property="og:description" content="' . e(!empty($seo['ogDescription']) ? $seo['ogDescription'] : (isset($seo['description']) ? $seo['description'] : '')) . '">
  <meta property="og:url" content="' . e($canonicalUrl) . '">
  <meta property="og:image" content="' . e($ogImgAbs) . '">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="' . e(!empty($hero['subjectAlt']) ? $hero['subjectAlt'] : $fullPageTitle) . '">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="' . e(!empty($seo['ogTitle']) ? $seo['ogTitle'] : $fullPageTitle) . '">
  <meta name="twitter:description" content="' . e(!empty($seo['ogDescription']) ? $seo['ogDescription'] : (isset($seo['description']) ? $seo['description'] : '')) . '">
  <meta name="twitter:image" content="' . e($ogImgAbs) . '">

  <!-- Preload فونت و تصویر اصلی هدر برای سرعت لود بالا (Core Web Vitals) -->
  <link rel="preload" href="' . e(asset_url('assets/fonts/default-farsi-vf.woff2')) . '" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="' . e(asset_url(!empty($hero['subjectImage']) ? $hero['subjectImage'] : 'assets/images/head-hero.png')) . '" as="image" fetchpriority="high">

  <style>' . $fontCss . "\n" . $themeCss . '</style>
  <link rel="stylesheet" href="' . e(asset_url('assets/css/style.css')) . '">
  ' . $schemaTag . '
  ' . $customHead . '
</head>
<body>
  <div class="brochure-page">
    ' . $sideRailsHtml . '

    <main class="brochure-wrap">
      <!-- ==================== هدر و قاب طلایی سوژه (مطابق دقیق فیگما 2.png) ==================== -->
      <header class="hero-block">
        <div class="hero-stage" style="--subj-scale:' . e($subjScale) . '; --left-scale:' . e($leftScale) . '; --right-scale:' . e($rightScale) . ';">
          <div class="hero-gold-frame"></div>
          ' . (!empty($hero['rightLogoImage']) ? '<img class="hero-logo hero-logo-vahid" src="' . e(asset_url($hero['rightLogoImage'])) . '" alt="' . e(!empty($hero['rightLogoAlt']) ? $hero['rightLogoAlt'] : 'وحید تاج') . '" decoding="async">' : '') . '
          ' . (!empty($hero['leftLogoImage']) ? '<img class="hero-logo hero-logo-seid" src="' . e(asset_url($hero['leftLogoImage'])) . '" alt="' . e(!empty($hero['leftLogoAlt']) ? $hero['leftLogoAlt'] : 'صید عشق') . '" decoding="async">' : '') . '
          ' . (!empty($hero['subjectImage']) ? '<img class="hero-portrait" src="' . e(asset_url($hero['subjectImage'])) . '" alt="' . e(!empty($hero['subjectAlt']) ? $hero['subjectAlt'] : 'استاد وحید تاج') . '" fetchpriority="high" decoding="async">' : '') . '
        </div>

        <div class="hero-headings">
          <h1 class="hero-h1">' . e(fa_num(!empty($hero['mainTitle']) ? $hero['mainTitle'] : 'تور کنسرت سرزمین پدری', $faDigits)) . '</h1>
          ' . (!empty($hero['subtitle']) ? '<p class="hero-sub">' . e(fa_num($hero['subtitle'], $faDigits)) . '</p>' : '') . '
        </div>

        ' . ($badgesHtml !== '' ? '<div class="hero-pills-row">' . $badgesHtml . '</div>' : '') . '
      </header>

      <!-- ==================== کارت‌های محتوایی بروشور ==================== -->
      <div class="brochure-cards">
        ' . $sectionsHtml . '
      </div>

      <!-- ==================== فوتر و امضای آژانس تبلیغاتی نگاه ==================== -->
      <footer class="brochure-footer">
        <div class="footer-brand-row">
          <a class="footer-agency-link" href="' . e($agencyUrl) . '" target="_blank" rel="noopener">' . e(fa_num($agencyText, $faDigits)) . '</a>
          ' . (!empty($foot['showDot']) ? '<span class="footer-gold-dot" aria-hidden="true"></span>' : '') . '
          ' . (!empty($foot['showVahidLogo']) && !empty($foot['footerLogoImage'])
                ? '<img class="footer-calligraphy" src="' . e(asset_url($foot['footerLogoImage'])) . '" alt="وحید تاج" loading="lazy">'
                : '') . '
        </div>
        <p class="footer-legal-line">
          <span>' . e(fa_num($copyPrefix, $faDigits)) . '</span>
          <span class="footer-pipe">|</span>
          <a class="footer-powered-link" href="' . e($poweredUrl) . '" target="_blank" rel="noopener">' . e(fa_num($poweredText, $faDigits)) . '</a>
          <span class="footer-year">' . e(fa_num($copyYear, $faDigits)) . '</span>
        </p>
      </footer>
    </main>
  </div>

  <!-- پنجره اشتراک‌گذاری بروشور -->
  <div id="share-modal" class="share-backdrop" hidden>
    <div class="share-dialog" role="dialog" aria-modal="true" aria-labelledby="share-dlg-title">
      <div class="share-dlg-head">
        <h3 id="share-dlg-title">اشتراک‌گذاری بروشور اجرای دیجیتال</h3>
        <button type="button" class="share-dlg-close" data-share-close aria-label="بستن">&times;</button>
      </div>
      <p class="share-dlg-desc">لینک مستقیم بروشور دیجیتال کنسرت را کپی کرده یا در پیام‌رسان‌ها ارسال نمایید:</p>
      <div class="share-copy-box">
        <input id="share-url-input" type="text" readonly dir="ltr" value="' . e($canonicalUrl) . '">
        <button type="button" id="share-copy-btn">کپی لینک</button>
      </div>
      <div class="share-quick-links">
        <a id="share-tg" target="_blank" rel="noopener noreferrer" class="share-net-btn" href="#">ارسال در تلگرام</a>
        <a id="share-wa" target="_blank" rel="noopener noreferrer" class="share-net-btn" href="#">ارسال در واتساپ</a>
      </div>
    </div>
  </div>

  <script>
    window.BROCHURE_FA_DIGITS = ' . ($faDigits ? 'true' : 'false') . ';
    window.BROCHURE_SHARE_TITLE = ' . json_encode($fullPageTitle, JSON_UNESCAPED_UNICODE) . ';
  </script>
  <script src="' . e(asset_url('assets/js/main.js')) . '" defer></script>
  ' . $customBody . '
</body>
</html>';
}
