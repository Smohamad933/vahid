<?php
/**
 * داشبورد کامل مدیریت بروشور اجرای دیجیتال (مستقل از سایت عمومی)
 * پوشش کامل: هدر و تصاویر، درباره اجرا، قطعات اجرایی + آپلود صوت و شعر،
 * نوازندگان، کادر اجرایی، مکان برگزاری، آپلود فونت اختصاصی، سئو، رنگ‌ها و امنیت.
 */
require_once dirname(__DIR__) . '/includes/init.php';
init_brochure_session();
require_admin_login();

$validTabs = array('hero', 'about', 'tracks', 'people', 'venue', 'fonts', 'seo', 'settings');
$activeTab = (isset($_GET['tab']) && in_array($_GET['tab'], $validTabs, true)) ? $_GET['tab'] : 'tracks';

/* ================== خروجی پشتیبان JSON ================== */
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="vahid-taj-brochure-backup-' . date('Ymd-His') . '.json"');
    echo json_encode($siteData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/* ================== پردازش فرم‌های ذخیره (POST) ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = isset($_POST['form_action']) ? (string)$_POST['form_action'] : '';
    $returnTab = (isset($_POST['return_tab']) && in_array($_POST['return_tab'], $validTabs, true))
        ? $_POST['return_tab']
        : $activeTab;

    try {
        /* --------- ۱. تب هدر، تصاویر و بج‌ها --------- */
        if ($action === 'save_hero') {
            $siteData['hero']['mainTitle']   = trim((string)(isset($_POST['mainTitle']) ? $_POST['mainTitle'] : ''));
            $siteData['hero']['subtitle']    = trim((string)(isset($_POST['subtitle']) ? $_POST['subtitle'] : ''));
            $siteData['hero']['subjectAlt']  = trim((string)(isset($_POST['subjectAlt']) ? $_POST['subjectAlt'] : ''));
            $siteData['hero']['leftLogoAlt'] = trim((string)(isset($_POST['leftLogoAlt']) ? $_POST['leftLogoAlt'] : ''));
            $siteData['hero']['rightLogoAlt']= trim((string)(isset($_POST['rightLogoAlt']) ? $_POST['rightLogoAlt'] : ''));

            $siteData['hero']['subjectScale']   = max(60, min(140, (int)(isset($_POST['subjectScale']) ? $_POST['subjectScale'] : 100)));
            $siteData['hero']['leftLogoScale']  = max(50, min(150, (int)(isset($_POST['leftLogoScale']) ? $_POST['leftLogoScale'] : 100)));
            $siteData['hero']['rightLogoScale'] = max(50, min(150, (int)(isset($_POST['rightLogoScale']) ? $_POST['rightLogoScale'] : 100)));

            $imgFields = array(
                'subjectFile'   => 'subjectImage',
                'leftLogoFile'  => 'leftLogoImage',
                'rightLogoFile' => 'rightLogoImage'
            );
            foreach ($imgFields as $fileInput => $targetKey) {
                if (!empty($_FILES[$fileInput])) {
                    $up = upload_brochure_file($_FILES[$fileInput], 'images');
                    if ($up['ok']) {
                        $siteData['hero'][$targetKey] = $up['relative_url'];
                    } elseif (!$up['empty']) {
                        throw new RuntimeException($up['error']);
                    }
                }
            }

            $badgesOut = array();
            if (!empty($_POST['badge_text']) && is_array($_POST['badge_text'])) {
                foreach ($_POST['badge_text'] as $i => $txt) {
                    $txt = trim((string)$txt);
                    if ($txt === '') continue;
                    $badgesOut[] = array(
                        'id'      => 'b_' . ($i + 1),
                        'icon'    => isset($_POST['badge_icon'][$i]) ? (string)$_POST['badge_icon'][$i] : 'calendar',
                        'text'    => $txt,
                        'enabled' => !empty($_POST['badge_enabled'][$i]),
                        'action'  => isset($_POST['badge_action'][$i]) ? (string)$_POST['badge_action'][$i] : 'none',
                        'url'     => isset($_POST['badge_url'][$i]) ? trim((string)$_POST['badge_url'][$i]) : ''
                    );
                }
            }
            $siteData['hero']['badges'] = $badgesOut;
            save_site_data($siteData);
            set_flash('تنظیمات هدر، تصاویر سوژه، لوگوها و بج‌ها با موفقیت ذخیره شد.');
        }

        /* --------- ۲. تب درباره اجرا و ترتیب بخش‌ها --------- */
        elseif ($action === 'save_about') {
            $siteData['about']['enabled']     = !empty($_POST['about_enabled']);
            $siteData['about']['title']       = trim((string)(isset($_POST['about_title']) ? $_POST['about_title'] : 'درباره این اجرا'));
            $siteData['about']['showDivider'] = !empty($_POST['about_divider']);
            $siteData['about']['textAlign']   = (isset($_POST['about_align']) && $_POST['about_align'] === 'right') ? 'right' : 'justify';
            $siteData['about']['body']        = trim((string)(isset($_POST['about_body']) ? $_POST['about_body'] : ''));

            if (!empty($_POST['section_order']) && is_array($_POST['section_order'])) {
                $cleanOrder = array();
                foreach ($_POST['section_order'] as $sk) {
                    $sk = trim((string)$sk);
                    if ($sk !== '' && !in_array($sk, $cleanOrder, true)) {
                        $cleanOrder[] = $sk;
                    }
                }
                if (!empty($cleanOrder)) {
                    $siteData['sectionOrder'] = $cleanOrder;
                }
            }

            $customs = array();
            if (!empty($_POST['custom_title']) && is_array($_POST['custom_title'])) {
                foreach ($_POST['custom_title'] as $i => $ct) {
                    $ct = trim((string)$ct);
                    if ($ct === '') continue;
                    $cid = !empty($_POST['custom_id'][$i]) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_POST['custom_id'][$i]) : new_id('sec_');
                    $customs[] = array(
                        'id'      => $cid,
                        'enabled' => !empty($_POST['custom_enabled'][$i]),
                        'title'   => $ct,
                        'body'    => isset($_POST['custom_body'][$i]) ? trim((string)$_POST['custom_body'][$i]) : ''
                    );
                }
            }
            $siteData['customSections'] = $customs;
            save_site_data($siteData);
            set_flash('بخش «درباره این اجرا»، کارت‌های سفارشی و چیدمان بخش‌ها ذخیره شد.');
        }

        /* --------- ۳. تب فهرست قطعات اجرایی + آپلود صوت و متن شعر --------- */
        elseif ($action === 'save_tracks') {
            $siteData['tracksSection']['enabled']          = !empty($_POST['tracks_enabled']);
            $siteData['tracksSection']['title']            = trim((string)(isset($_POST['tracks_title']) ? $_POST['tracks_title'] : 'فهرست قطعات اجرایی'));
            $siteData['tracksSection']['countBadgeMode']   = isset($_POST['count_mode']) ? (string)$_POST['count_mode'] : 'auto';
            $siteData['tracksSection']['customCountText']  = trim((string)(isset($_POST['custom_count']) ? $_POST['custom_count'] : ''));
            $siteData['tracksSection']['emptyPlaceholder'] = trim((string)(isset($_POST['tracks_placeholder']) ? $_POST['tracks_placeholder'] : ''));

            $itemsOut = array();
            $titles = (isset($_POST['trk_title']) && is_array($_POST['trk_title'])) ? $_POST['trk_title'] : array();

            foreach ($titles as $idx => $tTitle) {
                $tTitle = trim((string)$tTitle);
                if ($tTitle === '') continue;

                $existingAudio = isset($_POST['trk_audio_existing'][$idx]) ? trim((string)$_POST['trk_audio_existing'][$idx]) : '';
                if (!empty($_POST['trk_remove_audio'][$idx])) {
                    $existingAudio = '';
                }

                // بررسی آپلود فایل صوتی جدید برای این ردیف قطعه
                if (!empty($_FILES['trk_audio_file']['name'][$idx])) {
                    $singleAudio = array(
                        'name'     => $_FILES['trk_audio_file']['name'][$idx],
                        'type'     => $_FILES['trk_audio_file']['type'][$idx],
                        'tmp_name' => $_FILES['trk_audio_file']['tmp_name'][$idx],
                        'error'    => $_FILES['trk_audio_file']['error'][$idx],
                        'size'     => $_FILES['trk_audio_file']['size'][$idx]
                    );
                    $upAudio = upload_brochure_file($singleAudio, 'audio');
                    if ($upAudio['ok']) {
                        $existingAudio = $upAudio['relative_url'];
                    } elseif (!$upAudio['empty']) {
                        throw new RuntimeException('قطعه «' . $tTitle . '»: ' . $upAudio['error']);
                    }
                }

                $itemsOut[] = array(
                    'id'            => !empty($_POST['trk_id'][$idx]) ? (string)$_POST['trk_id'][$idx] : new_id('trk_'),
                    'title'         => $tTitle,
                    'composer'      => isset($_POST['trk_composer'][$idx]) ? trim((string)$_POST['trk_composer'][$idx]) : '',
                    'poet'          => isset($_POST['trk_poet'][$idx]) ? trim((string)$_POST['trk_poet'][$idx]) : '',
                    'arranger'      => isset($_POST['trk_arranger'][$idx]) ? trim((string)$_POST['trk_arranger'][$idx]) : '',
                    'performerNote' => isset($_POST['trk_note'][$idx]) ? trim((string)$_POST['trk_note'][$idx]) : '',
                    'audioUrl'      => $existingAudio,
                    'allowDownload' => !empty($_POST['trk_allow_dl'][$idx]),
                    'lyrics'        => isset($_POST['trk_lyrics'][$idx]) ? trim((string)$_POST['trk_lyrics'][$idx]) : ''
                );
            }

            $siteData['tracksSection']['items'] = $itemsOut;
            save_site_data($siteData);
            set_flash('فهرست قطعات اجرایی، فایل‌های صوتی و اشعار با موفقیت ذخیره شد.');
        }

        /* --------- ۴. تب نوازندگان و کادر اجرایی --------- */
        elseif ($action === 'save_people') {
            $siteData['musiciansSection']['enabled']          = !empty($_POST['mus_enabled']);
            $siteData['musiciansSection']['title']            = trim((string)(isset($_POST['mus_title']) ? $_POST['mus_title'] : 'نوازندگان'));
            $siteData['musiciansSection']['emptyPlaceholder'] = trim((string)(isset($_POST['mus_placeholder']) ? $_POST['mus_placeholder'] : ''));

            $musOut = array();
            if (!empty($_POST['mus_name']) && is_array($_POST['mus_name'])) {
                foreach ($_POST['mus_name'] as $i => $mName) {
                    $mName = trim((string)$mName);
                    if ($mName === '') continue;
                    $musOut[] = array(
                        'id'        => new_id('m_'),
                        'name'      => $mName,
                        'role'      => isset($_POST['mus_role'][$i]) ? trim((string)$_POST['mus_role'][$i]) : '',
                        'highlight' => !empty($_POST['mus_hl'][$i])
                    );
                }
            }
            $siteData['musiciansSection']['items'] = $musOut;

            $siteData['crewSection']['enabled']          = !empty($_POST['crew_enabled']);
            $siteData['crewSection']['title']            = trim((string)(isset($_POST['crew_title']) ? $_POST['crew_title'] : 'کادر اجرایی'));
            $siteData['crewSection']['emptyPlaceholder'] = trim((string)(isset($_POST['crew_placeholder']) ? $_POST['crew_placeholder'] : ''));

            $crewOut = array();
            if (!empty($_POST['crew_name']) && is_array($_POST['crew_name'])) {
                foreach ($_POST['crew_name'] as $i => $cName) {
                    $cName = trim((string)$cName);
                    $cRole = isset($_POST['crew_role'][$i]) ? trim((string)$_POST['crew_role'][$i]) : '';
                    if ($cName === '' && $cRole === '') continue;
                    $crewOut[] = array(
                        'id'   => new_id('c_'),
                        'role' => $cRole,
                        'name' => $cName
                    );
                }
            }
            $siteData['crewSection']['items'] = $crewOut;
            save_site_data($siteData);
            set_flash('فهرست نوازندگان و کادر اجرایی با موفقیت بروزرسانی شد.');
        }

        /* --------- ۵. تب مکان اجرایی و راهنمای ورود --------- */
        elseif ($action === 'save_venue') {
            $siteData['venueSection']['enabled']       = !empty($_POST['venue_enabled']);
            $siteData['venueSection']['title']         = trim((string)(isset($_POST['venue_title']) ? $_POST['venue_title'] : 'مکان اجرایی و راهنمای ورود'));
            $siteData['venueSection']['venueName']     = trim((string)(isset($_POST['venue_name']) ? $_POST['venue_name'] : ''));
            $siteData['venueSection']['address']       = trim((string)(isset($_POST['venue_address']) ? $_POST['venue_address'] : ''));
            $siteData['venueSection']['doorsOpenText'] = trim((string)(isset($_POST['venue_doors']) ? $_POST['venue_doors'] : ''));
            $siteData['venueSection']['phone']         = trim((string)(isset($_POST['venue_phone']) ? $_POST['venue_phone'] : ''));
            $siteData['venueSection']['googleMapsUrl'] = trim((string)(isset($_POST['venue_gmap']) ? $_POST['venue_gmap'] : ''));
            $siteData['venueSection']['neshanUrl']     = trim((string)(isset($_POST['venue_neshan']) ? $_POST['venue_neshan'] : ''));
            $siteData['venueSection']['baladUrl']      = trim((string)(isset($_POST['venue_balad']) ? $_POST['venue_balad'] : ''));
            $siteData['venueSection']['mapEmbedUrl']   = trim((string)(isset($_POST['venue_embed']) ? $_POST['venue_embed'] : ''));

            $rawRules = preg_split('/\R+/', (string)(isset($_POST['venue_guidelines']) ? $_POST['venue_guidelines'] : ''));
            $cleanRules = array();
            foreach ($rawRules as $r) {
                $r = trim($r);
                if ($r !== '') $cleanRules[] = $r;
            }
            $siteData['venueSection']['guidelines'] = $cleanRules;
            save_site_data($siteData);
            set_flash('اطلاعات مکان اجرایی و راهنمای ورود ذخیره شد.');
        }

        /* --------- ۶. تب آپلود فونت اختصاصی و تایپوگرافی --------- */
        elseif ($action === 'save_fonts') {
            $mode = isset($_POST['font_mode']) ? (string)$_POST['font_mode'] : 'default';
            if (!in_array($mode, array('default', 'variable', 'weights'), true)) {
                $mode = 'default';
            }
            $siteData['typography']['mode']                   = $mode;
            $siteData['typography']['customFontName']         = trim((string)(isset($_POST['custom_font_name']) ? $_POST['custom_font_name'] : 'Abar FaNum VF'));
            $siteData['typography']['forcePersianDigits']     = !empty($_POST['force_fa_digits']);
            $siteData['typography']['baseScale']              = max(0.85, min(1.25, (float)(isset($_POST['base_scale']) ? $_POST['base_scale'] : 1.0)));
            $siteData['typography']['headingLetterSpacingEm'] = max(-0.09, min(0.05, (float)(isset($_POST['heading_ls']) ? $_POST['heading_ls'] : -0.045)));

            if (isset($_POST['variable_font_url'])) {
                $siteData['typography']['variableFontUrl'] = trim((string)$_POST['variable_font_url']);
            }

            // آپلود فونت متغیر (Variable Font)
            if (!empty($_FILES['variable_font_file'])) {
                $upVF = upload_brochure_file($_FILES['variable_font_file'], 'fonts');
                if ($upVF['ok']) {
                    $siteData['typography']['variableFontUrl'] = $upVF['relative_url'];
                    $siteData['typography']['mode'] = 'variable';
                    $siteData['typography']['uploadedFonts'][] = array(
                        'name' => $upVF['original'],
                        'url'  => $upVF['relative_url'],
                        'date' => date('Y-m-d H:i')
                    );
                } elseif (!$upVF['empty']) {
                    throw new RuntimeException($upVF['error']);
                }
            }

            // آپلود وزن‌های مجزا (Regular / SemiBold / Bold / ExtraBold)
            $weightSlots = array('regular', 'semiBold', 'bold', 'extraBold');
            foreach ($weightSlots as $wKey) {
                if (isset($_POST['weight_url_' . $wKey])) {
                    $siteData['typography']['weights'][$wKey] = trim((string)$_POST['weight_url_' . $wKey]);
                }
                if (!empty($_FILES['weight_file_' . $wKey])) {
                    $upW = upload_brochure_file($_FILES['weight_file_' . $wKey], 'fonts');
                    if ($upW['ok']) {
                        $siteData['typography']['weights'][$wKey] = $upW['relative_url'];
                        $siteData['typography']['mode'] = 'weights';
                        $siteData['typography']['uploadedFonts'][] = array(
                            'name' => $upW['original'],
                            'url'  => $upW['relative_url'],
                            'date' => date('Y-m-d H:i')
                        );
                    } elseif (!$upW['empty']) {
                        throw new RuntimeException($upW['error']);
                    }
                }
            }

            save_site_data($siteData);
            set_flash('فونت و تنظیمات تایپوگرافی بروشور با موفقیت ذخیره و فعال شد.');
        }

        /* --------- ۷. تب مدیریت جامع سئو و اسکیما --------- */
        elseif ($action === 'save_seo') {
            $siteData['seo']['tabTitle']          = trim((string)(isset($_POST['seo_tab_title']) ? $_POST['seo_tab_title'] : 'بروشور اجرای دیجیتال'));
            $siteData['seo']['titleSuffix']       = trim((string)(isset($_POST['seo_title_suffix']) ? $_POST['seo_title_suffix'] : ''));
            $siteData['seo']['description']       = trim((string)(isset($_POST['seo_desc']) ? $_POST['seo_desc'] : ''));
            $siteData['seo']['keywords']          = trim((string)(isset($_POST['seo_keywords']) ? $_POST['seo_keywords'] : ''));
            $siteData['seo']['canonicalUrl']      = trim((string)(isset($_POST['seo_canonical']) ? $_POST['seo_canonical'] : ''));
            $siteData['seo']['robots']            = trim((string)(isset($_POST['seo_robots']) ? $_POST['seo_robots'] : 'index, follow'));
            $siteData['seo']['ogTitle']           = trim((string)(isset($_POST['seo_og_title']) ? $_POST['seo_og_title'] : ''));
            $siteData['seo']['ogDescription']     = trim((string)(isset($_POST['seo_og_desc']) ? $_POST['seo_og_desc'] : ''));
            $siteData['seo']['siteName']          = trim((string)(isset($_POST['seo_site_name']) ? $_POST['seo_site_name'] : ''));
            $siteData['seo']['schemaEnabled']     = !empty($_POST['seo_schema_enabled']);
            $siteData['seo']['eventStartDateIso'] = trim((string)(isset($_POST['seo_event_date']) ? $_POST['seo_event_date'] : ''));
            $siteData['seo']['eventCity']         = trim((string)(isset($_POST['seo_event_city']) ? $_POST['seo_event_city'] : ''));
            $siteData['seo']['eventVenueName']    = trim((string)(isset($_POST['seo_event_venue']) ? $_POST['seo_event_venue'] : ''));
            $siteData['seo']['eventAddress']      = trim((string)(isset($_POST['seo_event_addr']) ? $_POST['seo_event_addr'] : ''));
            $siteData['seo']['performerName']     = trim((string)(isset($_POST['seo_performer']) ? $_POST['seo_performer'] : ''));
            $siteData['seo']['organizerName']     = trim((string)(isset($_POST['seo_organizer']) ? $_POST['seo_organizer'] : ''));
            $siteData['seo']['organizerUrl']      = trim((string)(isset($_POST['seo_organizer_url']) ? $_POST['seo_organizer_url'] : 'https://negahm.ir'));
            $siteData['seo']['ticketUrl']         = trim((string)(isset($_POST['seo_ticket_url']) ? $_POST['seo_ticket_url'] : ''));
            $siteData['seo']['customHeadHtml']    = (string)(isset($_POST['seo_custom_head']) ? $_POST['seo_custom_head'] : '');
            $siteData['seo']['customBodyEndHtml'] = (string)(isset($_POST['seo_custom_body']) ? $_POST['seo_custom_body'] : '');

            if (!empty($_FILES['seo_og_file'])) {
                $upOg = upload_brochure_file($_FILES['seo_og_file'], 'images');
                if ($upOg['ok']) {
                    $siteData['seo']['ogImage'] = $upOg['relative_url'];
                } elseif (!$upOg['empty']) {
                    throw new RuntimeException($upOg['error']);
                }
            }
            if (!empty($_FILES['seo_fav_file'])) {
                $upFav = upload_brochure_file($_FILES['seo_fav_file'], 'images');
                if ($upFav['ok']) {
                    $siteData['seo']['favicon'] = $upFav['relative_url'];
                } elseif (!$upFav['empty']) {
                    throw new RuntimeException($upFav['error']);
                }
            }

            save_site_data($siteData);
            set_flash('تنظیمات سئو، تایتل تب مرورگر، فاوآیکون، OpenGraph و فایل sitemap.xml بروزرسانی شد.');
        }

        /* --------- ۸. تب فوتر، پالت رنگ، بازیابی پشتیبان و امنیت مدیر --------- */
        elseif ($action === 'save_footer_theme') {
            $siteData['footer']['agencyText']      = trim((string)(isset($_POST['foot_agency_text']) ? $_POST['foot_agency_text'] : 'آژانس تبلیغاتی نگاه'));
            $siteData['footer']['agencyUrl']       = trim((string)(isset($_POST['foot_agency_url']) ? $_POST['foot_agency_url'] : 'https://negahm.ir'));
            $siteData['footer']['poweredByText']   = trim((string)(isset($_POST['foot_powered_text']) ? $_POST['foot_powered_text'] : 'قدرت گرفته از نگاه مدیا'));
            $siteData['footer']['poweredByUrl']    = trim((string)(isset($_POST['foot_powered_url']) ? $_POST['foot_powered_url'] : 'https://negahm.ir'));
            $siteData['footer']['copyrightPrefix'] = trim((string)(isset($_POST['foot_copy_prefix']) ? $_POST['foot_copy_prefix'] : ''));
            $siteData['footer']['copyrightYear']   = trim((string)(isset($_POST['foot_copy_year']) ? $_POST['foot_copy_year'] : '© ۱۴۰۵'));
            $siteData['footer']['showDot']         = !empty($_POST['foot_show_dot']);
            $siteData['footer']['showVahidLogo']   = !empty($_POST['foot_show_logo']);

            $siteData['theme']['bgCenter']                = trim((string)(isset($_POST['th_bg_center']) ? $_POST['th_bg_center'] : '#2A4C6A'));
            $siteData['theme']['bgEdge']                  = trim((string)(isset($_POST['th_bg_edge']) ? $_POST['th_bg_edge'] : '#16324B'));
            $siteData['theme']['cardBg']                  = trim((string)(isset($_POST['th_card_bg']) ? $_POST['th_card_bg'] : '#23435F'));
            $siteData['theme']['heroGoldCenter']          = trim((string)(isset($_POST['th_gold_center']) ? $_POST['th_gold_center'] : '#CEB21D'));
            $siteData['theme']['heroGoldEdge']            = trim((string)(isset($_POST['th_gold_edge']) ? $_POST['th_gold_edge'] : '#BAA12C'));
            $siteData['theme']['accentGold']              = trim((string)(isset($_POST['th_accent_gold']) ? $_POST['th_accent_gold'] : '#C9AD1E'));
            $siteData['theme']['showSideLines']           = !empty($_POST['th_side_lines']);
            $siteData['theme']['preserveFigmaMinHeights'] = !empty($_POST['th_min_heights']);

            save_site_data($siteData);
            set_flash('تنظیمات فوتر، لینک‌های نگاه مدیا و رنگ‌بندی ذخیره شد.');
        }

        elseif ($action === 'change_credentials') {
            $currPass = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
            $newUser  = isset($_POST['new_username']) ? trim((string)$_POST['new_username']) : '';
            $newPass  = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';

            $admin = get_admin_credentials();
            if (!password_verify($currPass, (string)$admin['password_hash'])) {
                throw new RuntimeException('رمز عبور فعلی نادرست وارد شده است.');
            }
            if (strlen($newUser) < 3) {
                throw new RuntimeException('نام کاربری باید حداقل ۳ کاراکتر باشد.');
            }
            if ($newPass !== '' && strlen($newPass) < 6) {
                throw new RuntimeException('رمز عبور جدید باید حداقل ۶ کاراکتر باشد.');
            }
            save_admin_credentials($newUser, $newPass !== '' ? $newPass : null);
            $_SESSION['vahid_admin_user'] = $newUser;
            set_flash('اطلاعات ورود مدیر با موفقیت بروزرسانی شد.');
        }

        elseif ($action === 'import_json') {
            if (empty($_FILES['backup_json_file']) || $_FILES['backup_json_file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('فایل پشتیبان معتبر انتخاب نشده است.');
            }
            $rawJson = @file_get_contents($_FILES['backup_json_file']['tmp_name']);
            $parsed = json_decode((string)$rawJson, true);
            if (!is_array($parsed) || empty($parsed['hero'])) {
                throw new RuntimeException('ساختار فایل JSON پشتیبان معتبر نیست.');
            }
            save_site_data($parsed);
            set_flash('فایل پشتیبان با موفقیت بازیابی و اعمال شد.');
        }
    } catch (Exception $ex) {
        set_flash($ex->getMessage(), 'error');
    }

    redirect('admin/index.php?tab=' . urlencode($returnTab));
}

