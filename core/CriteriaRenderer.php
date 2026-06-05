<?php
/**
 * ClubCMS — Rendu des critères d'inscription
 * Centralise le rendu HTML pour inscription, profil et admin
 */
class CriteriaRenderer {

    /**
     * Retourne le HTML d'un champ critère
     * @param array  $cr     Critère (depuis cc_planning_criteria)
     * @param string $saved  Valeur sauvegardée (value)
     * @param string $saved2 Valeur 2 sauvegardée (pour range)
     * @param bool   $locked Si true : affiche en lecture seule (inscription créneau, valeur mémorisée)
     */
    public static function field(array $cr, string $saved = '', string $saved2 = '', bool $locked = false): string {
        $id    = $cr['id'];
        $ftype = $cr['field_type'] ?? 'text';
        $opts  = json_decode($cr['options'] ?? '[]', true) ?? [];
        $name  = 'crit_' . $id;
        $req   = ($cr['is_required_here'] ?? $cr['required'] ?? 0) ? 'required' : '';

        if ($locked) {
            return self::renderLocked($cr, $saved, $saved2);
        }

        switch ($ftype) {
            case 'number':
                return '<input type="number" name="'.$name.'" value="'.htmlspecialchars($saved).'" '.$req
                    .' placeholder="Ex: 18" min="'.($cr['range_min']??'').'" max="'.($cr['range_max']??'').'"'
                    .' style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .875rem;font-size:.9rem;font-family:inherit;width:120px;outline:none">';

            case 'range':
                $unit = htmlspecialchars($cr['range_unit'] ?? '');
                $min  = $cr['range_min'] ?? '';
                $max  = $cr['range_max'] ?? '';
                return '<div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">'
                    . '<span style="font-size:.85rem;color:#64748b">entre</span>'
                    . '<input type="number" name="'.$name.'" value="'.htmlspecialchars($saved).'" '.$req
                    .   ' placeholder="'.htmlspecialchars((string)$min).'" min="'.$min.'" max="'.$max.'"'
                    .   ' style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.875rem;width:90px;outline:none">'
                    . '<span style="font-size:.85rem;color:#64748b">et</span>'
                    . '<input type="number" name="'.$name.'_2" value="'.htmlspecialchars($saved2).'" '.$req
                    .   ' placeholder="'.htmlspecialchars((string)$max).'" min="'.$min.'" max="'.$max.'"'
                    .   ' style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .6rem;font-size:.875rem;width:90px;outline:none">'
                    . ($unit ? '<span style="font-size:.85rem;color:#64748b">'.$unit.'</span>' : '')
                    . '</div>';

            case 'select':
                $html = '<select name="'.$name.'" '.$req
                    .' style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .875rem;font-size:.875rem;font-family:inherit;outline:none;width:100%;max-width:300px">'
                    . '<option value="">— Choisissez —</option>';
                foreach ($opts as $o) {
                    $sel = $saved === $o['label'] ? 'selected' : '';
                    $html .= '<option value="'.htmlspecialchars($o['label']).'" '.$sel.'>'.htmlspecialchars($o['label']).'</option>';
                }
                if ($cr['allow_other'] ?? 0) {
                    $isOther = $saved && !array_filter($opts, fn($o) => $o['label'] === $saved);
                    $html .= '<option value="__other__" '.($isOther?'selected':'').'>Autre…</option>';
                }
                $html .= '</select>';
                if ($cr['allow_other'] ?? 0) {
                    $isOther = $saved && !array_filter($opts, fn($o) => $o['label'] === $saved);
                    $html .= '<input type="text" name="'.$name.'_other" value="'.htmlspecialchars($isOther?$saved:'').'"'
                        .' placeholder="Précisez…"'
                        .' style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.45rem .75rem;font-size:.875rem;margin-top:.35rem;width:100%;max-width:300px;display:'.($isOther?'block':'none').'"'
                        .' id="other-'.$id.'">';
                    $html .= '<script>document.querySelector(\'[name="'.$name.'"]\').addEventListener("change",function(){var o=document.getElementById("other-'.$id.'");if(o)o.style.display=this.value==="__other__"?"block":"none";});</script>';
                }
                return $html;

            case 'radio':
                $isOther = $saved && !empty($opts) && !array_filter($opts, fn($o) => $o['label'] === $saved);
                $html = '<div style="display:flex;flex-wrap:wrap;gap:.4rem">';
                foreach ($opts as $o) {
                    $sel   = $saved === $o['label'];
                    $color = htmlspecialchars($o['color'] ?? '#6366f1');
                    $html .= '<label style="display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .875rem;border-radius:99px;border:2px solid '.$color.';cursor:pointer;font-size:.875rem;font-weight:600;transition:all .15s;background:'.($sel?$color:'transparent').';color:'.($sel?'#fff':'inherit').'">'
                        . '<input type="radio" name="'.$name.'" value="'.htmlspecialchars($o['label']).'" '.($sel?'checked':'').' '.$req
                        .   ' style="accent-color:'.$color.';margin:0;width:14px;height:14px">'
                        . htmlspecialchars($o['label'])
                        . '</label>';
                }
                if ($cr['allow_other'] ?? 0) {
                    $html .= '<label style="display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .875rem;border-radius:99px;border:2px solid #e2e8f0;cursor:pointer;font-size:.875rem;background:'.($isOther?'#f1f5f9':'transparent').'">'
                        . '<input type="radio" name="'.$name.'" value="__other__" '.($isOther?'checked':'').' style="margin:0;width:14px;height:14px">'
                        . '<span>Autre :</span>'
                        . '<input type="text" name="'.$name.'_other" value="'.htmlspecialchars($isOther?$saved:'').'"'
                        .   ' placeholder="Précisez…"'
                        .   ' style="border:none;border-bottom:1px solid #cbd5e1;outline:none;font-size:.875rem;width:100px;background:transparent">'
                        . '</label>';
                }
                $html .= '</div>';
                return $html;

            default: // text
                return '<input type="text" name="'.$name.'" value="'.htmlspecialchars($saved).'" '.$req
                    .' placeholder="Votre '.htmlspecialchars(strtolower($cr['name']??'')).'"'
                    .' style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:.55rem .875rem;font-size:.9rem;font-family:inherit;outline:none">';
        }
    }

