<?php
// 1. INCLUDE DATABASE CONNECTION
require_once '../db.php';

// 2. FETCH ACTIVE DOCTOR PROFILE (ID: 1)
$user_data = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM `user` WHERE `user_id` = 1 LIMIT 1");
    $stmt->execute();
    $user_data = $stmt->fetch();
}

// Fallback configuration if database user profile is empty
if (!$user_data) {
    $user_data = [
        'name'        => "Dr. MD. Golam Mortuza",
        'profession'  => "Dermatology & Dermato Surgeon",
        'address'     => "Advanced Skin Care, Jessore, Bangladesh",
        'number'      => "+15550192834"
    ];
}

// 3. HANDLE NEW SAVED ENTRY COMMITS WITH DYNAMIC MATRICES
$success_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_prescription'])) {
    if ($pdo) {
        $med_names        = $_POST['med_name'] ?? [];
        $med_instructions = $_POST['med_instructions'] ?? [];
        $dosage_patterns  = $_POST['dosage_pattern'] ?? [];
        $timing_contexts  = $_POST['timing_context'] ?? [];

        $sql = "INSERT INTO `prescriptions` (
                    `patient_name`, `age`, `gender`, `date`, 
                    `med_name_1`, `med_instructions_1`, `dosage_pattern_1`, `timing_context_1`,
                    `med_name_2`, `med_instructions_2`, `dosage_pattern_2`, `timing_context_2`,
                    `med_name_3`, `med_instructions_3`, `dosage_pattern_3`, `timing_context_3`,
                    `clinical_advice`
                ) VALUES (
                    :patient_name, :age, :gender, :date, 
                    :med_name_1, :med_instructions_1, :dosage_pattern_1, :timing_context_1,
                    :med_name_2, :med_instructions_2, :dosage_pattern_2, :timing_context_2,
                    :med_name_3, :med_instructions_3, :dosage_pattern_3, :timing_context_3,
                    :clinical_advice
                )";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_name'        => $_POST['patient_name'],
            ':age'                 => $_POST['age'],
            ':gender'              => $_POST['gender'],
            ':date'                => $_POST['date'],
            ':med_name_1'          => $med_names[0] ?? null,
            ':med_instructions_1'  => $med_instructions[0] ?? null,
            ':dosage_pattern_1'    => $dosage_patterns[0] ?? null,
            ':timing_context_1'    => $timing_contexts[0] ?? null,
            ':med_name_2'          => $med_names[1] ?? null,
            ':med_instructions_2'  => $med_instructions[1] ?? null,
            ':dosage_pattern_2'    => $dosage_patterns[1] ?? null,
            ':timing_context_2'    => $timing_contexts[1] ?? null,
            ':med_name_3'          => $med_names[2] ?? null,
            ':med_instructions_3'  => $med_instructions[2] ?? null,
            ':dosage_pattern_3'    => $dosage_patterns[2] ?? null,
            ':timing_context_3'    => $timing_contexts[2] ?? null,
            ':clinical_advice'     => $_POST['clinical_advice']
        ]);
        
        $success_message = "Prescription successfully logged to clinical records database!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Medical Pad Engine</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-cream: #FDFBF7;
            --charcoal: #1D1D1F;
            --muted-gray: #6E6E73;
            --gold-accent: #C5A880;
            --gold-light: rgba(197, 168, 128, 0.10);
            --border-color: rgba(29, 29, 31, 0.08);
            --danger-red: #dc3545;
            --success-green: #28a745;
            --info-blue: #17a2b8;
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-serif: 'Cinzel', serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #EAE5DC; font-family: var(--font-main); color: var(--charcoal); padding: 30px 20px; display: flex; flex-direction: column; align-items: center; -webkit-font-smoothing: antialiased; }

        /* Action bar for screen operations */
        .system-action-bar { width: 800px; background: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .back-index-btn { text-decoration: none; color: var(--muted-gray); font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .action-cluster { display: flex; gap: 10px; }
        
        .action-btn { border: none; padding: 8px 16px; font-weight: 600; font-size: 13px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
        .btn-records { background-color: var(--info-blue); color: white; }
        .btn-records:hover { background-color: #138496; }
        .btn-save { background-color: var(--success-green); color: white; }
        .btn-save:hover { background-color: #218838; }
        
        .toast-banner { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 8px 15px; border-radius: 4px; font-size: 13px; font-weight: 500; width: 800px; margin-bottom: 12px; text-align: center; }

        form { display: block; }

        /* Pad Layout Canvas */
        .prescription-pad { background-color: var(--bg-cream); width: 800px; min-height: 1060px; height: auto; padding: 40px 50px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08); border-radius: 4px; position: relative; display: flex; flex-direction: column; }

        /* Clean Input Fields */
        input[type="text"], input[type="date"], select { background: transparent; border: none; border-bottom: 1px solid transparent; font-family: var(--font-main); color: var(--charcoal); font-size: 13.5px; width: 100%; padding: 3px 2px; outline: none; }
        input[type="text"]:focus, input[type="date"]:focus, select:focus { border-bottom: 1px solid var(--gold-accent); background: rgba(197, 168, 128, 0.03); }
        
        /* CENTERED LOGO / MEDICAL HEADER AREA */
        .centered-header { text-align: center; border-bottom: 2px solid var(--gold-accent); padding-bottom: 12px; margin-bottom: 20px; }
        .centered-header h1 { font-family: var(--font-serif); font-size: 25px; font-weight: 700; color: var(--charcoal); letter-spacing: 0.5px; line-height: 1.2; margin-bottom: 3px; }
        .centered-header .profession-subtitle { font-family: var(--font-main); font-size: 11.5px; font-weight: 600; color: #a38171; letter-spacing: 2.5px; text-transform: uppercase; margin-bottom: 5px; }
        .centered-header .address-line { font-size: 11px; color: var(--muted-gray); font-weight: 500; max-width: 600px; margin: 0 auto; line-height: 1.4; }
        .centered-header .address-line i { color: var(--gold-accent); margin-right: 3px; }

        /* Patient Matrix Meta Elements Grid */
        .patient-info-bar { display: grid; grid-template-columns: 2.4fr 1fr 1.1fr 1.5fr; gap: 15px; background: linear-gradient(to right, var(--gold-light), transparent); border: 1px solid rgba(197, 168, 128, 0.2); border-radius: 6px; padding: 10px 14px; margin-bottom: 20px; }
        .info-group { display: flex; align-items: center; gap: 6px; font-size: 13.5px; }
        .info-label { color: var(--muted-gray); font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        .info-value { border-bottom: 1px dotted rgba(0, 0, 0, 0.12) !important; font-weight: 600; }

        /* Clinical Rx Panels Area */
        .prescription-body { display: flex; flex-grow: 1; flex-direction: column; }
        .rx-symbol-header { font-family: var(--font-serif); font-size: 36px; font-weight: 700; color: var(--gold-accent); line-height: 1; margin-bottom: 10px; }

        /* Medical Sheet Matrix Tables */
        .medication-table { width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 15px; }
        .medication-table th { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted-gray); padding: 6px 4px; border-bottom: 2px solid var(--gold-accent); font-weight: 700; }
        .medication-table tr { page-break-inside: avoid; } 
        .medication-table td { padding: 8px 4px; border-bottom: 1px solid var(--border-color); font-size: 13.5px; vertical-align: top; }
        
        .input-med-name { font-weight: 700; color: var(--charcoal); font-size: 14.5px; margin-bottom: 2px; }
        .input-med-instruction { font-size: 11.5px; color: var(--muted-gray); font-style: italic; }
        .input-med-pattern { font-weight: 600; }

        /* Controls */
        .dynamic-btn-row { margin-bottom: 15px; display: flex; gap: 10px; }
        .btn-control { background: transparent; border: 1px dashed var(--gold-accent); color: var(--charcoal); padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-control:hover { background: var(--gold-light); }
        .btn-delete-row { background: transparent; border: none; color: var(--danger-red); cursor: pointer; font-size: 14px; padding-top: 4px; }

        /* AUTOGROW CLINICAL ADVICE SECTION */
        .additional-notes { background-color: rgba(0, 0, 0, 0.01); border-left: 3px solid var(--gold-accent); padding: 10px 14px; border-radius: 0 6px 6px 0; margin-top: 20px; display: flex; flex-direction: column; page-break-inside: avoid; }
        .additional-notes h4 { font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--charcoal); margin-bottom: 4px; font-weight: 700; }
        
        .additional-notes textarea { 
            background: transparent;
            border: none;
            font-family: var(--font-main); 
            color: var(--charcoal);
            font-size: 13px; 
            line-height: 1.5; 
            resize: none; 
            width: 100%;
            min-height: 45px;
            outline: none;
            overflow: hidden;
            display: block;
        }

        /* EXTERNAL PRINT BUTTON CANVAS (PLACED AFTER THE PRESCRIPTION CONTAINER) */
        .outside-prescription-print-row { width: 800px; margin-top: 20px; display: flex; justify-content: center; }
        .btn-outer-print { background-color: var(--charcoal); color: white; border: none; padding: 12px 30px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 100%; justify-content: center; }
        .btn-outer-print:hover { background-color: #000; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }

        /* Bottom Footer Components */
        .prescription-footer { margin-top: auto; padding-top: 40px; page-break-inside: avoid; }
        .signature-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 15px; padding: 0 5px; }
        .stamp-box { width: 110px; height: 60px; border: 1px dashed var(--gold-accent); border-radius: 4px; display: flex; justify-content: center; align-items: center; color: rgba(197, 168, 128, 0.5); font-size: 10px; text-transform: uppercase; font-weight: 600; }
        .signature-wrapper { text-align: center; width: 160px; }
        .signature-line { border-top: 1px solid var(--charcoal); margin-bottom: 4px; }
        .signature-title { font-size: 10.5px; font-weight: 600; color: var(--muted-gray); text-transform: uppercase; letter-spacing: 0.5px; }

        .clinic-footer-bar { border-top: 1px solid var(--border-color); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; color: var(--muted-gray); font-weight: 500; }
        .clinic-footer-bar span i { color: var(--gold-accent); margin-right: 3px; }

        /* ==================== SCREEN VS PRINT OVERRIDES ==================== */
        @media screen {
            input[type="text"], input[type="date"], .additional-notes textarea, select { border-bottom: 1px dashed rgba(197, 168, 128, 0.4); }
        }

        @media print {
            @page { size: A4 portrait; margin: 0; } 
            html, body { width: 210mm; height: 100%; background-color: #ffffff !important; margin: 0 !important; padding: 0 !important; }
            .system-action-bar, .toast-banner, .dynamic-btn-row, .action-th, .action-td, .outside-prescription-print-row { display: none !important; }
            .prescription-pad { width: 100% !important; min-height: 297mm !important; height: auto !important; box-shadow: none !important; border-radius: 0 !important; padding: 15mm 15mm !important; background-color: #ffffff !important; box-sizing: border-box !important; }
            input[type="text"], input[type="date"], textarea, select { border-bottom: none !important; background: transparent !important; padding: 0 !important; }
            input[type="text"]::placeholder, textarea::placeholder { color: transparent !important; }
            select { -webkit-appearance: none; appearance: none; }
            .patient-info-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: rgba(197, 168, 128, 0.08) !important; }
            .additional-notes { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: rgba(0, 0, 0, 0.02) !important; }
        }
    </style>
</head>
<body>

    <?php if(!empty($success_message)): ?>
        <div class="toast-banner"><i class="fa-solid fa-circle-check"></i> <?php echo $success_message; ?></div>
    <?php endif; ?>

    <div class="system-action-bar">
        <a href="index.php" class="back-index-btn"><i class="fa-solid fa-arrow-left"></i> Return to Site Front</a>
        <div class="action-cluster">
            <a href="records.php" class="action-btn btn-records">
                <i class="fa-solid fa-folder-open"></i> Previous Records
            </a>
            <button type="submit" form="prescriptionForm" name="save_prescription" class="action-btn btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Log Entry to DB
            </button>
        </div>
    </div>

    <form id="prescriptionForm" method="POST" action="pres.php">
        <div class="prescription-pad">
            
            <header class="centered-header">
                <h1><?php echo htmlspecialchars($user_data['name']); ?></h1>
                <div class="profession-subtitle"><?php echo htmlspecialchars($user_data['profession']); ?></div>
                <div class="address-line">
                    <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($user_data['address']); ?>
                </div>
            </header>

            <section class="patient-info-bar">
                <div class="info-group">
                    <span class="info-label">Patient Name:</span>
                    <input type="text" name="patient_name" class="info-value" placeholder="Enter Full Name" required>
                </div>
                <div class="info-group">
                    <span class="info-label">Age:</span>
                    <input type="text" name="age" class="info-value" placeholder="e.g. 28 Yrs" required>
                </div>
                <div class="info-group">
                    <span class="info-label">Gender:</span>
                    <select name="gender" class="info-value" style="width: auto;" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="info-group">
                    <span class="info-label">Date:</span>
                    <input type="date" name="date" class="info-value" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </section>

            <main class="prescription-body">
                <div class="rx-symbol-header">R<sub>x</sub></div>
                
                <table class="medication-table" id="medicationTable">
                    <thead>
                        <tr>
                            <th style="width: 46%;">Medication Name & Instructions</th>
                            <th style="width: 18%;">Dosage Pattern</th>
                            <th style="width: 14%;">Duration</th>
                            <th style="width: 18%; text-align: right;">Timing Context</th>
                            <th class="action-th" style="width: 4%;"></th>
                        </tr>
                    </thead>
                    <tbody id="medicationContainer">
                        <tr>
                            <td>
                                <input type="text" name="med_name[]" class="input-med-name" placeholder="Name / Strength Capsule or Tab" required>
                                <input type="text" name="med_instructions[]" class="input-med-instruction" placeholder="Special administration directives...">
                            </td>
                            <td><input type="text" name="dosage_pattern[]" class="input-med-pattern" placeholder="e.g. 1 - 0 - 1"></td>
                            <td><input type="text" name="duration[]" placeholder="e.g. 7 Days"></td>
                            <td style="text-align: right;">
                                <select name="timing_context[]" style="text-align-last: right;">
                                    <option value="After Food">After Food</option>
                                    <option value="Before Food">Before Food</option>
                                    <option value="With Meal">With Meal</option>
                                    <option value="Empty Stomach">Empty Stomach</option>
                                </select>
                            </td>
                            <td class="action-td" style="text-align: center; vertical-align: middle;">
                                <button type="button" class="btn-delete-row" onclick="removeMedRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="dynamic-btn-row">
                    <button type="button" class="btn-control" onclick="addMedRow()">
                        <i class="fa-solid fa-plus"></i> Add Medication Row
                    </button>
                </div>

                <div class="additional-notes">
                    <h4><i class="fa-solid fa-user-doctor" style="color: var(--gold-accent); margin-right: 5px;"></i> Clinical Advice & Follow-up</h4>
                    <textarea id="clinicalAdviceTextarea" name="clinical_advice" placeholder="Write advice parameters here (e.g., diet controls, skincare modifications, follow-up window timelines)..." rows="1"></textarea>
                </div>
            </main>

            <footer class="prescription-footer">
                <div class="signature-row">
                    <div class="stamp-box">Doctor Stamp</div>
                    <div class="signature-wrapper">
                        <div class="signature-line"></div>
                        <div class="signature-title">Authorized Signature</div>
                    </div>
                </div>

                <div class="clinic-footer-bar">
                    <div>&copy; <?php echo date("Y"); ?> Clinical Registry Network.</div>
                    <div>
                        <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($user_data['number']); ?></span>
                    </div>
                </div>
            </footer>

        </div> <div class="outside-prescription-print-row">
            <button type="button" class="btn-outer-print" onclick="window.print();">
                <i class="fa-solid fa-print"></i> Print Prescription Document
            </button>
        </div>
    </form>

    <script>
        function addMedRow() {
            const container = document.getElementById('medicationContainer');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="text" name="med_name[]" class="input-med-name" placeholder="Name / Strength Capsule or Tab" required>
                    <input type="text" name="med_instructions[]" class="input-med-instruction" placeholder="Special administration directives...">
                </td>
                <td><input type="text" name="dosage_pattern[]" class="input-med-pattern" placeholder="e.g. 1 - 0 - 1"></td>
                <td><input type="text" name="duration[]" placeholder="e.g. 7 Days"></td>
                <td style="text-align: right;">
                    <select name="timing_context[]" style="text-align-last: right;">
                        <option value="After Food">After Food</option>
                        <option value="Before Food">Before Food</option>
                        <option value="With Meal">With Meal</option>
                        <option value="Empty Stomach">Empty Stomach</option>
                    </select>
                </td>
                <td class="action-td" style="text-align: center; vertical-align: middle;">
                    <button type="button" class="btn-delete-row" onclick="removeMedRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                </td>
            `;
            container.appendChild(tr);
            autoGrowAdvice();
        }

        function removeMedRow(button) {
            const container = document.getElementById('medicationContainer');
            if (container.getElementsByTagName('tr').length > 1) {
                button.closest('tr').remove();
                autoGrowAdvice();
            } else {
                alert("At least one medication record input row must exist on the active sheet.");
            }
        }

        // --- AUTOGROW TEXTAREA LOGIC ENGINE ---
        const adviceTx = document.getElementById('clinicalAdviceTextarea');
        
        function autoGrowAdvice() {
            adviceTx.style.height = 'auto'; 
            adviceTx.style.height = adviceTx.scrollHeight + 'px'; 
        }

        adviceTx.addEventListener('input', autoGrowAdvice);
        window.addEventListener('load', autoGrowAdvice);
    </script>
</body>
</html>