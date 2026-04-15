<?php
/**
 * Admin action handler — all CUD (create/update/delete) operations POST here.
 * Always redirects back after processing (Post/Redirect/Get pattern).
 */
require __DIR__ . '/../db.php';

$entity = $_POST['entity'] ?? '';
$action = $_POST['action'] ?? '';
$tab    = $_POST['tab']    ?? 'firearms';
$id     = (int)($_POST['id'] ?? 0);

// ── Whitelist allowed entities ──────────────────────────────────
$allowed = [
    'firearm_type', 'fire_mode', 'brand',
    'ammo', 'manufacturer', 'attachment', 'firearm', 'firearm_picture',
];
if (!in_array($entity, $allowed, true) || !in_array($action, ['add','edit','delete'], true)) {
    redirect($tab);
}

// ── Dispatch ─────────────────────────────────────────────────────
match ($entity) {
    'firearm_type'    => simple($conn, 'firearm_type', 'type',  $action, $id, $tab),
    'fire_mode'       => simple($conn, 'fire_mode',    'mode',  $action, $id, $tab),
    'brand'           => simple($conn, 'brand',        'brand', $action, $id, $tab),
    'ammo'            => handle_ammo($conn, $action, $id, $tab),
    'manufacturer'    => handle_manufacturer($conn, $action, $id, $tab),
    'attachment'      => handle_attachment($conn, $action, $id, $tab),
    'firearm'         => handle_firearm($conn, $action, $id),
    'firearm_picture' => handle_firearm_picture($conn, $action, $id),
    default           => redirect($tab),
};

// ── Helpers ───────────────────────────────────────────────────────

/**
 * Move an uploaded image to assets/images/ and return its relative path.
 * Returns null if no file was uploaded or the file is invalid.
 */
function handle_upload(string $field): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        return null;
    }
    $filename = uniqid('img_', true) . '.' . $ext;
    $dest = __DIR__ . '/../assets/images/' . $filename;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return null;
    }
    return 'assets/images/' . $filename;
}

/**
 * Handle multiple uploaded images from a multi-file input (name="field[]").
 * Returns an array of saved relative paths (may be empty).
 */
function handle_uploads(string $field): array {
    $paths = [];
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $paths;
    }
    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    $count = count($_FILES[$field]['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($_FILES[$field]['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext, true)) continue;
        $filename = uniqid('img_', true) . '.' . $ext;
        $dest = __DIR__ . '/../assets/images/' . $filename;
        if (move_uploaded_file($_FILES[$field]['tmp_name'][$i], $dest)) {
            $paths[] = 'assets/images/' . $filename;
        }
    }
    return $paths;
}

function redirect(string $tab, string $extra = ''): never {
    header("Location: index.php?tab={$tab}{$extra}");
    exit;
}

function redirect_form(string $suffix = ''): never {
    header("Location: firearm_form.php{$suffix}");
    exit;
}

// Single-field lookup tables (firearm_type, fire_mode, brand)
function simple(mysqli $conn, string $table, string $field,
                string $action, int $id, string $tab): never
{
    $value = trim($_POST['value'] ?? '');

    if ($action === 'add' && $value !== '') {
        $stmt = $conn->prepare("INSERT INTO `{$table}` (`{$field}`) VALUES (?)");
        $stmt->bind_param('s', $value);
        $stmt->execute();

    } elseif ($action === 'edit' && $id > 0 && $value !== '') {
        $stmt = $conn->prepare("UPDATE `{$table}` SET `{$field}`=? WHERE id=?");
        $stmt->bind_param('si', $value, $id);
        $stmt->execute();

    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM `{$table}` WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
    redirect($tab);
}

function handle_ammo(mysqli $conn, string $action, int $id, string $tab): never {
    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM ammo WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        redirect($tab);
    }

    $calibre = trim($_POST['calibre'] ?? '');
    $type    = trim($_POST['type']    ?? '');
    $brands  = array_map('intval', $_POST['brands'] ?? []);

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO ammo (calibre, type) VALUES (?,?)");
        $stmt->bind_param('ss', $calibre, $type);
        $stmt->execute();
        $ammo_id = $conn->insert_id;
    } else {
        $ammo_id = $id;
        $stmt = $conn->prepare("UPDATE ammo SET calibre=?, type=? WHERE id=?");
        $stmt->bind_param('ssi', $calibre, $type, $ammo_id);
        $stmt->execute();
        $d = $conn->prepare("DELETE FROM ammo_brand WHERE ammo_id=?");
        $d->bind_param('i', $ammo_id);
        $d->execute();
    }

    foreach ($brands as $bid) {
        $ins = $conn->prepare("INSERT IGNORE INTO ammo_brand (ammo_id, brand_id) VALUES (?,?)");
        $ins->bind_param('ii', $ammo_id, $bid);
        $ins->execute();
    }
    redirect($tab);
}

function handle_manufacturer(mysqli $conn, string $action, int $id, string $tab): never {
    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM manufacturer WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        redirect($tab);
    }

    $name     = trim($_POST['name'] ?? '');
    // Use uploaded file path, or fall back to the existing path passed as a hidden field
    $img_path = handle_upload('img_file') ?? trim($_POST['existing_img'] ?? '');

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO manufacturer (name, img_path) VALUES (?,?)");
        $stmt->bind_param('ss', $name, $img_path);
    } else {
        $stmt = $conn->prepare("UPDATE manufacturer SET name=?, img_path=? WHERE id=?");
        $stmt->bind_param('ssi', $name, $img_path, $id);
    }
    $stmt->execute();
    redirect($tab);
}

