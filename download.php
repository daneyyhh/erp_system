<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$type = $_GET['type'] ?? 'document';
$id = $_GET['id'] ?? '0';

// Simple mockup download manager
// In a production environment, this would generate a real PDF using libraries like FPDF or Dompdf
// and pull real data from the database.

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $type . '_' . $id . '.pdf"');

// Create a simple text-based PDF content (minimalist approach for demonstration)
echo "%PDF-1.4\n";
echo "1 0 obj < < /Type /Catalog /Pages 2 0 R > > endobj\n";
echo "2 0 obj < < /Type /Pages /Kids [3 0 R] /Count 1 > > endobj\n";
echo "3 0 obj < < /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources < < /Font < < /F1 5 0 R > > > > > > endobj\n";
echo "4 0 obj < < /Length 130 > > stream\n";
echo "BT /F1 24 Tf 100 700 Td (PROJECT ERP SYSTEM) Tj\n";
echo "0 -40 Td (Official " . ucfirst($type) . " Record) Tj\n";
echo "0 -40 Td (ID: " . $id . ") Tj\n";
echo "0 -40 Td (Date: " . date('d M Y') . ") Tj\n";
echo "0 -40 Td (This is an electronically generated document.) Tj ET\n";
echo "endstream endobj\n";
echo "5 0 obj < < /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold > > endobj\n";
echo "xref\n0 6\n0000000000 65535 f\n0000000018 00000 n\n0000000077 00000 n\n0000000178 00000 n\n0000000457 00000 n\n0000000637 00000 n\ntrailer < < /Size 6 /Root 1 0 R > >\nstartxref\n726\n%%EOF";
exit();
?>
