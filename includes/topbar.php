<?php
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = ucfirst($_SESSION['user_role'] ?? 'Student');
$user_id   = $_SESSION['user_id'] ?? 0;
$initial   = strtoupper(substr($user_name, 0, 1));

$settings_path = match($_SESSION['user_role'] ?? '') {
    'admin'   => '../admin/settings.php',
    'teacher' => '../teacher/settings.php',
    'parent'  => '../parent/settings.php',
    default   => '../student/settings.php',
};
?>
<div class="topbar">
    <!-- Mobile Sidebar Toggle -->
    <button class="topbar-btn mobile-menu-btn" onclick="document.querySelector('.sidebar')?.classList.toggle('open')" title="Toggle Menu" aria-label="Toggle Menu">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Search -->
    <div class="topbar-search">
        <i class="fas fa-search ts-icon"></i>
        <input type="text" id="globalSearch" placeholder="What do you want to find?" autocomplete="off">
    </div>

    <!-- Actions -->
    <div class="topbar-actions">

        <!-- Notification Bell -->
        <div style="position:relative;">
            <button class="topbar-btn" id="notifBtn" onclick="toggleNotifPanel()" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="topbar-badge" id="notifBadge" style="display:none;">0</span>
            </button>

            <!-- Notification Dropdown -->
            <div id="notifPanel" style="
                display:none;position:absolute;right:0;top:44px;
                width:320px;background:#fff;
                border:1.5px solid var(--border);border-radius:16px;
                box-shadow:0 12px 32px rgba(0,0,0,0.12);z-index:999;
                overflow:hidden;
            ">
                <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:14px;font-weight:700;color:var(--text);">Notifications</span>
                    <button onclick="markAllRead()" style="background:none;border:none;font-size:12px;font-weight:600;color:var(--primary);cursor:pointer;">Mark all read</button>
                </div>
                <div id="notifList" style="max-height:340px;overflow-y:auto;">
                    <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-bell" style="font-size:24px;opacity:0.2;display:block;margin-bottom:8px;"></i>
                        Loading...
                    </div>
                </div>
                <div style="padding:12px 16px;border-top:1.5px solid var(--border);text-align:center;">
                    <a href="#" style="font-size:12px;font-weight:600;color:var(--primary);text-decoration:none;">View All Notifications</a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div style="position:relative;">
            <button class="topbar-btn" id="msgBtn" onclick="toggleMsgPanel()" title="Messages">
                <i class="fas fa-comment-dots"></i>
                <span class="topbar-badge" id="msgBadge" style="display:none;">0</span>
            </button>

            <!-- Messages Dropdown -->
            <div id="msgPanel" style="
                display:none;position:absolute;right:0;top:44px;
                width:340px;background:#fff;
                border:1.5px solid var(--border);border-radius:16px;
                box-shadow:0 12px 32px rgba(0,0,0,0.12);z-index:999;
                overflow:hidden;
            ">
                <div style="padding:14px 16px;border-bottom:1.5px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:14px;font-weight:700;color:var(--text);">Messages</span>
                    <button onclick="showComposeModal()" style="background:var(--primary);color:#fff;border:none;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-pen"></i> Compose
                    </button>
                </div>
                <div id="msgList" style="max-height:340px;overflow-y:auto;">
                    <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-inbox" style="font-size:24px;opacity:0.2;display:block;margin-bottom:8px;"></i>
                        Loading...
                    </div>
                </div>
            </div>
        </div>

        <!-- User chip -->
        <a href="<?php echo $settings_path; ?>" class="topbar-user">
            <div class="topbar-avatar"><?php echo $initial; ?></div>
            <div class="topbar-user-info">
                <div class="topbar-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="topbar-user-role"><?php echo $user_role; ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size:10px;color:var(--text-muted);margin-left:4px;"></i>
        </a>

        <a href="../logout.php" class="topbar-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<!-- Compose Modal -->
<div id="composeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9999;display:none;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:28px;width:460px;max-width:95%;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <span style="font-size:16px;font-weight:700;color:var(--text);">New Message</span>
            <button onclick="hideComposeModal()" style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text-muted);">&times;</button>
        </div>
        <div class="mb-3">
            <label class="form-label">To (User ID)</label>
            <input type="number" id="msgTo" class="form-control" placeholder="Enter recipient User ID">
        </div>
        <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" id="msgSubject" class="form-control" placeholder="Subject">
        </div>
        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea id="msgBody" class="form-control" rows="4" placeholder="Type your message..."></textarea>
        </div>
        <button onclick="sendMessage()" class="btn btn-primary btn-w">
            <i class="fas fa-paper-plane"></i> Send Message
        </button>
        <div id="composeStatus" style="margin-top:10px;font-size:13px;font-weight:600;text-align:center;"></div>
    </div>
</div>

<script>
const CURRENT_USER_ID = <?php echo $user_id; ?>;

// ── Global search ─────────────────────────────────────────
document.getElementById('globalSearch')?.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('table tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── Close dropdowns when clicking outside ─────────────────
document.addEventListener('click', function(e) {
    if (!e.target.closest('#notifBtn') && !e.target.closest('#notifPanel'))
        document.getElementById('notifPanel').style.display = 'none';
    if (!e.target.closest('#msgBtn') && !e.target.closest('#msgPanel') && !e.target.closest('#composeModal'))
        document.getElementById('msgPanel').style.display = 'none';
});

// ── Notifications ─────────────────────────────────────────
function toggleNotifPanel() {
    const p = document.getElementById('notifPanel');
    const isOpen = p.style.display === 'block';
    document.getElementById('msgPanel').style.display = 'none';
    p.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) loadNotifications();
}

