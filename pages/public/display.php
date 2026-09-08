<?php
/**
 * TokenFlow Pro — Public Queue Display
 * Lobby TV / Display Board — No authentication required
 */
require_once __DIR__ . '/../../includes/helpers.php';
tfInit();

$db = Database::getInstance();
$branchId = (int)($_GET['branch'] ?? 1);

$branch = $db->fetch("SELECT * FROM branches WHERE id = ?", [$branchId]);
if (!$branch) die('Branch not found.');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display — <?= e($branch['name']) ?> — <?= APP_NAME ?></title>
    <meta name="robots" content="noindex">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/design-system.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>css/animations.css" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; overflow: hidden; font-family: var(--font-display); background: var(--bg-base); color: var(--text-primary); }
        .display-container { display: grid; grid-template-rows: auto 1fr; height: 100vh; }
        .display-header { display: flex; align-items: center; justify-content: space-between; padding: var(--space-6) var(--space-8); background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-glass); }
        .display-logo { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: var(--space-3); }
        .display-logo i { color: var(--accent-primary); }
        .display-clock { font-size: 2rem; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--text-secondary); }
        .display-body { display: grid; grid-template-columns: 1fr 320px; gap: var(--space-6); padding: var(--space-6) var(--space-8); overflow: hidden; }
        .serving-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-5); align-content: start; }
        .serving-card { background: var(--glass-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-xl); padding: var(--space-8); text-align: center; animation: fadeUp 0.5s ease; }
        .serving-card .counter-label { text-transform: uppercase; font-size: var(--text-sm); letter-spacing: 0.15em; color: var(--text-tertiary); margin-bottom: var(--space-3); }
        .serving-card .counter-number { font-size: 1.2rem; font-weight: 700; color: var(--accent-success); margin-bottom: var(--space-4); }
        .serving-card .token { font-size: 3.5rem; font-weight: 800; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .serving-card .service { font-size: var(--text-sm); color: var(--text-secondary); margin-top: var(--space-3); }
        .queue-sidebar { background: var(--glass-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-xl); overflow: hidden; }
        .queue-sidebar-header { padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--border-glass); text-transform: uppercase; letter-spacing: 0.1em; font-size: var(--text-sm); font-weight: 700; color: var(--accent-warning); }
        .queue-sidebar-list { padding: var(--space-3); overflow-y: auto; max-height: calc(100vh - 200px); }
        .queue-item { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-2); background: rgba(255,255,255,0.02); }
        .queue-item .pos { width: 28px; height: 28px; border-radius: 50%; background: rgba(var(--accent-primary-rgb), 0.15); color: var(--accent-primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xs); font-weight: 700; }
        .queue-item .tk { font-weight: 700; font-size: var(--text-base); }
        .queue-item .svc { font-size: var(--text-xs); color: var(--text-secondary); }
        .queue-item.priority { border-left: 3px solid var(--accent-warning); }
        .flash { animation: tokenFlash 1s ease infinite; }
        @keyframes tokenFlash { 0%,100% { opacity: 1; } 50% { opacity: 0.5; } }
        .marquee { padding: var(--space-2) var(--space-4); background: rgba(var(--accent-primary-rgb), 0.08); border-top: 1px solid var(--border-glass); color: var(--text-secondary); font-size: var(--text-sm); overflow: hidden; white-space: nowrap; }
        .marquee span { display: inline-block; animation: marquee 30s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(100vw); } 100% { transform: translateX(-100%); } }
    </style>
    <script>window.TF = { baseUrl: '<?= BASE_URL ?>', apiUrl: '<?= API_URL ?>' };</script>
</head>
<body>
    <div class="display-container">
        <div class="display-header">
            <div class="display-logo"><i class="bi bi-layers"></i> TokenFlow Pro — <?= e($branch['name']) ?></div>
            <div class="display-clock" id="clock"></div>
        </div>
        
        <div class="display-body">
            <div>
                <div style="text-transform:uppercase;font-size:var(--text-sm);letter-spacing:0.15em;color:var(--accent-success);font-weight:700;margin-bottom:var(--space-4);">
                    <i class="bi bi-play-circle"></i> NOW SERVING
                </div>
                <div class="serving-grid" id="serving-grid">
                    <div class="serving-card"><div class="counter-label">Loading...</div></div>
                </div>
            </div>
            <div class="queue-sidebar">
                <div class="queue-sidebar-header"><i class="bi bi-people"></i> Upcoming Queue</div>
                <div class="queue-sidebar-list" id="queue-list"></div>
            </div>
        </div>
        
        <div class="marquee"><span>Welcome to <?= e($branch['name']) ?>. Please wait for your token to be called. Priority tokens may be served first. Thank you for your patience.</span></div>
    </div>
    
    <script>
        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateClock, 1000);
        updateClock();
        
        // Fetch queue
        let lastServing = [];
        async function fetchQueue() {
            try {
                const resp = await fetch(`${window.TF.apiUrl}queue/display.php?branch_id=<?= $branchId ?>`);
                const result = await resp.json();
                if (!result.success) return;
                
                const { serving, upcoming } = result.data;
                
                // Check for new serving
                serving.forEach(s => {
                    if (!lastServing.find(ls => ls.display_number === s.display_number)) {
                        announceToken(s);
                    }
                });
                lastServing = serving;
                
                // Render serving
                const grid = document.getElementById('serving-grid');
                if (serving.length === 0) {
                    grid.innerHTML = '<div class="serving-card"><div class="counter-label">All Clear</div><div class="token" style="font-size:2rem;">—</div></div>';
                } else {
                    grid.innerHTML = serving.map(s => `
                        <div class="serving-card">
                            <div class="counter-label">Counter</div>
                            <div class="counter-number">${s.counter_number}</div>
                            <div class="token flash">${s.display_number}</div>
                            <div class="service">${s.service_name}</div>
                        </div>
                    `).join('');
                }
                
                // Render upcoming
                const list = document.getElementById('queue-list');
                list.innerHTML = upcoming.map((t, i) => `
                    <div class="queue-item ${t.type === 'priority' ? 'priority' : ''}">
                        <div class="pos">${i + 1}</div>
                        <div><div class="tk">${t.display_number}</div><div class="svc">${t.service_name}</div></div>
                    </div>
                `).join('') || '<div style="padding:var(--space-6);text-align:center;color:var(--text-tertiary);">Queue is empty</div>';
            } catch(e) {}
        }
        
        function announceToken(token) {
            if ('speechSynthesis' in window) {
                const msg = new SpeechSynthesisUtterance(`Token number ${token.display_number}, please proceed to Counter ${token.counter_number}`);
                msg.lang = 'en-IN'; msg.rate = 0.9; msg.volume = 1;
                speechSynthesis.speak(msg);
            }
        }
        
        fetchQueue();
        setInterval(fetchQueue, 3000);
    </script>
</body>
</html>
