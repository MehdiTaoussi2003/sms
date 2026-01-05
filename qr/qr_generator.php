<?php
/**
 * Pure PHP QR Code Generator
 * Stock Management System (SMS)
 * 
 * This is a simplified QR code generator for demonstration purposes.
 * In production, consider using a more robust library.
 */

// Prevent direct access
if (!defined('SMS_INCLUDED')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

class QRGenerator {
    private static $qr_modes = [
        'numeric' => 1,
        'alphanumeric' => 2,
        'byte' => 4,
        'kanji' => 8
    ];
    
    /**
     * Generate QR code as SVG using Google Charts API
     */
    public static function generateSVG($text, $size = 200) {
        // Use Google Charts API for reliable QR generation
        $encoded_text = urlencode($text);
        $google_chart_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_text}&choe=UTF-8";
        
        // For SVG, we'll create a container that embeds the QR image
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
        $svg .= '<image x="0" y="0" width="' . $size . '" height="' . $size . '" xlink:href="' . $google_chart_url . '"/>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    
    /**
     * Create a simple matrix representation
     */
    private static function createMatrix($text) {
        // This is a simplified implementation for demo purposes
        // Real QR codes require proper error correction, encoding modes, etc.
        
        $size = 21; // Standard QR code size for version 1
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        
        // Add finder patterns (corners)
        self::addFinderPattern($matrix, 0, 0);
        self::addFinderPattern($matrix, 0, $size - 7);
        self::addFinderPattern($matrix, $size - 7, 0);
        
        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2) ? 0 : 1;
            $matrix[$i][6] = ($i % 2) ? 0 : 1;
        }
        
        // Add data based on text hash (simplified)
        $hash = md5($text);
        $data_start_row = 9;
        $data_start_col = 9;
        
        for ($i = 0; $i < strlen($hash) && $data_start_row < $size - 1; $i++) {
            $val = hexdec($hash[$i]) % 2;
            if ($data_start_col < $size - 1) {
                $matrix[$data_start_row][$data_start_col] = $val;
                $data_start_col++;
            } else {
                $data_start_col = 9;
                $data_start_row++;
            }
        }
        
        return $matrix;
    }
    
    /**
     * Add finder pattern to matrix
     */
    private static function addFinderPattern(&$matrix, $start_row, $start_col) {
        $pattern = [
            [1,1,1,1,1,1,1],
            [1,0,0,0,0,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,0,0,0,0,1],
            [1,1,1,1,1,1,1]
        ];
        
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($start_row + $i < count($matrix) && $start_col + $j < count($matrix[0])) {
                    $matrix[$start_row + $i][$start_col + $j] = $pattern[$i][$j];
                }
            }
        }
    }
    
    /**
     * Convert matrix to SVG
     */
    private static function matrixToSVG($matrix, $size) {
        $module_size = $size / count($matrix);
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
        
        for ($row = 0; $row < count($matrix); $row++) {
            for ($col = 0; $col < count($matrix[$row]); $col++) {
                if ($matrix[$row][$col] == 1) {
                    $x = $col * $module_size;
                    $y = $row * $module_size;
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $module_size . '" height="' . $module_size . '" fill="black"/>';
                }
            }
        }
        
        $svg .= '</svg>';
        return $svg;
    }
    
    /**
     * Generate QR code for product using Google Charts API (most reliable)
     */
    public static function generateProductQR($product_data, $size = 300) {
        $qr_text = $product_data['qr_code_value'];
        
        // Generate QR code using Google Charts API
        $encoded_text = urlencode($qr_text);
        $qr_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_text}&choe=UTF-8&chld=M|0";
        
        // Create enhanced SVG with QR code and product info
        $total_height = $size + 120;
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $size . '" height="' . $total_height . '" viewBox="0 0 ' . $size . ' ' . $total_height . '" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';
        $svg .= '<rect width="' . $size . '" height="' . $total_height . '" fill="white" stroke="#000" stroke-width="1"/>';
        
        // Embed QR code image
        $svg .= '<image x="10" y="10" width="' . ($size-20) . '" height="' . ($size-20) . '" xlink:href="' . $qr_url . '"/>';
        
        // Add product information
        $text_y = $size + 20;
        $svg .= '<text x="' . ($size/2) . '" y="' . $text_y . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" font-weight="bold">';
        $svg .= htmlspecialchars($product_data['product_name'], ENT_QUOTES, 'UTF-8');
        $svg .= '</text>';
        
        $svg .= '<text x="' . ($size/2) . '" y="' . ($text_y + 25) . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="14">';
        $svg .= 'SKU: ' . htmlspecialchars($product_data['sku'], ENT_QUOTES, 'UTF-8');
        $svg .= '</text>';
        
        $svg .= '<text x="' . ($size/2) . '" y="' . ($text_y + 45) . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#666">';
        $svg .= 'QR: ' . htmlspecialchars($qr_text, ENT_QUOTES, 'UTF-8');
        $svg .= '</text>';
        
        $svg .= '<text x="' . ($size/2) . '" y="' . ($text_y + 65) . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#888">';
        $svg .= 'Stock Management System - ' . date('Y-m-d H:i');
        $svg .= '</text>';
        
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Generate PNG QR code using Google Charts API
     */
    public static function generatePNG($text, $size = 300, $save_path = null) {
        $encoded_text = urlencode($text);
        $google_chart_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_text}&choe=UTF-8&chld=M|0";
        
        // Download the QR code image
        $qr_image_data = file_get_contents($google_chart_url);
        
        if ($qr_image_data === false) {
            throw new Exception('Failed to generate QR code image');
        }
        
        if ($save_path) {
            // Save to file
            if (file_put_contents($save_path, $qr_image_data)) {
                return $save_path;
            } else {
                throw new Exception('Failed to save QR code to: ' . $save_path);
            }
        }
        
        return $qr_image_data;
    }
}
?>