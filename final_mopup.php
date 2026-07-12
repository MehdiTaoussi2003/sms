<?php
/**
 * Final Mop-up Polish Script
 */

$root = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;

        // 1. Fix alert-danger -> alert-danger
        $content = str_replace('alert-danger', 'alert-danger', $content);

        // 2. Dashboard KPI Icons (Only in index.php)
        if (basename($path) === 'index.php') {
            // Add SVG icons to stat cards
            $icons = [
                'Total Products' => '<svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>',
                'Low Stock' => '<svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                'Out of Stock' => '<svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>',
                'Updated Today' => '<svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>',
                'Pending Deliveries' => '<svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>'
            ];

            foreach ($icons as $label => $svg) {
                $content = preg_replace(
                    '/<div class="stat-label">\s*' . preg_quote($label) . '\s*<\/div>/is',
                    '<div class="flex items-center justify-between mb-2"><div class="stat-label m-0">' . $label . '</div>' . $svg . '</div>',
                    $content
                );
            }
        }

        if ($content !== $original) {
            file_put_contents($path, $content);
        }
    }
}

// 3. Add styling for stat-icon to pages.css or components.css
$pages_css_path = $root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'pages.css';
$pages_css = file_get_contents($pages_css_path);
if (strpos($pages_css, '.stat-icon') === false) {
    $pages_css .= "\n.stat-icon { opacity: 0.2; transform: translateY(-2px); transition: opacity var(--transition-normal); }\n.stat-card:hover .stat-icon { opacity: 0.8; }\n";
    file_get_contents($pages_css_path, $pages_css); // Mistake in previous line, fixing now.
    file_put_contents($pages_css_path, $pages_css);
}

echo "Mop-up complete.\n";
