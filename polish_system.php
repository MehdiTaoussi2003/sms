<?php
/**
 * SMS Design System Polishing Script
 * This script performs systemic UI/UX improvements across the entire codebase.
 */

$root = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$stats = [
    'refactored_layout' => 0,
    'polished_components' => 0,
    'files_touched' => 0
];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $relPath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        
        // Skip certain directories
        if (preg_match('/(includes|config|auth|api|assets|vendor)/', $relPath)) {
            continue;
        }

        $content = file_get_contents($path);
        $originalContent = $content;
        $modified = false;

        // --- 1. RE-REFACTOR LAYOUT SHELLS (More aggressive regex) ---
        // Calculate relative prefix for includes
        $depth = substr_count($relPath, DIRECTORY_SEPARATOR);
        $prefix = str_repeat('../', $depth);
        
        // Find the start of the HTML (DOCTYPE or <html)
        $pattern_head = '/<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
]*>|<div class="main-content">|<div class="content[^>]*>|<header class="top-bar">)/is';
        if (preg_match($pattern_head, $content, $matches)) {
            $replacement = "<?php require_once '{$prefix}includes/header.php'; ?>\n<?php require_once '{$prefix}includes/sidebar.php'; ?>\n<?php require_once '{$prefix}includes/topbar.php'; ?>\n\n            <!-- Content -->\n            <main class=\"content\">\n";
            $content = str_replace($matches[0], $replacement, $content);
            $modified = true;
            $stats['refactored_layout']++;
        }

        // Find the end of the HTML
        $pattern_tail = '/(?:<script.*?<\/script>\s*)*<\/body>\s*<\/html>/is';
        if (preg_match($pattern_tail, $content, $matches)) {
            // Find the last </div> tags that close main-content and admin-layout
            // We want to replace everything from the closing main tag to the end of the file
            // But we need to be careful about extra divs. 
            // Most pages follow: </main> </div> </div> </body> </html>
            $replace_tail = "\n            </main>\n<?php require_once '{$prefix}includes/footer.php'; ?>";
            
            // Try to find the closing </main> or last </div> before scripts
            if (preg_match('/(?:<\/main>|<\/div>\s*<\/div>)\s*(?:<script.*?<\/script>\s*)*<\/body>\s*<\/html>$/is', $content, $match_tail)) {
                $content = str_replace($match_tail[0], $replace_tail, $content);
                $modified = true;
            }
        }

        // --- 2. INTERNAL COMPONENT POLISHING ---
        
        // A. Standardize Card Headers (Remove inline flex boxes, use class)
        $content = preg_replace(
            '/<div class="card-header">\s*<div style="display:\s*flex;\s*justify-content:\s*space-between;\s*align-items:\s*center;">/is',
            '<div class="card-header justify-between">',
            $content
        );
        // Close the inner div if it was there
        $content = preg_replace(
            '/<\/h3>\s*<\/div>\s*<\/div>\s*<div class="card-body">/is',
            "</h3>\n                    </div>\n                    <div class=\"card-body\">",
            $content
        );

        // B. Clean up filter-bar into form-row
        if (strpos($content, 'filter-bar') !== false && strpos($content, 'form-row') === false) {
            $content = preg_replace(
                '/<div class="filter-bar">\s*<form method="GET" action="">/is',
                '<div class="filter-bar"><form method="GET" action="" class="w-full"><div class="form-row">',
                $content
            );
            $content = str_replace('</form>', '</div></form>', $content);
        }

        // C. Replace Emojis with SVGs in buttons
        $svg_plus = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>';
        $content = str_replace('<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> ', $svg_plus . ' ', $content);
        
        $svg_search = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>';
        $content = str_replace('<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> ', $svg_search . ' ', $content);

        // D. Button classes standardization (success/warning to primary/secondary for premium look)
        $content = str_replace('btn-primary', 'btn-primary', $content);
        $content = str_replace('btn-secondary', 'btn-secondary', $content);
        $content = str_replace('btn-secondary', 'btn-secondary', $content);
        
        // E. Form selects
        $content = str_replace('class="form-control"', 'class="form-control"', $content); // Placeholder for future specific logic
        // If it's a select tag, use form-select
        $content = preg_replace('/<select(.*?)class="form-select"(.*?)>/is', '<select$1class="form-select"$2>', $content);

        if ($content !== $originalContent) {
            file_put_contents($path, $content);
            $stats['files_touched']++;
            $stats['polished_components']++;
        }
    }
}

echo "System-wide UI polish complete.\n";
print_r($stats);
