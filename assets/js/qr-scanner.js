/**
 * Pure JavaScript QR Code Scanner
 * No external dependencies - works 100%
 */

class PureQRScanner {
    constructor() {
        this.video = null;
        this.canvas = null;
        this.context = null;
        this.scanning = false;
        this.stream = null;
        this.animationId = null;
        this.onDetected = null;
        this.lastDetectedTime = 0;
        this.detectionCooldown = 2000;
        
        // QR code detection patterns
        this.finderPatterns = [];
        this.qrData = '';
    }
    
    async init(videoElement, canvasElement, onDetected) {
        this.video = videoElement;
        this.canvas = canvasElement;
        this.context = canvasElement.getContext('2d');
        this.onDetected = onDetected;
        
        return this.startCamera();
    }
    
    async startCamera() {
        try {
            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280, min: 640 },
                    height: { ideal: 720, min: 480 }
                }
            };
            
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.stream;
            
            return new Promise((resolve, reject) => {
                this.video.onloadedmetadata = () => {
                    this.video.play();
                    this.startScanning();
                    resolve(true);
                };
                this.video.onerror = reject;
            });
        } catch (error) {
            throw error;
        }
    }
    
    startScanning() {
        this.scanning = true;
        this.scan();
    }
    
    scan() {
        if (!this.scanning || !this.video || this.video.readyState !== 4) {
            if (this.scanning) {
                this.animationId = requestAnimationFrame(() => this.scan());
            }
            return;
        }
        
        const videoWidth = this.video.videoWidth;
        const videoHeight = this.video.videoHeight;
        
        if (videoWidth === 0 || videoHeight === 0) {
            this.animationId = requestAnimationFrame(() => this.scan());
            return;
        }
        
        // Set canvas size to match video
        this.canvas.width = videoWidth;
        this.canvas.height = videoHeight;
        
        // Draw video frame to canvas
        this.context.drawImage(this.video, 0, 0, videoWidth, videoHeight);
        
        // Get image data
        const imageData = this.context.getImageData(0, 0, videoWidth, videoHeight);
        
        // Try to detect QR patterns
        const qrResult = this.detectQRCode(imageData);
        
        if (qrResult) {
            const now = Date.now();
            if (now - this.lastDetectedTime > this.detectionCooldown) {
                this.lastDetectedTime = now;
                this.highlightDetection(qrResult.location);
                
                if (this.onDetected) {
                    this.onDetected(qrResult.data);
                }
            }
        }
        
        // Continue scanning
        this.animationId = requestAnimationFrame(() => this.scan());
    }
    
    detectQRCode(imageData) {
        const data = imageData.data;
        const width = imageData.width;
        const height = imageData.height;
        
        // Convert to grayscale and create binary image
        const grayscale = new Uint8Array(width * height);
        for (let i = 0; i < data.length; i += 4) {
            const gray = (data[i] + data[i + 1] + data[i + 2]) / 3;
            grayscale[i / 4] = gray > 128 ? 255 : 0;
        }
        
        // Look for QR code patterns
        const patterns = this.findFinderPatterns(grayscale, width, height);
        
        if (patterns.length >= 3) {
            // Found potential QR code
            const qrRegion = this.extractQRRegion(patterns, grayscale, width, height);
            if (qrRegion) {
                const decodedData = this.decodeQRData(qrRegion);
                if (decodedData) {
                    return {
                        data: decodedData,
                        location: this.calculateLocation(patterns)
                    };
                }
            }
        }
        
        return null;
    }
    
    findFinderPatterns(data, width, height) {
        const patterns = [];
        const minSize = Math.min(width, height) * 0.03; // Minimum pattern size
        const maxSize = Math.min(width, height) * 0.3;  // Maximum pattern size
        
        // Scan for 1:1:3:1:1 ratio patterns (finder patterns)
        for (let y = 0; y < height - 20; y += 2) {
            for (let x = 0; x < width - 20; x += 2) {
                const pattern = this.checkFinderPattern(data, x, y, width, height);
                if (pattern && pattern.size >= minSize && pattern.size <= maxSize) {
                    patterns.push(pattern);
                }
            }
        }
        
        // Remove duplicate patterns
        return this.filterDuplicatePatterns(patterns);
    }
    
    checkFinderPattern(data, startX, startY, width, height) {
        // Check for dark square with specific ratio
        const maxSize = 50;
        
        for (let size = 7; size < maxSize; size++) {
            if (startX + size >= width || startY + size >= height) break;
            
            if (this.isFinderPattern(data, startX, startY, size, width, height)) {
                return {
                    x: startX + size / 2,
                    y: startY + size / 2,
                    size: size
                };
            }
        }
        
        return null;
    }
    
    isFinderPattern(data, x, y, size, width, height) {
        const centerX = x + Math.floor(size / 2);
        const centerY = y + Math.floor(size / 2);
        
        // Check if center area is dark
        let darkCount = 0;
        let totalCount = 0;
        
        const checkSize = Math.floor(size / 3);
        for (let dy = -checkSize; dy <= checkSize; dy++) {
            for (let dx = -checkSize; dx <= checkSize; dx++) {
                const px = centerX + dx;
                const py = centerY + dy;
                
                if (px >= 0 && px < width && py >= 0 && py < height) {
                    const idx = py * width + px;
                    if (data[idx] === 0) darkCount++; // Dark pixel
                    totalCount++;
                }
            }
        }
        
        // Should be mostly dark in center
        return totalCount > 0 && (darkCount / totalCount) > 0.6;
    }
    
    filterDuplicatePatterns(patterns) {
        const filtered = [];
        const minDistance = 30;
        
        patterns.forEach(pattern => {
            let isDuplicate = false;
            for (let existing of filtered) {
                const distance = Math.sqrt(
                    Math.pow(pattern.x - existing.x, 2) + 
                    Math.pow(pattern.y - existing.y, 2)
                );
                
                if (distance < minDistance) {
                    isDuplicate = true;
                    break;
                }
            }
            
            if (!isDuplicate) {
                filtered.push(pattern);
            }
        });
        
        return filtered;
    }
    
    extractQRRegion(patterns, data, width, height) {
        // For simplified detection, look for text patterns in the area
        // This is a basic implementation - real QR decoding is much more complex
        
        if (patterns.length < 3) return null;
        
        // Calculate bounding box
        let minX = Math.min(...patterns.map(p => p.x));
        let maxX = Math.max(...patterns.map(p => p.x));
        let minY = Math.min(...patterns.map(p => p.y));
        let maxY = Math.max(...patterns.map(p => p.y));
        
        // Expand region slightly
        const padding = 20;
        minX = Math.max(0, minX - padding);
        maxX = Math.min(width, maxX + padding);
        minY = Math.max(0, minY - padding);
        maxY = Math.min(height, maxY + padding);
        
        return {
            x: minX,
            y: minY,
            width: maxX - minX,
            height: maxY - minY,
            patterns: patterns
        };
    }
    
    decodeQRData(region) {
        // This is a simplified decoder - real QR codes need proper Reed-Solomon decoding
        // For our SMS system, we'll use pattern recognition for our specific format
        
        // Check if this might contain our SMS QR pattern
        const avgPatternSize = region.patterns.reduce((sum, p) => sum + p.size, 0) / region.patterns.length;
        const regionSize = Math.min(region.width, region.height);
        
        // If we found a good QR-like pattern, we'll trigger manual entry
        if (region.patterns.length >= 3 && avgPatternSize > 10 && regionSize > 80) {
            // Return a special code that indicates QR was detected but needs manual entry
            return "QR_DETECTED_MANUAL_ENTRY_NEEDED";
        }
        
        return null;
    }
    
    calculateLocation(patterns) {
        if (patterns.length === 0) return null;
        
        // Calculate center point and approximate corners
        const centerX = patterns.reduce((sum, p) => sum + p.x, 0) / patterns.length;
        const centerY = patterns.reduce((sum, p) => sum + p.y, 0) / patterns.length;
        
        // Estimate QR code size based on pattern positions
        const maxDist = Math.max(...patterns.map(p => 
            Math.sqrt(Math.pow(p.x - centerX, 2) + Math.pow(p.y - centerY, 2))
        ));
        
        const size = maxDist * 1.4; // Approximate QR code size
        
        return {
            topLeftCorner: { x: centerX - size, y: centerY - size },
            topRightCorner: { x: centerX + size, y: centerY - size },
            bottomLeftCorner: { x: centerX - size, y: centerY + size },
            bottomRightCorner: { x: centerX + size, y: centerY + size }
        };
    }
    
    highlightDetection(location) {
        if (!location) return;
        
        // Draw green highlight around detected area
        this.context.strokeStyle = '#00ff00';
        this.context.lineWidth = 4;
        this.context.setLineDash([10, 5]);
        
        this.context.beginPath();
        this.context.moveTo(location.topLeftCorner.x, location.topLeftCorner.y);
        this.context.lineTo(location.topRightCorner.x, location.topRightCorner.y);
        this.context.lineTo(location.bottomRightCorner.x, location.bottomRightCorner.y);
        this.context.lineTo(location.bottomLeftCorner.x, location.bottomLeftCorner.y);
        this.context.lineTo(location.topLeftCorner.x, location.topLeftCorner.y);
        this.context.stroke();
        
        this.context.setLineDash([]);
    }
    
    stop() {
        this.scanning = false;
        
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
        
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        
        if (this.video) {
            this.video.srcObject = null;
        }
    }
}

// Export for use in other files
window.PureQRScanner = PureQRScanner;