<?php
/**
 * ClubCMS — Rendu des blocs JSON en HTML
 * Utilisé pour afficher les pages, articles, et blocs de la page d'accueil
 */
class BlockRenderer {

    public static function render(string|array|null $content, string $context = 'page'): string {
        if (empty($content)) return '';

        // Si c'est une chaîne, tenter de décoder le JSON
        if (is_string($content)) {
            $decoded = @json_decode($content, true);
            if (!is_array($decoded)) {
                // Ancien contenu HTML brut → l'afficher tel quel
                return $content;
            }
            $blocks = $decoded;
        } else {
            $blocks = $content;
        }

        if (empty($blocks)) return '';

        $html = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'paragraph';
            $html .= self::renderBlock($type, $block, $context);
        }
        return $html;
    }

    private static function renderBlock(string $type, array $b, string $ctx): string {
        $wrap = 'style="margin-bottom:1.75rem"';

        return match($type) {

            // ── Texte ──────────────────────────────────────────────
            'paragraph' => self::p_paragraph($b, $wrap),
            'heading'   => self::p_heading($b, $wrap),
            'quote'     => self::p_quote($b, $wrap),

            // ── Mise en page ───────────────────────────────────────
            'info_boxes'  => self::p_info_boxes($b, $wrap),
            'two_columns' => self::p_two_columns($b, $wrap),
            'cta'         => self::p_cta($b, $wrap),
            'alert'       => self::p_alert($b, $wrap),
            'divider'     => self::p_divider($b),

            // ── Médias ─────────────────────────────────────────────
            'image'       => self::p_image($b, $wrap),
            'video'       => self::p_video($b, $wrap),
            'gallery_grid'=> self::p_gallery_grid($b, $wrap),

            // ── Interactif ─────────────────────────────────────────
            'map'              => self::p_map($b, $wrap),
            'faq'              => self::p_faq($b, $wrap),
            'schedule'         => self::p_schedule($b, $wrap),
            'team'             => self::p_team($b, $wrap),
            'newsletter_form'  => self::p_newsletter($b, $wrap),

            // ── HTML libre & embed ────────────────────────────────
            'html'  => '<div '.$wrap.'>' . ($b['html'] ?? '') . '</div>',
            'embed' => self::p_embed($b, $wrap),

            // ── Nouveaux blocs ─────────────────────────────────────
            'icon_list'      => self::p_icon_list($b, $wrap),
            'table'          => self::p_table($b, $wrap),
            'highlight_box'  => self::p_highlight_box($b, $wrap),
            'stats_counter'  => self::p_stats_counter($b, $wrap),
            'steps'          => self::p_steps($b, $wrap),
            'price_table'    => self::p_price_table($b, $wrap),
            'testimonials'   => self::p_testimonials($b, $wrap),
            'countdown'      => self::p_countdown($b, $wrap),
            'latest_articles'=> self::p_latest_articles($b, $wrap),
            'contact_info'   => self::p_contact_info($b, $wrap),
            'social_links'   => self::p_social_links($b, $wrap),
            'accordion'      => self::p_accordion($b, $wrap),
            'banner_image'   => self::p_banner_image($b, $wrap),
            'partners'       => self::p_partners($b, $wrap),
            'weather'        => self::p_weather($b, $wrap),

            default => '',
        };
    }

    // ── PARAGRAPHE ───────────────────────────────────────────────
    private static function p_paragraph(array $b, string $wrap): string {
        $content = $b['content'] ?? '';
        if (empty(trim(strip_tags($content)))) return '';
        return '<div style="margin-bottom:1.75rem;line-height:1.85;font-size:1rem">'.$content.'</div>';
    }

    // ── TITRE ────────────────────────────────────────────────────
    private static function p_heading(array $b, string $wrap): string {
        $level    = in_array($b['level']??'h2', ['h2','h3','h4']) ? ($b['level']??'h2') : 'h2';
        $align    = in_array($b['align']??'left', ['left','center','right']) ? ($b['align']??'left') : 'left';
        $sizes    = ['h2'=>'2rem','h3'=>'1.5rem','h4'=>'1.2rem'];
        $size     = $sizes[$level];
        $title    = htmlspecialchars($b['title'] ?? '');
        $subtitle = htmlspecialchars($b['subtitle'] ?? '');
        $html     = '<div style="margin-bottom:1.75rem;text-align:'.$align.'">';
        $html    .= '<'.$level.' style="font-family:var(--font-heading);font-size:'.$size.';letter-spacing:.05em;margin-bottom:'.($subtitle?'.35rem':'.25rem').'">'.$title.'</'.$level.'>';
        if ($subtitle) $html .= '<p style="color:var(--color-muted);font-size:1rem;margin:0">'.$subtitle.'</p>';
        $html    .= '</div>';
        return $html;
    }

    // ── CITATION ─────────────────────────────────────────────────
    private static function p_quote(array $b, string $wrap): string {
        $color   = htmlspecialchars($b['color'] ?? '#3b82f6');
        $content = $b['content'] ?? '';
        $author  = htmlspecialchars($b['author'] ?? '');
        $html    = '<blockquote style="border-left:4px solid '.$color.';background:color-mix(in srgb,'.$color.' 8%,#fff);padding:1.25rem 1.5rem;border-radius:0 10px 10px 0;margin:0 0 1.75rem">';
        $html   .= '<div style="font-size:1.05rem;line-height:1.75;color:#374151;font-style:italic">'.$content.'</div>';
        if ($author) $html .= '<div style="margin-top:.75rem;font-size:.875rem;font-weight:700;color:'.$color.'">— '.$author.'</div>';
        $html   .= '</blockquote>';
        return $html;
    }

    // ── BOÎTES D'INFO ────────────────────────────────────────────
    private static function p_info_boxes(array $b, string $wrap): string {
        $cols  = max(2, min(4, (int)($b['cols'] ?? 3)));
        $title = htmlspecialchars($b['title'] ?? '');
        $html  = '<div '.$wrap.'>';
        if ($title) $html .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;text-align:center;margin-bottom:1.5rem;letter-spacing:.05em">'.$title.'</h2>';
        $html .= '<div style="display:grid;grid-template-columns:repeat('.$cols.',1fr);gap:1.25rem">';
        for ($c = 1; $c <= $cols; $c++) {
            $icon  = htmlspecialchars($b['box'.$c.'_icon'] ?? '');
            $btitle= htmlspecialchars($b['box'.$c.'_title'] ?? '');
            $text  = nl2br(htmlspecialchars($b['box'.$c.'_text'] ?? ''));
            if (!$btitle && !$text) continue;
            $html .= '<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:12px;padding:1.5rem;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.06)">';
            if ($icon) $html .= '<div style="font-size:2.5rem;margin-bottom:.875rem">'.$icon.'</div>';
            if ($btitle) $html .= '<div style="font-weight:700;font-size:1rem;margin-bottom:.5rem;color:var(--color-text)">'.$btitle.'</div>';
            if ($text) $html .= '<div style="font-size:.875rem;color:var(--color-muted);line-height:1.65">'.$text.'</div>';
            $html .= '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    // ── DEUX COLONNES ────────────────────────────────────────────
    private static function p_two_columns(array $b, string $wrap): string {
        $split  = $b['split'] ?? '50-50';
        $splits = ['50-50'=>'1fr 1fr','60-40'=>'3fr 2fr','40-60'=>'2fr 3fr','70-30'=>'7fr 3fr'];
        $grid   = $splits[$split] ?? '1fr 1fr';
        $left   = $b['col_left'] ?? '';
        $right  = $b['col_right'] ?? '';
        return '<div style="margin-bottom:1.75rem;display:grid;grid-template-columns:'.$grid.';gap:2rem;align-items:start">'
            . '<div style="line-height:1.8">'.$left.'</div>'
            . '<div style="line-height:1.8">'.$right.'</div>'
            . '</div>';
    }

    // ── CTA ──────────────────────────────────────────────────────
    private static function p_cta(array $b, string $wrap): string {
        $bg       = htmlspecialchars($b['bg_color'] ?? '#1d4ed8');
        $title    = htmlspecialchars($b['title'] ?? '');
        $subtitle = htmlspecialchars($b['subtitle'] ?? '');
        $btnLabel = htmlspecialchars($b['btn_label'] ?? 'En savoir plus');
        $btnUrl   = htmlspecialchars($b['btn_url'] ?? '/');
        $btnStyle = $b['btn_style'] ?? 'white';
        $btnCss   = match($btnStyle) {
            'outline' => 'background:transparent;color:#fff;border:2px solid #fff',
            'dark'    => 'background:#1e293b;color:#fff;border:none',
            default   => 'background:#fff;color:'.$bg.';border:none',
        };
        $html  = '<div style="margin-bottom:1.75rem;background:'.$bg.';border-radius:16px;padding:3rem 2rem;text-align:center">';
        if ($title)    $html .= '<h2 style="font-family:var(--font-heading);font-size:2rem;color:#fff;letter-spacing:.06em;margin-bottom:.5rem">'.$title.'</h2>';
        if ($subtitle) $html .= '<p style="color:rgba(255,255,255,.85);font-size:1rem;margin-bottom:1.5rem">'.$subtitle.'</p>';
        $html .= '<a href="'.u($btnUrl).'" style="display:inline-block;'.$btnCss.';padding:.75rem 2rem;border-radius:8px;font-weight:700;font-size:1rem;text-decoration:none">'.$btnLabel.'</a>';
        $html .= '</div>';
        return $html;
    }

    // ── ALERTE ───────────────────────────────────────────────────
    private static function p_alert(array $b, string $wrap): string {
        $type  = $b['alert_type'] ?? 'info';
        $styles= [
            'info'    => ['bg'=>'#eff6ff','border'=>'#bfdbfe','text'=>'#1d4ed8','icon'=>'ℹ️'],
            'success' => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','text'=>'#15803d','icon'=>'✅'],
            'warning' => ['bg'=>'#fefce8','border'=>'#fef08a','text'=>'#a16207','icon'=>'⚠️'],
            'error'   => ['bg'=>'#fef2f2','border'=>'#fecaca','text'=>'#dc2626','icon'=>'❌'],
        ];
        $s      = $styles[$type] ?? $styles['info'];
        $title  = htmlspecialchars($b['title'] ?? '');
        $content= $b['content'] ?? '';
        $html   = '<div style="margin-bottom:1.75rem;background:'.$s['bg'].';border:1.5px solid '.$s['border'].';border-radius:10px;padding:1rem 1.25rem">';
        $html  .= '<div style="display:flex;gap:.75rem;align-items:flex-start">';
        $html  .= '<span style="font-size:1.1rem;flex-shrink:0;margin-top:.1rem">'.$s['icon'].'</span>';
        $html  .= '<div style="color:'.$s['text'].'">';
        if ($title) $html .= '<div style="font-weight:700;margin-bottom:.25rem">'.$title.'</div>';
        $html  .= '<div style="font-size:.9rem;line-height:1.65">'.$content.'</div>';
        $html  .= '</div></div></div>';
        return $html;
    }

    // ── SÉPARATEUR ───────────────────────────────────────────────
    private static function p_divider(array $b): string {
        $style   = $b['style'] ?? 'line';
        $spacing = match($b['spacing'] ?? 'md') { 'sm'=>'1rem', 'lg'=>'3rem', default=>'2rem' };
        $inner   = match($style) {
            'thick' => '<hr style="border:none;border-top:3px solid var(--color-border)">',
            'dots'  => '<div style="text-align:center;color:var(--color-muted);letter-spacing:.5rem">· · · · ·</div>',
            'stars' => '<div style="text-align:center;color:var(--color-muted);letter-spacing:.5rem">✦ ✦ ✦</div>',
            'space' => '',
            default => '<hr style="border:none;border-top:1px solid var(--color-border)">',
        };
        return '<div style="margin:'.$spacing.' 0">'.$inner.'</div>';
    }

    // ── IMAGE ────────────────────────────────────────────────────
    private static function p_image(array $b, string $wrap): string {
        $src     = !empty($b['image_src']) ? asset($b['image_src']) : htmlspecialchars($b['image_url'] ?? '');
        if (!$src) return '';
        $caption = htmlspecialchars($b['caption'] ?? '');
        $align   = $b['align'] ?? 'center';
        $maxW    = match($b['width'] ?? 'full') { 'large'=>'800px','medium'=>'500px','small'=>'300px',default=>'100%' };
        $html    = '<figure style="text-align:'.$align.';margin:0 0 1.75rem">';
        $html   .= '<img src="'.$src.'" alt="'.htmlspecialchars($caption).'" style="max-width:'.$maxW.';width:100%;border-radius:10px;display:inline-block">';
        if ($caption) $html .= '<figcaption style="margin-top:.5rem;font-size:.82rem;color:var(--color-muted)">'.$caption.'</figcaption>';
        $html   .= '</figure>';
        return $html;
    }

    // ── VIDÉO ────────────────────────────────────────────────────
    private static function p_video(array $b, string $wrap): string {
        $url = $b['url'] ?? '';
        if (!$url) return '';
        $embedUrl = self::videoEmbedUrl($url);
        if (!$embedUrl) return '<div style="margin-bottom:1.75rem"><p style="color:var(--color-muted)">URL vidéo non supportée.</p></div>';
        $ratio = match($b['ratio'] ?? '16-9') { '4-3'=>'75%','1-1'=>'100%',default=>'56.25%' };
        $title = htmlspecialchars($b['title'] ?? '');
        $html  = '<div '.$wrap.'>';
        if ($title) $html .= '<h3 style="font-family:var(--font-heading);font-size:1.3rem;margin-bottom:.875rem">'.$title.'</h3>';
        $html .= '<div style="position:relative;padding-bottom:'.$ratio.';height:0;border-radius:12px;overflow:hidden">';
        $html .= '<iframe src="'.$embedUrl.'" style="position:absolute;inset:0;width:100%;height:100%;border:none" allowfullscreen loading="lazy"></iframe>';
        $html .= '</div></div>';
        return $html;
    }

    private static function videoEmbedUrl(string $url): string {
        if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $m)) return 'https://www.youtube.com/embed/'.$m[1];
        if (preg_match('/youtu\.be\/([^?]+)/', $url, $m)) return 'https://www.youtube.com/embed/'.$m[1];
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) return 'https://player.vimeo.com/video/'.$m[1];
        return '';
    }

    // ── GALERIE ──────────────────────────────────────────────────
    private static function p_gallery_grid(array $b, string $wrap): string {
        $cols   = (int)($b['cols'] ?? 3);
        $count  = (int)($b['count'] ?? 6);
        $title  = htmlspecialchars($b['title'] ?? '');
        $photos = Database::all("SELECT filename, folder_id FROM cc_gallery_photos ORDER BY id DESC LIMIT ?", [$count]);
        if (empty($photos)) return '';
        $html   = '<div '.$wrap.'>';
        if ($title) $html .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;text-align:center;margin-bottom:1.25rem">'.$title.'</h2>';
        $html  .= '<div style="display:grid;grid-template-columns:repeat('.$cols.',1fr);gap:.75rem">';
        foreach ($photos as $photo) {
            $src   = asset('assets/uploads/gallery/'.$photo['filename']);
            $html .= '<div style="aspect-ratio:1;overflow:hidden;border-radius:8px"><img src="'.$src.'" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>';
        }
        $html  .= '</div></div>';
        return $html;
    }

    // ── CARTE ────────────────────────────────────────────────────
    private static function p_map(array $b, string $wrap): string {
        $address   = htmlspecialchars($b['address'] ?? '');
        if (!$address) return '';
        $title     = htmlspecialchars($b['title'] ?? '');
        $height    = (int)($b['height'] ?? 400);
        $extraInfo = htmlspecialchars($b['extra_info'] ?? '');
        $encoded   = urlencode($address);
        $html      = '<div '.$wrap.'>';
        if ($title) $html .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1rem">'.$title.'</h2>';
        $html .= '<div style="border-radius:12px;overflow:hidden;border:1px solid var(--color-border)">';
        $html .= '<iframe src="https://maps.google.com/maps?q='.$encoded.'&output=embed&z=15" width="100%" height="'.$height.'" style="border:none" loading="lazy"></iframe>';
        $html .= '</div>';
        if ($address || $extraInfo) {
            $html .= '<div style="margin-top:.75rem;font-size:.875rem;color:var(--color-muted);display:flex;gap:.5rem;align-items:center">';
            $html .= '<span>📍</span><span>'.$address.($extraInfo?' &nbsp;·&nbsp; '.$extraInfo:'').'</span></div>';
        }
        $html .= '</div>';
        return $html;
    }

    // ── FAQ ──────────────────────────────────────────────────────
    private static function p_faq(array $b, string $wrap): string {
        $faqs  = $b['faqs'] ?? [];
        if (empty($faqs)) return '';
        $title = htmlspecialchars($b['title'] ?? '');
        $id    = 'faq-'.uniqid();
        $html  = '<div '.$wrap.'>';
        if ($title) $html .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1.25rem">'.$title.'</h2>';
        foreach ($faqs as $fi => $faq) {
            $q    = htmlspecialchars($faq['q'] ?? '');
            $a    = nl2br(htmlspecialchars($faq['a'] ?? ''));
            $fid  = $id.'-'.$fi;
            if (!$q) continue;
            $html .= '<details style="border:1.5px solid var(--color-border);border-radius:10px;margin-bottom:.5rem;overflow:hidden">';
            $html .= '<summary style="padding:1rem 1.25rem;cursor:pointer;font-weight:600;font-size:.95rem;list-style:none;display:flex;justify-content:space-between;align-items:center;user-select:none">';
            $html .= $q.'<span style="font-size:.75rem;color:var(--color-muted);flex-shrink:0;margin-left:.5rem">▼</span></summary>';
            $html .= '<div style="padding:.875rem 1.25rem 1.25rem;color:var(--color-muted);line-height:1.7;font-size:.9rem;border-top:1px solid var(--color-border)">'.$a.'</div>';
            $html .= '</details>';
        }
        $html  .= '</div>';
        return $html;
    }

    // ── PLANNING ─────────────────────────────────────────────────
    private static function p_schedule(array $b, string $wrap): string {
        $count  = (int)($b['count'] ?? 5);
        $title  = htmlspecialchars($b['title'] ?? 'Prochains créneaux');
        $slots  = Database::all("SELECT * FROM cc_planning_slots WHERE date_start >= NOW() ORDER BY date_start ASC LIMIT ?", [$count]);
        $html   = '<div '.$wrap.'>';
        $html  .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1rem">'.$title.'</h2>';
        if (empty($slots)) {
            $html .= '<p style="color:var(--color-muted)">Aucun créneau à venir.</p>';
        } else {
            $html .= '<div style="display:flex;flex-direction:column;gap:.5rem">';
            foreach ($slots as $s) {
                $html .= '<div style="display:flex;align-items:center;gap:1rem;padding:.875rem 1rem;background:#fff;border:1px solid var(--color-border);border-radius:10px">';
                $html .= '<div style="background:var(--color-primary);color:#fff;border-radius:8px;padding:.4rem .75rem;text-align:center;min-width:52px;flex-shrink:0"><div style="font-size:1.1rem;font-weight:700">'.date('d',strtotime($s['date_start'])).'</div><div style="font-size:.65rem;text-transform:uppercase">'.Helpers::dateFormat($s['date_start']).'</div></div>';
                $html .= '<div><div style="font-weight:600">' . htmlspecialchars($s['title']??'Créneau') . '</div>';
                if ($s['coach_name']??false) $html .= '<div style="font-size:.8rem;color:var(--color-muted)">Coach : '.htmlspecialchars($s['coach_name']).'</div>';
                $html .= '</div></div>';
            }
            $html .= '</div>';
        }
        $html  .= '<div style="margin-top:1rem"><a href="'.u('/planning').'" style="color:var(--color-primary);font-weight:600;font-size:.875rem">Voir tout le planning →</a></div>';
        $html  .= '</div>';
        return $html;
    }

    // ── ÉQUIPE ───────────────────────────────────────────────────
    private static function p_team(array $b, string $wrap): string {
        $title   = htmlspecialchars($b['title'] ?? 'Notre équipe');
        $content = $b['content'] ?? '';
        $coaches = Database::all("SELECT * FROM cc_users WHERE role IN ('coach','admin','superadmin') AND status='active' ORDER BY role DESC LIMIT 12");
        $html    = '<div '.$wrap.'>';
        $html   .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:'.($content?'.75rem':'1.25rem').'">'.$title.'</h2>';
        if ($content) $html .= '<p style="color:var(--color-muted);margin-bottom:1.5rem;line-height:1.75">'.$content.'</p>';
        if ($coaches) {
            $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1.25rem">';
            foreach ($coaches as $m) {
                $initials = mb_strtoupper(mb_substr($m['firstname'],0,1).mb_substr($m['lastname'],0,1));
                $html .= '<div style="text-align:center">';
                if ($m['avatar']) {
                    $html .= '<img src="'.asset($m['avatar']).'" style="width:72px;height:72px;border-radius:50%;object-fit:cover;margin:0 auto .5rem;display:block">';
                } else {
                    $html .= '<div style="width:72px;height:72px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;margin:0 auto .5rem">'.$initials.'</div>';
                }
                $html .= '<div style="font-weight:600;font-size:.9rem">'.htmlspecialchars($m['firstname'].' '.$m['lastname']).'</div>';
                $html .= '<div style="font-size:.75rem;color:var(--color-muted)">'.htmlspecialchars(ucfirst($m['role'])).'</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html  .= '</div>';
        return $html;
    }

    // ── NEWSLETTER ───────────────────────────────────────────────
    private static function p_newsletter(array $b, string $wrap): string {
        $title    = htmlspecialchars($b['title'] ?? 'Restez informé');
        $subtitle = htmlspecialchars($b['subtitle'] ?? 'Newsletter du club');
        return '<div style="margin-bottom:1.75rem;background:var(--color-primary);border-radius:16px;padding:2rem;text-align:center">'
            . '<div style="font-weight:700;font-size:1.1rem;color:#fff;margin-bottom:.25rem">'.$title.'</div>'
            . '<div style="font-size:.875rem;color:rgba(255,255,255,.8);margin-bottom:1.25rem">'.$subtitle.'</div>'
            . '<form action="'.u('/newsletter/subscribe').'" method="post" style="display:flex;gap:.5rem;max-width:400px;margin:0 auto">'
            . '<input type="email" name="email" placeholder="votre@email.fr" required style="flex:1;border:none;border-radius:6px;padding:.55rem .875rem;font-size:.9rem">'
            . '<button type="submit" style="background:#fff;color:var(--color-primary);border:none;border-radius:6px;padding:.55rem 1.25rem;font-weight:700;cursor:pointer">S\'inscrire</button>'
            . '</form></div>';
    }

    private static function p_embed(array $b, string $wrap): string {
        $html = $b['html'] ?? '';
        if (!$html) return '';
        $title = htmlspecialchars($b['title'] ?? '');
        $height = (int)($b['height'] ?? 400);
        $out = '<div '.$wrap.'>';
        if ($title) $out .= '<h3 style="font-family:var(--font-heading);font-size:1.3rem;margin-bottom:.875rem">'.$title.'</h3>';
        $out .= '<div style="min-height:'.$height.'px">'.$html.'</div></div>';
        return $out;
    }

    private static function p_icon_list(array $b, string $wrap): string {
        $items = $b['items'] ?? '';
        if (!$items) return '';
        $lines = array_filter(array_map('trim', explode("
", $items)));
        $cols  = (int)($b['cols'] ?? 1);
        $title = htmlspecialchars($b['title'] ?? '');
        $grid  = $cols === 2 ? 'display:grid;grid-template-columns:1fr 1fr;gap:.5rem .75rem' : 'display:flex;flex-direction:column;gap:.5rem';
        $out   = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1rem">'.$title.'</h2>';
        $out  .= '<div style="'.$grid.'">';
        foreach ($lines as $line) {
            $out .= '<div style="display:flex;align-items:flex-start;gap:.6rem;padding:.4rem 0">';
            $out .= '<span style="flex-shrink:0;font-size:1.1rem">'.mb_substr($line,0,2).'</span>';
            $out .= '<span style="line-height:1.55;color:var(--color-text)">'.htmlspecialchars(trim(mb_substr($line,2))).'</span>';
            $out .= '</div>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_table(array $b, string $wrap): string {
        $headers = array_map('trim', explode('|', $b['headers'] ?? ''));
        $rows    = array_filter(array_map('trim', explode("
", $b['rows'] ?? '')));
        $title   = htmlspecialchars($b['title'] ?? '');
        $striped = !empty($b['striped']);
        if (empty($headers[0]) && empty($rows)) return '';
        $out = '<div style="margin-bottom:1.75rem;overflow-x:auto">';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.4rem;margin-bottom:.875rem">'.$title.'</h2>';
        $out .= '<table style="width:100%;border-collapse:collapse;font-size:.9rem">';
        if ($headers[0]) {
            $out .= '<thead><tr>';
            foreach ($headers as $h) $out .= '<th style="padding:.65rem 1rem;text-align:left;background:var(--color-primary);color:#fff;font-weight:700;border:1px solid rgba(255,255,255,.2)">'.htmlspecialchars($h).'</th>';
            $out .= '</tr></thead>';
        }
        $out .= '<tbody>';
        foreach (array_values($rows) as $ri => $row) {
            $cells = array_map('trim', explode('|', $row));
            $bg    = ($striped && $ri%2===1) ? 'background:#f8fafc' : '';
            $out  .= '<tr>';
            foreach ($cells as $cell) $out .= '<td style="padding:.6rem 1rem;border:1px solid #e2e8f0;'.$bg.'">'.htmlspecialchars($cell).'</td>';
            $out  .= '</tr>';
        }
        $out .= '</tbody></table></div>';
        return $out;
    }

    private static function p_highlight_box(array $b, string $wrap): string {
        $bg     = htmlspecialchars($b['bg_color'] ?? '#fef3c7');
        $border = htmlspecialchars($b['border_color'] ?? '#f59e0b');
        $icon   = htmlspecialchars($b['icon'] ?? '⭐');
        $title  = htmlspecialchars($b['title'] ?? '');
        $content= $b['content'] ?? '';
        return '<div style="margin-bottom:1.75rem;background:'.$bg.';border:2px solid '.$border.';border-radius:12px;padding:1.25rem 1.5rem">'
            . '<div style="display:flex;gap:.875rem;align-items:flex-start">'
            . '<span style="font-size:1.8rem;flex-shrink:0">'.$icon.'</span>'
            . '<div>'.($title ? '<div style="font-weight:700;font-size:1rem;margin-bottom:.4rem">'.$title.'</div>' : '')
            . '<div style="line-height:1.7;font-size:.95rem">'.$content.'</div>'
            . '</div></div></div>';
    }

    private static function p_stats_counter(array $b, string $wrap): string {
        $title = htmlspecialchars($b['title'] ?? '');
        $stats = [];
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($b['stat'.$i.'_num'])) $stats[] = [$b['stat'.$i.'_num'],$b['stat'.$i.'_unit']??'',$b['stat'.$i.'_label']??''];
        }
        if (empty($stats)) return '';
        $cols = count($stats);
        $out  = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;text-align:center;margin-bottom:1.5rem">'.$title.'</h2>';
        $out .= '<div style="display:grid;grid-template-columns:repeat('.$cols.',1fr);gap:1rem;text-align:center">';
        foreach ($stats as [$num,$unit,$label]) {
            $out .= '<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:12px;padding:1.5rem">';
            $out .= '<div style="font-family:var(--font-heading);font-size:2.5rem;color:var(--color-primary);letter-spacing:.05em">'.htmlspecialchars($num).'<small style="font-size:1.2rem">'.htmlspecialchars($unit).'</small></div>';
            $out .= '<div style="color:var(--color-muted);font-size:.875rem;margin-top:.25rem">'.htmlspecialchars($label).'</div>';
            $out .= '</div>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_steps(array $b, string $wrap): string {
        $steps = $b['steps'] ?? [];
        if (empty($steps)) return '';
        $title = htmlspecialchars($b['title'] ?? '');
        $out   = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1.5rem">'.$title.'</h2>';
        $out  .= '<div style="display:flex;flex-direction:column;gap:1.25rem">';
        foreach (array_values($steps) as $i => $step) {
            if (empty($step['title'])) continue;
            $out .= '<div style="display:flex;gap:1.25rem;align-items:flex-start">';
            $out .= '<div style="background:var(--color-primary);color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0;margin-top:.1rem">'.($i+1).'</div>';
            $out .= '<div style="flex:1"><div style="font-weight:700;font-size:1rem;margin-bottom:.3rem">'.htmlspecialchars($step['title']).'</div>';
            if (!empty($step['content'])) $out .= '<div style="color:var(--color-muted);font-size:.9rem;line-height:1.65">'.htmlspecialchars($step['content']).'</div>';
            $out .= '</div></div>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_price_table(array $b, string $wrap): string {
        $cols  = (int)($b['cols'] ?? 2);
        $title = htmlspecialchars($b['title'] ?? '');
        $out   = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.8rem;text-align:center;margin-bottom:1.5rem">'.$title.'</h2>';
        $out  .= '<div style="display:grid;grid-template-columns:repeat('.$cols.',1fr);gap:1.25rem">';
        for ($p = 1; $p <= $cols; $p++) {
            $name     = htmlspecialchars($b['plan'.$p.'_name'] ?? '');
            $price    = htmlspecialchars($b['plan'.$p.'_price'] ?? '');
            $desc     = htmlspecialchars($b['plan'.$p.'_desc'] ?? '');
            $features = array_filter(array_map('trim', explode("
", $b['plan'.$p.'_features'] ?? '')));
            $hl       = !empty($b['plan'.$p.'_highlight']);
            if (!$name && !$price) continue;
            $bg = $hl ? 'background:var(--color-primary);color:#fff' : 'background:#fff;color:var(--color-text)';
            $out .= '<div style="border:'.(2).'px solid '.($hl?'var(--color-primary)':'#e2e8f0').';border-radius:16px;padding:2rem;text-align:center;'.$bg.'">';
            $out .= '<div style="font-weight:700;font-size:1.1rem;margin-bottom:.5rem;'.($hl?'color:#fff':'').'">'.$name.'</div>';
            $out .= '<div style="font-family:var(--font-heading);font-size:3rem;margin:.5rem 0;letter-spacing:.02em;'.($hl?'color:#fff':'color:var(--color-primary)').'">'.$price.'</div>';
            if ($desc) $out .= '<div style="font-size:.82rem;opacity:.75;margin-bottom:1.25rem">'.$desc.'</div>';
            if ($features) {
                $out .= '<ul style="list-style:none;padding:0;margin:1.25rem 0;text-align:left">';
                foreach ($features as $f) $out .= '<li style="padding:.4rem 0;border-bottom:1px solid '.($hl?'rgba(255,255,255,.2)':'#f1f5f9').';font-size:.875rem">✓ '.htmlspecialchars($f).'</li>';
                $out .= '</ul>';
            }
            $out .= '</div>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_testimonials(array $b, string $wrap): string {
        $items = $b['items'] ?? [];
        if (empty($items)) return '';
        $title = htmlspecialchars($b['title'] ?? '');
        $out   = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;text-align:center;margin-bottom:1.5rem">'.$title.'</h2>';
        $out  .= '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem">';
        foreach ($items as $t) {
            if (empty($t['text'])) continue;
            $out .= '<div style="background:#fff;border:1.5px solid var(--color-border);border-radius:12px;padding:1.5rem">';
            $out .= '<div style="font-size:2rem;color:var(--color-primary);margin-bottom:.75rem">❝</div>';
            $out .= '<p style="color:var(--color-text);line-height:1.7;font-style:italic;margin-bottom:1rem">'.htmlspecialchars($t['text']).'</p>';
            $out .= '<div style="font-weight:700;font-size:.9rem">'.htmlspecialchars($t['name'] ?? '').'</div>';
            if (!empty($t['role'])) $out .= '<div style="font-size:.78rem;color:var(--color-muted)">'.htmlspecialchars($t['role']).'</div>';
            $out .= '</div>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_countdown(array $b, string $wrap): string {
        $target = $b['target_date'] ?? '';
        if (!$target) return '';
        $title  = htmlspecialchars($b['title'] ?? '');
        $event  = htmlspecialchars($b['event_name'] ?? '');
        $id     = 'cd-'.uniqid();
        $out    = '<div style="margin-bottom:1.75rem;background:var(--color-primary);color:#fff;border-radius:16px;padding:2.5rem;text-align:center">';
        if ($title)  $out .= '<div style="font-size:.9rem;opacity:.8;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.1em">'.$title.'</div>';
        if ($event)  $out .= '<div style="font-family:var(--font-heading);font-size:1.8rem;letter-spacing:.05em;margin-bottom:1.5rem">'.$event.'</div>';
        $out .= '<div id="'.$id.'" style="display:flex;justify-content:center;gap:1.5rem">';
        foreach (['jours','heures','minutes','secondes'] as $unit) {
            $out .= '<div><div class="'.$id.'-num" data-unit="'.$unit.'" style="font-family:var(--font-heading);font-size:3rem;font-weight:700;min-width:3rem">--</div><div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;opacity:.75">'.$unit.'</div></div>';
        }
        $out .= '</div>';
        $out .= '<script>
(function(){
  var target = new Date("'.$target.'").getTime();
  var units = document.querySelectorAll(".'.$id.'-num");
  function update(){
    var now = Date.now();
    var diff = Math.max(0, target - now);
    var d = Math.floor(diff/86400000);
    var h = Math.floor((diff%86400000)/3600000);
    var m = Math.floor((diff%3600000)/60000);
    var s = Math.floor((diff%60000)/1000);
    [d,h,m,s].forEach(function(v,i){units[i].textContent=String(v).padStart(2,"0");});
    if(diff>0) setTimeout(update,1000);
    else units.forEach(function(u){u.textContent="00";});
  }
  update();
})();
</script>';
        $out .= '</div>';
        return $out;
    }

    private static function p_latest_articles(array $b, string $wrap): string {
        $count  = (int)($b['count'] ?? 3);
        $layout = $b['layout'] ?? 'grid';
        $title  = htmlspecialchars($b['title'] ?? 'Dernières actualités');
        $arts   = Database::all("SELECT a.*, u.firstname, u.lastname FROM cc_articles a JOIN cc_users u ON a.user_id=u.id WHERE a.type='article' AND a.published=1 ORDER BY a.created_at DESC LIMIT ?", [$count]);
        if (empty($arts)) return '';
        $gridCss = $layout === 'grid' ? 'display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem' : 'display:flex;flex-direction:column;gap:1rem';
        $out = '<div '.$wrap.'>';
        $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1.25rem">'.$title.'</h2>';
        $out .= '<div style="'.$gridCss.'">';
        foreach ($arts as $a) {
            $out .= '<article style="background:#fff;border:1px solid var(--color-border);border-radius:10px;overflow:hidden">';
            if ($a['cover'] && $layout === 'grid') $out .= '<img src="'.asset($a['cover']).'" style="width:100%;height:180px;object-fit:cover">';
            $out .= '<div style="padding:1rem">';
            $out .= '<div style="font-size:.75rem;color:var(--color-muted);margin-bottom:.3rem">'.Helpers::dateFormat($a['created_at']).'</div>';
            $out .= '<h3 style="font-weight:700;font-size:.95rem;margin-bottom:.4rem"><a href="'.u('/'.$a['slug']).'" style="color:var(--color-text);text-decoration:none">'.htmlspecialchars($a['title']).'</a></h3>';
            if ($a['excerpt']) $out .= '<p style="font-size:.82rem;color:var(--color-muted);line-height:1.55">'.htmlspecialchars(Helpers::excerpt($a['excerpt'],80)).'</p>';
            $out .= '<a href="'.u('/'.$a['slug']).'" style="color:var(--color-primary);font-size:.82rem;font-weight:600;display:inline-block;margin-top:.5rem">Lire →</a>';
            $out .= '</div></article>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_contact_info(array $b, string $wrap): string {
        $title = htmlspecialchars($b['title'] ?? '');
        $items = [
            '📞' => htmlspecialchars($b['phone'] ?? ''),
            '✉️' => htmlspecialchars($b['email'] ?? ''),
            '📍' => htmlspecialchars($b['address'] ?? ''),
            '🕐' => htmlspecialchars($b['hours'] ?? ''),
        ];
        $out = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1.25rem">'.$title.'</h2>';
        $out .= '<div style="display:flex;flex-direction:column;gap:.875rem">';
        foreach ($items as $icon => $val) {
            if (!$val) continue;
            $out .= '<div style="display:flex;align-items:center;gap:.875rem;padding:.75rem 1rem;background:#fff;border:1px solid var(--color-border);border-radius:10px">';
            $out .= '<span style="font-size:1.3rem;flex-shrink:0">'.$icon.'</span>';
            $out .= '<span style="font-size:.95rem">'.$val.'</span>';
            $out .= '</div>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_social_links(array $b, string $wrap): string {
        $title = htmlspecialchars($b['title'] ?? '');
        $networks = [
            'facebook'  => ['label'=>'Facebook',   'color'=>'#1877f2'],
            'instagram' => ['label'=>'Instagram',  'color'=>'#e1306c'],
            'youtube'   => ['label'=>'YouTube',    'color'=>'#ff0000'],
            'twitter'   => ['label'=>'Twitter / X','color'=>'#000000'],
            'tiktok'    => ['label'=>'TikTok',     'color'=>'#010101'],
            'linkedin'  => ['label'=>'LinkedIn',   'color'=>'#0a66c2'],
            'snapchat'  => ['label'=>'Snapchat',   'color'=>'#fffc00', 'text'=>'#000'],
            'discord'   => ['label'=>'Discord',    'color'=>'#5865f2'],
            'twitch'    => ['label'=>'Twitch',     'color'=>'#9146ff'],
            'whatsapp'  => ['label'=>'WhatsApp',   'color'=>'#25d366'],
        ];
        $out = '<div style="margin-bottom:1.75rem;text-align:center">';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.4rem;margin-bottom:1.25rem">'.$title.'</h2>';
        $out .= '<div style="display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center">';
        foreach ($networks as $key => $net) {
            $url = $b[$key] ?? '';
            if (!$url) continue;
            $textColor = $net['text'] ?? '#fff';
            $out .= '<a href="'.htmlspecialchars($url).'" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.25rem;background:'.$net['color'].';color:'.$textColor.';border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">';
            $out .= htmlspecialchars($net['label']).'</a>';
        }
        // Réseaux personnalisés libres
        for ($sn = 1; $sn <= 3; $sn++) {
            $curl  = $b['custom'.$sn.'_url'] ?? '';
            $clabel= $b['custom'.$sn.'_label'] ?? '';
            if (!$curl) continue;
            $out .= '<a href="'.htmlspecialchars($curl).'" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.25rem;background:#64748b;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">🔗 '.htmlspecialchars($clabel ?: 'Lien').'</a>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private static function p_accordion(array $b, string $wrap): string {
        $items = $b['items'] ?? [];
        if (empty($items)) return '';
        $title = htmlspecialchars($b['title'] ?? '');
        $out   = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.6rem;margin-bottom:1.25rem">'.$title.'</h2>';
        foreach ($items as $item) {
            if (empty($item['title'])) continue;
            $out .= '<details style="border:1.5px solid var(--color-border);border-radius:10px;margin-bottom:.5rem;overflow:hidden">';
            $out .= '<summary style="padding:1rem 1.25rem;cursor:pointer;font-weight:600;font-size:.95rem;display:flex;justify-content:space-between;align-items:center;user-select:none;list-style:none">';
            $out .= htmlspecialchars($item['title']).'<span style="font-size:.75rem;color:var(--color-muted)">▼</span></summary>';
            $out .= '<div style="padding:.875rem 1.25rem 1.25rem;color:var(--color-muted);line-height:1.7;border-top:1px solid var(--color-border)">'.htmlspecialchars($item['content'] ?? '').'</div>';
            $out .= '</details>';
        }
        $out .= '</div>';
        return $out;
    }

    private static function p_banner_image(array $b, string $wrap): string {
        $src     = !empty($b['image_src']) ? asset($b['image_src']) : htmlspecialchars($b['image_url'] ?? '');
        if (!$src) return '';
        $height  = (int)($b['height'] ?? 400);
        $overlay = min(80, max(0, (int)($b['overlay'] ?? 40)));
        $title   = htmlspecialchars($b['title'] ?? '');
        $subtitle= htmlspecialchars($b['subtitle'] ?? '');
        $out     = '<div style="margin-bottom:1.75rem;position:relative;height:'.$height.'px;border-radius:16px;overflow:hidden;display:flex;align-items:center;justify-content:center">';
        $out    .= '<img src="'.$src.'" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">';
        $out    .= '<div style="position:absolute;inset:0;background:rgba(0,0,0,'.($overlay/100).')"></div>';
        if ($title || $subtitle) {
            $out .= '<div style="position:relative;text-align:center;color:#fff;padding:2rem;z-index:1">';
            if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:clamp(1.5rem,4vw,3rem);letter-spacing:.06em;margin-bottom:.5rem">'.$title.'</h2>';
            if ($subtitle) $out .= '<p style="font-size:1.1rem;opacity:.9">'.$subtitle.'</p>';
            $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
    }

    private static function p_weather(array $b, string $wrap): string {
        $city  = htmlspecialchars($b['city'] ?? '');
        $style = $b['weather_style'] ?? 'compact';
        if (!$city) return '';
        $id = 'wttr-'.uniqid();
        if ($style === 'card') {
            $out  = '<div '.$wrap.' style="text-align:center;padding:1rem">';
            $out .= '<div id="'.$id.'" style="display:inline-block;background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;padding:1rem 1.5rem;font-size:.95rem;color:#0369a1">';
            $out .= '<span style="font-size:1.25rem">🌤</span> <strong>'.htmlspecialchars($city).'</strong> : <span id="'.$id.'-data">Chargement…</span>';
            $out .= '</div></div>';
        } else {
            $out  = '<div '.$wrap.' style="padding:.5rem 0">';
            $out .= '<div id="'.$id.'" style="font-size:.85rem;color:#0369a1;display:flex;align-items:center;gap:.4rem">';
            $out .= '<span>🌤</span> <strong>'.htmlspecialchars($city).'</strong> : <span id="'.$id.'-data">…</span>';
            $out .= '</div></div>';
        }
        $out .= '<script>fetch("https://wttr.in/'.rawurlencode($city).'?format=%C+%t+%w&lang=fr").then(r=>r.text()).then(t=>document.getElementById("'.$id.'-data").textContent=t.trim()).catch(()=>document.getElementById("'.$id.'").style.display="none");</script>';
        return $out;
    }

    private static function p_partners(array $b, string $wrap): string {
        $title    = htmlspecialchars($b['title'] ?? '');
        $logoSize = match($b['logo_size'] ?? 'md') {
            'sm' => '48px', 'lg' => '120px', 'xl' => '160px', default => '80px'
        };
        $out = '<div '.$wrap.'>';
        if ($title) $out .= '<h2 style="font-family:var(--font-heading);font-size:1.4rem;text-align:center;margin-bottom:1.5rem">'.$title.'</h2>';
        $out .= '<div style="display:flex;flex-wrap:wrap;gap:1.5rem;justify-content:center;align-items:center">';
        for ($p = 1; $p <= 6; $p++) {
            $name    = htmlspecialchars($b['partner'.$p.'_name'] ?? '');
            $url     = htmlspecialchars($b['partner'.$p.'_url'] ?? '');
            $logoSrc = $b['partner'.$p.'_logo_src'] ?? '';
            $logoUrl = $b['partner'.$p.'_logo_url'] ?? '';
            $logo    = $logoSrc ? asset($logoSrc) : ($logoUrl ? htmlspecialchars($logoUrl) : '');
            if (!$name && !$logo) continue;
            $tag  = $url ? 'a href="'.htmlspecialchars($url).'" target="_blank" rel="noopener"' : 'div';
            $endT = $url ? 'a' : 'div';
            $hoverStyle = 'onmouseover="this.style.boxShadow=\'0 4px 16px rgba(0,0,0,.1)\'" onmouseout="this.style.boxShadow=\'none\'"';
            $out .= '<'.$tag.' style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.6rem;padding:1rem 1.5rem;background:#fff;border:1.5px solid var(--color-border);border-radius:10px;text-decoration:none;color:var(--color-text);font-weight:600;font-size:.875rem;text-align:center;min-width:100px;transition:box-shadow .2s" '.$hoverStyle.'>';
            if ($logo) {
                $out .= '<img src="'.$logo.'" alt="'.htmlspecialchars($name).'" style="height:'.$logoSize.';max-width:200px;object-fit:contain">';
            }
            if ($name) {
                $out .= '<span style="'.($logo ? 'font-size:.8rem;color:var(--color-muted)' : 'font-size:.9rem').'">'.$name.'</span>';
            }
            $out .= '</'.$endT.'>';
        }
        $out .= '</div></div>';
        return $out;
    }
}