function handle_attachment(mysqli $conn, string $action, int $id, string $tab): never {
    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM attachment WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        redirect($tab);
    }

    $name     = trim($_POST['name'] ?? '');
    $type     = trim($_POST['type'] ?? '');
    $img_path = handle_upload('img_file') ?? trim($_POST['existing_img'] ?? '');

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO attachment (name, type, img_path) VALUES (?,?,?)");
        $stmt->bind_param('sss', $name, $type, $img_path);
    } else {
        $stmt = $conn->prepare("UPDATE attachment SET name=?, type=?, img_path=? WHERE id=?");
        $stmt->bind_param('sssi', $name, $type, $img_path, $id);
    }
    $stmt->execute();
    redirect($tab);
}

function handle_firearm(mysqli $conn, string $action, int $id): never {
    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM firearm WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        redirect('firearms');
    }

    // Collect and sanitize fields
    $name      = trim($_POST['name'] ?? '');
    $type_id   = (int)($_POST['firearm_type_id'] ?? 0);
    $rof       = $_POST['rate_of_fire']   !== '' ? (float)$_POST['rate_of_fire']   : null;
    $capacity  = $_POST['capacity']       !== '' ? (int)  $_POST['capacity']        : null;
    $range     = $_POST['effective_range']!== '' ? (float)$_POST['effective_range'] : null;
    $barrel    = $_POST['barrel_length']  !== '' ? (float)$_POST['barrel_length']   : null;
    $weight    = $_POST['weight']         !== '' ? (float)$_POST['weight']          : null;

    $modes   = array_map('intval', $_POST['modes']        ?? []);
    $ammos   = array_map('intval', $_POST['ammos']        ?? []);
    $mfrs    = array_map('intval', $_POST['manufacturers'] ?? []);
    $atts    = array_map('intval', $_POST['attachments']  ?? []);

    if ($action === 'add') {
        $stmt = $conn->prepare("
            INSERT INTO firearm (name, rate_of_fire, capacity, effective_range,
                                 barrel_length, weight, firearm_type_id)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->bind_param('sdddddi', $name, $rof, $capacity, $range, $barrel, $weight, $type_id);
        $stmt->execute();
        $id = $conn->insert_id;
    } else {
        $stmt = $conn->prepare("
            UPDATE firearm
            SET name=?, rate_of_fire=?, capacity=?, effective_range=?,
                barrel_length=?, weight=?, firearm_type_id=?
            WHERE id=?
        ");
        $stmt->bind_param('sdddddii', $name, $rof, $capacity, $range, $barrel, $weight, $type_id, $id);
        $stmt->execute();

        // Clear old relations
        foreach (['firearm_fire_mode','firearm_ammo','firearm_manufacturer','firearm_attachment'] as $tbl) {
            $d = $conn->prepare("DELETE FROM `{$tbl}` WHERE firearm_id=?");
            $d->bind_param('i', $id);
            $d->execute();
        }
    }

    // Re-insert relations
    foreach ($modes as $mid) {
        $ins = $conn->prepare("INSERT IGNORE INTO firearm_fire_mode (firearm_id, fire_mode_id) VALUES (?,?)");
        $ins->bind_param('ii', $id, $mid); $ins->execute();
    }
    foreach ($ammos as $aid) {
        $ins = $conn->prepare("INSERT IGNORE INTO firearm_ammo (firearm_id, ammo_id) VALUES (?,?)");
        $ins->bind_param('ii', $id, $aid); $ins->execute();
    }
    foreach ($mfrs as $mid) {
        $ins = $conn->prepare("INSERT IGNORE INTO firearm_manufacturer (firearm_id, manufacturer_id) VALUES (?,?)");
        $ins->bind_param('ii', $id, $mid); $ins->execute();
    }
    foreach ($atts as $aid) {
        $ins = $conn->prepare("INSERT IGNORE INTO firearm_attachment (firearm_id, attachment_id) VALUES (?,?)");
        $ins->bind_param('ii', $id, $aid); $ins->execute();
    }

    // Upload new pictures if provided (supports multiple files)
    foreach (handle_uploads('picture_file') as $new_pic) {
        $ins = $conn->prepare("INSERT INTO picture (img_path) VALUES (?)");
        $ins->bind_param('s', $new_pic);
        $ins->execute();
        $pic_id = $conn->insert_id;
        $ins->close();

        $ins = $conn->prepare("INSERT INTO firearm_picture (firearm_id, picture_id) VALUES (?,?)");
        $ins->bind_param('ii', $id, $pic_id);
        $ins->execute();
        $ins->close();
    }

    redirect('firearms');
}

function handle_firearm_picture(mysqli $conn, string $action, int $id): never {
    $firearm_id = (int)($_POST['firearm_id'] ?? 0);
    if ($action === 'delete' && $id > 0) {
        // Get the picture path before deleting so we can remove the file
        $s = $conn->prepare("SELECT p.img_path FROM picture p JOIN firearm_picture fp ON p.id=fp.picture_id WHERE fp.id=?");
        $s->bind_param('i', $id);
        $s->execute();
        $res = $s->get_result();
        $pic = $res->fetch_assoc();
        $res->free(); $s->close();

        // Delete the firearm_picture row (picture row deleted by cascade if unused)
        $d = $conn->prepare("DELETE FROM firearm_picture WHERE id=?");
        $d->bind_param('i', $id);
        $d->execute();
        $d->close();

        // Remove physical file if it exists
        if ($pic && $pic['img_path']) {
            $full = __DIR__ . '/../' . $pic['img_path'];
            if (file_exists($full)) @unlink($full);
        }
    }
    header("Location: firearm_form.php?id={$firearm_id}");
    exit;
}