function loadNotifications() {
    fetch('../api/notifications.php?user_id=' + CURRENT_USER_ID + '&limit=8')
    .then(r => r.json())
    .then(function(res) {
        if (!res.ok) return;

        const badge = document.getElementById('notifBadge');
        if (res.unread > 0) {
            badge.textContent = res.unread;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }

        const list = document.getElementById('notifList');
        if (res.notifications.length === 0) {
            list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;"><i class="fas fa-check-circle" style="font-size:24px;opacity:0.2;display:block;margin-bottom:8px;"></i>All caught up!</div>';
            return;
        }

        const icons = { 'Fee Reminder':'fa-credit-card', 'General':'fa-bell', 'Attendance':'fa-clipboard-check', 'Exam':'fa-file-alt' };
        list.innerHTML = res.notifications.map(function(n) {
            const icon  = icons[n.type] || 'fa-bell';
            const unread = n.is_read == 0;
            const bg = unread ? 'var(--primary-soft)' : '#fff';
            const time = new Date(n.created_at).toLocaleDateString('en-IN', {day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
            return `<div onclick="markRead(${n.id}, this)" style="padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border-light);background:${bg};transition:background 0.15s;">
                <div style="display:flex;align-items:flex-start;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;margin-top:2px;">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:${unread?700:500};color:var(--text);line-height:1.4;">${n.message}</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">${n.type} · ${time}</div>
                    </div>
                    ${unread ? '<div style="width:7px;height:7px;background:var(--primary);border-radius:50%;flex-shrink:0;margin-top:5px;"></div>' : ''}
                </div>
            </div>`;
        }).join('');
    }).catch(function() {});
}

function markRead(id, el) {
    fetch('../api/notifications.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'mark_read', id:id})
    }).then(function() {
        el.style.background = '#fff';
        el.querySelector('div > div:last-child')?.remove();
        loadBadgeCounts();
    });
}

function markAllRead() {
    fetch('../api/notifications.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'mark_all', user_id:CURRENT_USER_ID})
    }).then(function() { loadNotifications(); });
}

// ── Messages ──────────────────────────────────────────────
function toggleMsgPanel() {
    const p = document.getElementById('msgPanel');
    const isOpen = p.style.display === 'block';
    document.getElementById('notifPanel').style.display = 'none';
    p.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) loadMessages();
}

function loadMessages() {
    fetch('../api/messages.php?user_id=' + CURRENT_USER_ID)
    .then(r => r.json())
    .then(function(res) {
        if (!res.ok) return;

        const badge = document.getElementById('msgBadge');
        if (res.unread > 0) { badge.textContent = res.unread; badge.style.display = 'flex'; }
        else badge.style.display = 'none';

        const list = document.getElementById('msgList');
        if (res.messages.length === 0) {
            list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;"><i class="fas fa-inbox" style="font-size:24px;opacity:0.2;display:block;margin-bottom:8px;"></i>No messages yet</div>';
            return;
        }

        list.innerHTML = res.messages.map(function(m) {
            const unread = m.is_read == 0;
            const bg = unread ? 'var(--primary-soft)' : '#fff';
            const initial = (m.from_name||'?')[0].toUpperCase();
            const time = new Date(m.created_at).toLocaleDateString('en-IN',{day:'numeric',month:'short'});
            return `<div onclick="markMsgRead(${m.id}, this)" style="padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border-light);background:${bg};">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">${initial}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;">
                            <span style="font-size:13px;font-weight:${unread?700:500};color:var(--text);">${m.from_name}</span>
                            <span style="font-size:11px;color:var(--text-muted);">${time}</span>
                        </div>
                        <div style="font-size:12px;color:var(--text-sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${m.subject||m.body.substring(0,50)}</div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }).catch(function() {});
}

function markMsgRead(id) {
    fetch('../api/messages.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'mark_read', id:id})
    }).then(function() { loadMessages(); });
}

function showComposeModal() {
    document.getElementById('composeModal').style.display = 'flex';
    document.getElementById('msgPanel').style.display = 'none';
}
function hideComposeModal() {
    document.getElementById('composeModal').style.display = 'none';
    document.getElementById('composeStatus').textContent = '';
}

function sendMessage() {
    const to   = document.getElementById('msgTo').value;
    const subj = document.getElementById('msgSubject').value;
    const body = document.getElementById('msgBody').value;
    const status = document.getElementById('composeStatus');
    if (!to || !body) { status.style.color='var(--red)'; status.textContent = 'Please fill recipient and message.'; return; }

    fetch('../api/messages.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'send', from_user:CURRENT_USER_ID, to_user:parseInt(to), subject:subj, body:body})
    }).then(r => r.json()).then(function(res) {
        if (res.ok) {
            status.style.color = 'var(--green)';
            status.textContent = '✅ Message sent!';
            document.getElementById('msgTo').value = '';
            document.getElementById('msgSubject').value = '';
            document.getElementById('msgBody').value = '';
            setTimeout(hideComposeModal, 1500);
        }
    });
}

// ── Load badge counts on page load ────────────────────────
function loadBadgeCounts() {
    fetch('../api/notifications.php?user_id=' + CURRENT_USER_ID + '&limit=1')
    .then(r => r.json()).then(function(res) {
        if (res.ok && res.unread > 0) {
            document.getElementById('notifBadge').textContent = res.unread;
            document.getElementById('notifBadge').style.display = 'flex';
        }
    }).catch(function(){});

    fetch('../api/messages.php?user_id=' + CURRENT_USER_ID)
    .then(r => r.json()).then(function(res) {
        if (res.ok && res.unread > 0) {
            document.getElementById('msgBadge').textContent = res.unread;
            document.getElementById('msgBadge').style.display = 'flex';
        }
    }).catch(function(){});
}

// Load counts on every page
if (CURRENT_USER_ID) loadBadgeCounts();
</script>