    /** Affichage lecture seule (valeur mémorisée pour membre connecté) */
    private static function renderLocked(array $cr, string $saved, string $saved2): string {
        $ftype = $cr['field_type'] ?? 'text';
        $opts  = json_decode($cr['options'] ?? '[]', true) ?? [];
        $unit  = htmlspecialchars($cr['col_unit'] ?? $cr['range_unit'] ?? '');
        $color = null;

        if ($ftype === 'radio' || $ftype === 'select') {
            $match = array_values(array_filter($opts, fn($o) => $o['label'] === $saved));
            $color = $match[0]['color'] ?? ($cr['use_color'] ? $cr['color'] : null);
        } elseif ($cr['use_color'] ?? 0) {
            $color = $cr['color'];
        }

        if ($ftype === 'range') {
            $display = 'entre <strong>'.htmlspecialchars($saved).'</strong> et <strong>'.htmlspecialchars($saved2).'</strong>'.($unit?' '.$unit:'');
        } elseif ($ftype === 'number') {
            $display = '<strong>'.htmlspecialchars($saved).'</strong>'.($unit?' '.$unit:'');
        } else {
            $display = '<strong>'.htmlspecialchars($saved).'</strong>';
        }

        $badge = $color
            ? '<span style="background:'.htmlspecialchars($color).';color:#fff;padding:.3rem .875rem;border-radius:99px;font-weight:700;font-size:.875rem">'.$display.'</span>'
            : '<span style="background:#f1f5f9;padding:.3rem .875rem;border-radius:8px;font-size:.875rem">'.$display.'</span>';

        // Champs cachés pour soumettre la valeur mémorisée
        $hidden = '<input type="hidden" name="crit_'.$cr['id'].'" value="'.htmlspecialchars($saved).'">';
        if ($ftype === 'range') $hidden .= '<input type="hidden" name="crit_'.$cr['id'].'_2" value="'.htmlspecialchars($saved2).'">';

        return '<div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">'
            . $badge . $hidden
            . '<span style="font-size:.75rem;color:#94a3b8">Pré-rempli — <a href="'.u('/membre/profil').'" style="color:var(--color-primary)">Modifier dans mon profil</a></span>'
            . '</div>';
    }

    /** Extrait la valeur depuis $_POST pour un critère donné */
    public static function fromPost(array $cr): array {
        $id    = $cr['id'];
        $ftype = $cr['field_type'] ?? 'text';
        $val   = trim($_POST['crit_'.$id] ?? '');
        $val2  = '';

        if ($ftype === 'range') {
            $val2 = trim($_POST['crit_'.$id.'_2'] ?? '');
        } elseif (in_array($ftype, ['select','radio']) && $val === '__other__') {
            $val = trim($_POST['crit_'.$id.'_other'] ?? '');
        }
        return ['value' => $val, 'value2' => $val2];
    }

    /** Affichage compact pour les listes (badge dans inscriptions admin) */
    public static function badge(array $cr, string $saved, string $saved2 = ''): string {
        if ($saved === '') return '';
        $ftype = $cr['field_type'] ?? 'text';
        $opts  = json_decode($cr['options'] ?? '[]', true) ?? [];
        $unit  = htmlspecialchars($cr['range_unit'] ?? '');

        $color = null;
        if ($cr['use_color'] ?? 0) $color = $cr['color'];
        if (in_array($ftype, ['radio','select'])) {
            $match = array_values(array_filter($opts, fn($o) => $o['label'] === $saved));
            if ($match) $color = $match[0]['color'] ?? $color;
        }

        if ($ftype === 'range') {
            $text = $saved.' – '.$saved2.($unit?' '.$unit:'');
        } elseif ($ftype === 'number') {
            $text = $saved.($unit?' '.$unit:'');
        } else {
            $text = $saved;
        }

        $bg    = $color ?? '#e2e8f0';
        $txtcol = $color ? '#fff' : '#374151';
        return '<span title="'.htmlspecialchars($cr['name']).'" style="display:inline-block;background:'.htmlspecialchars($bg).';color:'.$txtcol.';padding:.15rem .5rem;border-radius:99px;font-size:.72rem;font-weight:700;margin:.1rem">'.htmlspecialchars($text).'</span>';
    }
}
