<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: logs.php?msg=deleted");
    exit();
}

$page_title = "Audit logs";

$logs = $pdo->query("
    SELECT l.*, u.email 
    FROM logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.timestamp DESC 
    LIMIT 100
")->fetchAll();

$unique_users = [];
foreach ($logs as $l) {
    if (!in_array($l['user_name'], $unique_users)) {
        $unique_users[] = $l['user_name'];
    }
}
sort($unique_users);

function getActionBadgeData($dbAction) {
    $actionLower = strtolower($dbAction);
    if (strpos($actionLower, 'delete') !== false || strpos($actionLower, 'reject') !== false)
        return ['bg' => '#FEE2E2', 'text' => '#DC2626', 'label' => 'Deleting'];
    if (strpos($actionLower, 'update') !== false || strpos($actionLower, 'edit') !== false)
        return ['bg' => '#FFEDD5', 'text' => '#EA580C', 'label' => 'Updates'];
    if (strpos($actionLower, 'upload') !== false || strpos($actionLower, 'cert') !== false || strpos($actionLower, 'submit') !== false)
        return ['bg' => '#DBEAFE', 'text' => '#2563EB', 'label' => 'Uploading'];
    if (strpos($actionLower, 'feedback') !== false)
        return ['bg' => '#F3E8FF', 'text' => '#9333EA', 'label' => 'Feedback'];
    if (strpos($actionLower, 'admin') !== false || strpos($actionLower, 'change') !== false || strpos($actionLower, 'approval') !== false)
        return ['bg' => '#DCFCE7', 'text' => '#16A34A', 'label' => 'Changing'];
    return ['bg' => '#F3F4F6', 'text' => '#4B5563', 'label' => 'Comment'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs | Project ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .main-content { font-family: 'Inter', sans-serif; }

        .logs-page { padding: 28px 32px; }

        .logs-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .logs-page-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1E1B4B;
            margin: 0;
        }
        .logs-page-header p {
            font-size: 13px;
            color: #9CA3AF;
            margin: 4px 0 0;
        }
        .btn-export-new {
            background: linear-gradient(135deg, #7C3AED, #6D28D9);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(109, 40, 217, 0.35);
            transition: all 0.2s ease;
        }
        .btn-export-new:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(109, 40, 217, 0.45);
        }

        .controls-card {
            background: #fff;
            border-radius: 16px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border: 1px solid #EDE9FE;
        }

        select.filter-select {
            background: #F5F3FF;
            border: 1.5px solid #DDD6FE;
            border-radius: 10px;
            padding: 9px 36px 9px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #5B21B6;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="%235B21B6" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px top 50%;
            transition: all 0.2s;
            min-width: 160px;
        }
        select.filter-select:focus {
            outline: none;
            border-color: #7C3AED;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }

        .search-wrapper { position: relative; }
        .search-wrapper i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #A78BFA;
            font-size: 13px;
        }
        .search-wrapper input {
            border: 1.5px solid #DDD6FE;
            border-radius: 10px;
            padding: 9px 16px 9px 36px;
            font-size: 13px;
            color: #1E1B4B;
            width: 260px;
            background: #F5F3FF;
            transition: all 0.2s;
        }
        .search-wrapper input::placeholder { color: #C4B5FD; }
        .search-wrapper input:focus {
            outline: none;
            border-color: #7C3AED;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }

        .table-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #EDE9FE;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .audit-table { width: 100%; border-collapse: collapse; }
        .audit-table thead tr {
            background: #F5F3FF;
            border-bottom: 1.5px solid #EDE9FE;
        }
        .audit-table th {
            font-size: 11.5px;
            font-weight: 700;
            color: #7C3AED;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 22px;
            text-align: left;
        }
        .audit-table td {
            padding: 16px 22px;
            border-bottom: 1px solid #F5F3FF;
            vertical-align: middle;
        }
        .audit-table tbody tr { transition: background 0.15s; }
        .audit-table tbody tr:hover { background: #FAFAFF; }
        .audit-table tbody tr:last-child td { border-bottom: none; }

        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #7C3AED, #A78BFA);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; font-weight: 700; flex-shrink: 0;
        }
        .u-name { font-size: 13.5px; font-weight: 600; color: #1E1B4B; line-height: 1.3; }
        .u-email { font-size: 12px; color: #9CA3AF; }

        .action-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 9999px;
            font-size: 12px; font-weight: 600;
        }
        .dot { width: 6px; height: 6px; border-radius: 50%; }

        .date-primary { font-size: 13px; font-weight: 600; color: #1E1B4B; }
        .date-secondary { font-size: 12px; color: #9CA3AF; }
        .subject-text { font-size: 13px; font-weight: 600; color: #374151; }

        .icon-btn {
            width: 32px; height: 32px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all 0.2s; font-size: 13px;
        }
        .icon-btn-view { background: #EEF2FF; color: #6366F1; }
        .icon-btn-view:hover { background: #6366F1; color: #fff; }
        .icon-btn-delete { background: #FEF2F2; color: #EF4444; }
        .icon-btn-delete:hover { background: #EF4444; color: #fff; }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 40px; color: #DDD6FE; margin-bottom: 12px; display: block; }
        .empty-state p { color: #9CA3AF; font-size: 14px; }

        /* Modal */
        .detail-modal .modal-content { border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(109,40,217,0.15); }
        .detail-modal .modal-header { border-bottom: 1.5px solid #EDE9FE; padding: 20px 24px 16px; }
        .detail-modal .modal-title { font-weight: 700; color: #1E1B4B; font-size: 16px; }
        .detail-modal .modal-body { padding: 20px 24px; }
        .detail-item { margin-bottom: 18px; }
        .detail-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #A78BFA; margin-bottom: 5px; }
        .detail-value { font-size: 14px; font-weight: 500; color: #1E1B4B; }
        .detail-code {
            background: #F5F3FF; border: 1px solid #EDE9FE; border-radius: 8px;
            padding: 12px 14px; font-size: 12.5px; color: #5B21B6;
            font-family: 'Courier New', monospace; word-break: break-all;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">
        <?php include('../includes/topbar.php'); ?>
        <div class="logs-page">

            <div class="logs-page-header">
                <div>
                    <h1><i class="fas fa-shield-halved me-2" style="color:#7C3AED;"></i>Audit Logs</h1>
                    <p>Real-time system action tracking for compliance and monitoring</p>
                </div>
                <a href="export_logs.php" class="btn-export-new">
                    <i class="fas fa-arrow-up-from-bracket"></i> Export Logs
                </a>
            </div>

            <div class="controls-card">
                <div class="d-flex align-items-center gap-3">
                    <select id="userFilter" class="filter-select" onchange="filterTable()">
                        <option value="">All Users</option>
                        <?php foreach($unique_users as $uname): ?>
                            <option value="<?php echo htmlspecialchars($uname); ?>"><?php echo htmlspecialchars($uname); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span style="font-size:12px;color:#C4B5FD;font-weight:600;"><?php echo count($logs); ?> entries</span>
                </div>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search logs..." oninput="filterTable()">
                </div>
            </div>

            <div class="table-card">
                <table class="audit-table" id="logsTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Date &amp; Time</th>
                            <th>Subject</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-list"></i>
                                    <p>No audit logs recorded yet.</p>
                                </div>
                            </td></tr>
                        <?php endif; ?>

                        <?php foreach ($logs as $log):
                            $badge = getActionBadgeData($log['action']);
                            $dateStr = date('d M, Y', strtotime($log['timestamp']));
                            $timeStr = date('H:i:s', strtotime($log['timestamp']));
                            $email = $log['email'] ?: 'unknown@user.com';
                            $initials = strtoupper(substr($log['user_name'], 0, 1));
                            $detailsTrimmed = strlen($log['details']) > 36 ? substr($log['details'], 0, 36) . '...' : $log['details'];
                        ?>
                        <tr class="log-row">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <div class="u-name"><?php echo htmlspecialchars($log['user_name']); ?></div>
                                        <div class="u-email"><?php echo htmlspecialchars($email); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="action-pill" style="background:<?php echo $badge['bg']; ?>;color:<?php echo $badge['text']; ?>;">
                                    <div class="dot" style="background:<?php echo $badge['text']; ?>;"></div>
                                    <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="date-primary"><?php echo $dateStr; ?></div>
                                <div class="date-secondary"><?php echo $timeStr; ?></div>
                            </td>
                            <td>
                                <div class="subject-text">"<?php echo htmlspecialchars($detailsTrimmed); ?>"</div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="javascript:void(0)" class="icon-btn icon-btn-view"
                                       data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $log['id']; ?>"
                                       title="View Details">
                                        <i class="far fa-eye"></i>
                                    </a>
                                    <a href="logs.php?delete_id=<?php echo $log['id']; ?>"
                                       class="icon-btn icon-btn-delete" title="Delete Log"
                                       onclick="return confirm('Delete this audit log permanently?');">
                                        <i class="far fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php foreach ($logs as $log): ?>
<div class="modal fade detail-modal" id="viewModal<?php echo $log['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Log Details</h5>
                    <p style="font-size:12px;color:#A78BFA;margin:2px 0 0;">ID #<?php echo $log['id']; ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="detail-item">
                    <div class="detail-label">Performed By</div>
                    <div class="detail-value"><?php echo htmlspecialchars($log['user_name']); ?>
                        <span style="color:#9CA3AF;font-size:12px;margin-left:6px;">ID: <?php echo $log['user_id']; ?></span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Timestamp</div>
                    <div class="detail-value"><?php echo date('d M, Y \a\t H:i:s', strtotime($log['timestamp'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">System Action</div>
                    <div class="detail-value"><?php echo htmlspecialchars($log['action']); ?></div>
                </div>
                <div class="detail-item" style="margin-bottom:0;">
                    <div class="detail-label">Full Details</div>
                    <div class="detail-code"><?php echo htmlspecialchars($log['details']); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterTable() {
    var searchVal = document.getElementById("searchInput").value.toLowerCase();
    var userVal   = document.getElementById("userFilter").value.toLowerCase();
    var trs = document.getElementById("logsTable").getElementsByClassName("log-row");
    for (var i = 0; i < trs.length; i++) {
        var userName = trs[i].querySelector(".u-name").innerText.toLowerCase();
        var rowText  = trs[i].innerText.toLowerCase();
        var matchUser   = (userVal === "" || userName === userVal);
        var matchSearch = (searchVal === "" || rowText.includes(searchVal));
        trs[i].style.display = (matchUser && matchSearch) ? "" : "none";
    }
}
</script>
</body>
</html>
