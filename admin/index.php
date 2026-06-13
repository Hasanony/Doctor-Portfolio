<?php

session_start();
// Redirect to login if not authenticated
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// 1. INCLUDE THE DATABASE CONNECTION
require_once '../db.php';

$status_message = "";
$status_type = "";
$scroll_to_section = ""; // Dynamic tracking variable to maintain viewport position after submit

// Start a session to safely pass status message blocks across local PRG engine redirects
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Read and purge flash session attributes if they exist
if (isset($_SESSION['flash_status_message'])) {
    $status_message = $_SESSION['flash_status_message'];
    $status_type = $_SESSION['flash_status_type'];
    $scroll_to_section = $_SESSION['flash_scroll_to_section'];
    unset($_SESSION['flash_status_message'], $_SESSION['flash_status_type'], $_SESSION['flash_scroll_to_section']);
}

// 2. DATA PROCESSING ENGINE: SAVE CONFIGURATION TO THE 'user' AND 'about' TABLES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin_panel'])) {
    // --- USER TABLE FIELD EXTRACTION ---
    $admin_name        = trim($_POST['user_name']);
    $admin_profession  = trim($_POST['user_profession']);
    $admin_number      = trim($_POST['user_number']);
    $admin_description = trim($_POST['user_description']); 
    
    // Default or existing image path fallback tracking
    $image_path = isset($_POST['existing_image']) ? $_POST['existing_image'] : 'img/so.png';

    // --- ABOUT TABLE FIELD EXTRACTION ---
    $about_badge       = trim($_POST['about_badge']);
    $about_name        = trim($_POST['about_name']);
    $about_description = trim($_POST['about_description']);

    $scroll_to_section = "about-settings-section";

    // Handle Profile Image Upload
    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['user_image']['tmp_name'];
        $file_name = basename($_FILES['user_image']['name']);
        
        if (!is_dir('img')) {
            mkdir('img', 0755, true);
        }
        
        $target_destination = 'img/' . time() . '_' . $file_name;
        if (move_uploaded_file($file_tmp, $target_destination)) {
            $image_path = $target_destination;
        } else {
            $status_message = "Failed to upload image file to server.";
            $status_type = "error";
        }
    }

    if ($pdo && empty($status_message)) {
        try {
            $pdo->beginTransaction();

            // --- A. PERSIST USER CONFIGURATIONS ---
            $check_stmt = $pdo->query("SELECT COUNT(*) FROM `user`");
            $user_count = $check_stmt->fetchColumn();

            if ($user_count > 0) {
                $update_sql = "UPDATE `user` SET `name` = ?, `profession` = ?, `number` = ?, `image` = ?, `description` = ? LIMIT 1";
                $stmt = $pdo->prepare($update_sql);
                $stmt->execute([$admin_name, $admin_profession, $admin_number, $image_path, $admin_description]);
            } else {
                $insert_sql = "INSERT INTO `user` (`name`, `profession`, `email`, `number`, `image`, `address`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($insert_sql);
                $stmt->execute([$admin_name, $admin_profession, 'doctor.admin@localhost.com', $admin_number, $image_path, 'Jessore', $admin_description]);
            }

            // --- B. PERSIST ABOUT SECTION CONFIGURATIONS ---
            $check_about = $pdo->query("SELECT COUNT(*) FROM `about`");
            $about_count = $check_about->fetchColumn();

            if ($about_count > 0) {
                $update_about_sql = "UPDATE `about` SET `badge_des` = ?, `name` = ?, `description` = ? LIMIT 1";
                $stmt_about = $pdo->prepare($update_about_sql);
                $stmt_about->execute([$about_badge, $about_name, $about_description]);
            } else {
                $insert_about_sql = "INSERT INTO `about` (`badge_des`, `name`, `description`) VALUES (?, ?, ?)";
                $stmt_about = $pdo->prepare($insert_about_sql);
                $stmt_about->execute([$about_badge, $about_name, $about_description]);
            }

            $pdo->commit();
            
            $_SESSION['flash_status_message'] = "All core configurations saved successfully!";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $status_message = "Database Synchronization Error: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    }
}

// 2.1 PROCESS THERAPIES FORM SUBMISSION (ADD INTERACTION ROUTE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_therapy_action'])) {
    $therapy_name = trim($_POST['therapy_name']);
    $therapy_desc = trim($_POST['therapy_description']);
    $scroll_to_section = "therapies-settings-section";

    if ($pdo && !empty($therapy_name)) {
        try {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM `therapies`");
            $total_therapies = $count_stmt->fetchColumn();

            if ($total_therapies >= 6) {
                $_SESSION['flash_status_message'] = "Maximum allowance restriction reached. You can only save up to 6 signature therapies.";
                $_SESSION['flash_status_type'] = "error";
            } else {
                $insert_therapy_sql = "INSERT INTO `therapies` (`name`, `description`) VALUES (?, ?)";
                $stmt_th = $pdo->prepare($insert_therapy_sql);
                $stmt_th->execute([$therapy_name, $therapy_desc]);
                $_SESSION['flash_status_message'] = "Signature Therapy card added successfully!";
                $_SESSION['flash_status_type'] = "success";
            }
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error writing therapy row: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    }
}

// 2.2 PROCESS THERAPIES PURGE ACTION (DELETE INTERACTION ROUTE)
if (isset($_GET['delete_therapy'])) {
    $delete_id = (int)$_GET['delete_therapy'];
    $scroll_to_section = "therapies-settings-section";

    if ($pdo && $delete_id > 0) {
        try {
            $delete_sql = "DELETE FROM `therapies` WHERE `therapies_id` = ?";
            $stmt_del = $pdo->prepare($delete_sql);
            $stmt_del->execute([$delete_id]);
            
            $_SESSION['flash_status_message'] = "Therapy record permanently deleted from dashboard engine.";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error dropping therapy entry: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    }
}

// 2.3 PROCESS CLINICAL RESULTS SUBMISSION ENGINE (BEFORE/AFTER UPLOADS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_result_action'])) {
    $result_name = trim($_POST['result_name']);
    $result_desc = trim($_POST['result_description']);
    $scroll_to_section = "results-settings-section";

    $bef_image_path = "";
    $after_image_path = "";
    $upload_errors = false;

    if (!is_dir('img')) {
        mkdir('img', 0755, true);
    }

    // Process Before Photo Asset
    if (isset($_FILES['bef_img']) && $_FILES['bef_img']['error'] === UPLOAD_ERR_OK) {
        $bef_dest = 'img/bef_' . time() . '_' . basename($_FILES['bef_img']['name']);
        if (move_uploaded_file($_FILES['bef_img']['tmp_name'], $bef_dest)) {
            $bef_image_path = $bef_dest;
        } else {
            $upload_errors = true;
            $status_message = "Failed to store Before image on file system workspace.";
        }
    } else {
        $upload_errors = true;
        $status_message = "Please upload a valid Before transformation file image.";
    }

    // Process After Photo Asset
    if (!$upload_errors && isset($_FILES['after_img']) && $_FILES['after_img']['error'] === UPLOAD_ERR_OK) {
        $after_dest = 'img/aft_' . time() . '_' . basename($_FILES['after_img']['name']);
        if (move_uploaded_file($_FILES['after_img']['tmp_name'], $after_dest)) {
            $after_image_path = $after_dest;
        } else {
            $upload_errors = true;
            $status_message = "Failed to store After image on file system workspace.";
        }
    } else {
        if(!$upload_errors) {
            $upload_errors = true;
            $status_message = "Please upload a valid After transformation file image.";
        }
    }

    if ($pdo && !$upload_errors && !empty($result_name)) {
        try {
            $insert_result_sql = "INSERT INTO `results` (`bef_img`, `after_img`, `name`, `description`) VALUES (?, ?, ?, ?)";
            $stmt_res = $pdo->prepare($insert_result_sql);
            $stmt_res->execute([$bef_image_path, $after_image_path, $result_name, $result_desc]);
            
            $_SESSION['flash_status_message'] = "Clinical transformation case study published successfully!";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error creating results data profile: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    } else {
        if(empty($status_type)) { $status_type = "error"; }
    }
}

// 2.4 PROCESS CLINICAL RESULTS PURGE (DELETE INTERACTION ROUTE)
if (isset($_GET['delete_result'])) {
    $delete_res_id = (int)$_GET['delete_result'];
    $scroll_to_section = "results-settings-section";

    if ($pdo && $delete_res_id > 0) {
        try {
            // Fetch asset paths first to clear memory from local filesystem
            $find_stmt = $pdo->prepare("SELECT `bef_img`, `after_img` FROM `results` WHERE `result_id` = ?");
            $find_stmt->execute([$delete_res_id]);
            $images = $find_stmt->fetch();

            if ($images) {
                if (!empty($images['bef_img']) && file_exists($images['bef_img'])) { @unlink($images['bef_img']); }
                if (!empty($images['after_img']) && file_exists($images['after_img'])) { @unlink($images['after_img']); }
            }

            $delete_sql = "DELETE FROM `results` WHERE `result_id` = ?";
            $stmt_del = $pdo->prepare($delete_sql);
            $stmt_del->execute([$delete_res_id]);
            
            $_SESSION['flash_status_message'] = "Case study entry record dropped cleanly from system.";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error dropping results record line: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    }
}

// 2.5 PROCESS AWARDS & CERTIFICATES SUBMISSION ENGINE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_award_action'])) {
    $award_badge = trim($_POST['award_badge']);
    $award_name  = trim($_POST['award_name']);
    $scroll_to_section = "awards-settings-section";

    $award_image_path = "";
    $upload_errors = false;

    if (!is_dir('img')) {
        mkdir('img', 0755, true);
    }

    if (isset($_FILES['award_img']) && $_FILES['award_img']['error'] === UPLOAD_ERR_OK) {
        $award_dest = 'img/awd_' . time() . '_' . basename($_FILES['award_img']['name']);
        if (move_uploaded_file($_FILES['award_img']['tmp_name'], $award_dest)) {
            $award_image_path = $award_dest;
        } else {
            $upload_errors = true;
            $status_message = "Failed to store certificate asset on the local file workspace.";
        }
    } else {
        $upload_errors = true;
        $status_message = "Please upload a valid image file representing the credential award document.";
    }

    if ($pdo && !$upload_errors && !empty($award_name)) {
        try {
            $insert_award_sql = "INSERT INTO `awards` (`badge`, `name`, `image_path`) VALUES (?, ?, ?)";
            $stmt_awd = $pdo->prepare($insert_award_sql);
            $stmt_awd->execute([$award_badge, $award_name, $award_image_path]);
            
            $_SESSION['flash_status_message'] = "New portfolio award credential registered successfully!";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error writing award row data: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    } else {
        if(empty($status_type)) { $status_type = "error"; }
    }
}

// 2.6 PROCESS AWARDS REJECTION PURGE ACTION (DELETE INTERACTION ROUTE)
if (isset($_GET['delete_award'])) {
    $delete_awd_id = (int)$_GET['delete_award'];
    $scroll_to_section = "awards-settings-section";

    if ($pdo && $delete_awd_id > 0) {
        try {
            $find_awd_stmt = $pdo->prepare("SELECT `image_path` FROM `awards` WHERE `award_id` = ?");
            $find_awd_stmt->execute([$delete_awd_id]);
            $award_asset = $find_awd_stmt->fetch();

            if ($award_asset && !empty($award_asset['image_path']) && file_exists($award_asset['image_path'])) {
                @unlink($award_asset['image_path']);
            }

            $delete_awd_sql = "DELETE FROM `awards` WHERE `award_id` = ?";
            $stmt_awd_del = $pdo->prepare($delete_awd_sql);
            $stmt_awd_del->execute([$delete_awd_id]);
            
            $_SESSION['flash_status_message'] = "Award credential removed cleanly from system cache logs.";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error dropping award tracking row index: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    }
}

// ==========================================================================
// 2.7 PROCESS CLINICAL AVAILABILITY SETTINGS ENGINE (AVAILABILITY TABLE)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability_action'])) {
    $start_day     = trim($_POST['start_day']);
    $end_day       = trim($_POST['end_day']);
    $rest_days     = !empty($_POST['rest_days']) ? trim($_POST['rest_days']) : null;
    $start_time    = trim($_POST['start_time']);
    $end_time      = trim($_POST['end_time']);
    $timezone      = trim($_POST['timezone']);
    
    $scroll_to_section = "availability-settings-section";

    if ($pdo) {
        try {
            // Check if availability row already exists for user_id = 1
            $check_avail = $pdo->prepare("SELECT COUNT(*) FROM `availability` WHERE `user_id` = 1");
            $check_avail->execute();
            $avail_count = $check_avail->fetchColumn();

            if ($avail_count > 0) {
                $update_avail_sql = "UPDATE `availability` SET 
                    `start_day` = ?, `end_day` = ?, `rest_days` = ?, 
                    `start_time` = ?, `end_time` = ?, `timezone` = ? 
                    WHERE `user_id` = 1";
                $stmt_av = $pdo->prepare($update_avail_sql);
                $stmt_av->execute([$start_day, $end_day, $rest_days, $start_time, $end_time, $timezone]);
            } else {
                $insert_avail_sql = "INSERT INTO `availability` 
                    (`user_id`, `start_day`, `end_day`, `rest_days`, `start_time`, `end_time`, `timezone`) 
                    VALUES (1, ?, ?, ?, ?, ?, ?)";
                $stmt_av = $pdo->prepare($insert_avail_sql);
                $stmt_av->execute([$start_day, $end_day, $rest_days, $start_time, $end_time, $timezone]);
            }

            $_SESSION['flash_status_message'] = "Clinical office hours schedule map configured successfully!";
            $_SESSION['flash_status_type'] = "success";
            $_SESSION['flash_scroll_to_section'] = $scroll_to_section;
            
            header("Location: ./");
            exit;
        } catch (PDOException $e) {
            $status_message = "Error writing availability profile data: " . htmlspecialchars($e->getMessage());
            $status_type = "error";
        }
    }
}

// 3. RETRIEVE PERSISTED ROWS FOR LIVE PRE-FILLING
$user_profile = null;
$about_profile = null;
$therapies_list = [];
$results_list = [];
$awards_list = [];
$availability_profile = null;

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM `user` LIMIT 1");
    $user_profile = $stmt->fetch();

    $stmt_about = $pdo->query("SELECT * FROM `about` LIMIT 1");
    $about_profile = $stmt_about->fetch();

    $stmt_therapies = $pdo->query("SELECT * FROM `therapies` ORDER BY `therapies_id` ASC");
    $therapies_list = $stmt_therapies->fetchAll();

    $stmt_results = $pdo->query("SELECT * FROM `results` ORDER BY `result_id` ASC");
    $results_list = $stmt_results->fetchAll();

    $stmt_awards = $pdo->query("SELECT * FROM `awards` ORDER BY `award_id` ASC");
    $awards_list = $stmt_awards->fetchAll();

    $stmt_avail = $pdo->query("SELECT * FROM `availability` WHERE `user_id` = 1 LIMIT 1");
    $availability_profile = $stmt_avail->fetch();
}

$current_name        = !empty($user_profile['name']) ? $user_profile['name'] : "Dr. Samir Patel";
$current_profession  = !empty($user_profile['profession']) ? $user_profile['profession'] : "Premium Dermatology";
$current_number      = !empty($user_profile['number']) ? $user_profile['number'] : "+1 (555) 019-2834";
$current_image       = !empty($user_profile['image']) ? $user_profile['image'] : "img/so.png";
$current_description = !empty($user_profile['description']) ? $user_profile['description'] : "Leading skin specialist in Jessore. Science-backed, artful transformations for your unique anatomy.";

$current_about_badge = !empty($about_profile['badge_des']) ? $about_profile['badge_des'] : "Jessore's Trusted Dermatologist";
$current_about_name  = !empty($about_profile['name']) ? $about_profile['name'] : "Advanced Skin Care in Jessore";
$current_about_des   = !empty($about_profile['description']) ? $about_profile['description'] : "Dr. Samir Patel offers elite clinical dermatology in Jessore. We provide specialized care for acne, pigmentation, and structural rejuvenation using advanced medical procedures.";

// Map availability values fallbacks
$current_start_day   = !empty($availability_profile['start_day']) ? $availability_profile['start_day'] : "Monday";
$current_end_day     = !empty($availability_profile['end_day']) ? $availability_profile['end_day'] : "Friday";
$current_rest_days   = !empty($availability_profile['rest_days']) ? $availability_profile['rest_days'] : "Saturday, Sunday";
$current_start_time  = !empty($availability_profile['start_time']) ? $availability_profile['start_time'] : "09:00:00";
$current_end_time    = !empty($availability_profile['end_time']) ? $availability_profile['end_time'] : "18:00:00";
$current_timezone    = !empty($availability_profile['timezone']) ? $availability_profile['timezone'] : "EST";

// Convert time strings to standard H:i to support basic browser UI element matching properties cleanly
$current_start_time  = date("H:i", strtotime($current_start_time));
$current_end_time    = date("H:i", strtotime($current_end_time));

$name_parts = explode(' ', $current_name);
$display_last_name = (count($name_parts) > 1) ? end($name_parts) : $current_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title> Dr. MD. Golam Mortuza | Premium Dermatology & Aesthetic Services</title>
    <meta name="description" content="Expert dermatological care by Dr. Samir Patel. Specializing in advanced laser resurfacing, acne scar revision, and non-surgical contouring in a premium clinical setting. Secure administration portal for Dr. MD. Golam Mortuza, the best skin doctor in Jessore. Specialist Dermatologist & Dermato Surgeon.Book your consultation today.">
    <meta name="keywords" content="Dr. MD. Golam Mortuza, Best skin doctor in Jessore, Dermatologist Jessore, Dermato Surgeon, Skin specialist in Jessore">
   
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($user_profile['image']); ?>" alt="<?php echo htmlspecialchars($user_data['name']); ?>" class="doctor-profile-img">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-light: #ffffff;
            --card-bg: rgba(248, 246, 240, 0.4); 
            --card-border: rgba(0, 0, 0, 0.05);
            --text-main: #1d1d1f; 
            --text-muted: #6e6e73; 
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --success-green: #24b47e;
            --error-red: #ff3b30;
            --brand-gold: #a38171;
        }

        html { scroll-behavior: smooth; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: var(--bg-light);
            color: var(--text-main);
            font-family: var(--font);
            -webkit-font-smoothing: antialiased;
            padding-bottom: 120px;
        }

        /* Responsive Navigation Bar Architecture */
        .glass-nav {
            position: fixed;
            top: 0; width: 100%; z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
        }
        
        .nav-container {
            max-width: 1200px; margin: 0 auto; padding: 12px 24px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 15px;
        }

        .header-left-admin-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        
        .logo-static {
            color: var(--text-main); font-weight: 700; font-size: 1.15rem; letter-spacing: -0.5px;
            white-space: nowrap; text-decoration: none;
        }
        .logo-static span { color: var(--text-muted); font-weight: 300; }

        .divider-line { width: 1px; height: 24px; background: rgba(0,0,0,0.1); margin: 0 4px; }

        .header-inline-input {
            background: rgba(255, 255, 255, 0.9); border: 1px dashed rgba(0, 0, 0, 0.15);
            border-radius: 8px; padding: 8px 12px; font-family: var(--font); font-size: 0.85rem;
            color: var(--text-main); outline: none; transition: all 0.3s ease;
        }
        .header-inline-input:focus { border-color: #a38171; background: #ffffff; border-style: solid; }
        .header-inline-input.bold-name { font-weight: 600; width: 150px; }
        .header-inline-input.prof-des { color: var(--text-muted); width: 160px; }

        .header-right-admin-group { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        
        .header-number-input {
            background: rgba(255, 255, 255, 0.9); border: 1px dashed rgba(0, 0, 0, 0.15);
            border-radius: 8px; padding: 8px 12px; font-family: var(--font); font-size: 0.85rem;
            text-align: right; width: 150px; outline: none; color: var(--text-main);
        }
        .header-number-input:focus { border-color: #a38171; border-style: solid; }

        .btn-about-jump {
            background: transparent; color: var(--text-muted); border: 1px solid var(--card-border);
            padding: 8px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 500;
            text-decoration: none; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;
        }
        .btn-about-jump:hover { color: #a38171; background: rgba(163, 129, 113, 0.05); border-color: rgba(163, 129, 113, 0.2); }

        .btn-call-preview {
            background: var(--text-main); color: var(--bg-light); padding: 10px 22px;
            border-radius: 30px; font-size: 0.85rem; font-weight: 500; text-decoration: none; white-space: nowrap;
        }

        .nav-prescription-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(163, 129, 113, 0.1); 
            color: #8e6553; 
            padding: 9px 18px; 
            border-radius: 14px; 
            font-size: 14px; 
            font-weight: 600;
            text-decoration: none; 
            border: 1px solid rgba(163, 129, 113, 0.2);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .nav-prescription-btn:hover {
            background: #a38171;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(163, 129, 113, 0.2);
        }

        /* Main Canvas Layout Rules with Responsive Top Offset */
        .main-content { max-width: 1140px; margin: 160px auto 0 auto; padding: 0 24px; }
        
        .section-label {
            font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;
            color: #a38171; margin-bottom: 12px; margin-left: 4px; display: block; margin-top: 50px;
        }

        /* Upgraded Responsive Card Containers Using CSS Grid fractions */
        .hero-card-layout { 
            display: grid; grid-template-columns: 1.1fr 0.9fr; align-items: center; gap: 40px;
            padding: 60px 50px; background: var(--card-bg); border: 1px solid var(--card-border);
            backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px); border-radius: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.03); margin-bottom: 20px; scroll-margin-top: 180px; 
        }

        .hero-text-details-pane { width: 100%; min-width: 0; }

        /* Responsive Text Inputs */
        .hero-input-large {
            width: 100%; font-size: clamp(1.8rem, 4vw, 2.5rem); font-weight: 700; font-family: var(--font);
            letter-spacing: -1.5px; border: 1px dashed rgba(0,0,0,0.15); background: rgba(255,255,255,0.6);
            border-radius: 12px; padding: 5px 12px; color: var(--text-main); margin-bottom: 8px; outline: none;
        }
        
        .hero-input-sub {
            width: 100%; font-size: clamp(1.1rem, 2.5vw, 1.8rem); font-weight: 600; font-family: var(--font);
            letter-spacing: -0.5px; border: 1px dashed rgba(0,0,0,0.15); background: rgba(255,255,255,0.6);
            border-radius: 12px; padding: 5px 12px; color: #a38171; outline: none;
        }

        .hero-description-textarea {
            width: 100%; height: 85px; font-size: 1.05rem; color: var(--text-muted); font-family: var(--font);
            line-height: 1.6; border: 1px dashed rgba(0,0,0,0.15); background: rgba(255,255,255,0.6);
            border-radius: 12px; padding: 8px 12px; margin-top: 15px; margin-bottom: 20px; resize: none;
            outline: none; transition: border-color 0.3s;
        }
        .hero-description-textarea:focus { border-color: #a38171; background: #ffffff; border-style: solid; }

        .about-badge-input {
            display: inline-block; background: rgba(163, 129, 113, 0.08); border: 1px dashed rgba(142, 101, 83, 0.35);
            color: #8e6553; padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px; outline: none; width: auto; max-width: 100%; margin-bottom: 14px;
        }
        .about-badge-input:focus { border-style: solid; background: #ffffff; }

        .mock-stats-display-box {
            background: rgba(0,0,0,0.01); border: 1px dashed var(--card-border); border-radius: 24px;
            padding: 45px 20px; text-align: center; opacity: 0.6; pointer-events: none; width: 100%;
        }
        .mock-stats-display-box h3 { font-size: 2.2rem; color: #a38171; font-weight: 700; margin-bottom: 4px; }
        .mock-stats-display-box p { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .cta-placeholder-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-mock-style { padding: 10px 24px; border-radius: 20px; font-size: 0.85rem; border: 1px solid #ccc; background: transparent; white-space: nowrap; text-decoration: none; text-align: center; color: var(--text-main); }
        .btn-live-link { transition: all 0.2s ease; cursor: pointer; }
        .btn-live-link:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

        /* Profile Image Layout Box with File Picker Mask */
        .profile-upload-wrapper { position: relative; display: flex; justify-content: center; align-items: center; width: 100%; }
        .doctor-avatar-preview { width: 100%; max-width: 360px; height: auto; display: block; object-fit: contain; filter: drop-shadow(0px 10px 25px rgba(0, 0, 0, 0.06)); border-radius: 24px; }

        .upload-interaction-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px); border-radius: 24px; display: flex; flex-direction: column; justify-content: center;
            align-items: center; opacity: 0; transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            border: 2px dashed rgba(163, 129, 113, 0.3); cursor: pointer; padding: 20px; text-align: center;
        }
        .profile-upload-wrapper:hover .upload-interaction-overlay { opacity: 1; }
        .icon-symbol { font-size: 2.2rem; margin-bottom: 8px; }
        .action-lbl-text { font-size: 0.85rem; font-weight: 600; color: #a38171; text-transform: uppercase; letter-spacing: 0.5px; }

        /* THERAPIES GRID LAYOUT CONFIGURATION */
        .therapies-admin-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 30px; }
        
        .therapy-admin-card {
            background: #ffffff; border: 1px solid var(--card-border); border-radius: 20px;
            padding: 28px; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.01); transition: transform 0.2s, box-shadow 0.2s;
        }
        .therapy-admin-card:hover { transform: translateY(-4px); box-shadow: 0 12px 35px rgba(0,0,0,0.03); }
        .therapy-card-icon { font-size: 1.5rem; margin-bottom: 15px; display: inline-block; }
        .therapy-card-title { font-size: 1.15rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px; }
        .therapy-card-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 40px; }

        .btn-drop-therapy {
            position: absolute; bottom: 20px; left: 28px; font-size: 0.8rem; color: var(--error-red);
            text-decoration: none; font-weight: 500; background: rgba(255, 59, 48, 0.05); padding: 5px 12px; border-radius: 12px;
        }
        .btn-drop-therapy:hover { background: rgba(255, 59, 48, 0.12); }
        .therapy-count-badge { float: right; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; background: rgba(0,0,0,0.04); padding: 2px 10px; border-radius: 10px; text-transform: none; letter-spacing: 0; }
        .disabled-form-mask { opacity: 0.5; pointer-events: none; }

        /* DYNAMIC TWO-COLUMN GRID SYSTEM FOR CLINICAL RESULTS (BEFORE / AFTER) */
        .results-admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-bottom: 30px; }
        
        .result-admin-card {
            background: #ffffff; border: 1px solid var(--card-border); border-radius: 24px;
            padding: 30px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.01);
            position: relative;
        }
        
        .result-images-split-preview {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: rgba(0,0,0,0.02);
            padding: 10px; border-radius: 16px; border: 1px solid var(--card-border);
        }
        
        .result-preview-box { text-align: center; position: relative; }
        
        .result-img-tag-badge {
            position: absolute; top: 8px; left: 8px; background: rgba(29, 29, 31, 0.75);
            color: #fff; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
            padding: 3px 8px; border-radius: 8px; backdrop-filter: blur(4px);
        }
        
        .result-preview-box img {
            width: 100%; height: 160px; object-fit: cover; border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04); display: block;
        }
        
        .result-card-badge {
            display: inline-block; align-self: flex-start; background: rgba(163, 129, 113, 0.08);
            color: #a38171; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 12px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .dual-file-uploader-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        
        .file-upload-block-btn {
            border: 1px dashed rgba(0,0,0,0.15); background: rgba(255,255,255,0.6);
            border-radius: 12px; padding: 15px; text-align: center; cursor: pointer; transition: background 0.2s;
        }
        .file-upload-block-btn:hover { background: rgba(163, 129, 113, 0.04); border-color: #a38171; }
        .file-upload-block-btn input { display: none; }
        .file-upload-block-btn span { font-size: 0.8rem; font-weight: 500; color: var(--text-muted); display: block; margin-top: 4px; }

        /* Save & Status Base Structural Layers */
        .bottom-action-container { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        
        .master-save-btn {
            background: #1d1d1f; color: #ffffff; border: none; padding: 15px 50px; border-radius: 30px;
            font-family: var(--font); font-size: 0.95rem; font-weight: 600; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); width: auto;
        }
        .master-save-btn:hover { background: #000000; transform: translateY(-2px); }

        .status-pill { padding: 10px 20px; border-radius: 12px; font-size: 0.9rem; font-weight: 500; }
        .status-pill.success { background: rgba(36, 180, 126, 0.1); color: var(--success-green); }
        .status-pill.error { background: rgba(255, 59, 48, 0.1); color: var(--error-red); }

        #user_image { display: none; }

        /* RESPONSIVENESS ENGINE STAGE BREAKPOINTS */
        @media (max-width: 991px) {
            .hero-card-layout { grid-template-columns: 1fr; gap: 30px; padding: 40px 30px; }
            .therapies-admin-grid { grid-template-columns: repeat(2, 1fr); }
            .results-admin-grid { grid-template-columns: 1fr; }
            .main-content { margin-top: 210px; }
            .doctor-avatar-preview { max-width: 280px; }
        }

        @media (max-width: 680px) {
            .glass-nav { position: relative; }
            .main-content { margin-top: 30px; padding: 0 16px; }
            .nav-container { flex-direction: column; align-items: flex-start; gap: 12px; }
            .header-right-admin-group { width: 100%; justify-content: space-between; }
            .header-inline-input.bold-name, .header-inline-input.prof-des, .header-number-input { width: 100%; }
            .divider-line { display: none; }
            .hero-card-layout { padding: 30px 20px; border-radius: 20px; }
            .therapies-admin-grid { grid-template-columns: 1fr; }
            .dual-file-uploader-row { grid-template-columns: 1fr; gap: 12px; }
            .bottom-action-container { flex-direction: column; align-items: stretch; }
            .master-save-btn { width: 100%; text-align: center; }
        }
        .nav-logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 59, 48, 0.08);
            color: #ff3b30;
            border-radius: 14px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(255, 59, 48, 0.15);
            transition: all 0.3s ease;
        }

        .nav-logout-btn:hover {
            background: #ff3b30;
            color: #ffffff;
            box-shadow: 0 5px 15px rgba(255, 59, 48, 0.2);
        }
    </style>
</head>
<body>

    <form action="./" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($current_image); ?>">

        <header class="glass-nav">
            <div class="nav-container">
                
                <div class="header-left-admin-group">
                    <a href="#" class="logo-static" id="live-top-left-logo">
                        <span id="logo-name-span"><?php echo htmlspecialchars($display_last_name); ?></span><span id="logo-prof-span">. <?php echo htmlspecialchars(strtolower(explode(' ', $current_profession)[0])); ?></span>
                    </a>
                    <div class="divider-line"></div>
                    <input type="text" name="user_name" id="header-name-field" class="header-inline-input bold-name" value="<?php echo htmlspecialchars($current_name); ?>" placeholder="Add Name..." required>
                    <input type="text" name="user_profession" id="header-profession-field" class="header-inline-input prof-des" value="<?php echo htmlspecialchars($current_profession); ?>" placeholder="Add Profession..." required>
                </div>
                
                <div class="header-right-admin-group">
                    <a href="#about-settings-section" class="btn-about-jump">About</a>
                    <a href="#therapies-settings-section" class="btn-about-jump">Therapies</a>
                    <a href="#results-settings-section" class="btn-about-jump">Results</a>
                    <a href="#awards-settings-section" class="btn-about-jump">Awards</a>
                    <a href="#availability-settings-section" class="btn-about-jump">Availability</a>

                    <input type="text" name="user_number" id="header-phone-field" class="header-number-input" value="<?php echo htmlspecialchars($current_number); ?>" placeholder="Add Phone Number..." required>
                    <a href="javascript:void(0);" class="btn-call-preview" id="live-phone-preview-btn">Call <?php echo htmlspecialchars($current_number); ?></a>
                    
                    <a href="pres.php" class="nav-prescription-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>Prescriptions</span>
                    </a>

                    <a href="logout.php" class="nav-logout-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Sign Out</span>
                    </a>
                </div>

            </div>
        </header>

        <main class="main-content">
            
            <div>
                <?php if (!empty($status_message)): ?>
                    <div class="status-pill <?php echo $status_type; ?>" style="margin-bottom: 25px; display: inline-block;">
                        <?php echo $status_message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <label class="section-label">1. Hero Content Workspace</label>
            <div class="hero-card-layout">
                <div class="hero-text-details-pane">
                    <div class="live-preview-title-display">
                        <input type="text" id="canvas-name-preview" class="hero-input-large" value="<?php echo htmlspecialchars($current_name); ?>">
                        <input type="text" id="canvas-profession-preview" class="hero-input-sub" value="<?php echo htmlspecialchars($current_profession); ?>">
                    </div>
                    <textarea name="user_description" id="canvas-description-preview" class="hero-description-textarea" placeholder="Type a description for your profile layout..." required><?php echo htmlspecialchars($current_description); ?></textarea>
                    <div class="cta-placeholder-buttons">
                        <div class="btn-mock-style">Explore</div>
                        <div class="btn-mock-style" style="background:#1d1d1f; color:#fff; border-color:#1d1d1f;">Call Now</div>
                        <a href="pres.php" class="btn-mock-style btn-live-link" style="background: rgba(163, 129, 113, 0.15); color: #8e6553; border-color: rgba(163, 129, 113, 0.3); font-weight: 600;">📋 Write Prescription</a>
                    </div>
                </div>
                <div class="profile-upload-wrapper">
                    <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Doctor Avatar Preview" class="doctor-avatar-preview" id="live-avatar-frame">
                    <label for="user_image" class="upload-interaction-overlay">
                        <span class="icon-symbol">📷</span>
                        <span class="action-lbl-text">Add / Change Image</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Click to upload file</span>
                    </label>
                    <input type="file" name="user_image" id="user_image" accept="image/*">
                </div>
            </div>

            <label class="section-label" id="about-settings-section">2. Bio Overview Settings (About Table)</label>
            <div class="hero-card-layout">
                <div class="hero-text-details-pane">
                    <input type="text" name="about_badge" class="about-badge-input" value="<?php echo htmlspecialchars($current_about_badge); ?>" placeholder="Type section badge..." required>
                    <input type="text" name="about_name" class="hero-input-sub" style="color: var(--text-main); margin-bottom: 5px;" value="<?php echo htmlspecialchars($current_about_name); ?>" placeholder="Type about section title..." required>
                    <textarea name="about_description" class="hero-description-textarea" style="height: 110px;" placeholder="Type the comprehensive bio profile text..." required><?php echo htmlspecialchars($current_about_des); ?></textarea>
                </div>
                <div class="mock-stats-display-box">
                    <h3>12+</h3>
                    <p>Years of Premium Clinical Experience</p>
                </div>
            </div>

            <div class="bottom-action-container" style="margin-bottom: 40px;">
               
                <button type="submit" name="save_admin_panel" class="master-save-btn">Save Main Settings</button>
            </div>
        </form>

        <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 40px 0;">
        <label class="section-label" id="therapies-settings-section">
            3. Dynamic Clinic Services (Therapies)
            <span class="therapy-count-badge"><?php echo count($therapies_list); ?> / 6 Active Cards</span>
        </label>

        <div class="therapies-admin-grid">
            <?php if (!empty($therapies_list)): ?>
                <?php 
                $decor_icons = ['✦', '💧', '⏳', '✨', '☀️', '🌿'];
                foreach ($therapies_list as $index => $therapy): 
                    $icon = isset($decor_icons[$index]) ? $decor_icons[$index] : '✦';
                ?>
                    <div class="therapy-admin-card">
                        <span class="therapy-card-icon"><?php echo $icon; ?></span>
                        <div class="therapy-card-title"><?php echo htmlspecialchars($therapy['name']); ?></div>
                        <div class="therapy-card-desc"><?php echo htmlspecialchars($therapy['description']); ?></div>
                        <a href="./?delete_therapy=<?php echo $therapy['therapies_id']; ?>" class="btn-drop-therapy" onclick="return confirm('Are you sure you want to delete this therapy option?');">Remove Card</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="therapy-admin-card" style="grid-column: span 3; text-align: center; padding: 40px; opacity:0.6;">
                    <p>No active therapeutic treatments configured inside database table rows.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-card-layout <?php echo (count($therapies_list) >= 6) ? 'disabled-form-mask' : ''; ?>" style="align-items: flex-start; padding: 40px; margin-bottom: 40px;">
            <div class="hero-text-details-pane" style="grid-column: span 2;">
                <h3 style="font-size:1.3rem; font-weight:600; margin-bottom:15px; color:var(--text-main);">
                    <?php echo (count($therapies_list) >= 6) ? '⚠️ Max Therapy Limit Reached (6 of 6)' : '➕ Register New Treatment Card'; ?>
                </h3>
                <form action="./" method="POST">
                    <input type="text" name="therapy_name" class="hero-input-sub" style="color: var(--text-main); font-size:1.1rem; padding: 10px 14px; margin-bottom:12px;" placeholder="Type Treatment Title (e.g., Chemical Peeling)..." required>
                    <textarea name="therapy_description" class="hero-description-textarea" style="height: 90px; margin-top:0; margin-bottom:15px;" placeholder="Describe treatment process..." required></textarea>
                    <button type="submit" name="add_therapy_action" class="master-save-btn" style="background:#a38171; padding: 12px 35px; font-size:0.85rem;" <?php echo (count($therapies_list) >= 6) ? 'disabled' : ''; ?>>Add Therapy Card</button>
                </form>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 40px 0;">
        <label class="section-label" id="results-settings-section">
            4. Clinical Results
            <span class="therapy-count-badge"><?php echo count($results_list); ?> Case Metrics Active</span>
        </label>

        <div class="results-admin-grid">
            <?php if (!empty($results_list)): ?>
                <?php foreach ($results_list as $index => $case): 
                    $case_index_number = 401 + $index; 
                ?>
                    <div class="result-admin-card">
                        <div class="result-images-split-preview">
                            <div class="result-preview-box">
                                <span class="result-img-tag-badge">Before</span>
                                <img src="<?php echo htmlspecialchars($case['bef_img']); ?>" alt="Before treatment image">
                            </div>
                            <div class="result-preview-box">
                                <span class="result-img-tag-badge">After</span>
                                <img src="<?php echo htmlspecialchars($case['after_img']); ?>" alt="After treatment image">
                            </div>
                        </div>

                        <span class="result-card-badge">Case Study <?php echo $case_index_number; ?></span>
                        <div style="min-height: 70px;">
                            <h4 style="font-size: 1.2rem; font-weight:600; margin-bottom: 6px; color: var(--text-main);"><?php echo htmlspecialchars($case['name']); ?></h4>
                            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;"><?php echo htmlspecialchars($case['description']); ?></p>
                        </div>

                        <a href="./?delete_result=<?php echo $case['result_id']; ?>" 
                           class="btn-drop-therapy" 
                           style="position: static; align-self: flex-start;"
                           onclick="return confirm('Permanently drop this transformation case entry from logs?');">
                           Remove Case Study
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="therapy-admin-card" style="grid-column: span 2; text-align: center; padding: 50px; opacity:0.6;">
                    <p>No medical result transformations uploaded to database storage maps yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-card-layout" style="align-items: flex-start; padding: 40px; margin-bottom: 40px;">
            <div class="hero-text-details-pane" style="grid-column: span 2;">
                <h3 style="font-size:1.3rem; font-weight:600; margin-bottom:20px; color:var(--text-main);">➕ Upload New Clinical Case Study</h3>
                
                <form action="./" method="POST" enctype="multipart/form-data">
                    <div class="dual-file-uploader-row">
                        <label class="file-upload-block-btn">
                            <strong>📸 Select Before Image</strong>
                            <span>Click to browse device files</span>
                            <input type="file" name="bef_img" accept="image/*" required>
                        </label>
                        
                        <label class="file-upload-block-btn">
                            <strong>📸 Select After Image</strong>
                            <span>Click to browse device files</span>
                            <input type="file" name="after_img" accept="image/*" required>
                        </label>
                    </div>

                    <input type="text" 
                           name="result_name" 
                           class="hero-input-sub" 
                           style="color: var(--text-main); font-size:1.1rem; padding: 10px 14px; margin-bottom:12px;" 
                           placeholder="Condition/Case Title (e.g., Hyperpigmentation Treatment Matrix)..." 
                           required>

                    <textarea name="result_description" 
                              class="hero-description-textarea" 
                              style="height: 90px; margin-top:0; margin-bottom:15px;" 
                              placeholder="Describe skin diagnostics metrics, application intervals, and patient outcomes summary..." 
                              required></textarea>

                    <button type="submit" name="add_result_action" class="master-save-btn" style="background:#1d1d1f; padding: 12px 35px; font-size:0.85rem;">
                        Publish Transformation Case
                    </button>
                    
                </form>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 40px 0;">
        <label class="section-label" id="awards-settings-section">
            5. Awards & Certificates Showroom
            <span class="therapy-count-badge"><?php echo count($awards_list); ?> Active Credentials</span>
        </label>

        <div class="results-admin-grid">
            <?php if (!empty($awards_list)): ?>
                <?php foreach ($awards_list as $award): ?>
                    <div class="result-admin-card" style="flex-direction: row; align-items: center; gap: 24px; padding: 24px;">
                        <div style="width: 35%; max-width: 160px; flex-shrink: 0; background: rgba(0,0,0,0.02); padding: 8px; border-radius: 12px; border:1px solid var(--card-border);">
                            <img src="<?php echo htmlspecialchars($award['image_path']); ?>" alt="Certificate Image File" style="width: 100%; height: 110px; object-fit: contain; border-radius: 8px;">
                        </div>

                        <div style="flex-grow: 1; display: flex; flex-direction: column; gap: 6px;">
                            <span class="result-card-badge" style="background: rgba(163,129,113,0.1); margin-bottom: 2px;"><?php echo htmlspecialchars($award['badge']); ?></span>
                            <h4 style="font-size: 1.1rem; font-weight:600; color: var(--text-main); margin: 0; line-height:1.4;"><?php echo htmlspecialchars($award['name']); ?></h4>
                            
                            <a href="./?delete_award=<?php echo $award['award_id']; ?>" 
                               class="btn-drop-therapy" 
                               style="position: static; align-self: flex-start; margin-top: 8px;"
                               onclick="return confirm('Permanently drop this achievement credential profile row?');">
                               Remove Achievement
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="therapy-admin-card" style="grid-column: span 2; text-align: center; padding: 50px; opacity:0.6;">
                    <p>No medical awards or certification documents uploaded to data logs maps yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-card-layout" style="align-items: flex-start; padding: 40px; margin-bottom: 40px;">
            <div class="hero-text-details-pane" style="grid-column: span 2;">
                <h3 style="font-size:1.3rem; font-weight:600; margin-bottom:20px; color:var(--text-main);">➕ Register New Professional Award / Certificate</h3>
                
                <form action="./" method="POST" enctype="multipart/form-data">
                    <div class="dual-file-uploader-row" style="grid-template-columns: 1fr;">
                        <label class="file-upload-block-btn" style="padding: 24px;">
                            <strong>📜 Select Award / Certificate Image</strong>
                            <span>Click to browse local device files</span>
                            <input type="file" name="award_img" accept="image/*" required>
                        </label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 15px;">
                        <input type="text" 
                               name="award_badge" 
                               class="hero-input-sub" 
                               style="color: #a38171; font-size:1rem; padding: 12px 14px; margin: 0;" 
                               placeholder="Badge Label (e.g., CERTIFICATION)..." 
                               required>

                        <input type="text" 
                               name="award_name" 
                               class="hero-input-sub" 
                               style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0;" 
                               placeholder="Official Award/Membership Title String..." 
                               required>
                    </div>

                    <button type="submit" name="add_award_action" class="master-save-btn" style="background:#1d1d1f; padding: 12px 35px; font-size:0.85rem;">
                        Publish Verified Credential
                    </button>
                </form>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 40px 0;">
        <label class="section-label" id="availability-settings-section">
            6. Operational Profile Desk Hours 
        </label>

        <div class="hero-card-layout" style="align-items: flex-start; padding: 40px; margin-bottom: 40px;">
            <div class="hero-text-details-pane" style="grid-column: span 2;">
                <h3 style="font-size:1.3rem; font-weight:600; margin-bottom:20px; color:var(--text-main);">⚙️ System Scheduling Profile Strategy</h3>
                
                <form action="./" method="POST">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">Start Weekday</label>
                            <input type="text" name="start_day" class="hero-input-sub" style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0; width: 100%;" value="<?php echo htmlspecialchars($current_start_day); ?>" placeholder="e.g., Monday" required>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">End Weekday</label>
                            <input type="text" name="end_day" class="hero-input-sub" style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0; width: 100%;" value="<?php echo htmlspecialchars($current_end_day); ?>" placeholder="e.g., Friday" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 15px;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">Opening Time</label>
                            <input type="time" name="start_time" class="hero-input-sub" style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0; width: 100%; height: 48px;" value="<?php echo htmlspecialchars($current_start_time); ?>" required>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">Closing Time</label>
                            <input type="time" name="end_time" class="hero-input-sub" style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0; width: 100%; height: 48px;" value="<?php echo htmlspecialchars($current_end_time); ?>" required>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">Timezone Identifier</label>
                            <input type="text" name="timezone" class="hero-input-sub" style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0; width: 100%;" value="<?php echo htmlspecialchars($current_timezone); ?>" placeholder="e.g., EST" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; margin-bottom: 25px;">
                        <div>
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px;">Standard Weekly Off-days (Rest Days)</label>
                            <input type="text" name="rest_days" class="hero-input-sub" style="color: var(--text-main); font-size:1rem; padding: 12px 14px; margin: 0; width: 100%;" value="<?php echo htmlspecialchars($current_rest_days); ?>" placeholder="e.g., Saturday, Sunday">
                        </div>
                    </div>

                    <button type="submit" name="save_availability_action" class="master-save-btn" style="background:#1d1d1f; padding: 12px 35px; font-size:0.85rem;">
                        Save Availability Settings
                    </button>
                 
                </form>
            </div>
        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navName = document.getElementById('header-name-field');
            const navProf = document.getElementById('header-profession-field');
            const navPhone = document.getElementById('header-phone-field');

            const cardName = document.getElementById('canvas-name-preview');
            const cardProf = document.getElementById('canvas-profession-preview');
            const previewPhoneBtn = document.getElementById('live-phone-preview-btn');
            
            const logoNameSpan = document.getElementById('logo-name-span');
            const logoProfSpan = document.getElementById('logo-prof-span');
            
            const imageInput = document.getElementById('user_image');
            const avatarFrame = document.getElementById('live-avatar-frame');

            function updateTopLeftCornerLogo() {
                const nameVal = navName.value.trim();
                if(nameVal !== "") {
                    const parts = nameVal.split(' ');
                    logoNameSpan.innerText = parts[parts.length - 1]; 
                } else {
                    logoNameSpan.innerText = "Patel";
                }

                const profVal = navProf.value.trim();
                if(profVal !== "") {
                    const firstWord = profVal.split(' ')[0].toLowerCase();
                    logoProfSpan.innerText = ". " + firstWord; 
                } else {
                    logoProfSpan.innerText = ". skin";
                }
            }

            navName.addEventListener('input', (e) => { cardName.value = e.target.value; updateTopLeftCornerLogo(); });
            cardName.addEventListener('input', (e) => { navName.value = e.target.value; updateTopLeftCornerLogo(); });
            navProf.addEventListener('input', (e) => { cardProf.value = e.target.value; updateTopLeftCornerLogo(); });
            cardProf.addEventListener('input', (e) => { navProf.value = e.target.value; updateTopLeftCornerLogo(); });

            navPhone.addEventListener('input', (e) => {
                previewPhoneBtn.innerText = e.target.value.trim() !== "" ? "Call " + e.target.value : "Call +1 (555) 019-2834";
            });

            imageInput.addEventListener('change', function() {
                const targetFile = this.files[0];
                if (targetFile) {
                    const reader = new FileReader();
                    reader.addEventListener('load', function() { avatarFrame.setAttribute('src', this.result); });
                    reader.readAsDataURL(targetFile);
                }
            });

            // Handle visual verification check feedback during file selection steps across all image uploads
            document.querySelectorAll('.file-upload-block-btn input').forEach(input => {
                input.addEventListener('change', function() {
                    if(this.files.length > 0) {
                        this.parentElement.style.borderColor = "var(--success-green)";
                        this.parentElement.querySelector('span').innerText = "✓ " + this.files[0].name;
                        this.parentElement.querySelector('span').style.color = "var(--success-green)";
                    }
                });
            });

            // Smooth tracking jump alignment on page postback reload runs
            <?php if (!empty($scroll_to_section)): ?>
                setTimeout(() => {
                    const targetElement = document.getElementById('<?php echo $scroll_to_section; ?>');
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 300);
            <?php endif; ?>
        });
    </script>
</body>
</html>