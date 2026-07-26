<?php
/**
 * Prüft, ob eine Markdown-Link-URL als href ausgegeben werden darf.
 * Erlaubt sind absolute http(s)-Links, seiteninterne Pfade, Anker und mailto/tel.
 * Alles andere (javascript:, data:, vbscript: …) wird abgelehnt.
 */
function link_ziel_erlaubt(string $url): bool {
    // Steuer- und Leerzeichen entfernen: "java\tscript:" bzw. " javascript:"
    // würden vom Browser sonst weiterhin als Schema gelesen.
    $probe = preg_replace('/[\x00-\x20\x7F]+/', '', $url);
    if ($probe === '' || $probe === null) {
        return false;
    }

    // "//host" löst der Browser protokollrelativ auf – wie ein externer Link,
    // aber ohne erkennbares Schema. Nicht erlauben.
    if (str_starts_with($probe, '//')) {
        return false;
    }

    // Relativ, seitenintern oder Anker – kein Schema möglich.
    if (str_starts_with($probe, '#') || str_starts_with($probe, '/')) {
        return true;
    }

    // Enthält die URL vor dem ersten / ? # keinen Doppelpunkt,
    // ist sie relativ (z. B. "themen/artikel").
    $bis_pfad = preg_split('#[/?\#]#', $probe, 2)[0];
    if (!str_contains($bis_pfad, ':')) {
        return true;
    }

    return (bool) preg_match('#^(https?|mailto|tel):#i', $probe);
}

/**
 * Leichtgewichtiger Markdown-Parser für Themenartikel
 */
function parse_markdown(string $markdown): string {
    $lines = explode("\n", str_replace("\r\n", "\n", $markdown));
    $html = '';
    $in_list = false;
    $in_quote = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Leere Zeile schließt offene Listen / Quotes
        if ($trimmed === '') {
            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }
            if ($in_quote) {
                $html .= "</blockquote>\n";
                $in_quote = false;
            }
            continue;
        }

        // Inline Formatting: **bold**, *italic*, [link](url)
        // Achtung: Der Callback läuft auf der bereits escapten Zeile,
        // $m[1] und $m[2] sind also schon HTML-sicher. Erneutes Escapen
        // würde "&amp;" zu "&amp;amp;" machen und den Link verfälschen.
        $line_fmt = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($m) {
            $text = $m[1];
            $url  = $m[2];

            // Nur unbedenkliche Ziele verlinken – sonst wären z. B.
            // javascript: oder data: als Link-Ziel möglich.
            if (!link_ziel_erlaubt($url)) {
                return $text;
            }

            $extern = (bool) preg_match('#^\s*https?://#i', $url);
            $attr   = $extern ? ' target="_blank" rel="noopener noreferrer"' : '';

            return '<a href="' . $url . '" class="text-mint-dark underline font-semibold"' . $attr . '>' . $text . '</a>';
        }, htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8'));

        $line_fmt = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $line_fmt);
        $line_fmt = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $line_fmt);

        // Überschriften (# -> H2, ## -> H2, ### -> H3)
        if (str_starts_with($trimmed, '# ')) {
            if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
            if ($in_quote) { $html .= "</blockquote>\n"; $in_quote = false; }
            $html .= '<h2 class="font-display text-2xl md:text-3xl font-bold mt-8 mb-4 text-text-strong">' . substr($line_fmt, 2) . "</h2>\n";
            continue;
        }
        if (str_starts_with($trimmed, '## ')) {
            if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
            if ($in_quote) { $html .= "</blockquote>\n"; $in_quote = false; }
            $html .= '<h2 class="font-display text-2xl md:text-3xl font-bold mt-8 mb-4 text-text-strong">' . substr($line_fmt, 3) . "</h2>\n";
            continue;
        }
        if (str_starts_with($trimmed, '### ')) {
            if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
            if ($in_quote) { $html .= "</blockquote>\n"; $in_quote = false; }
            $html .= '<h3 class="font-display text-xl font-semibold mt-6 mb-3 text-text-strong">' . substr($line_fmt, 4) . "</h3>\n";
            continue;
        }

        // Listen (- Item oder * Item)
        if (str_starts_with($trimmed, '- ') || str_starts_with($trimmed, '* ')) {
            if ($in_quote) { $html .= "</blockquote>\n"; $in_quote = false; }
            if (!$in_list) {
                $html .= "<ul class=\"list-disc list-inside space-y-2 my-4 text-text-body font-body\">\n";
                $in_list = true;
            }
            $item_text = substr($line_fmt, 2);
            $html .= '  <li>' . $item_text . "</li>\n";
            continue;
        }

        // Blockquotes (> Text)
        if (str_starts_with($trimmed, '> ')) {
            if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
            if (!$in_quote) {
                $html .= "<blockquote class=\"my-6 p-4 rounded-xl border-l-4 border-mint bg-lightyellow font-body italic text-text-body\">\n";
                $in_quote = true;
            }
            $html .= '<p>' . substr($line_fmt, 2) . "</p>\n";
            continue;
        }

        // Normaler Absatz
        if ($in_list) { $html .= "</ul>\n"; $in_list = false; }
        if ($in_quote) { $html .= "</blockquote>\n"; $in_quote = false; }
        $html .= '<p class="font-body text-base text-text-body mb-4 leading-relaxed break-words">' . $line_fmt . "</p>\n";
    }

    if ($in_list) { $html .= "</ul>\n"; }
    if ($in_quote) { $html .= "</blockquote>\n"; }

    return $html;
}
