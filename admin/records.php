<?php 
// 1. INCLUDE THE DATABASE CONNECTION
require_once '../db.php';

// ==========================================
// ACTION HANDLER: DROP PRESCRIPTION RECORD
// ==========================================
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    if ($pdo) {
        $stmt = $pdo->prepare("DELETE FROM `prescriptions` WHERE `id` = :id");
        $stmt->execute([':id' => $id]);
    }
    header("Location: records.php");
    exit();
}

// ==========================================
// DATA FETCH: ALL LOGGED PRESCRIPTIONS
// ==========================================
$prescriptions = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `prescriptions` ORDER BY `date` DESC, `id` DESC");
    $prescriptions = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Registry Network - Historical Patient Records</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-canvas: #EAE5DC;
            --panel-bg: rgba(255, 255, 255, 0.95);
            --charcoal: #1D1D1F;
            --muted-gray: #6E6E73;
            --gold-accent: #C5A880;
            --gold-light: rgba(197, 168, 128, 0.08);
            --border-color: rgba(29, 29, 31, 0.08);
            --danger-red: #ff453a;
            --success-green: #28a745;
            --info-blue: #17a2b8;
            --font-main: 'Plus Jakarta Sans', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background-color: var(--bg-canvas); 
            font-family: var(--font-main); 
            color: var(--charcoal); 
            padding: 40px 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            -webkit-font-smoothing: antialiased; 
        }

        /* Responsive Container Frame Wrapper Layout */
        .records-container { 
            width: 100%;
            max-width: 1140px; 
            background: var(--panel-bg); 
            padding: 35px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06); 
            border: 1px solid rgba(255, 255, 255, 0.5);
            overflow: hidden;
        }

        /* Top Header Grid Alignment Components */
        .records-header { 
            display: flex; 
            flex-direction: row;
            justify-content: space-between; 
            align-items: center; 
            gap: 20px;
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .records-title h2 { 
            font-size: 22px; 
            font-weight: 700; 
            color: var(--charcoal); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .records-title h2 i { color: var(--gold-accent); }
        .records-title p { 
            font-size: 13px; 
            color: var(--muted-gray); 
            margin-top: 2px; 
            font-weight: 500;
        }

        .btn-back-pad { 
            text-decoration: none; 
            background-color: var(--charcoal); 
            color: white; 
            padding: 10px 18px; 
            font-size: 13px; 
            font-weight: 600; 
            border-radius: 6px; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.2s; 
            white-space: nowrap;
        }
        .btn-back-pad:hover { background-color: #000; transform: translateY(-1px); }

        .search-engine-wrapper { 
            position: relative; 
            margin-bottom: 20px; 
        }
        .search-engine-wrapper i { 
            position: absolute; 
            left: 15px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: var(--muted-gray); 
            font-size: 14px; 
        }
        .search-input { 
            width: 100%; 
            padding: 12px 16px 12px 42px; 
            font-family: var(--font-main); 
            font-size: 14px; 
            color: var(--charcoal); 
            background: rgba(0, 0, 0, 0.02); 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            outline: none; 
            transition: all 0.2s; 
            font-weight: 500;
        }
        .search-input:focus { 
            background: white; 
            border-color: var(--gold-accent); 
            box-shadow: 0 0 0 3px var(--gold-light); 
        }

        /* Responsive Wrapper Block for Small Displays */
        .table-responsive-scroller {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .records-table { 
            width: 100%; 
            border-collapse: collapse; 
            text-align: left; 
            min-width: 850px; /* Forces dynamic scroll bar engine on tiny layout monitors */
        }
        .records-table th { 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.7px; 
            color: var(--muted-gray); 
            padding: 14px 16px; 
            border-bottom: 2px solid var(--gold-accent); 
            font-weight: 700; 
            background: rgba(0,0,0,0.01);
        }
        .records-table td { 
            padding: 16px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 13.5px; 
            vertical-align: top; 
        }
        .room-row:hover { background-color: rgba(197, 168, 128, 0.02); }

        .patient-main { font-weight: 700; color: var(--charcoal); font-size: 14px; }
        .patient-sub { font-size: 11.5px; color: var(--muted-gray); margin-top: 2px; }
        
        .med-list-block { list-style: none; }
        .med-list-item { font-size: 12.5px; font-weight: 600; color: var(--charcoal); margin-bottom: 4px; }
        .med-list-item span { font-weight: 400; color: var(--muted-gray); font-style: italic; font-size: 11.5px; }

        .clinical-advice-text { font-size: 12px; color: var(--muted-gray); line-height: 1.4; max-width: 250px; }

        .action-links { text-align: right; white-space: nowrap; }
        .action-links a { 
            text-decoration: none; 
            font-size: 12px; 
            font-weight: 700; 
            padding: 6px 12px; 
            border-radius: 4px; 
            display: inline-flex; 
            align-items: center; 
            gap: 5px; 
            transition: all 0.2s; 
        }
        .action-links a.update-link { color: var(--success-green); background: rgba(40, 167, 69, 0.08); margin-right: 5px; }
        .action-links a.update-link:hover { color: white; background: var(--success-green); }
        .action-links a.delete-link { color: var(--danger-red); background: rgba(255, 69, 58, 0.08); }
        .action-links a.delete-link:hover { color: white; background: var(--danger-red); }

        .empty-registry { text-align: center; padding: 40px 20px; color: var(--muted-gray); font-size: 14px; font-weight: 500; }
        .empty-registry i { font-size: 32px; color: var(--gold-accent); margin-bottom: 10px; display: block; }

        .sweet-glass-popup { 
            border-radius: 24px !important; 
            background: rgba(255, 255, 255, 0.96) !important; 
            backdrop-filter: blur(20px) !important; 
            border: 1px solid rgba(255,255,255,0.6) !important; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.15) !important; 
        }

        /* ==================== SCREEN DISPLAY BREAKPOINTS ==================== */
        @media (max-width: 768px) {
            body { padding: 15px 10px; }
            .records-container { padding: 20px 15px; border-radius: 10px; }
            .records-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .btn-back-pad { width: 100%; justify-content: center; }
        }

        /* ==================== PRINT ARCHITECTURE OVERRIDES ==================== */
        @media print {
            @page { size: A4 landscape; margin: 15mm; }
            body { background: #fff !important; padding: 0 !important; }
            .btn-back-pad, .search-engine-wrapper, .action-th, .action-links { display: none !important; }
            .records-container { width: 100% !important; box-shadow: none !important; padding: 0 !important; background: transparent !important; border: none !important; }
            .table-responsive-scroller { overflow: visible !important; }
            .records-table { min-width: 100% !important; }
            .records-table th { border-bottom: 2px solid #000 !important; background: transparent !important; }
            .records-table td { border-bottom: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>

    <div class="records-container">
        
        <header class="records-header">
            <div class="records-title">
                <h2><i class="fa-solid fa-folder-open"></i> Clinical Entry Registry</h2>
                <p>Review, explore, filter, and maintain historical database-logged prescriptions.</p>
            </div>
            <a href="pres.php" class="btn-back-pad">
                <i class="fa-solid fa-file-medical"></i> Open Prescription Pad
            </a>
        </header>

        <div class="search-engine-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="roomSearch" class="search-input" placeholder="Search by patient name, medicine rows, or clinical advice features...">
        </div>

        <div class="table-responsive-scroller">
            <?php if (!empty($prescriptions)): ?>
                <table class="records-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Date Logged</th>
                            <th style="width: 23%;">Patient Parameters</th>
                            <th style="width: 35%;">Medications Matrix</th>
                            <th style="width: 15%;">Clinical Advice</th>
                            <th class="action-th" style="width: 15%; text-align: right;">Operations</th>
                        </tr>
                    </thead>
                    <tbody id="recordsTableBody">
                        <?php foreach ($prescriptions as $row): ?>
                            <tr class="room-row">
                                <td>
                                    <strong><?php echo date('d M, Y', strtotime($row['date'])); ?></strong>
                                </td>
                                
                                <td>
                                    <div class="patient-main"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                                    <div class="patient-sub">Age: <?php echo htmlspecialchars($row['age']); ?> | <?php echo htmlspecialchars($row['gender']); ?></div>
                                </td>
                                
                                <td>
                                    <ul class="med-list-block">
                                        <?php for($i = 1; $i <= 3; $i++): ?>
                                            <?php if(!empty($row["med_name_$i"])): ?>
                                                <li class="med-list-item">
                                                    <i class="fa-solid fa-pills" style="color: var(--gold-accent); font-size: 11px; margin-right: 4px;"></i>
                                                    <?php echo htmlspecialchars($row["med_name_$i"]); ?>
                                                    <?php if(!empty($row["dosage_pattern_$i"]) || !empty($row["timing_context_$i"])): ?>
                                                        <span>(<?php echo htmlspecialchars($row["dosage_pattern_$i"] . ' - ' . $row["timing_context_$i"]); ?>)</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </ul>
                                </td>
                                
                                <td>
                                    <div class="clinical-advice-text">
                                        <?php 
                                            if(!empty($row['clinical_advice'])) {
                                                echo htmlspecialchars(mb_strimwidth($row['clinical_advice'], 0, 65, "..."));
                                            } else {
                                                echo '<span style="color:#bbb; font-style:italic;">None logged</span>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                
                                <td class="action-links">
                                    <a href="edit_pres.php?id=<?php echo $row['id']; ?>" class="update-link">
                                        <i class="fa-solid fa-pencil"></i> Edit
                                    </a>
                                    <a class="delete-link" onclick="triggerDelete(event, 'records.php?delete_id=<?php echo $row['id']; ?>')" href="#">
                                        <i class="fa-solid fa-trash-can"></i> Drop
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-registry">
                    <i class="fa-solid fa-box-open"></i>
                    No logged entries found inside the clinical data index records.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        document.getElementById('roomSearch').addEventListener('input', function(e) {
            const text = e.target.value.toLowerCase();
            document.querySelectorAll('.room-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(text) ? '' : 'none';
            });
        });

        function triggerDelete(event, redirectUrl) {
            event.preventDefault();
            Swal.fire({
                title: 'Drop Prescription Record?',
                text: "This operation completely removes this historical entry row from live database configurations instantly.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff453a',
                cancelButtonColor: 'rgba(0,0,0,0.06)',
                confirmButtonText: 'Confirm Removal',
                cancelButtonText: 'Cancel',
                customClass: { popup: 'sweet-glass-popup' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = redirectUrl;
                }
            });
        }
    </script>
</body>
</html>