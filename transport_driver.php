<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver Broadcast Hub | ia Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #7C3AED;
            --primary-dark: #6D28D9;
            --bg: #0F172A;
            --card: #1E293B;
            --text: #F8FAFC;
            --text-muted: #94A3B8;
            --success: #10B981;
            --danger: #EF4444;
        }

        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        .bg-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            background: var(--primary);
            filter: blur(150px);
            opacity: 0.15;
            z-index: -1;
        }

        .card {
            background: var(--card);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 32px;
            padding: 32px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            position: relative;
            overflow: hidden;
        }

        .header { text-align: center; margin-bottom: 32px; }
        .icon-box {
            width: 64px;
            height: 64px;
            background: rgba(124,58,237,0.15);
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
        }

        h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.5px; }
        p { font-size: 14px; color: var(--text-muted); line-height: 1.5; }

        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            color: var(--text-muted);
            margin-bottom: 8px;
            margin-left: 4px;
        }

        input, select {
            width: 100%;
            padding: 14px 18px;
            background: rgba(15,23,42,0.5);
            border: 1.5px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            color: var(--text);
            font-family: inherit;
            font-size: 15px;
            outline: none;
            transition: all 0.3s;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            background: rgba(15,23,42,0.8);
            box-shadow: 0 0 0 4px rgba(124,58,237,0.1);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 18px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-start {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 10px 25px rgba(124,58,237,0.4);
        }

        .btn-start:active { transform: scale(0.95); }

        .btn-stop {
            background: rgba(239,68,68,0.1);
            color: var(--danger);
            border: 1.5px solid rgba(239,68,68,0.2);
            margin-top: 12px;
        }

        .status-badge {
            margin-top: 24px;
            padding: 16px;
            border-radius: 20px;
            background: rgba(16,185,129,0.05);
            border: 1px solid rgba(16,185,129,0.1);
            display: none;
            text-align: center;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .status-badge.active { display: block; }
        .status-badge.error { background: rgba(239,68,68,0.05); border-color: rgba(239,68,68,0.1); }

        .pulse {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 0 rgba(16,185,129,0.4);
            animation: pulse-ring 1.5s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16,185,129,0); }
            100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        }

        .coords {
            margin-top: 12px;
            font-family: monospace;
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .version {
            position: absolute;
            bottom: 20px;
            font-size: 10px;
            color: var(--text-muted);
            opacity: 0.4;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <div class="card">
        <div class="header">
            <div class="icon-box">
                <i class="fas fa-satellite-dish"></i>
            </div>
            <h1>Broadcast Hub</h1>
            <p>Ready to transmit live coordinates to the academy network.</p>
        </div>

        <div class="form-group">
            <label>Reporting Official</label>
            <input type="text" id="driverName" placeholder="Driver Name" value="Senior Driver">
        </div>

        <div class="form-group row g-2" style="display:flex; gap:12px;">
            <div style="flex:1">
                <label>Vessel ID</label>
                <select id="busId">
                    <option value="BUS-1">BUS-01</option>
                    <option value="BUS-2">BUS-02</option>
                    <option value="BUS-3">BUS-03</option>
                </select>
            </div>
            <div style="flex:2">
                <label>Designated Route</label>
                <select id="routeName">
                    <option value="Alappuzha → Kochi (NH-66)">Kochi Express (NH-66)</option>
                    <option value="Route R-104 (Downtown)">Downtown Loop</option>
                    <option value="Route R-105 (North Campus)">North Boundary</option>
                </select>
            </div>
        </div>

        <button class="btn btn-start" id="startBtn" onclick="startBroadcast()">
            <i class="fas fa-play"></i> START BROADCAST
        </button>
        
        <button class="btn btn-stop" id="stopBtn" onclick="stopBroadcast()" style="display:none;">
            <i class="fas fa-power-off"></i> TERMINATE
        </button>

        <div class="status-badge" id="statusBox">
            <div style="font-size: 14px; font-weight: 600; display:flex; align-items:center; justify-content:center;">
                <span class="pulse" id="statusPulse"></span>
                <span id="statusText">System Active</span>
            </div>
            <div class="coords" id="coordsBox"></div>
        </div>
    </div>

    <div class="version">ia ACADEMY &middot; GPS OVERRIDE V2.4</div>

    <script>
        let watchId = null;
        const API = 'api/gps_location.php';

        function startBroadcast() {
            if (!navigator.geolocation) {
                showError('GPS MODULE NOT FOUND');
                return;
            }
            
            document.getElementById('startBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display  = 'flex';
            
            watchId = navigator.geolocation.watchPosition(
                function(pos) {
                    const lat   = pos.coords.latitude;
                    const lng   = pos.coords.longitude;
                    const speed = pos.coords.speed ? (pos.coords.speed * 3.6).toFixed(1) : 0;
                    sendLocation(lat, lng, speed);
                },
                function(err) { showError('SIGNAL LOST: ' + err.message); },
                { enableHighAccuracy: true, maximumAge: 2000, timeout: 15000 }
            );
        }

        function stopBroadcast() {
            if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
            document.getElementById('startBtn').style.display = 'flex';
            document.getElementById('stopBtn').style.display  = 'none';
            document.getElementById('statusBox').classList.remove('active');
        }

        function sendLocation(lat, lng, speed) {
            const payload = {
                lat:         lat,
                lng:         lng,
                speed:       parseFloat(speed),
                bus_id:      document.getElementById('busId').value,
                driver_name: document.getElementById('driverName').value,
                route_name:  document.getElementById('routeName').value,
            };

            fetch(API, {
                method:  'POST',
                headers: {'Content-Type': 'application/json'},
                body:    JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(function(data) {
                if (data.ok) {
                    const box = document.getElementById('statusBox');
                    box.classList.add('active');
                    box.classList.remove('error');
                    document.getElementById('statusPulse').style.background = '#10B981';
                    document.getElementById('statusText').innerText = 'TRANSMITTING LIVE';
                    document.getElementById('coordsBox').innerText = 'LAT: ' + lat.toFixed(5) + ' | LNG: ' + lng.toFixed(5);
                }
            })
            .catch(function() { showError('NETWORK DOWN — RETRYING'); });
        }

        function showError(msg) {
            const box = document.getElementById('statusBox');
            box.classList.add('active', 'error');
            document.getElementById('statusPulse').style.background = '#EF4444';
            document.getElementById('statusText').innerText = msg;
        }
    </script>
</body>
</html>
