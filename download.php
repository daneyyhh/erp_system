<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

require_once('config/db.php');

$type = $_GET['type'] ?? 'document';
$id = $_GET['id'] ?? '0';

// Determine if student is downloading their own, or admin/teacher downloading it
$student_id = $_SESSION['user_role'] === 'student' ? $_SESSION['user_id'] : '0';

if ($type !== 'receipt' && $type !== 'transcript') {
    // If it's a specific certificate, query its real details
    $certStmt = $pdo->prepare("SELECT c.*, s.roll_no, s.class, u.name as student_name 
                              FROM certificates c 
                              JOIN students s ON c.student_id = s.id 
                              JOIN users u ON s.user_id = u.id 
                              WHERE c.id = ?");
    $certStmt->execute([$id]);
    $cert = $certStmt->fetch();

    if (!$cert) {
        die("Certificate record not found or not approved.");
    }
    $type = strtolower($cert['type']);
    $student_name = $cert['student_name'];
    $roll_no = $cert['roll_no'];
    $class_name = $cert['class'];
    $date_generated = date('d M Y', strtotime($cert['request_date'] ?? date('Y-m-d')));
} else {
    // For transcripts or receipts, just pull student data directly
    $student_id_to_fetch = $student_id;
    if ($student_id == '0') $student_id_to_fetch = $id; // Admin passing student user_id directly

    $stmt = $pdo->prepare("SELECT u.name, s.roll_no, s.class FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
    $stmt->execute([$student_id_to_fetch]);
    $student = $stmt->fetch();

    if (!$student) die("Student record not found.");
    
    $student_name = $student['name'];
    $roll_no = $student['roll_no'];
    $class_name = $student['class'];
    $date_generated = date('d M Y');
}

$ref_no = "PHC/" . date('Y') . "/" . strtoupper(substr(md5($id . $type . time()), 0, 8));
$watermark_text = "PURPLEHEART";

// -------------------------------------------------------------
// TEMPLATE LOGIC - Build unique HTML layouts per type
// -------------------------------------------------------------

$html = "";

if ($type === 'bonafide') {
    $html = "
    <div class='cert-body bonafide-theme'>
        <div class='cert-header-fancy'>
            <div class='logo-emblem'>P</div>
            <div>
                <h1 class='college-brand'>PURPLEHEART COLLEGE</h1>
                <p class='college-sub'>Excellence in Higher Education & Research</p>
                <p class='college-address'>124 University Avenue, Knowledge Park, NY 10012</p>
            </div>
        </div>
        <hr class='fancy-hr'>
        <div class='meta-row'>
            <span><b>Ref No:</b> $ref_no</span>
            <span><b>Date:</b> $date_generated</span>
        </div>
        <h2 class='cert-title-cursive'>Bonafide Certificate</h2>
        <div class='cert-content'>
            <p>This is to certify that <b>$student_name</b>, bearing the academic Roll Number <b>$roll_no</b>, is a bonafide and active student of PurpleHeart College.</p>
            <p>The student is presently enrolled in the <b>$class_name</b> program for the current academic session.</p>
            <p>During their tenure, we have found their character and conduct to be exemplary. This certificate is issued upon the student's request for official purposes.</p>
        </div>
        <div class='cert-footer'>
            <div class='sign-block'>
                <div class='signature cursive-font' style='color: #4a148c;'>J. Peterson</div>
                <div class='sign-line'></div>
                <div class='sign-role'>Head of Department</div>
            </div>
            <div class='seal-block'><div class='seal-inner'>OFFICIAL<br>COLLEGE<br>SEAL</div></div>
            <div class='sign-block'>
                <div class='signature cursive-font' style='color: #4a148c;'>A. Williams</div>
                <div class='sign-line'></div>
                <div class='sign-role'>Principal / Registrar</div>
            </div>
        </div>
    </div>";
} 
elseif ($type === 'tc') {
    $html = "
    <div class='cert-body tc-theme'>
        <div class='top-bar'></div>
        <div class='tc-header'>
            <h1>TRANSFER CERTIFICATE</h1>
            <h3>PURPLEHEART COLLEGE</h3>
        </div>
        <div class='info-grid'>
            <div class='info-box'><b>Name of Student:</b> $student_name</div>
            <div class='info-box'><b>Roll Number:</b> $roll_no</div>
            <div class='info-box'><b>Course/Class:</b> $class_name</div>
            <div class='info-box'><b>Date of Leaving:</b> $date_generated</div>
        </div>
        <div class='cert-content tc-content'>
            <p>It is certified that the above-mentioned student has cleared all institutional dues and returned all library materials. There is no pending disciplinary action against them.</p>
            <p>The college has <b>No Objection</b> to the student pursuing further studies elsewhere. We wish them success in their future endeavors.</p>
        </div>
        <div class='tc-footer'>
            <div><b>Prepared By:</b> ___________</div>
            <div><b>Checked By:</b> ___________</div>
            <div class='sign-block' style='margin-top: -30px;'>
                <div class='signature cursive-font'>A. Williams</div>
                <div class='sign-line'></div>
                <div class='sign-role'>Principal Signature</div>
            </div>
        </div>
    </div>";
}
elseif ($type === 'noc') {
    $watermark_text = "NOC";
    $html = "
    <div class='cert-body noc-theme'>
        <div class='noc-border p-5'>
            <div class='text-center mb-5'>
                <h1 style='font-family: Arial, sans-serif; letter-spacing: 5px; color: #333;'>NO OBJECTION CERTIFICATE</h1>
                <p style='color: #666; font-size: 14px; text-transform: uppercase;'>PurpleHeart College Administration</p>
                <p style='font-size: 12px;'>Date: $date_generated | Ref: $ref_no</p>
            </div>
            <div class='cert-content' style='font-size: 20px; line-height: 2.2; text-align: justify; text-justify: inter-word;'>
                TO WHOMSOEVER IT MAY CONCERN
                <br><br>
                This is to certify that Mr./Ms. <strong style='font-size: 24px;'>$student_name</strong>, registered under Roll Number <strong>$roll_no</strong> in the <strong>$class_name</strong> department, is a student of this prestigious institution.
                <br><br>
                The college administration holds no objection to the student undertaking internships, projects, or employment opportunities alongside their academic curriculum, provided it does not interfere with the mandatory attendance regulations.
            </div>
            <div style='margin-top: 80px; text-align: right;'>
                <h3 style='margin: 0; font-family: cursive; color: #444;'>Dr. Robert Vance</h3>
                <p style='margin: 5px 0 0 0; font-weight: bold;'>Dean of Students</p>
                <p style='margin: 0; font-size: 12px; color: #777;'>PurpleHeart College</p>
            </div>
        </div>
    </div>";
}
elseif ($type === 'transcript') {
    $html = "
    <div class='cert-body transcript-theme'>
        <div class='transcript-header'>
            <div class='logo-square'>PH</div>
            <div class='header-titles'>
                <h2>PURPLEHEART COLLEGE</h2>
                <h1>OFFICIAL ACADEMIC TRANSCRIPT</h1>
            </div>
        </div>
        <div class='transcript-student-info'>
            <div><b>Student Name:</b> $student_name</div>
            <div><b>Roll Number:</b> $roll_no</div>
            <div><b>Program:</b> $class_name</div>
            <div><b>Issue Date:</b> $date_generated</div>
        </div>
        <table class='transcript-table'>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Credits</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>BCA-301</td><td>Database Management Systems</td><td>4</td><td>A</td><td>Pass</td></tr>
                <tr><td>BCA-302</td><td>Web Technologies</td><td>4</td><td>A+</td><td>Pass</td></tr>
                <tr><td>BCA-303</td><td>Software Engineering</td><td>3</td><td>B+</td><td>Pass</td></tr>
                <tr><td>BCA-304</td><td>Data Structures Lab</td><td>2</td><td>O</td><td>Pass</td></tr>
            </tbody>
        </table>
        <div class='transcript-summary'>
            <b>Semester GPA:</b> 3.84 &nbsp;|&nbsp; <b>Cumulative GPA:</b> 3.79 &nbsp;|&nbsp; <b>Result:</b> FIRST CLASS WITH DISTINCTION
        </div>
        <div class='cert-footer mt-5'>
            <div class='seal-block'><div class='seal-inner' style='border-radius: 0; border: 2px solid #333;'>REGISTRAR SEAL</div></div>
            <div class='sign-block'>
                <div class='signature cursive-font'>R. G. Patel</div>
                <div class='sign-line'></div>
                <div class='sign-role'>Controller of Examinations</div>
            </div>
        </div>
    </div>";
}
else {
    // Fallback standard document
    $html = "
    <div class='cert-body standard-theme'>
        <h1 class='text-center mt-5 mb-5 uppercase' style='color: #8E24AA;'>$type Document</h1>
        <div class='cert-content text-center px-5'>
            This document is officially issued to <b>$student_name</b> (Roll No: $roll_no) from $class_name under the authorization of the administration on $date_generated.
        </div>
    </div>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo strtoupper($type); ?> - <?php echo $student_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Plus+Jakarta+Sans:wght@400;600;800&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; background: #e2e8f0; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; justify-content: center; padding: 40px; }
        
        .print-btn { position: fixed; bottom: 30px; right: 30px; background: #8e24aa; color: white; border: none; padding: 15px 30px; font-size: 16px; font-weight: 800; font-family: inherit; border-radius: 30px; cursor: pointer; box-shadow: 0 10px 20px rgba(142,36,170,0.3); transition: 0.3s; z-index: 1000; }
        .print-btn:hover { background: #6a1b9a; transform: translateY(-3px); }

        .cert-container { width: 1122px; min-height: 793px; background: white; margin: 0 auto; box-shadow: 0 20px 40px rgba(0,0,0,0.1); position: relative; overflow: hidden; page-break-after: always; box-sizing: border-box;}
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 140px; font-weight: 900; color: rgba(142,36,170,0.03); z-index: 0; pointer-events: none; text-transform: uppercase; font-family: 'Playfair Display', serif; }

        .cert-body { position: relative; z-index: 1; height: 100%; box-sizing: border-box; }
        
        /* 1. BONAFIDE THEME (Classic Premium) */
        .bonafide-theme { padding: 60px; border: 20px solid #8e24aa; outline: 3px solid #e9d5ff; outline-offset: -28px; }
        .bonafide-theme .cert-header-fancy { display: flex; align-items: center; gap: 30px; margin-bottom: 20px; }
        .bonafide-theme .logo-emblem { width: 100px; height: 100px; background: #8e24aa; border-radius: 50%; color: white; font-size: 48px; font-weight: 900; display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; border: 4px solid #e9d5ff; outline: 2px solid #8e24aa; }
        .bonafide-theme .college-brand { font-family: 'Playfair Display', serif; font-size: 46px; color: #1e1b4b; font-weight: 900; margin: 0; letter-spacing: 2px; }
        .bonafide-theme .college-sub { color: #8e24aa; font-weight: 800; text-transform: uppercase; letter-spacing: 3px; font-size: 14px; margin: 5px 0; }
        .bonafide-theme .college-address { color: #64748b; font-size: 13px; margin: 0; }
        .bonafide-theme .fancy-hr { border: 0; height: 2px; background: linear-gradient(to right, transparent, #8e24aa, transparent); margin: 30px 0; }
        .bonafide-theme .meta-row { display: flex; justify-content: space-between; font-size: 14px; color: #475569; margin-bottom: 40px; }
        .bonafide-theme .cert-title-cursive { font-family: 'Great Vibes', cursive; color: #8e24aa; font-size: 64px; text-align: center; margin: 40px 0; font-weight: 400; }
        .bonafide-theme .cert-content { font-size: 24px; color: #334155; line-height: 2.2; text-align: center; margin: 0 40px 80px; font-family: 'Playfair Display', serif; }
        
        /* 2. TC THEME (Formal Corporate) */
        .tc-theme { padding: 40px; border: 4px solid #1e293b; }
        .tc-theme .top-bar { height: 10px; background: #1e293b; width: 100%; position: absolute; top: 0; left: 0; }
        .tc-theme .tc-header { text-align: center; margin-bottom: 50px; border-bottom: 4px double #1e293b; padding-bottom: 20px; padding-top: 20px; }
        .tc-theme .tc-header h1 { font-size: 48px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: 4px; }
        .tc-theme .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; background: #f8fafc; padding: 30px; border: 1px solid #cbd5e1; }
        .tc-theme .info-box { font-size: 18px; color: #334155; border-bottom: 1px dashed #94a3b8; padding-bottom: 5px; }
        .tc-theme .tc-content { font-size: 20px; line-height: 1.8; margin-bottom: 60px; font-weight: 500; color: #1e293b; }
        .tc-theme .tc-footer { display: flex; justify-content: space-between; align-items: flex-end; font-size: 18px; }

        /* 3. NOC THEME (Minimalist Academic) */
        .noc-theme { padding: 50px; }
        .noc-theme .noc-border { border: 20px inset #e2e8f0; height: 100%; box-sizing: border-box; }

        /* 4. TRANSCRIPT THEME (Tabular Data) */
        .transcript-theme { padding: 50px; background: white; border-top: 20px solid #4f46e5; border-bottom: 20px solid #4f46e5; }
        .transcript-theme .transcript-header { display: flex; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 30px; margin-bottom: 40px; }
        .transcript-theme .logo-square { width: 80px; height: 80px; background: #4f46e5; color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 900; margin-right: 30px; }
        .transcript-theme .header-titles h1 { font-family: 'Playfair Display', serif; font-size: 36px; margin: 0; color: #1e1b4b; }
        .transcript-theme .header-titles h2 { font-size: 16px; color: #6366f1; letter-spacing: 5px; margin: 0 0 5px 0; }
        .transcript-theme .transcript-student-info { display: flex; justify-content: space-between; flex-wrap: wrap; background: #f8fafc; padding: 20px 30px; border-radius: 8px; margin-bottom: 40px; font-size: 16px; border-left: 5px solid #4f46e5; }
        .transcript-theme .transcript-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .transcript-theme .transcript-table th, .transcript-theme .transcript-table td { border: 1px solid #e2e8f0; padding: 15px 20px; text-align: left; }
        .transcript-theme .transcript-table th { background: #f1f5f9; font-weight: 800; color: #334155; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; }
        .transcript-theme .transcript-table td { font-size: 16px; font-weight: 600; color: #1e293b; }
        .transcript-theme .transcript-summary { background: #e0e7ff; padding: 20px; text-align: center; color: #3730a3; border-radius: 8px; font-size: 18px; }

        /* SHARED FOOTER ELEMENTS */
        .cert-footer { display: flex; justify-content: space-between; align-items: flex-end; padding: 0 20px; margin-top: auto; }
        .sign-block { text-align: center; width: 250px; }
        .signature.cursive-font { font-family: 'Great Vibes', cursive; font-size: 42px; display: inline-block; padding: 0 20px; transform: rotate(-5deg); }
        .sign-line { border-top: 2px solid #64748b; margin-top: -10px; margin-bottom: 8px; }
        .sign-role { font-size: 14px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .seal-block { width: 120px; height: 120px; border-radius: 50%; border: 3px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; }
        .seal-inner { width: 100px; height: 100px; border-radius: 50%; background: rgba(142,36,170,0.05); border: 2px solid #8e24aa; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 11px; font-weight: 800; color: #8e24aa; letter-spacing: 1px; line-height: 1.4; }

        @media print {
            body { padding: 0; background: white; margin: 0; }
            .print-btn { display: none; }
            .cert-container { width: 100%; height: 100%; box-shadow: none; border: none !important; }
            @page { size: A4 landscape; margin: 0; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print me-2"></i> Print Official Document</button>
    <div class="cert-container">
        <div class="watermark"><?php echo $watermark_text; ?></div>
        <?php echo $html; ?>
    </div>
</body>
</html>