$favUrl = asset_url(!empty($siteData['seo']['favicon']) ? $siteData['seo']['favicon'] : 'assets/images/favicon.png');
$adminInfo = get_admin_credentials();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>پنل مدیریت | بروشور اجرای دیجیتال</title>
  <link rel="icon" type="image/png" href="<?= e($favUrl) ?>">
  <style><?= build_brochure_font_css(isset($siteData['typography']) ? $siteData['typography'] : array()) ?></style>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/admin.css')) ?>">
</head>
<body class="adm-body">

  <!-- سایدبار پنل مدیریت -->
  <aside class="adm-sidebar">
    <div class="adm-brand">
      <img src="<?= e($favUrl) ?>" alt="استاد وحید تاج">
      <div>
        <strong>پنل مدیریت بروشور</strong>
        <span>کاربر فعال: <?= e($adminInfo['username']) ?></span>
      </div>
    </div>

    <nav class="adm-nav">
      <a href="?tab=tracks" class="<?= $activeTab === 'tracks' ? 'active' : '' ?>">🎵 فهرست قطعات، صوت و اشعار</a>
      <a href="?tab=hero" class="<?= $activeTab === 'hero' ? 'active' : '' ?>">🖼️ هدر، تصاویر سوژه و لوگوها</a>
      <a href="?tab=about" class="<?= $activeTab === 'about' ? 'active' : '' ?>">📝 درباره اجرا و چیدمان بخش‌ها</a>
      <a href="?tab=people" class="<?= $activeTab === 'people' ? 'active' : '' ?>">🎻 نوازندگان و کادر اجرایی</a>
      <a href="?tab=venue" class="<?= $activeTab === 'venue' ? 'active' : '' ?>">📍 مکان اجرایی و راهنمای ورود</a>
      <a href="?tab=fonts" class="<?= $activeTab === 'fonts' ? 'active' : '' ?>">🔤 آپلود فونت و تایپوگرافی</a>
      <a href="?tab=seo" class="<?= $activeTab === 'seo' ? 'active' : '' ?>">🚀 مدیریت حرفه‌ای سئو و تب بار</a>
      <a href="?tab=settings" class="<?= $activeTab === 'settings' ? 'active' : '' ?>">⚙️ فوتر، رنگ‌بندی، بکاپ و رمز</a>
    </nav>

    <div class="adm-sidebar-foot">
      <a href="<?= e(url('')) ?>" target="_blank" class="adm-btn adm-btn-outline adm-btn-full">مشاهده زنده بروشور ↗</a>
      <a href="<?= e(url('admin/logout.php')) ?>" class="adm-btn adm-btn-danger-ghost adm-btn-full">خروج از حساب</a>
    </div>
  </aside>

  <!-- محتوای اصلی پنل -->
  <main class="adm-main">
    <?= render_flash() ?>

    <?php if ($activeTab === 'tracks'):
      $tr = $siteData['tracksSection'];
      $items = (!empty($tr['items']) && is_array($tr['items'])) ? $tr['items'] : array();
    ?>
      <form method="post" enctype="multipart/form-data" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_tracks">
        <input type="hidden" name="return_tab" value="tracks">

        <div class="adm-card-head">
          <div>
            <h2>مدیریت فهرست قطعات اجرایی، فایل‌های صوتی و اشعار</h2>
            <p>اگر برای قطعه‌ای فایل صوتی آپلود کنید، دکمه پخش + نوار زمان + دانلود فعال می‌شود؛ اگر شعر وارد کنید، دکمه بازشوی «متن قطعه» نمایش داده می‌شود.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تغییرات قطعات</button>
        </div>

        <div class="adm-grid-3">
          <div class="adm-field">
            <label>عنوان بخش قطعات</label>
            <input type="text" name="tracks_title" value="<?= e($tr['title']) ?>">
          </div>
          <div class="adm-field">
            <label>نمایش شمارنده تعداد قطعات</label>
            <select name="count_mode">
              <option value="auto" <?= ($tr['countBadgeMode'] === 'auto') ? 'selected' : '' ?>>شمارش خودکار (مثلاً ۱۱ قطعه)</option>
              <option value="custom" <?= ($tr['countBadgeMode'] === 'custom') ? 'selected' : '' ?>>متن سفارشی</option>
              <option value="hidden" <?= ($tr['countBadgeMode'] === 'hidden') ? 'selected' : '' ?>>عدم نمایش شمارنده</option>
            </select>
          </div>
          <div class="adm-field">
            <label>متن سفارشی شمارنده</label>
            <input type="text" name="custom_count" value="<?= e($tr['customCountText']) ?>" placeholder="مثلاً بخش اول: ۶ قطعه">
          </div>
        </div>

        <label class="adm-check">
          <input type="checkbox" name="tracks_enabled" value="1" <?= !empty($tr['enabled']) ? 'checked' : '' ?>>
          <span>نمایش کارت «فهرست قطعات اجرایی» در بروشور فعال باشد</span>
        </label>

        <hr class="adm-sep">

        <div id="tracks-repeater" class="adm-repeater">
          <?php foreach ($items as $idx => $item): ?>
            <div class="adm-rep-item" data-rep-row>
              <input type="hidden" name="trk_id[<?= $idx ?>]" value="<?= e($item['id']) ?>">
              <div class="adm-rep-bar">
                <strong class="adm-rep-num">قطعه #<?= ($idx + 1) ?> — <?= e($item['title']) ?></strong>
                <div class="adm-rep-btns">
                  <button type="button" class="adm-mini-btn" data-move-up title="انتقال به بالا">↑</button>
                  <button type="button" class="adm-mini-btn" data-move-down title="انتقال به پایین">↓</button>
                  <button type="button" class="adm-mini-btn adm-mini-del" data-remove-row>حذف قطعه</button>
                </div>
              </div>

              <div class="adm-grid-3">
                <div class="adm-field">
                  <label>نام قطعه *</label>
                  <input type="text" name="trk_title[<?= $idx ?>]" value="<?= e($item['title']) ?>" required>
                </div>
                <div class="adm-field">
                  <label>آهنگساز (اختیاری)</label>
                  <input type="text" name="trk_composer[<?= $idx ?>]" value="<?= e(isset($item['composer']) ? $item['composer'] : '') ?>" placeholder="مثلاً وحید تاج">
                </div>
                <div class="adm-field">
                  <label>شاعر / شعر (اختیاری)</label>
                  <input type="text" name="trk_poet[<?= $idx ?>]" value="<?= e(isset($item['poet']) ? $item['poet'] : '') ?>" placeholder="مثلاً مولانا">
                </div>
              </div>

              <div class="adm-grid-2">
                <div class="adm-field">
                  <label>تنظیم‌کننده (اختیاری)</label>
                  <input type="text" name="trk_arranger[<?= $idx ?>]" value="<?= e(isset($item['arranger']) ? $item['arranger'] : '') ?>" placeholder="مثلاً گروه سرزمین پدری">
                </div>
                <div class="adm-field">
                  <label>توضیح / نوازنده (مثلاً «بی‌کلام» یا «به نوازندگی ...»)</label>
                  <input type="text" name="trk_note[<?= $idx ?>]" value="<?= e(isset($item['performerNote']) ? $item['performerNote'] : '') ?>" placeholder="بی‌کلام / به نوازندگی ...">
                </div>
              </div>

              <div class="adm-audio-box">
                <div class="adm-grid-2">
                  <div class="adm-field">
                    <label>آپلود فایل صوتی قطعه (MP3 / M4A / WAV / OGG)</label>
                    <input type="file" name="trk_audio_file[<?= $idx ?>]" accept=".mp3,.m4a,.wav,.ogg,.aac,.flac,audio/*">
                  </div>
                  <div class="adm-field">
                    <label>آدرس فایل صوتی فعلی (یا لینک مستقیم)</label>
                    <input type="text" dir="ltr" name="trk_audio_existing[<?= $idx ?>]" value="<?= e(isset($item['audioUrl']) ? $item['audioUrl'] : '') ?>" placeholder="uploads/audio/...">
                  </div>
                </div>
                <div class="adm-audio-flags">
                  <label class="adm-check">
                    <input type="checkbox" name="trk_allow_dl[<?= $idx ?>]" value="1" <?= (!isset($item['allowDownload']) || !empty($item['allowDownload'])) ? 'checked' : '' ?>>
                    <span>نمایش دکمه دانلود کنار نوار پخش صوت</span>
                  </label>
                  <?php if (!empty($item['audioUrl'])): ?>
                    <label class="adm-check adm-check-warn">
                      <input type="checkbox" name="trk_remove_audio[<?= $idx ?>]" value="1">
                      <span>حذف فایل صوتی این قطعه</span>
                    </label>
                    <audio controls preload="none" src="<?= e(asset_url($item['audioUrl'])) ?>" class="adm-audio-preview"></audio>
                  <?php endif; ?>
                </div>
              </div>

              <div class="adm-field">
                <label>متن شعر قطعه (هر مصرع در یک خط مجزا — در صورت پر بودن، کشوی «متن قطعه» فعال می‌شود)</label>
                <textarea name="trk_lyrics[<?= $idx ?>]" rows="4" placeholder="مصرع اول...&#10;مصرع دوم..."><?= e(isset($item['lyrics']) ? $item['lyrics'] : '') ?></textarea>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="adm-bottom-actions">
          <button type="button" id="btn-add-track" class="adm-btn adm-btn-outline">+ افزودن قطعه جدید به فهرست</button>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره کامل فهرست قطعات</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'hero'):
      $hero = $siteData['hero'];
    ?>
      <form method="post" enctype="multipart/form-data" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_hero">
        <input type="hidden" name="return_tab" value="hero">

        <div class="adm-card-head">
          <div>
            <h2>تنظیمات هدر، پرتره استاد، لوگوها و بج‌های اطلاعات کنسرت</h2>
            <p>تصویر اصلی سوژه، تایپوگرافی صید عشق، لوگوی وحید تاج و قرص‌های زیر عنوان را مدیریت کنید.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره هدر</button>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>تیتر اصلی کنسرت (H1)</label>
            <input type="text" name="mainTitle" value="<?= e($hero['mainTitle']) ?>" required>
          </div>
          <div class="adm-field">
            <label>زیرعنوان هدر</label>
            <input type="text" name="subtitle" value="<?= e($hero['subtitle']) ?>">
          </div>
        </div>

        <div class="adm-grid-3">
          <div class="adm-img-box">
            <strong>تصویر سوژه (استاد وحید تاج)</strong>
            <img src="<?= e(asset_url($hero['subjectImage'])) ?>" alt="Subject">
            <input type="file" name="subjectFile" accept="image/*">
            <label>متن جایگزین سئو (Alt)</label>
            <input type="text" name="subjectAlt" value="<?= e($hero['subjectAlt']) ?>">
            <label>مقیاس اندازه (%): <?= (int)$hero['subjectScale'] ?></label>
            <input type="number" name="subjectScale" min="60" max="140" value="<?= (int)$hero['subjectScale'] ?>">
          </div>

          <div class="adm-img-box">
            <strong>لوگوی سمت چپ (صید عشق)</strong>
            <img src="<?= e(asset_url($hero['leftLogoImage'])) ?>" alt="Left Logo">
            <input type="file" name="leftLogoFile" accept="image/*">
            <label>متن جایگزین سئو (Alt)</label>
            <input type="text" name="leftLogoAlt" value="<?= e($hero['leftLogoAlt']) ?>">
            <label>مقیاس اندازه (%)</label>
            <input type="number" name="leftLogoScale" min="50" max="150" value="<?= (int)$hero['leftLogoScale'] ?>">
          </div>

          <div class="adm-img-box">
            <strong>لوگوی سمت راست (وحید تاج)</strong>
            <img src="<?= e(asset_url($hero['rightLogoImage'])) ?>" alt="Right Logo">
            <input type="file" name="rightLogoFile" accept="image/*">
            <label>متن جایگزین سئو (Alt)</label>
            <input type="text" name="rightLogoAlt" value="<?= e($hero['rightLogoAlt']) ?>">
            <label>مقیاس اندازه (%)</label>
            <input type="number" name="rightLogoScale" min="50" max="150" value="<?= (int)$hero['rightLogoScale'] ?>">
          </div>
        </div>

        <hr class="adm-sep">
        <h3>بج‌های زیر عنوان هدر (تاریخ، ساعت، مکان، اشتراک‌گذاری)</h3>
        <div id="badges-repeater" class="adm-repeater">
          <?php foreach ($hero['badges'] as $i => $b): ?>
            <div class="adm-rep-item" data-rep-row>
              <div class="adm-grid-4">
                <div class="adm-field">
                  <label>متن بج</label>
                  <input type="text" name="badge_text[<?= $i ?>]" value="<?= e($b['text']) ?>">
                </div>
                <div class="adm-field">
                  <label>آیکون</label>
                  <select name="badge_icon[<?= $i ?>]">
                    <option value="calendar" <?= $b['icon'] === 'calendar' ? 'selected' : '' ?>>تقویم</option>
                    <option value="clock" <?= $b['icon'] === 'clock' ? 'selected' : '' ?>>ساعت</option>
                    <option value="pin" <?= $b['icon'] === 'pin' ? 'selected' : '' ?>>مکان</option>
                    <option value="share" <?= $b['icon'] === 'share' ? 'selected' : '' ?>>اشتراک‌گذاری</option>
                    <option value="ticket" <?= $b['icon'] === 'ticket' ? 'selected' : '' ?>>بلیت</option>
                  </select>
                </div>
                <div class="adm-field">
                  <label>عملیات کلیک</label>
                  <select name="badge_action[<?= $i ?>]">
                    <option value="none" <?= $b['action'] === 'none' ? 'selected' : '' ?>>بدون کلیک (نمایشی)</option>
                    <option value="scroll-venue" <?= $b['action'] === 'scroll-venue' ? 'selected' : '' ?>>اسکرول به بخش مکان</option>
                    <option value="share" <?= $b['action'] === 'share' ? 'selected' : '' ?>>پنجره اشتراک‌گذاری</option>
                    <option value="link" <?= $b['action'] === 'link' ? 'selected' : '' ?>>باز کردن لینک سفارشی</option>
                  </select>
                </div>
                <div class="adm-field">
                  <label>لینک مقصد (در حالت لینک)</label>
                  <input type="text" dir="ltr" name="badge_url[<?= $i ?>]" value="<?= e(isset($b['url']) ? $b['url'] : '') ?>" placeholder="https://...">
                </div>
              </div>
              <label class="adm-check">
                <input type="checkbox" name="badge_enabled[<?= $i ?>]" value="1" <?= !empty($b['enabled']) ? 'checked' : '' ?>>
                <span>نمایش این بج فعال باشد</span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تغییرات هدر</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'about'):
      $ab = $siteData['about'];
      $order = !empty($siteData['sectionOrder']) ? $siteData['sectionOrder'] : array('about', 'tracks', 'musicians', 'crew', 'venue');
      $secNames = array(
        'about'     => 'درباره این اجرا',
        'tracks'    => 'فهرست قطعات اجرایی',
        'musicians' => 'نوازندگان',
        'crew'      => 'کادر اجرایی',
        'venue'     => 'مکان اجرایی و راهنمای ورود'
      );
    ?>
      <form method="post" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_about">
        <input type="hidden" name="return_tab" value="about">

        <div class="adm-card-head">
          <div>
            <h2>مدیریت متن «درباره این اجرا»، ترتیب بخش‌ها و کارت‌های سفارشی</h2>
            <p>توضیحات معرفی کنسرت و ترتیب قرارگیری کارت‌ها در بروشور را ویرایش کنید.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تغییرات</button>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>عنوان کارت درباره اجرا</label>
            <input type="text" name="about_title" value="<?= e($ab['title']) ?>">
          </div>
          <div class="adm-field">
            <label>چینش متن</label>
            <select name="about_align">
              <option value="justify" <?= ($ab['textAlign'] !== 'right') ? 'selected' : '' ?>>تراز دوطرفه (Justify — مطابق فیگما)</option>
              <option value="right" <?= ($ab['textAlign'] === 'right') ? 'selected' : '' ?>>راست‌چین</option>
            </select>
          </div>
        </div>

        <div class="adm-field">
          <label>متن کامل درباره اجرا</label>
          <textarea name="about_body" rows="6"><?= e($ab['body']) ?></textarea>
        </div>

        <div class="adm-audio-flags">
          <label class="adm-check">
            <input type="checkbox" name="about_enabled" value="1" <?= !empty($ab['enabled']) ? 'checked' : '' ?>>
            <span>نمایش بخش «درباره این اجرا» فعال باشد</span>
          </label>
          <label class="adm-check">
            <input type="checkbox" name="about_divider" value="1" <?= !empty($ab['showDivider']) ? 'checked' : '' ?>>
            <span>نمایش خط افقی جداکننده زیر تیتر این کارت</span>
          </label>
        </div>

        <hr class="adm-sep">
        <h3>ترتیب نمایش بخش‌های بروشور (با دکمه‌های ↑ و ↓ جابه‌جا کنید)</h3>
        <div class="adm-repeater">
          <?php foreach ($order as $sk): ?>
            <div class="adm-rep-item adm-order-row" data-rep-row>
              <input type="hidden" name="section_order[]" value="<?= e($sk) ?>">
              <strong><?= e(isset($secNames[$sk]) ? $secNames[$sk] : ('بخش سفارشی: ' . $sk)) ?></strong>
              <div class="adm-rep-btns">
                <button type="button" class="adm-mini-btn" data-move-up>↑ بالا</button>
                <button type="button" class="adm-mini-btn" data-move-down>↓ پایین</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تغییرات</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'people'):
      $ms = $siteData['musiciansSection'];
      $cr = $siteData['crewSection'];
    ?>
      <form method="post" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_people">
        <input type="hidden" name="return_tab" value="people">

        <div class="adm-card-head">
          <div>
            <h2>مدیریت اعضای گروه نوازندگان و کادر اجرایی</h2>
            <p>اسامی هنرمندان، ساز تخصصی و عوامل اجرایی کنسرت را تنظیم نمایید.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تغییرات</button>
        </div>

        <h3>۱. بخش نوازندگان</h3>
        <div class="adm-grid-2">
          <div class="adm-field">
            <label>عنوان کارت نوازندگان</label>
            <input type="text" name="mus_title" value="<?= e($ms['title']) ?>">
          </div>
          <div class="adm-field">
            <label>متن جایگزین در صورت خالی بودن لیست</label>
            <input type="text" name="mus_placeholder" value="<?= e($ms['emptyPlaceholder']) ?>">
          </div>
        </div>
        <label class="adm-check">
          <input type="checkbox" name="mus_enabled" value="1" <?= !empty($ms['enabled']) ? 'checked' : '' ?>>
          <span>نمایش کارت نوازندگان در بروشور فعال باشد</span>
        </label>

        <div id="musicians-repeater" class="adm-repeater">
          <?php foreach ($ms['items'] as $i => $m): ?>
            <div class="adm-rep-item" data-rep-row>
              <div class="adm-grid-3">
                <div class="adm-field">
                  <label>نام هنرمند</label>
                  <input type="text" name="mus_name[<?= $i ?>]" value="<?= e($m['name']) ?>">
                </div>
                <div class="adm-field">
                  <label>ساز / نقش</label>
                  <input type="text" name="mus_role[<?= $i ?>]" value="<?= e($m['role']) ?>">
                </div>
                <div class="adm-field adm-field-inline">
                  <label class="adm-check">
                    <input type="checkbox" name="mus_hl[<?= $i ?>]" value="1" <?= !empty($m['highlight']) ? 'checked' : '' ?>>
                    <span>برجسته (طلایی)</span>
                  </label>
                  <button type="button" class="adm-mini-btn adm-mini-del" data-remove-row>حذف</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" id="btn-add-musician" class="adm-btn adm-btn-outline">+ افزودن نوازنده جدید</button>

        <hr class="adm-sep">
        <h3>۲. بخش کادر اجرایی</h3>
        <div class="adm-grid-2">
          <div class="adm-field">
            <label>عنوان کارت کادر اجرایی</label>
            <input type="text" name="crew_title" value="<?= e($cr['title']) ?>">
          </div>
          <div class="adm-field">
            <label>متن جایگزین در صورت خالی بودن لیست</label>
            <input type="text" name="crew_placeholder" value="<?= e($cr['emptyPlaceholder']) ?>">
          </div>
        </div>
        <label class="adm-check">
          <input type="checkbox" name="crew_enabled" value="1" <?= !empty($cr['enabled']) ? 'checked' : '' ?>>
          <span>نمایش کارت کادر اجرایی در بروشور فعال باشد</span>
        </label>

        <div id="crew-repeater" class="adm-repeater">
          <?php foreach ($cr['items'] as $i => $c): ?>
            <div class="adm-rep-item" data-rep-row>
              <div class="adm-grid-3">
                <div class="adm-field">
                  <label>سمت / مسئولیت</label>
                  <input type="text" name="crew_role[<?= $i ?>]" value="<?= e($c['role']) ?>">
                </div>
                <div class="adm-field">
                  <label>نام شخص یا مجموعه</label>
                  <input type="text" name="crew_name[<?= $i ?>]" value="<?= e($c['name']) ?>">
                </div>
                <div class="adm-field adm-field-inline">
                  <button type="button" class="adm-mini-btn adm-mini-del" data-remove-row>حذف ردیف</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" id="btn-add-crew" class="adm-btn adm-btn-outline">+ افزودن عضو کادر اجرایی</button>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره نوازندگان و کادر اجرایی</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'venue'):
      $vn = $siteData['venueSection'];
      $rulesTxt = (!empty($vn['guidelines']) && is_array($vn['guidelines'])) ? implode("\n", $vn['guidelines']) : '';
    ?>
      <form method="post" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_venue">
        <input type="hidden" name="return_tab" value="venue">

        <div class="adm-card-head">
          <div>
            <h2>مکان اجرایی، ساعت بازگشایی درها و لینک‌های مسیریابی</h2>
            <p>آدرس دقیق سالن، نکات راهنمای ورود مخاطبان و دکمه‌های گوگل‌مپ / نشان / بلد را تنظیم کنید.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره اطلاعات مکان</button>
        </div>

        <label class="adm-check">
          <input type="checkbox" name="venue_enabled" value="1" <?= !empty($vn['enabled']) ? 'checked' : '' ?>>
          <span>نمایش کارت «مکان اجرایی و راهنمای ورود» فعال باشد</span>
        </label>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>عنوان کارت</label>
            <input type="text" name="venue_title" value="<?= e($vn['title']) ?>">
          </div>
          <div class="adm-field">
            <label>نام تالار / هتل برگزاری</label>
            <input type="text" name="venue_name" value="<?= e($vn['venueName']) ?>">
          </div>
        </div>

        <div class="adm-field">
          <label>نشانی کامل پستی</label>
          <input type="text" name="venue_address" value="<?= e($vn['address']) ?>">
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>ساعت بازگشایی درها و شروع برنامه</label>
            <input type="text" name="venue_doors" value="<?= e($vn['doorsOpenText']) ?>">
          </div>
          <div class="adm-field">
            <label>شماره تلفن هماهنگی (اختیاری)</label>
            <input type="text" dir="ltr" name="venue_phone" value="<?= e($vn['phone']) ?>">
          </div>
        </div>

        <div class="adm-grid-3">
          <div class="adm-field">
            <label>لینک گوگل‌مپ (Google Maps)</label>
            <input type="text" dir="ltr" name="venue_gmap" value="<?= e($vn['googleMapsUrl']) ?>">
          </div>
          <div class="adm-field">
            <label>لینک مسیریاب نشان</label>
            <input type="text" dir="ltr" name="venue_neshan" value="<?= e($vn['neshanUrl']) ?>">
          </div>
          <div class="adm-field">
            <label>لینک مسیریاب بلد</label>
            <input type="text" dir="ltr" name="venue_balad" value="<?= e($vn['baladUrl']) ?>">
          </div>
        </div>

        <div class="adm-field">
          <label>آدرس Embed نقشه (iframe اختیاری)</label>
          <input type="text" dir="ltr" name="venue_embed" value="<?= e($vn['mapEmbedUrl']) ?>" placeholder="https://www.google.com/maps/embed?pb=...">
        </div>

        <div class="adm-field">
          <label>نکات راهنمای ورود به سالن (هر نکته در یک خط جداگانه)</label>
          <textarea name="venue_guidelines" rows="5"><?= e($rulesTxt) ?></textarea>
        </div>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره اطلاعات مکان</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'fonts'):
      $ty = $siteData['typography'];
    ?>
      <form method="post" enctype="multipart/form-data" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_fonts">
        <input type="hidden" name="return_tab" value="fonts">

        <div class="adm-card-head">
          <div>
            <h2>آپلود فونت اختصاصی سایت (Abar VF / فارسی) و تنظیمات تایپوگرافی</h2>
            <p>فایل فونت خود را با فرمت <code>.woff2</code>، <code>.woff</code>، <code>.ttf</code> یا <code>.otf</code> مستقیماً آپلود کنید تا بلافاصله روی کل بروشور اعمال شود.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">آپلود و ذخیره فونت</button>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>حالت فونت فعال بروشور</label>
            <select name="font_mode">
              <option value="default" <?= $ty['mode'] === 'default' ? 'selected' : '' ?>>فونت پیش‌فرض بروشور</option>
              <option value="variable" <?= $ty['mode'] === 'variable' ? 'selected' : '' ?>>فونت متغیر آپلودی (Variable Font — پیشنهادی برای Abar VF)</option>
              <option value="weights" <?= $ty['mode'] === 'weights' ? 'selected' : '' ?>>فونت چندوزنه آپلودی (Regular / SemiBold / Bold / ExtraBold)</option>
            </select>
          </div>
          <div class="adm-field">
            <label>نام نمایشی فونت اختصاصی</label>
            <input type="text" name="custom_font_name" value="<?= e($ty['customFontName']) ?>">
          </div>
        </div>

        <div class="adm-audio-box">
          <h3>۱. آپلود تک‌فایل Variable Font (مانند AbarVF-FaNum.woff2)</h3>
          <div class="adm-grid-2">
            <div class="adm-field">
              <label>انتخاب فایل فونت متغیر (.woff2, .woff, .ttf, .otf)</label>
              <input type="file" name="variable_font_file" accept=".woff2,.woff,.ttf,.otf">
            </div>
            <div class="adm-field">
              <label>مسیر فایل فونت متغیر فعلی</label>
              <input type="text" dir="ltr" name="variable_font_url" value="<?= e($ty['variableFontUrl']) ?>" placeholder="uploads/fonts/...">
            </div>
          </div>
        </div>

        <div class="adm-audio-box">
          <h3>۲. یا آپلود وزن‌های جداگانه (در صورت غیرمتغیر بودن فونت)</h3>
          <div class="adm-grid-2">
            <?php
            $wLabels = array(
              'regular'   => 'وزن معمولی (Regular 400)',
              'semiBold'  => 'وزن نیمه‌ضخیم (SemiBold 600)',
              'bold'      => 'وزن ضخیم (Bold 700)',
              'extraBold' => 'وزن فوق‌ضخیم تیتر اصلی (ExtraBold 800)'
            );
            foreach ($wLabels as $wk => $wl):
              $currW = isset($ty['weights'][$wk]) ? $ty['weights'][$wk] : '';
            ?>
              <div class="adm-field">
                <label><?= e($wl) ?></label>
                <input type="file" name="weight_file_<?= $wk ?>" accept=".woff2,.woff,.ttf,.otf">
                <input type="text" dir="ltr" name="weight_url_<?= $wk ?>" value="<?= e($currW) ?>" placeholder="مسیر فعلی...">
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="adm-grid-3">
          <div class="adm-field">
            <label>مقیاس کلی اندازه فونت‌ها (۰.۸۵ تا ۱.۲۵)</label>
            <input type="number" step="0.02" min="0.85" max="1.25" name="base_scale" value="<?= e($ty['baseScale']) ?>">
          </div>
          <div class="adm-field">
            <label>فاصله حروف تیترها (Letter-Spacing بر حسب em)</label>
            <input type="number" step="0.005" min="-0.09" max="0.05" name="heading_ls" value="<?= e($ty['headingLetterSpacingEm']) ?>">
          </div>
          <div class="adm-field adm-field-inline">
            <label class="adm-check">
              <input type="checkbox" name="force_fa_digits" value="1" <?= !empty($ty['forcePersianDigits']) ? 'checked' : '' ?>>
              <span>تبدیل خودکار تمام اعداد به ارقام فارسی</span>
            </label>
          </div>
        </div>

        <div class="adm-font-preview">
          <strong>پیش‌نمایش زنده فونت فعلی بروشور:</strong>
          <h2>تور کنسرت سرزمین پدری — بروشور اجرای دیجیتال</h2>
          <p>اجرای موسیقی اصیل ایرانی به خوانندگی استاد وحید تاج • پنجشنبه ۴ آذر ۱۴۰۵ • ساعت ۱۸:۳۰</p>
        </div>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">آپلود و ذخیره تنظیمات فونت</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'seo'):
      $seo = $siteData['seo'];
    ?>
      <form method="post" enctype="multipart/form-data" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_seo">
        <input type="hidden" name="return_tab" value="seo">

        <div class="adm-card-head">
          <div>
            <h2>مدیریت جامع سئو، تب بار مرورگر و استقرار روی زیرپوشه Negahm.ir</h2>
            <p>بهینه‌سازی کامل برای مسیرهای <code>https://negahm.ir/vahidtaj</code> و <code>https://negahm.ir/vahid-taj</code> به همراه اسکیما کنسرت گوگل و نقشه سایت خودکار.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تنظیمات سئو</button>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>متن اصلی تب مرورگر (Tab Bar Title)</label>
            <input type="text" id="seo-tab-title" name="seo_tab_title" value="<?= e($seo['tabTitle']) ?>" required>
          </div>
          <div class="adm-field">
            <label>پسوند تکمیلی عنوان برای سئوی گوگل (اختیاری — بعد از | قرار می‌گیرد)</label>
            <input type="text" id="seo-title-suffix" name="seo_title_suffix" value="<?= e($seo['titleSuffix']) ?>" placeholder="تور کنسرت سرزمین پدری استاد وحید تاج">
          </div>
        </div>

        <div class="adm-field">
          <label>توضیحات متا سئو (Meta Description — پیشنهاد ۱۴۰ تا ۱۶۵ کاراکتر)</label>
          <textarea id="seo-desc" name="seo_desc" rows="3"><?= e($seo['description']) ?></textarea>
        </div>

        <div class="adm-field">
          <label>کلمات کلیدی هدف (با ویرگول جدا کنید)</label>
          <input type="text" name="seo_keywords" value="<?= e($seo['keywords']) ?>">
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>آدرس Canonical نهایی (در صورت خالی بودن، خودکار از URL فعلی مثلاً negahm.ir/vahid-taj تشخیص داده می‌شود)</label>
            <input type="text" id="seo-canonical" dir="ltr" name="seo_canonical" value="<?= e($seo['canonicalUrl']) ?>" placeholder="https://negahm.ir/vahid-taj">
          </div>
          <div class="adm-field">
            <label>دستور ربات‌های جستجوگر (Robots)</label>
            <select name="seo_robots">
              <option value="index, follow, max-image-preview:large, max-snippet:-1" <?= (strpos($seo['robots'], 'noindex') === false) ? 'selected' : '' ?>>Index, Follow (ایندکس کامل در گوگل — پیشنهادی)</option>
              <option value="noindex, nofollow" <?= (strpos($seo['robots'], 'noindex') !== false) ? 'selected' : '' ?>>NoIndex, NoFollow (مخفی از موتورهای جستجو)</option>
            </select>
          </div>
        </div>

        <!-- پیش‌نمایش زنده نتیجه جستجوی گوگل -->
        <div class="adm-serp-box">
          <span class="adm-serp-badge">پیش‌نمایش نتیجه در گوگل (SERP Preview)</span>
          <div class="adm-serp-url" dir="ltr" id="serp-url"><?= e(!empty($seo['canonicalUrl']) ? $seo['canonicalUrl'] : 'https://negahm.ir/vahid-taj/') ?></div>
          <div class="adm-serp-title" id="serp-title"><?= e($seo['tabTitle'] . ($seo['titleSuffix'] !== '' ? ' | ' . $seo['titleSuffix'] : '')) ?></div>
          <div class="adm-serp-desc" id="serp-desc"><?= e($seo['description']) ?></div>
        </div>

        <hr class="adm-sep">
        <h3>تصویر فاوآیکون تب بار (عکس استاد با کادر مستطیل) و کاور اشتراک‌گذاری (OpenGraph)</h3>
        <div class="adm-grid-2">
          <div class="adm-img-box">
            <strong>فاوآیکون تب مرورگر (عکس استاد در کادر مستطیل)</strong>
            <img src="<?= e(asset_url($seo['favicon'])) ?>" alt="Favicon" style="width:72px;height:72px;">
            <input type="file" name="seo_fav_file" accept="image/png,image/x-icon,image/webp">
          </div>
          <div class="adm-img-box">
            <strong>کاور پیش‌نمایش لینک در تلگرام/واتساپ (OpenGraph 1200×630)</strong>
            <img src="<?= e(asset_url($seo['ogImage'])) ?>" alt="OG Cover">
            <input type="file" name="seo_og_file" accept="image/*">
          </div>
        </div>

        <div class="adm-grid-3">
          <div class="adm-field">
            <label>عنوان OpenGraph</label>
            <input type="text" name="seo_og_title" value="<?= e($seo['ogTitle']) ?>">
          </div>
          <div class="adm-field">
            <label>توضیحات OpenGraph</label>
            <input type="text" name="seo_og_desc" value="<?= e($seo['ogDescription']) ?>">
          </div>
          <div class="adm-field">
            <label>نام سایت (og:site_name)</label>
            <input type="text" name="seo_site_name" value="<?= e($seo['siteName']) ?>">
          </div>
        </div>

        <hr class="adm-sep">
        <h3>داده‌های ساختاریافته کنسرت برای گوگل (Schema.org MusicEvent JSON-LD)</h3>
        <label class="adm-check">
          <input type="checkbox" name="seo_schema_enabled" value="1" <?= !empty($seo['schemaEnabled']) ? 'checked' : '' ?>>
          <span>تولید خودکار اسکیما رویداد موسیقی + فهرست قطعات + مسیر راهنما (Breadcrumb به negahm.ir) فعال باشد</span>
        </label>

        <div class="adm-grid-3">
          <div class="adm-field">
            <label>تاریخ و ساعت شروع (فرمت ISO)</label>
            <input type="text" dir="ltr" name="seo_event_date" value="<?= e($seo['eventStartDateIso']) ?>">
          </div>
          <div class="adm-field">
            <label>شهر برگزاری</label>
            <input type="text" name="seo_event_city" value="<?= e($seo['eventCity']) ?>">
          </div>
          <div class="adm-field">
            <label>نام سالن / هتل</label>
            <input type="text" name="seo_event_venue" value="<?= e($seo['eventVenueName']) ?>">
          </div>
        </div>

        <div class="adm-grid-3">
          <div class="adm-field">
            <label>نام خواننده / هنرمند اصلی</label>
            <input type="text" name="seo_performer" value="<?= e($seo['performerName']) ?>">
          </div>
          <div class="adm-field">
            <label>نام برگزارکننده / مجری</label>
            <input type="text" name="seo_organizer" value="<?= e($seo['organizerName']) ?>">
          </div>
          <div class="adm-field">
            <label>وب‌سایت برگزارکننده</label>
            <input type="text" dir="ltr" name="seo_organizer_url" value="<?= e($seo['organizerUrl']) ?>">
          </div>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>نشانی کامل محل برگزاری در اسکیما</label>
            <input type="text" name="seo_event_addr" value="<?= e($seo['eventAddress']) ?>">
          </div>
          <div class="adm-field">
            <label>لینک خرید بلیت (اختیاری)</label>
            <input type="text" dir="ltr" name="seo_ticket_url" value="<?= e($seo['ticketUrl']) ?>">
          </div>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>کد سفارشی داخل &lt;head&gt; (مانند تگ Google Search Console یا Analytics)</label>
            <textarea dir="ltr" name="seo_custom_head" rows="3"><?= e($seo['customHeadHtml']) ?></textarea>
          </div>
          <div class="adm-field">
            <label>کد سفارشی انتهای &lt;body&gt;</label>
            <textarea dir="ltr" name="seo_custom_body" rows="3"><?= e($seo['customBodyEndHtml']) ?></textarea>
          </div>
        </div>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره کامل تنظیمات سئو</button>
        </div>
      </form>

    <?php elseif ($activeTab === 'settings'):
      $foot = $siteData['footer'];
      $th   = $siteData['theme'];
    ?>
      <form method="post" class="adm-card">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="save_footer_theme">
        <input type="hidden" name="return_tab" value="settings">

        <div class="adm-card-head">
          <div>
            <h2>تنظیمات فوتر (لینک‌های Negahm.ir) و کالیبراسیون رنگ‌های بروشور</h2>
            <p>عناوین و لینک‌های فوتر و همچنین کدهای رنگی دقیق کالیبره‌شده با طرح فیگما را مدیریت کنید.</p>
          </div>
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره فوتر و رنگ‌ها</button>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>متن برند فوتر</label>
            <input type="text" name="foot_agency_text" value="<?= e($foot['agencyText']) ?>">
          </div>
          <div class="adm-field">
            <label>لینک برند فوتر (آژانس تبلیغاتی نگاه)</label>
            <input type="text" dir="ltr" name="foot_agency_url" value="<?= e($foot['agencyUrl']) ?>">
          </div>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>متن امضای مجری دیجیتال</label>
            <input type="text" name="foot_powered_text" value="<?= e($foot['poweredByText']) ?>">
          </div>
          <div class="adm-field">
            <label>لینک امضای مجری (قدرت گرفته از نگاه مدیا)</label>
            <input type="text" dir="ltr" name="foot_powered_url" value="<?= e($foot['poweredByUrl']) ?>">
          </div>
        </div>

        <div class="adm-grid-2">
          <div class="adm-field">
            <label>جمله کپی‌رایت فوتر</label>
            <input type="text" name="foot_copy_prefix" value="<?= e($foot['copyrightPrefix']) ?>">
          </div>
          <div class="adm-field">
            <label>سال کپی‌رایت</label>
            <input type="text" name="foot_copy_year" value="<?= e($foot['copyrightYear']) ?>">
          </div>
        </div>

        <div class="adm-audio-flags">
          <label class="adm-check">
            <input type="checkbox" name="foot_show_dot" value="1" <?= !empty($foot['showDot']) ? 'checked' : '' ?>>
            <span>نمایش نقطه طلایی جداکننده فوتر</span>
          </label>
          <label class="adm-check">
            <input type="checkbox" name="foot_show_logo" value="1" <?= !empty($foot['showVahidLogo']) ? 'checked' : '' ?>>
            <span>نمایش لوگوی وحید تاج در فوتر</span>
          </label>
        </div>

        <hr class="adm-sep">
        <h3>پالت رنگ کالیبره‌شده با طرح مرجع (2.png)</h3>
        <div class="adm-grid-3">
          <div class="adm-field">
            <label>رنگ مرکز پس‌زمینه (#2A4C6A)</label>
            <input type="color" name="th_bg_center" value="<?= e($th['bgCenter']) ?>">
          </div>
          <div class="adm-field">
            <label>رنگ حاشیه پس‌زمینه (#16324B)</label>
            <input type="color" name="th_bg_edge" value="<?= e($th['bgEdge']) ?>">
          </div>
          <div class="adm-field">
            <label>رنگ زمینه کارت‌ها (#23435F)</label>
            <input type="color" name="th_card_bg" value="<?= e($th['cardBg']) ?>">
          </div>
          <div class="adm-field">
            <label>طلایی مرکز کادر سوژه (#CEB21D)</label>
            <input type="color" name="th_gold_center" value="<?= e($th['heroGoldCenter']) ?>">
          </div>
          <div class="adm-field">
            <label>طلایی لبه کادر سوژه (#BAA12C)</label>
            <input type="color" name="th_gold_edge" value="<?= e($th['heroGoldEdge']) ?>">
          </div>
          <div class="adm-field">
            <label>طلایی اکسنت و کنترل‌ها (#C9AD1E)</label>
            <input type="color" name="th_accent_gold" value="<?= e($th['accentGold']) ?>">
          </div>
        </div>

        <div class="adm-audio-flags">
          <label class="adm-check">
            <input type="checkbox" name="th_side_lines" value="1" <?= !empty($th['showSideLines']) ? 'checked' : '' ?>>
            <span>نمایش خطوط عمودی طلایی طرفین صفحه</span>
          </label>
          <label class="adm-check">
            <input type="checkbox" name="th_min_heights" value="1" <?= !empty($th['preserveFigmaMinHeights']) ? 'checked' : '' ?>>
            <span>حفظ حداقل ارتفاع کارت‌ها در صورت خالی بودن محتوا</span>
          </label>
        </div>

        <div class="adm-bottom-actions">
          <button type="submit" class="adm-btn adm-btn-gold">ذخیره تنظیمات فوتر و رنگ‌ها</button>
        </div>
      </form>

      <!-- پشتیبان‌گیری و بازیابی JSON -->
      <div class="adm-card" style="margin-top:24px;">
        <div class="adm-card-head">
          <div>
            <h2>پشتیبان‌گیری و بازیابی اطلاعات بروشور (JSON)</h2>
            <p>می‌توانید از کل تنظیمات و قطعات بکاپ بگیرید یا فایل پشتیبان قبلی را بازگردانی کنید.</p>
          </div>
          <a href="<?= e(url('admin/index.php?export=json')) ?>" class="adm-btn adm-btn-outline">دانلود فایل پشتیبان (JSON)</a>
        </div>
        <form method="post" enctype="multipart/form-data" class="adm-inline-upload">
          <?= csrf_input() ?>
          <input type="hidden" name="form_action" value="import_json">
          <input type="hidden" name="return_tab" value="settings">
          <input type="file" name="backup_json_file" accept=".json,application/json" required>
          <button type="submit" class="adm-btn adm-btn-gold">بازیابی فایل پشتیبان</button>
        </form>
      </div>

      <!-- تغییر نام کاربری و رمز عبور مدیر -->
      <form method="post" class="adm-card" style="margin-top:24px;">
        <?= csrf_input() ?>
        <input type="hidden" name="form_action" value="change_credentials">
        <input type="hidden" name="return_tab" value="settings">
        <div class="adm-card-head">
          <div>
            <h2>تغییر نام کاربری و رمز عبور پنل مدیریت</h2>
            <p>برای تغییر مشخصات ورود، رمز عبور فعلی خود را وارد نمایید.</p>
          </div>
        </div>
        <div class="adm-grid-3">
          <div class="adm-field">
            <label>رمز عبور فعلی *</label>
            <input type="password" dir="ltr" name="current_password" required>
          </div>
          <div class="adm-field">
            <label>نام کاربری جدید *</label>
            <input type="text" dir="ltr" name="new_username" value="<?= e($adminInfo['username']) ?>" required>
          </div>
          <div class="adm-field">
            <label>رمز عبور جدید (در صورت عدم تغییر خالی بگذارید)</label>
            <input type="password" dir="ltr" name="new_password">
          </div>
        </div>
        <button type="submit" class="adm-btn adm-btn-gold">بروزرسانی مشخصات ورود</button>
      </form>
    <?php endif; ?>
  </main>

  <script src="<?= e(asset_url('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
