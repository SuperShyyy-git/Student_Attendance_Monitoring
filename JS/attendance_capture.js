// attendance_capture.js
(() => {
    const video = document.getElementById('cameraPreview');
    const rfidInput = document.getElementById('rfidInput');
    const statusMessage = document.getElementById('statusMessage');

    // start camera
    async function startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
        } catch (err) {
            console.error('Camera error', err);
            showStatus('Camera not available: ' + (err.message || err), 'error');
        }
    }

    // focus RFID input repeatedly so scanner types into it
    function keepFocusOnRFID() {
        setInterval(() => {
            if (document.activeElement !== rfidInput) {
                try { rfidInput.focus(); } catch (e) {}
            }
        }, 400);
    }

    // show status
    function showStatus(msg, type = 'info') {
        statusMessage.textContent = msg;
        statusMessage.className = 'status-message ' + (type === 'success' ? 'status-success' : (type === 'error' ? 'status-error' : ''));
        // optionally fade out for success
        if (type === 'success') {
            setTimeout(() => {
                if (statusMessage.textContent === msg) statusMessage.textContent = '';
            }, 2000);
        }
    }

    // take snapshot from video and return base64 data
    function takeSnapshot() {
        try {
            const w = video.videoWidth;
            const h = video.videoHeight;
            const canvas = document.createElement('canvas');
            canvas.width = w || 640;
            canvas.height = h || 480;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        } catch (e) {
            console.error('Snapshot error', e);
            return '';
        }
    }

    // send RFID + image to backend
    async function sendAttendance(rfid) {
        showStatus('Processing... verifying face...', 'info');

        const snapshot = takeSnapshot();

        const body = new URLSearchParams();
        body.append('rfid', rfid);
        body.append('image', snapshot);

        try {
            // Add 30-second timeout to prevent hanging requests
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000);

            const res = await fetch('../HTML/rfid_attendance_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // Get response text first to diagnose issues
            const responseText = await res.text();
            
            // Check for HTML error (common issue)
            if (responseText.includes('<') || responseText.includes('<!DOCTYPE') || responseText.includes('Error')) {
                console.error('Server returned HTML/error instead of JSON:', responseText.substring(0, 300));
                showStatus('Server error - check logs at: logs/php_rfid_errors.log', 'error');
                return;
            }
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseErr) {
                console.error('Invalid JSON response. Raw response:', responseText);
                console.error('Parse error:', parseErr.message);
                showStatus('Server error: Invalid JSON response. Check server logs.', 'error');
                return;
            }

            if (data.success) {
                let msg = data.message;
                if (data.face_verified !== undefined) {
                    msg += ' | Face: ' + (data.face_verified ? '✓ Verified' : '✗ Not verified');
                    if (data.face_distance !== null) {
                        msg += ' (distance: ' + data.face_distance.toFixed(3) + ')';
                    }
                }
                showStatus(msg, 'success');
            } else {
                let msg = data.message || 'Error: Unknown error occurred';
                if (data.face_verified === false) {
                    msg += ' (face mismatch)';
                    if (data.debug_info) {
                        console.log('Face verification debug:', data.debug_info);
                    }
                }
                showStatus(msg, 'error');
                console.log('Full response:', data);
            }
        } catch (err) {
            let errorMsg = 'Network error: ';
            if (err.name === 'AbortError') {
                errorMsg += 'Request timeout (server took too long to respond)';
                console.error('Request timeout - server may be hung or processing slowly', err);
            } else if (err instanceof TypeError) {
                errorMsg += 'Connection failed (check server availability and CORS)';
                console.error('Network/CORS error:', err);
            } else {
                errorMsg += err.message || 'Unknown error';
                console.error('Request error:', err);
            }
            showStatus(errorMsg, 'error');
        }
    }

    // handle Enter key (RFID scanners usually send Enter)
    rfidInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const code = rfidInput.value.trim();
            if (code.length === 0) {
                showStatus('No RFID scanned', 'error');
                return;
            }
            // clear input immediately to accept next scan
            rfidInput.value = '';
            sendAttendance(code);
        }
    });

    // autofocus on load and start camera, and keep focus
    window.addEventListener('load', () => {
        startCamera();
        rfidInput.focus();
        keepFocusOnRFID();
    });

})();
