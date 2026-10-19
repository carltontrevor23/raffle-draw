/**
 * Interactive Customer Spinning Wheel Component
 * Cornerstone Asset Managers - Customer Appreciation Month Raffle
 *
 * Requirements enforced:
 * - Equal visual partition size (9 segments = 40 deg each) regardless of probability.
 * - Server-authoritative: Outcome is fetched from backend before spinning.
 * - Wheel animates to the exact segment returned by the server.
 * - No client-side randomness or pointer detection used for outcome.
 */

class RaffleWheel {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            throw new Error(`Canvas #${canvasId} not found`);
        }
        this.ctx = this.canvas.getContext('2d');
        this.segments = options.segments || [];
        this.onComplete = options.onComplete || (() => {});
        this.onStart = options.onStart || (() => {});
        this.onError = options.onError || (() => {});

        this.currentAngle = 0; // In degrees
        this.isSpinning = false;
        this.numSegments = this.segments.length || 9;
        this.segmentAngle = 360 / this.numSegments;

        // Resize for crisp High-DPI screens
        this.setupDpi();
        this.draw();

        window.addEventListener('resize', () => {
            this.setupDpi();
            this.draw();
        });
    }

    setupDpi() {
        const rect = this.canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        const size = Math.min(rect.width || 420, 520);
        
        this.canvas.width = size * dpr;
        this.canvas.height = size * dpr;
        this.ctx.scale(dpr, dpr);
        this.size = size;
        this.centerX = size / 2;
        this.centerY = size / 2;
        this.radius = (size / 2) - 18; // Margin for pointer & studs
    }

    degToRad(deg) {
        return (deg * Math.PI) / 180;
    }

    draw() {
        const { ctx, centerX, centerY, radius, currentAngle, numSegments, segmentAngle } = this;
        ctx.clearRect(0, 0, this.size, this.size);

        // 1. Outer decorative gold ring
        ctx.save();
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius + 10, 0, Math.PI * 2);
        ctx.fillStyle = '#0f0e0c';
        ctx.shadowColor = 'rgba(212, 175, 55, 0.35)';
        ctx.shadowBlur = 18;
        ctx.fill();

        ctx.lineWidth = 4;
        ctx.strokeStyle = '#d4af37';
        ctx.stroke();
        ctx.restore();

        // 2. Decorative gold studs around outer border
        const totalStuds = 36;
        for (let i = 0; i < totalStuds; i++) {
            const studAngle = (i * (360 / totalStuds) * Math.PI) / 180;
            const sx = centerX + (radius + 5) * Math.cos(studAngle);
            const sy = centerY + (radius + 5) * Math.sin(studAngle);
            ctx.beginPath();
            ctx.arc(sx, sy, 2.5, 0, Math.PI * 2);
            ctx.fillStyle = i % 2 === 0 ? '#f5e6a8' : '#8c6e26';
            ctx.fill();
        }

        // 3. Draw equal-sized visual segments
        for (let i = 0; i < numSegments; i++) {
            const seg = this.segments[i] || { name: `Prize ${i+1}` };
            const startAngle = this.degToRad(currentAngle + (i * segmentAngle));
            const endAngle = this.degToRad(currentAngle + ((i + 1) * segmentAngle));

            // Segment slice
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, startAngle, endAngle);
            ctx.closePath();

            // Segment Background (Luxury obsidian/charcoal alternating tones)
            ctx.fillStyle = seg.color || (i % 2 === 0 ? '#1f1c18' : '#141311');
            ctx.fill();

            // Segment Divider line
            ctx.lineWidth = 1.5;
            ctx.strokeStyle = 'rgba(212, 175, 55, 0.45)';
            ctx.stroke();

            // Draw segment text & icon
            ctx.save();
            ctx.translate(centerX, centerY);
            // Angle to segment center
            const midAngle = currentAngle + (i * segmentAngle) + (segmentAngle / 2);
            ctx.rotate(this.degToRad(midAngle));

            // Icon & Label layout along radial axis
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            
            // Icon
            if (seg.icon) {
                ctx.font = `${Math.max(14, radius * 0.085)}px sans-serif`;
                ctx.fillText(seg.icon, radius - 16, 0);
            }

            // Text Label
            ctx.fillStyle = seg.text_color || (seg.is_win ? '#f3e5ab' : '#8d8577');
            ctx.font = `600 ${Math.max(10, radius * 0.055)}px "Libre Franklin", Arial, sans-serif`;
            
            const label = seg.segment_label || seg.name || '';
            const textOffset = seg.icon ? 38 : 16;
            ctx.fillText(label, radius - textOffset, 0);

            ctx.restore();
        }

        // 4. Center hub (Cornerstone emblem)
        ctx.save();
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius * 0.22, 0, Math.PI * 2);
        ctx.fillStyle = '#0f0e0c';
        ctx.shadowColor = 'rgba(0,0,0,0.6)';
        ctx.shadowBlur = 12;
        ctx.fill();
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#d4af37';
        ctx.stroke();

        // Inner gold ring
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius * 0.16, 0, Math.PI * 2);
        const grad = ctx.createLinearGradient(centerX - 20, centerY - 20, centerX + 20, centerY + 20);
        grad.addColorStop(0, '#d4af37');
        grad.addColorStop(0.5, '#f5e6a8');
        grad.addColorStop(1, '#9b7c23');
        ctx.fillStyle = grad;
        ctx.fill();

        // Center hub label
        ctx.fillStyle = '#000000';
        ctx.font = `700 ${Math.max(9, radius * 0.055)}px "Libre Franklin", sans-serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('CAM', centerX, centerY);
        ctx.restore();

        // 5. Fixed Golden Pointer at Top (12 o'clock / 270 deg)
        this.drawPointer();
    }

    drawPointer() {
        const { ctx, centerX, centerY, radius } = this;
        const pointerY = centerY - radius - 6;

        ctx.save();
        ctx.translate(centerX, pointerY);
        
        ctx.beginPath();
        ctx.moveTo(0, 24);         // Tip pointing DOWN into the wheel
        ctx.lineTo(-12, -4);       // Top left
        ctx.lineTo(12, -4);        // Top right
        ctx.closePath();

        const grad = ctx.createLinearGradient(-10, -4, 10, 24);
        grad.addColorStop(0, '#f9efb8');
        grad.addColorStop(0.5, '#d4af37');
        grad.addColorStop(1, '#785b14');
        ctx.fillStyle = grad;
        ctx.shadowColor = 'rgba(0,0,0,0.7)';
        ctx.shadowBlur = 8;
        ctx.fill();

        ctx.lineWidth = 1.5;
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();

        // Pointer pin cap
        ctx.beginPath();
        ctx.arc(0, -4, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#1c1b18';
        ctx.fill();
        ctx.strokeStyle = '#d4af37';
        ctx.stroke();

        ctx.restore();
    }

    /**
     * Spin wheel to target segment index decided by backend.
     * The pointer is at 12 o'clock (270 degrees on canvas).
     *
     * @param {number} targetSegmentIndex Index (0 to numSegments-1) returned by backend
     * @param {function} onAnimationDone Callback once wheel comes to full rest
     */
    spinTo(targetSegmentIndex, onAnimationDone) {
        if (this.isSpinning) {
            return;
        }
        this.isSpinning = true;

        const duration = 5200; // 5.2 seconds for realistic anticipation
        const startTime = performance.now();
        const startAngle = this.currentAngle;

        // Pointer is at Top (270 degrees)
        // Center of segment i is at (i + 0.5) * segmentAngle
        const segmentCenter = (targetSegmentIndex + 0.5) * this.segmentAngle;

        // Calculate needed landing angle:
        // (segmentCenter + targetAngle) % 360 == 270
        // targetAngle % 360 = (270 - segmentCenter + 360) % 360
        let targetModulo = (270 - segmentCenter) % 360;
        if (targetModulo < 0) {
            targetModulo += 360;
        }

        // Add 5 to 7 full revolutions for exciting spin
        const fullSpins = 6 * 360; 
        
        // Slight organic variation (+- 8 deg) well inside the 40 deg segment boundary
        const jitter = (Math.random() - 0.5) * 12;

        const currentModulo = startAngle % 360;
        let delta = targetModulo - currentModulo;
        if (delta < 0) {
            delta += 360;
        }

        const totalAngleToRotate = fullSpins + delta + jitter;
        const targetFinalAngle = startAngle + totalAngleToRotate;

        // Cubic-bezier ease-out deceleration curve
        const easeOut = (t) => {
            // Cubic bezier approximation f(t) = 1 - Math.pow(1 - t, 3.8)
            return 1 - Math.pow(1 - t, 3.8);
        };

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = easeOut(progress);

            this.currentAngle = startAngle + (totalAngleToRotate * ease);
            this.draw();

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                this.currentAngle = targetFinalAngle;
                this.draw();
                this.isSpinning = false;
                if (typeof onAnimationDone === 'function') {
                    onAnimationDone();
                }
            }
        };

        requestAnimationFrame(animate);
    }
}

window.RaffleWheel = RaffleWheel;

