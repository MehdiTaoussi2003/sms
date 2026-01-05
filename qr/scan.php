<?php
/**
 * QR Code Scanner with Camera
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

$page_title = "QR Scanner";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Stock Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* QR Scanner Responsive Styles */
        .scanner-container {
            text-align: center;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .video-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        #scanner-video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 12px;
        }

        /* removed legacy overlay/frame to avoid conflicts; using modern styles below */

        .scanner-controls {
            text-align: center;
            margin: 20px 0;
        }

        .scanner-controls .btn {
            margin: 0 10px;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 8px;
        }

        /* Modern QR Scanner Frame */
        .qr-scanner-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
        }

        .qr-scanner-frame {
            width: 280px;
            height: 280px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 2px solid transparent;
            border-radius: 20px;
            box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
            animation: none;
            box-sizing: border-box;
        }

        /* Corner indicators (yellow) anchored to inner edges */
        .corner-tl, .corner-tr, .corner-bl, .corner-br {
            position: absolute;
            width: 32px;
            height: 32px;
            border: 4px solid #ffd700;
            box-sizing: border-box;
        }

        .corner-tl {
            top: 0;
            left: 0;
            border-right: none;
            border-bottom: none;
            border-top-left-radius: 20px;
        }

        .corner-tr {
            top: 0;
            right: 0;
            border-left: none;
            border-bottom: none;
            border-top-right-radius: 20px;
        }

        .corner-bl {
            bottom: 0;
            left: 0;
            border-right: none;
            border-top: none;
            border-bottom-left-radius: 20px;
        }

        .corner-br {
            bottom: 0;
            right: 0;
            border-left: none;
            border-top: none;
            border-bottom-right-radius: 20px;
        }

        /* Center blue guide line */
        .scan-line {
            position: absolute;
            left: 8px;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            height: 2px;
            background: #1e90ff; /* blue */
            box-shadow: 0 0 8px rgba(30,144,255,0.8);
            border-radius: 1px;
            pointer-events: none;
        }

        /* scanLine animation removed as the center line is static */

        /* Center indicator removed; using a single blue guide line instead */
        .qr-scanner-frame::before { display: none; }

        /* centerPulse animation removed */

        .video-container.detected {
            border: 4px solid #28a745;
            box-shadow: 0 0 30px #28a745;
        }

        .scanner-instructions {
            max-width: 600px;
            margin: 0 auto;
            text-align: left;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .scanner-container {
                padding: 15px;
            }
            
            .video-container {
                max-width: 100%;
                margin: 10px auto;
            }
            
            .qr-scanner-frame {
                width: 200px;
                height: 200px;
            }
            
            .scanner-controls .btn {
                padding: 12px 20px;
                font-size: 16px;
                margin: 5px;
                width: 100%;
                max-width: 250px;
                display: block;
            }
            
            .scanner-instructions {
                padding: 15px;
                font-size: 14px;
            }
            
            .scanner-instructions ol, 
            .scanner-instructions ul {
                padding-left: 20px;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .scanner-controls .btn {
                font-size: 14px;
                padding: 10px 15px;
            }
            
            .qr-scanner-frame {
                width: 150px;
                height: 150px;
            }
            
            #scanner-status {
                font-size: 14px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Modern Sidebar -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <h1>SMS</h1>
                <p>Stock Management</p>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="/sms/">
                        <span class="nav-icon">📊</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="/sms/products/list.php">
                        <span class="nav-icon">📦</span>
                        <span class="nav-text">Products</span>
                    </a>
                </li>
                <li>
                    <a href="/sms/products/create.php">
                        <span class="nav-icon">➕</span>
                        <span class="nav-text">Add Product</span>
                    </a>
                </li>
                <li>
                    <a href="/sms/qr/scan.php" class="active">
                        <span class="nav-icon">📱</span>
                        <span class="nav-text">QR Scanner</span>
                    </a>
                </li>
                <li>
                    <a href="/sms/logs/stock_logs.php">
                        <span class="nav-icon">📋</span>
                        <span class="nav-text">Stock Logs</span>
                    </a>
                </li>
                <li>
                    <a href="/sms/exports/">
                        <span class="nav-icon">📤</span>
                        <span class="nav-text">Export Data</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Modern Top Bar -->
            <header class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                        <span>☰</span>
                    </button>
                    <h1 class="page-title">
                        <span class="title-icon">📱</span>
                        <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                </div>
                <div class="admin-info">
                    <div class="admin-welcome">
                        <div class="admin-name">Welcome, <?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="admin-role"><?php echo htmlspecialchars($admin['role'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                    <a href="/sms/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </header>
            
            <!-- Content -->
            <main class="content">
                <!-- Modern Scanner Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <span class="card-icon">📱</span>
                            QR Code Scanner
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="scanner-container">
                            <!-- Status Display -->
                            <div id="scanner-status" class="alert alert-info">
                                📱 Click "Start Camera Scanner" to begin scanning QR codes
                            </div>
                            
                            <!-- Camera Video Container -->
                            <div id="video-container" class="video-container" style="display: none;">
                                <video id="scanner-video" autoplay muted playsinline></video>
                                <div class="qr-scanner-overlay" style="display:flex;align-items:center;justify-content:center;">
                                    <div class="qr-scanner-frame">
                                        <!-- Corner indicators -->
                                        <div class="corner-tl"></div>
                                        <div class="corner-tr"></div>
                                        <div class="corner-bl"></div>
                                        <div class="corner-br"></div>
                                        <!-- Scanning line -->
                                        <div class="scan-line"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Scanner Controls -->
                            <div class="scanner-controls">
                                <button id="start-scanner" class="btn btn-success btn-lg">
                                    <span>📷</span>
                                    Start Camera Scanner
                                </button>
                                <button id="stop-scanner" class="btn btn-danger btn-lg" style="display: none;">
                                    <span>⏹️</span>
                                    Stop Scanner
                                </button>
                            </div>
                            
                            <!-- Scanner Instructions -->
                            <div class="scanner-instructions" style="margin: 20px 0; padding: 20px; background: #e7f3ff; border-radius: 8px;">
                                <h5>📋 How to Use QR Scanner:</h5>
                                <ol>
                                    <li><strong>Click "Start Camera Scanner"</strong> button above</li>
                                    <li><strong>Allow camera permissions</strong> when prompted</li>
                                    <li><strong>Point camera at QR code</strong> - anywhere in the video frame</li>
                                    <li><strong>Wait for detection</strong> - you'll see green flash and hear beep</li>
                                    <li><strong>Product details</strong> will appear automatically below</li>
                                </ol>
                                
                                <div class="alert alert-warning" style="margin-top: 15px;">
                                    <strong>💡 Scanner Tips:</strong>
                                    <ul style="margin: 10px 0;">
                                        <li>Hold QR code steady for 1-2 seconds</li>
                                        <li>Ensure good lighting for best results</li>
                                        <li>Try different distances (6-12 inches works best)</li>
                                        <li>QR code can be anywhere in the camera view</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modern Product Details Section -->
                <div id="product-section" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <span class="card-icon">📦</span>
                                Product Details
                            </h3>
                        </div>
                        <div class="card-body" id="product-details">
                            <!-- Product details will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Modern Quick Stock Update -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <span class="card-icon">⚡</span>
                                Quick Stock Update
                            </h3>
                        </div>
                        <div class="card-body">
                            <form id="quick-update-form">
                                <input type="hidden" id="product-id" name="product_id">
                                <?php echo CSRF::getTokenField(); ?>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new-quantity" class="form-label">New Quantity</label>
                                        <input type="number" id="new-quantity" name="quantity" class="form-control" min="0" step="1">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-status" class="form-label">Status</label>
                                        <select id="new-status" name="status" class="form-control">
                                            <option value="in_stock">In Stock</option>
                                            <option value="low_stock">Low Stock</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                            <option value="damaged">Damaged</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new-location" class="form-label">Location</label>
                                        <input type="text" id="new-location" name="location" class="form-control" maxlength="100">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="update-notes" class="form-label">Update Notes</label>
                                    <textarea id="update-notes" name="notes" class="form-control" rows="2" placeholder="Reason for update..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <div class="btn-group">
                                        <button type="submit" class="btn btn-success">
                                            <span>💾</span>
                                            Update Stock
                                        </button>
                                        <button type="button" id="clear-product" class="btn btn-secondary">
                                            <span>🗑️</span>
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- QR Scanner Library - Proven to Work -->
    <script type="module">
        import QrScanner from 'https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner.min.js';
        window.QrScanner = QrScanner;
    </script>
    
    <script>
        // Professional QR Scanner Implementation
        class QRScanner {
            constructor() {
                this.video = document.getElementById('scanner-video');
                this.videoContainer = document.getElementById('video-container');
                this.scanning = false;
                this.qrScanner = null;
                this.lastScannedCode = null;
                this.lastScanTime = 0;
                this.scanCooldown = 3000; // 3 second cooldown
            }
            
            async startScanner() {
                try {
                    this.updateStatus('📷 Starting camera...', 'info');
                    
                    // Wait for QrScanner to be available
                    if (typeof window.QrScanner === 'undefined') {
                        // Try to wait a bit for the module to load
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        
                        if (typeof window.QrScanner === 'undefined') {
                            throw new Error('QR Scanner library not loaded. Please refresh the page.');
                        }
                    }
                    
                    // Initialize QR Scanner
                    this.qrScanner = new window.QrScanner(
                        this.video,
                        (result) => this.onQRDetected(result.data),
                        {
                            returnDetailedScanResult: true,
                            highlightScanRegion: false,
                            highlightCodeOutline: false,
                            preferredCamera: 'environment' // Use back camera
                        }
                    );
                    
                    // Start scanner
                    await this.qrScanner.start();
                    this.scanning = true;
                    
                    // Show video container
                    this.videoContainer.style.display = 'block';
                    document.getElementById('start-scanner').style.display = 'none';
                    document.getElementById('stop-scanner').style.display = 'inline-block';
                    
                    this.updateStatus('✅ Camera active! Point at QR code anywhere in the frame', 'success');
                    
                } catch (error) {
                    console.error('Scanner error:', error);
                    
                    let errorMessage = '❌ Camera failed: ';
                    if (error.name === 'NotAllowedError') {
                        errorMessage += 'Camera permission denied. Please allow camera access and try again.';
                    } else if (error.name === 'NotFoundError') {
                        errorMessage += 'No camera found on this device.';
                    } else if (error.name === 'NotSupportedError') {
                        errorMessage += 'Camera not supported by this browser. Try Chrome or Firefox.';
                    } else {
                        errorMessage += error.message;
                    }
                    
                    this.updateStatus(errorMessage, 'error');
                }
            }
            
            async initializeMultipleScanners() {
                try {
                    // Method 1: Get camera stream directly
                    const constraints = {
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1920, min: 640 },
                            height: { ideal: 1080, min: 480 }
                        }
                    };
                    
                    this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                    this.video.srcObject = this.stream;
                    
                    await new Promise((resolve, reject) => {
                        this.video.onloadedmetadata = () => {
                            this.video.play();
                            resolve();
                        };
                        this.video.onerror = reject;
                    });
                    
                    // Method 2: Initialize ZXing if available
                    if (typeof ZXing !== 'undefined') {
                        try {
                            this.codeReader = new ZXing.BrowserMultiFormatReader();
                            this.scanMethods.push('zxing');
                        } catch (e) {
                            console.log('ZXing init failed:', e);
                        }
                    }
                    
                    // Method 3: Initialize QR-Scanner if available
                    if (typeof QrScanner !== 'undefined') {
                        try {
                            this.qrScanner = new QrScanner(
                                this.video,
                                (result) => this.onQRDetected(result.data),
                                {
                                    returnDetailedScanResult: true,
                                    highlightScanRegion: false,
                                    highlightCodeOutline: false
                                }
                            );
                            this.scanMethods.push('qr-scanner');
                        } catch (e) {
                            console.log('QR-Scanner init failed:', e);
                        }
                    }
                    
                    // Method 4: Canvas-based jsQR scanning (always available)
                    this.scanMethods.push('jsqr');
                    
                    console.log('Initialized scan methods:', this.scanMethods);
                    
                } catch (error) {
                    throw new Error('Camera access failed: ' + error.message);
                }
            }
            
            startContinuousScanning() {
                // Start all available scanning methods
                
                // Method 1: ZXing continuous scan
                if (this.codeReader && this.scanMethods.includes('zxing')) {
                    try {
                        this.codeReader.decodeFromVideoElement(this.video, (result) => {
                            if (result) {
                                this.onQRDetected(result.getText());
                            }
                        });
                    } catch (e) {
                        console.log('ZXing scan failed:', e);
                    }
                }
                
                // Method 2: QR-Scanner continuous scan
                if (this.qrScanner && this.scanMethods.includes('qr-scanner')) {
                    try {
                        this.qrScanner.start();
                    } catch (e) {
                        console.log('QR-Scanner start failed:', e);
                    }
                }
                
                // Method 3: Canvas-based jsQR scanning (most reliable fallback)
                this.scanInterval = setInterval(() => {
                    this.scanWithCanvas();
                }, 200); // Scan 5 times per second
            }
            
            scanWithCanvas() {
                if (!this.scanning || !this.video || this.video.readyState !== 4) {
                    return;
                }
                
                try {
                    const videoWidth = this.video.videoWidth;
                    const videoHeight = this.video.videoHeight;
                    
                    if (videoWidth === 0 || videoHeight === 0) return;
                    
                    // Set canvas to match video dimensions
                    this.canvas.width = videoWidth;
                    this.canvas.height = videoHeight;
                    
                    // Draw current video frame
                    this.ctx.drawImage(this.video, 0, 0, videoWidth, videoHeight);
                    
                    // Get image data
                    const imageData = this.ctx.getImageData(0, 0, videoWidth, videoHeight);
                    
                    // Debug: Show we're scanning
                    if (Date.now() % 2000 < 100) { // Every 2 seconds for 100ms
                        this.updateStatusNoScroll('🔍 Scanning for QR codes... Point camera at QR', 'info');
                    }
                    
                    // Try jsQR if available
                    if (typeof jsQR !== 'undefined') {
                        const qrCode = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: "dontInvert"
                        });
                        
                        if (qrCode && qrCode.data) {
                            console.log('jsQR detected:', qrCode.data);
                            this.onQRDetected(qrCode.data);
                            return;
                        }
                    } else {
                        console.log('jsQR not available');
                    }
                    
                    // Fallback: Simple pattern detection
                    this.detectSMSPattern(imageData);
                    
                } catch (error) {
                    console.error('Canvas scan error:', error);
                    this.updateStatusNoScroll('❌ Scan error: ' + error.message, 'error');
                }
            }
            
            detectSMSPattern(imageData) {
                // Look for SMS_ pattern in the image by checking for specific text patterns
                // This is a simplified detection for our specific QR code format
                
                const data = imageData.data;
                const width = imageData.width;
                const height = imageData.height;
                
                // Convert to grayscale for pattern detection
                const grayscale = new Uint8Array(width * height);
                for (let i = 0; i < data.length; i += 4) {
                    grayscale[i / 4] = (data[i] + data[i + 1] + data[i + 2]) / 3;
                }
                
                // Look for QR code finder patterns (simplified)
                let finderPatterns = 0;
                const stepSize = 20;
                
                for (let y = 0; y < height - stepSize; y += stepSize) {
                    for (let x = 0; x < width - stepSize; x += stepSize) {
                        if (this.isLikelyFinderPattern(grayscale, x, y, width, stepSize)) {
                            finderPatterns++;
                        }
                    }
                }
                
                // If we found potential QR patterns, prompt for manual entry
                if (finderPatterns >= 2) {
                    this.updateStatusNoScroll('📱 QR pattern detected! Enter code manually in the blue box below.', 'warning');
                    this.highlightForManualEntry();
                }
            }
            
            isLikelyFinderPattern(grayscale, x, y, width, size) {
                let darkPixels = 0;
                let lightPixels = 0;
                
                for (let dy = 0; dy < size; dy++) {
                    for (let dx = 0; dx < size; dx++) {
                        const idx = (y + dy) * width + (x + dx);
                        if (idx < grayscale.length) {
                            if (grayscale[idx] < 128) {
                                darkPixels++;
                            } else {
                                lightPixels++;
                            }
                        }
                    }
                }
                
                const total = darkPixels + lightPixels;
                return total > 0 && (darkPixels / total) > 0.3 && (darkPixels / total) < 0.7;
            }
            
            highlightForManualEntry() {
                const quickInput = document.getElementById('quick-qr-input');
                if (quickInput) {
                    quickInput.style.background = '#fff3cd';
                    quickInput.style.border = '2px solid #ffc107';
                    quickInput.focus();
                    
                    setTimeout(() => {
                        quickInput.style.background = '';
                        quickInput.style.border = '1px solid #ccc';
                    }, 3000);
                }
            }
            
            showQuickManualEntry() {
                // Add a prominent manual entry option when scanning is active
                const statusDiv = document.getElementById('scanner-status');
                const quickEntry = document.createElement('div');
                quickEntry.id = 'quick-manual-entry';
                quickEntry.innerHTML = `
                    <div style="margin: 15px 0; padding: 15px; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px;">
                        <h5 style="margin: 0 0 10px 0; color: #1976d2;">⌨️ Quick QR Entry</h5>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="quick-qr-input" placeholder="Type or paste QR code here..." 
                                   style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" 
                                   autocomplete="off">
                            <button id="quick-lookup-btn" style="padding: 8px 16px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                🔍 Lookup
                            </button>
                        </div>
                        <small style="color: #666; display: block; margin-top: 8px;">
                            💡 Can't scan? Type the QR code value manually (e.g., SMS_ABC123_PRODUCT001)
                        </small>
                    </div>
                `;
                
                statusDiv.parentNode.insertBefore(quickEntry, statusDiv.nextSibling);
                
                // Add event listeners
                document.getElementById('quick-lookup-btn').addEventListener('click', () => {
                    const qrValue = document.getElementById('quick-qr-input').value.trim();
                    if (qrValue) {
                        this.lookupProduct(qrValue);
                        document.getElementById('quick-qr-input').value = '';
                    }
                });
                
                document.getElementById('quick-qr-input').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        document.getElementById('quick-lookup-btn').click();
                    }
                });
            }
            
            onQRDetected(qrData) {
                const currentTime = Date.now();
                
                // Prevent rapid duplicate scans
                if (this.lastScannedCode === qrData && 
                    (currentTime - this.lastScanTime) < this.scanCooldown) {
                    return;
                }
                
                this.lastScannedCode = qrData;
                this.lastScanTime = currentTime;
                
                // Visual feedback
                this.highlightDetection();
                
                // Audio feedback
                this.playBeep();
                
                // Show detection
                this.updateStatus(`🎉 QR Code Detected: <strong>${qrData}</strong>`, 'success');
                
                // Store for potential re-lookup
                localStorage.setItem('last_scanned_qr', qrData);
                
                // Lookup product
                this.lookupProduct(qrData);
            }
            
            highlightDetection() {
                // Flash green border
                this.videoContainer.style.border = '4px solid #28a745';
                this.videoContainer.style.boxShadow = '0 0 30px #28a745';
                
                setTimeout(() => {
                    this.videoContainer.style.border = '';
                    this.videoContainer.style.boxShadow = '';
                }, 2000);
            }
            
            showManualEntry() {
                const form = document.getElementById('manual-qr-form');
                form.style.display = 'block';
                document.getElementById('manual-qr-value').focus();
            }
            
            playBeep() {
                // Create a short beep sound
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 800; // Hz
                    gainNode.gain.value = 0.1; // Volume
                    
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.1);
                } catch (error) {
                    // Audio not supported or blocked, ignore
                }
            }
            
            stopScanner() {
                this.scanning = false;
                
                if (this.qrScanner) {
                    try {
                        this.qrScanner.stop();
                        this.qrScanner.destroy();
                    } catch (e) {
                        console.log('Scanner stop error:', e);
                    }
                    this.qrScanner = null;
                }
                
                // Hide video container
                this.videoContainer.style.display = 'none';
                document.getElementById('start-scanner').style.display = 'inline-block';
                document.getElementById('stop-scanner').style.display = 'none';
                
                this.updateStatus('📷 Scanner stopped. Click "Start Camera Scanner" to scan again.', 'info');
            }
            
            lookupProduct(qrValue) {
                // Don't scroll during lookup
                this.updateStatusNoScroll('🔍 Looking up product...', 'info');
                
                // Show loading on quick input if it exists
                const quickInput = document.getElementById('quick-qr-input');
                if (quickInput) {
                    quickInput.value = qrValue;
                    quickInput.style.background = '#fff3cd';
                }
                
                fetch('../api/lookup_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        qr_value: qrValue,
                        csrf_token: document.querySelector('input[name="csrf_token"]').value
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.displayProduct(data.product);
                        // Reset quick input background
                        if (quickInput) {
                            quickInput.style.background = '#d4edda';
                            setTimeout(() => {
                                quickInput.style.background = '';
                                quickInput.value = '';
                            }, 2000);
                        }
                    } else {
                        this.updateStatusNoScroll('❌ Product not found: ' + (data.message || 'QR code not in database'), 'error');
                        if (quickInput) {
                            quickInput.style.background = '#f8d7da';
                            setTimeout(() => {
                                quickInput.style.background = '';
                            }, 3000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Lookup error:', error);
                    this.updateStatusNoScroll('❌ Network error. Check connection and try again.', 'error');
                    if (quickInput) {
                        quickInput.style.background = '#f8d7da';
                        setTimeout(() => {
                            quickInput.style.background = '';
                        }, 3000);
                    }
                });
            }
            
            displayProduct(product) {
                const detailsDiv = document.getElementById('product-details');
                
                // Status badge styling
                const statusClasses = {
                    'in_stock': 'badge-success',
                    'low_stock': 'badge-warning',
                    'out_of_stock': 'badge-danger',
                    'damaged': 'badge-secondary'
                };
                
                const statusClass = statusClasses[product.status] || 'badge-secondary';
                const statusText = product.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                detailsDiv.innerHTML = `
                    <div class="product-info">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h4>${this.escapeHtml(product.product_name)}</h4>
                                <p><strong>SKU:</strong> <code>${this.escapeHtml(product.sku)}</code></p>
                                <p><strong>Category:</strong> ${this.escapeHtml(product.category_name)}</p>
                                <p><strong>Current Stock:</strong> <span style="font-size: 1.2em; font-weight: bold;">${Number(product.quantity).toLocaleString()}</span> units</p>
                                <p><strong>Min Stock Level:</strong> ${Number(product.min_stock_level).toLocaleString()} units</p>
                            </div>
                            <div>
                                <p><strong>Status:</strong> <span class="badge ${statusClass}">${statusText}</span></p>
                                <p><strong>Location:</strong> ${this.escapeHtml(product.location || 'Not specified')}</p>
                                <p><strong>Supplier:</strong> ${this.escapeHtml(product.supplier || 'Not specified')}</p>
                                <p><strong>Last Updated:</strong> ${new Date(product.last_updated).toLocaleString()}</p>
                                <p><strong>QR Code:</strong> <small><code>${this.escapeHtml(product.qr_code_value)}</code></small></p>
                            </div>
                        </div>
                        ${product.notes ? `<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;"><strong>Notes:</strong> ${this.escapeHtml(product.notes)}</div>` : ''}
                    </div>
                `;
                
                // Populate quick update form
                document.getElementById('product-id').value = product.id;
                document.getElementById('new-quantity').value = product.quantity;
                document.getElementById('new-status').value = product.status;
                document.getElementById('new-location').value = product.location || '';
                
                // Show product section
                document.getElementById('product-section').style.display = 'block';
                
                // Smooth scroll to product section
                document.getElementById('product-section').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
                
                this.updateStatusNoScroll('✅ Product loaded! Update stock levels below.', 'success');
            }
            
            updateStatus(message, type = 'info') {
                const statusDiv = document.getElementById('scanner-status');
                const alertClasses = {
                    'info': 'alert-info',
                    'success': 'alert-success',
                    'error': 'alert-error',
                    'warning': 'alert-warning'
                };
                
                statusDiv.className = `alert ${alertClasses[type] || 'alert-info'}`;
                statusDiv.innerHTML = message;
                
                // Auto-scroll to status
                statusDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            updateStatusNoScroll(message, type = 'info') {
                const statusDiv = document.getElementById('scanner-status');
                const alertClasses = {
                    'info': 'alert-info',
                    'success': 'alert-success',
                    'error': 'alert-error',
                    'warning': 'alert-warning'
                };
                
                statusDiv.className = `alert ${alertClasses[type] || 'alert-info'}`;
                statusDiv.innerHTML = message;
            }
            
            highlightScan() {
                const videoContainer = document.getElementById('video-container');
                videoContainer.style.boxShadow = '0 0 20px #4caf50';
                videoContainer.style.border = '3px solid #4caf50';
                
                setTimeout(() => {
                    videoContainer.style.boxShadow = '';
                    videoContainer.style.border = '2px solid #3498db';
                }, 1500);
            }
            
            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Initialize scanner when page loads
        let scanner;
        document.addEventListener('DOMContentLoaded', function() {
            scanner = new QRScanner();
            
            // Initialize mobile menu
            initializeMobileMenu();
            
            // Check if QR Scanner library is loading
            setTimeout(() => {
                if (typeof window.QrScanner === 'undefined') {
                    console.log('QrScanner not loaded yet, will check when starting');
                }
            }, 2000);
        });
        
        // Enhanced Mobile Menu Functionality
        function initializeMobileMenu() {
            const mobileToggle = document.getElementById('mobile-menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            
            if (!mobileToggle || !sidebar || !sidebarOverlay) {
                console.error('Mobile menu elements not found');
                return;
            }
            
            // Toggle mobile menu with touch support
            mobileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMobileMenu();
            });
            
            // Touch events for mobile
            mobileToggle.addEventListener('touchstart', function(e) {
                e.preventDefault();
                toggleMobileMenu();
            });
            
            // Close menu when clicking overlay
            sidebarOverlay.addEventListener('click', function(e) {
                e.preventDefault();
                closeMobileMenu();
            });
            
            sidebarOverlay.addEventListener('touchstart', function(e) {
                e.preventDefault();
                closeMobileMenu();
            });
            
            // Close menu when clicking nav links (mobile only)
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        setTimeout(closeMobileMenu, 200); // Small delay for navigation
                    }
                });
            });
            
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    if (window.innerWidth > 1024) {
                        closeMobileMenu();
                    }
                }, 250);
            });
            
            // Handle escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                    closeMobileMenu();
                }
            });
            
            // Prevent body scroll when menu is open
            function toggleMobileMenu() {
                const isOpen = sidebar.classList.contains('mobile-open');
                
                if (isOpen) {
                    closeMobileMenu();
                } else {
                    openMobileMenu();
                }
            }
            
            function openMobileMenu() {
                sidebar.classList.add('mobile-open');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
                
                // Focus first nav item for accessibility
                const firstNavLink = sidebar.querySelector('.sidebar-nav a');
                if (firstNavLink) {
                    setTimeout(() => firstNavLink.focus(), 300);
                }
            }
            
            function closeMobileMenu() {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.width = '';
            }
        }
        
        // Camera scanner events
        document.getElementById('start-scanner').addEventListener('click', () => {
            scanner.startScanner();
        });
        
        document.getElementById('stop-scanner').addEventListener('click', () => {
            scanner.stopScanner();
        });
        
        document.getElementById('clear-product').addEventListener('click', () => {
            document.getElementById('product-section').style.display = 'none';
            document.getElementById('quick-update-form').reset();
            scanner.updateStatus('Product cleared. Scan another QR code.', 'info');
        });
        
        // Quick update form submission
        document.getElementById('quick-update-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            // Disable submit button during request
            submitBtn.disabled = true;
            submitBtn.textContent = '💾 Updating...';
            
            try {
                const response = await fetch('../api/update_product.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    scanner.updateStatusNoScroll('✅ Stock updated successfully!', 'success');
                    
                    // Flash success feedback
                    submitBtn.style.background = '#28a745';
                    submitBtn.textContent = '✅ Updated!';
                    
                    // Refresh product details after brief delay
                    setTimeout(() => {
                        const lastQR = localStorage.getItem('last_scanned_qr');
                        if (lastQR) {
                            scanner.lookupProduct(lastQR);
                        }
                    }, 1000);
                } else {
                    scanner.updateStatusNoScroll('❌ Update failed: ' + (data.message || 'Unknown error'), 'error');
                    submitBtn.style.background = '#dc3545';
                }
            } catch (error) {
                console.error('Update error:', error);
                scanner.updateStatus('❌ Network error. Please check your connection.', 'error');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Update Stock';
            }
        });
        
        // Test QR functionality
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'T') {
                // Test with a sample QR code
                e.preventDefault();
                const testQR = prompt('Enter QR code to test:', 'SMS_TEST_ABC123');
                if (testQR && scanner) {
                    scanner.updateStatus('🧪 Testing QR code...', 'info');
                    scanner.lookupProduct(testQR);
                }
            }
        });
    </script>
</body>
</html>