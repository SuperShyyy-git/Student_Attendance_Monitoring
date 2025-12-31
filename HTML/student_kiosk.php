<?php
/**
 * Student Kiosk - Face Recognition Check-in
 * Professional face mesh design with triangular pattern
 */

date_default_timezone_set('Asia/Manila');
include __DIR__ . "/../config/db_connect.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Check-in Kiosk</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f1a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
        }
        
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%);
        }
        
        .clock { font-size: 32px; font-weight: 600; color: #00d4ff; }
        .date { font-size: 14px; color: rgba(255,255,255,0.6); }
        
        .kiosk-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        
        .title {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 8px;
            background: linear-gradient(90deg, #00d4ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .subtitle {
            color: rgba(255,255,255,0.5);
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .camera-container {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 0 60px rgba(0, 212, 255, 0.2);
        }
        
        .camera-border {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 3px solid #00d4ff;
            border-radius: 16px;
            pointer-events: none;
            transition: all 0.3s;
        }
        
        .camera-border.face-detected {
            border-color: #00ff88;
            box-shadow: 0 0 40px rgba(0, 255, 136, 0.3);
        }
        
        .camera-border.no-face { border-color: #ff6b35; }
        
        .camera-border.scanning {
            border-color: #00d4ff;
            animation: scan-glow 1.5s infinite;
        }
        
        @keyframes scan-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 212, 255, 0.5); }
            50% { box-shadow: 0 0 50px rgba(0, 212, 255, 0.8); }
        }
        
        #camera-preview {
            width: 560px;
            height: 420px;
            object-fit: cover;
            display: block;
            transform: scaleX(-1);
        }
        
        #face-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 560px;
            height: 420px;
            pointer-events: none;
            transform: scaleX(-1);
        }
        
        #canvas { display: none; }
        
        .status-badge {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 13px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .status-badge.loading { background: rgba(0, 212, 255, 0.3); }
        .status-badge.ready { background: rgba(0, 255, 136, 0.3); }
        .status-badge.no-face { background: rgba(255, 107, 53, 0.3); }
        .status-badge.error { background: rgba(255, 68, 68, 0.3); }
        
        /* Scan line effect */
        .scan-line {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #00d4ff, transparent);
            opacity: 0;
            animation: none;
        }
        
        .scan-line.active {
            opacity: 1;
            animation: scan-down 2s linear infinite;
        }
        
        @keyframes scan-down {
            0% { top: 0; }
            100% { top: 100%; }
        }
        
        .btn-scan {
            margin-top: 25px;
            padding: 16px 60px;
            font-size: 18px;
            font-weight: 600;
            background: linear-gradient(135deg, #00d4ff 0%, #00ff88 100%);
            color: #0a0a0f;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 40px rgba(0, 212, 255, 0.3);
        }
        
        .btn-scan:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0, 255, 136, 0.4);
        }
        
        .btn-scan:disabled {
            background: #333;
            color: #666;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .result-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }
        
        .result-overlay.visible { display: flex; animation: fadeIn 0.3s; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .result-card {
            background: linear-gradient(135deg, #1a1a2e, #2a2a4e);
            border: 2px solid #00d4ff;
            border-radius: 20px;
            padding: 40px 60px;
            text-align: center;
            box-shadow: 0 0 60px rgba(0, 212, 255, 0.3);
        }
        
        .result-card .icon { font-size: 64px; margin-bottom: 15px; }
        .result-card .student-name { font-size: 28px; font-weight: 700; color: #fff; margin-bottom: 15px; }
        
        .result-card .action-badge {
            font-size: 20px;
            font-weight: 600;
            padding: 10px 35px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .result-card .action-badge.timein { background: rgba(0, 255, 136, 0.2); color: #00ff88; border: 1px solid #00ff88; }
        .result-card .action-badge.timeout { background: rgba(255, 200, 50, 0.2); color: #ffc832; border: 1px solid #ffc832; }
        .result-card .time { font-size: 16px; color: rgba(255,255,255,0.6); }
        
        .bottom-links {
            position: fixed;
            bottom: 15px;
            left: 0; right: 0;
            display: flex;
            justify-content: space-between;
            padding: 0 25px;
        }
        
        .bottom-links a {
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            font-size: 12px;
        }
        
        .bottom-links a:hover { color: #00d4ff; }
        
        .instructions {
            margin-top: 15px;
            padding: 10px 20px;
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 10px;
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div>
            <div class="clock" id="clock">00:00:00</div>
        </div>
        <div class="date" id="date"></div>
    </div>
    
    <div class="kiosk-container">
        <div class="title">🔐 Face Recognition Check-in</div>
        <p class="subtitle">Position your face in the frame</p>
        
        <div class="camera-container">
            <video id="camera-preview" autoplay playsinline></video>
            <canvas id="face-overlay" width="560" height="420"></canvas>
            <canvas id="canvas" width="560" height="420"></canvas>
            <div class="camera-border" id="camera-border"></div>
            <div class="scan-line" id="scan-line"></div>
            <div class="status-badge loading" id="status-badge">⏳ Initializing AI...</div>
        </div>
        
        <button class="btn-scan" id="btn-scan" disabled>📷 SCAN FACE</button>
        
        <div class="instructions">
            💡 Green mesh = Face detected. Press SCAN to check in or out.
        </div>
    </div>
    
    <div class="result-overlay" id="result-overlay">
        <div class="result-card">
            <div class="icon" id="result-icon">✅</div>
            <div class="student-name" id="result-name">Student Name</div>
            <div class="action-badge timein" id="result-action">TIME IN</div>
            <div class="time" id="result-time">08:00 AM</div>
        </div>
    </div>
    
    <div class="bottom-links">
        <a href="index.php">← Home</a>
        <a href="login.php">Admin →</a>
    </div>
    
    <script>
        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', { hour12: true });
            document.getElementById('date').textContent = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
        }
        updateClock();
        setInterval(updateClock, 1000);
        
        const video = document.getElementById('camera-preview');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const faceOverlay = document.getElementById('face-overlay');
        const faceCtx = faceOverlay.getContext('2d');
        const btnScan = document.getElementById('btn-scan');
        const cameraBorder = document.getElementById('camera-border');
        const statusBadge = document.getElementById('status-badge');
        const scanLine = document.getElementById('scan-line');
        const resultOverlay = document.getElementById('result-overlay');
        
        let modelsLoaded = false;
        
        // Triangular mesh connections (based on 68 landmarks)
        const MESH_CONNECTIONS = [
            // Forehead triangles
            [17, 18, 36], [18, 36, 37], [18, 19, 37], [19, 37, 38], [19, 20, 38], [20, 38, 39], [20, 21, 39],
            [21, 39, 27], [22, 27, 42], [22, 42, 43], [22, 23, 43], [23, 43, 44], [23, 24, 44], [24, 44, 45],
            [24, 25, 45], [25, 45, 46], [25, 26, 46],
            // Left eye area
            [36, 37, 41], [37, 38, 40], [37, 40, 41], [38, 39, 40],
            // Right eye area  
            [42, 43, 47], [43, 44, 46], [43, 46, 47], [44, 45, 46],
            // Nose bridge
            [27, 28, 39], [27, 28, 42], [28, 29, 39], [28, 29, 42],
            [29, 30, 31], [29, 30, 35], [30, 31, 32], [30, 32, 33], [30, 33, 34], [30, 34, 35],
            // Nose to eyes
            [39, 40, 31], [40, 41, 31], [31, 41, 48], [42, 46, 35], [35, 46, 45], [35, 45, 54],
            // Cheeks
            [0, 1, 36], [1, 36, 41], [1, 2, 41], [2, 41, 31], [2, 3, 31], [3, 31, 48], [3, 4, 48],
            [16, 15, 45], [15, 45, 46], [15, 14, 46], [14, 46, 35], [14, 13, 35], [13, 35, 54], [13, 12, 54],
            // Mouth area
            [48, 49, 60], [49, 50, 60], [50, 60, 61], [50, 51, 61], [51, 61, 62], [51, 52, 62],
            [52, 62, 63], [52, 53, 63], [53, 63, 64], [53, 54, 64],
            [48, 59, 60], [59, 60, 67], [54, 55, 64], [55, 64, 65],
            // Chin
            [4, 5, 48], [5, 6, 48], [6, 48, 59], [6, 7, 59], [7, 8, 59], [7, 8, 57],
            [12, 11, 54], [11, 10, 54], [10, 54, 55], [10, 9, 55], [9, 8, 55], [8, 9, 57],
            [57, 58, 59], [55, 56, 57]
        ];
        
        async function loadModels() {
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                modelsLoaded = true;
                statusBadge.textContent = '✓ AI Ready';
                statusBadge.className = 'status-badge ready';
                await startCamera();
            } catch (error) {
                statusBadge.textContent = '⚠ AI Error';
                statusBadge.className = 'status-badge error';
                await startCamera();
            }
        }
        
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: 'user' } });
                video.srcObject = stream;
                video.addEventListener('loadedmetadata', () => {
                    btnScan.disabled = false;
                    if (modelsLoaded) startFaceDetection();
                });
            } catch (err) {
                statusBadge.textContent = '❌ Camera Error';
                statusBadge.className = 'status-badge error';
            }
        }
        
        function startFaceDetection() {
            setInterval(async () => {
                if (!modelsLoaded || video.paused || video.ended) return;
                
                try {
                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })).withFaceLandmarks();
                    
                    faceCtx.clearRect(0, 0, faceOverlay.width, faceOverlay.height);
                    
                    if (detections.length > 0) {
                        cameraBorder.className = 'camera-border face-detected';
                        statusBadge.textContent = '✓ Face Detected';
                        statusBadge.className = 'status-badge ready';
                        
                        detections.forEach(det => {
                            const pts = det.landmarks.positions;
                            const scaleX = 560 / video.videoWidth;
                            const scaleY = 420 / video.videoHeight;
                            
                            // Draw triangular mesh
                            faceCtx.strokeStyle = 'rgba(255, 200, 100, 0.6)';
                            faceCtx.lineWidth = 1;
                            
                            MESH_CONNECTIONS.forEach(tri => {
                                if (tri.length === 3 && pts[tri[0]] && pts[tri[1]] && pts[tri[2]]) {
                                    faceCtx.beginPath();
                                    faceCtx.moveTo(pts[tri[0]].x * scaleX, pts[tri[0]].y * scaleY);
                                    faceCtx.lineTo(pts[tri[1]].x * scaleX, pts[tri[1]].y * scaleY);
                                    faceCtx.lineTo(pts[tri[2]].x * scaleX, pts[tri[2]].y * scaleY);
                                    faceCtx.closePath();
                                    faceCtx.stroke();
                                }
                            });
                            
                            // Draw landmark points
                            faceCtx.fillStyle = 'rgba(255, 200, 100, 0.9)';
                            pts.forEach(p => {
                                faceCtx.beginPath();
                                faceCtx.arc(p.x * scaleX, p.y * scaleY, 2, 0, Math.PI * 2);
                                faceCtx.fill();
                            });
                        });
                    } else {
                        cameraBorder.className = 'camera-border no-face';
                        statusBadge.textContent = '👤 No face detected';
                        statusBadge.className = 'status-badge no-face';
                    }
                } catch (e) {}
            }, 100);
        }
        
        btnScan.addEventListener('click', async function() {
            btnScan.disabled = true;
            cameraBorder.className = 'camera-border scanning';
            scanLine.className = 'scan-line active';
            statusBadge.textContent = '🔍 Scanning...';
            statusBadge.className = 'status-badge loading';
            
            ctx.save();
            ctx.scale(-1, 1);
            ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
            ctx.restore();
            
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            try {
                const response = await fetch('face_checkin_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'image=' + encodeURIComponent(imageData)
                });
                
                const data = await response.json();
                scanLine.className = 'scan-line';
                
                if (data.success) {
                    document.getElementById('result-icon').textContent = data.status === 'TIME IN' ? '✅' : '👋';
                    document.getElementById('result-name').textContent = data.student_name;
                    document.getElementById('result-action').textContent = data.status;
                    document.getElementById('result-action').className = 'action-badge ' + (data.status === 'TIME IN' ? 'timein' : 'timeout');
                    document.getElementById('result-time').textContent = data.time;
                    resultOverlay.className = 'result-overlay visible';
                    
                    setTimeout(() => {
                        resultOverlay.className = 'result-overlay';
                        cameraBorder.className = 'camera-border';
                        btnScan.disabled = false;
                    }, 4000);
                } else {
                    statusBadge.textContent = '❌ ' + data.message;
                    statusBadge.className = 'status-badge error';
                    setTimeout(() => { cameraBorder.className = 'camera-border'; btnScan.disabled = false; }, 3000);
                }
            } catch (error) {
                scanLine.className = 'scan-line';
                statusBadge.textContent = '❌ Error';
                statusBadge.className = 'status-badge error';
                btnScan.disabled = false;
            }
        });
        
        document.addEventListener('DOMContentLoaded', loadModels);
    </script>
</body>
</html>