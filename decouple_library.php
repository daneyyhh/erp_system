<?php
$roles = ['admin', 'teacher', 'student'];

foreach ($roles as $r) {
    if ($r == 'admin' || $r == 'teacher') {
        $source = file_get_contents(__DIR__ . "/$r/library.php");
        
        // Split out the components using their unique headers as delimiters
        // Seat Monitoring Map starts at <!-- Seat Monitoring Map -->
        // Issue tracking starts at <div class="row g-4">
        
        // 1. Library Books (Admin/Teacher) -> Keep topbuttons, hide seats, hide issues
        // Actually, let's just inject CSS to hide them by matching adjacent unique classes.
        $css_books = "<style> .mb-4.p-4 {display:none !important;} .row.g-4 {display:none !important;} </style>";
        $books_html = str_replace("</head>", $css_books . "\n</head>", $source);
        file_put_contents(__DIR__ . "/$r/library_books.php", $books_html);
        
        // 2. Library Seats -> Hide topbuttons, hide issues
        $css_seats = "<style> .d-flex.gap-3 {display:none !important;} .row.g-4 {display:none !important;} </style>";
        $seats_html = str_replace("</head>", $css_seats . "\n</head>", $source);
        file_put_contents(__DIR__ . "/$r/library_seats.php", $seats_html);
        
        // 3. Library Issues -> Hide topbuttons, hide seats
        $css_issues = "<style> .d-flex.gap-3 {display:none !important;} .mb-4.p-4 {display:none !important;} </style>";
        $issues_html = str_replace("</head>", $css_issues . "\n</head>", $source);
        file_put_contents(__DIR__ . "/$r/library_issues.php", $issues_html);
        
    } else { // Student
        $source = file_get_contents(__DIR__ . "/$r/library.php");
        
        // Student layout:
        // Seats: .col-lg-12
        // Issues: .col-lg-5
        // Books: .col-lg-7
        
        $css_books = "<style> .col-lg-12 {display:none !important;} .col-lg-5 {display:none !important;} .col-lg-7 {width:100% !important; flex: 0 0 100% !important;} </style>";
        $books_html = str_replace("</head>", $css_books . "\n</head>", $source);
        file_put_contents(__DIR__ . "/$r/library_books.php", $books_html);
        
        $css_seats = "<style> .col-lg-5 {display:none !important;} .col-lg-7 {display:none !important;} </style>";
        $seats_html = str_replace("</head>", $css_seats . "\n</head>", $source);
        file_put_contents(__DIR__ . "/$r/library_seats.php", $seats_html);
        
        $css_issues = "<style> .col-lg-12 {display:none !important;} .col-lg-7 {display:none !important;} .col-lg-5 {width:100% !important; flex: 0 0 100% !important;} </style>";
        $issues_html = str_replace("</head>", $css_issues . "\n</head>", $source);
        file_put_contents(__DIR__ . "/$r/library_issues.php", $issues_html);
    }
}
echo 'done';
