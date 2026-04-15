<?php
require __DIR__ . '/../db.php';

$id       = (int)($_GET['id'] ?? 0);
$is_edit  = $id > 0;
$fw       = null;
$sel_modes = $sel_ammos = $sel_mfrs = $sel_atts = [];

if ($is_edit) {
    $s = $conn->prepare("SELECT * FROM firearm WHERE id=?");
    $s->bind_param('i', $id);
    $s->execute();
    $res = $s->get_result();
    $fw  = $res->fetch_assoc();
    $res->free();
    $s->close();
    if (!$fw) { header('Location: index.php?tab=firearms'); exit; }

    // Pre-selected relations — store result, fetch all, then close before next prepare
    $r = $conn->prepare("SELECT fire_mode_id FROM firearm_fire_mode WHERE firearm_id=?");
    $r->bind_param('i', $id); $r->execute();
    $res = $r->get_result();
    while ($row = $res->fetch_row()) $sel_modes[] = $row[0];
    $res->free(); $r->close();

    $r = $conn->prepare("SELECT ammo_id FROM firearm_ammo WHERE firearm_id=?");
    $r->bind_param('i', $id); $r->execute();
    $res = $r->get_result();
    while ($row = $res->fetch_row()) $sel_ammos[] = $row[0];
    $res->free(); $r->close();

    $r = $conn->prepare("SELECT manufacturer_id FROM firearm_manufacturer WHERE firearm_id=?");
    $r->bind_param('i', $id); $r->execute();
    $res = $r->get_result();
    while ($row = $res->fetch_row()) $sel_mfrs[] = $row[0];
    $res->free(); $r->close();

    $r = $conn->prepare("SELECT attachment_id FROM firearm_attachment WHERE firearm_id=?");
    $r->bind_param('i', $id); $r->execute();
    $res = $r->get_result();
    while ($row = $res->fetch_row()) $sel_atts[] = $row[0];
    $res->free(); $r->close();
}

// Existing pictures (for edit view)
$existing_pics = [];
if ($is_edit) {
    $r = $conn->prepare("SELECT fp.id AS fp_id, p.img_path FROM firearm_picture fp JOIN picture p ON p.id=fp.picture_id WHERE fp.firearm_id=?");
    $r->bind_param('i', $id); $r->execute();
    $res = $r->get_result();
    while ($row = $res->fetch_assoc()) $existing_pics[] = $row;
    $res->free(); $r->close();
}

// Options for dropdowns / checkboxes
$ft_opts  = $conn->query("SELECT id, type  FROM firearm_type  ORDER BY type");
$fm_opts  = $conn->query("SELECT id, mode  FROM fire_mode     ORDER BY mode");
$am_opts  = $conn->query("SELECT id, CONCAT(calibre,' ',type) AS label FROM ammo ORDER BY calibre");
$mf_opts  = $conn->query("SELECT id, name  FROM manufacturer  ORDER BY name");
$at_opts  = $conn->query("SELECT id, name, type FROM attachment ORDER BY type, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Add' ?> Firearm — WikiGun Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3 mb-4">
    <span class="navbar-brand"><i class="bi bi-gear-fill"></i> WikiGun Admin</span>
    <div class="d-flex gap-2">
        <a href="index.php?tab=firearms" class="btn btn-sm btn-outline-light">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="../index.php" class="btn btn-sm btn-outline-secondary">View Site</a>
    </div>
</nav>

<div class="container" style="max-width:760px;">
    <h2 class="mb-4"><?= $is_edit ? 'Edit' : 'Add New' ?> Firearm</h2>

    <form method="post" action="action.php" enctype="multipart/form-data">
        <input type="hidden" name="entity" value="firearm">
        <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'add' ?>">
        <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <!-- Basic info -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Basic Info</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($fw['name'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Firearm Type <span class="text-danger">*</span></label>
                    <select name="firearm_type_id" class="form-select" required>
                        <option value="">— select —</option>
                        <?php while ($r = $ft_opts->fetch_assoc()): ?>
                            <option value="<?= $r['id'] ?>"
                                <?= ($fw['firearm_type_id'] ?? 0) == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['type']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Specs -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Specifications</div>
            <div class="card-body row g-3">
                <div class="col-6">
                    <label class="form-label">Rate of Fire (rpm)</label>
                    <input type="number" step="any" name="rate_of_fire" class="form-control"
                           placeholder="leave blank if N/A"
                           value="<?= htmlspecialchars($fw['rate_of_fire'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Capacity (rounds)</label>
                    <input type="number" name="capacity" class="form-control"
                           value="<?= htmlspecialchars($fw['capacity'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Effective Range (m)</label>
                    <input type="number" step="any" name="effective_range" class="form-control"
                           value="<?= htmlspecialchars($fw['effective_range'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Barrel Length (in)</label>
                    <input type="number" step="any" name="barrel_length" class="form-control"
                           value="<?= htmlspecialchars($fw['barrel_length'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Weight (kg)</label>
                    <input type="number" step="any" name="weight" class="form-control"
                           value="<?= htmlspecialchars($fw['weight'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Fire Modes -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Fire Modes</div>
            <div class="card-body d-flex flex-wrap gap-3">
                <?php while ($r = $fm_opts->fetch_assoc()): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="modes[]" value="<?= $r['id'] ?>"
                           id="mode_<?= $r['id'] ?>"
                           <?= in_array($r['id'], $sel_modes) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mode_<?= $r['id'] ?>">
                        <?= htmlspecialchars($r['mode']) ?>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Compatible Ammo -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Compatible Ammo</div>
            <div class="card-body d-flex flex-wrap gap-3">
                <?php while ($r = $am_opts->fetch_assoc()): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="ammos[]" value="<?= $r['id'] ?>"
                           id="ammo_<?= $r['id'] ?>"
                           <?= in_array($r['id'], $sel_ammos) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ammo_<?= $r['id'] ?>">
                        <?= htmlspecialchars($r['label']) ?>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Manufacturers -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Manufacturers</div>
            <div class="card-body d-flex flex-wrap gap-3">
                <?php while ($r = $mf_opts->fetch_assoc()): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="manufacturers[]" value="<?= $r['id'] ?>"
                           id="mfr_<?= $r['id'] ?>"
                           <?= in_array($r['id'], $sel_mfrs) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mfr_<?= $r['id'] ?>">
                        <?= htmlspecialchars($r['name']) ?>
                    </label>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Attachments -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Compatible Attachments</div>
            <div class="card-body">
                <?php
                $at_rows = [];
                while ($r = $at_opts->fetch_assoc()) $at_rows[] = $r;
                $by_type = [];
                foreach ($at_rows as $r) $by_type[$r['type']][] = $r;
                foreach ($by_type as $type => $items): ?>
                <p class="text-muted small mb-1 mt-2"><?= htmlspecialchars($type) ?></p>
                <div class="d-flex flex-wrap gap-3 mb-1">
                    <?php foreach ($items as $r): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="attachments[]" value="<?= $r['id'] ?>"
                               id="att_<?= $r['id'] ?>"
                               <?= in_array($r['id'], $sel_atts) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="att_<?= $r['id'] ?>">
                            <?= htmlspecialchars($r['name']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Picture queue (JS-driven, all files sent on submit) -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">Add Pictures</div>
            <div class="card-body">
                <!-- Hidden real input — JS assigns the accumulated FileList before submit -->
                <input type="file" id="picRealInput" name="picture_file[]"
                       accept="image/*" multiple style="display:none;">
                <!-- Single-pick trigger (no multiple — one at a time) -->
                <input type="file" id="picPicker" accept="image/*" style="display:none;">

                <button type="button" class="btn btn-outline-secondary btn-sm mb-3"
                        onclick="document.getElementById('picPicker').click()">
                    <i class="bi bi-plus-lg"></i> Add Picture
                </button>

                <!-- Preview queue -->
                <div id="picQueue" class="d-flex flex-wrap gap-2"></div>
            </div>
        </div>

        <script>
        (function () {
            const picker    = document.getElementById('picPicker');
            const realInput = document.getElementById('picRealInput');
            const queue     = document.getElementById('picQueue');
            let files = [];   // accumulated File objects

            picker.addEventListener('change', function () {
                if (!this.files.length) return;
                files.push(this.files[0]);
                this.value = '';   // reset so same file can be picked again
                renderQueue();
            });

            function renderQueue() {
                queue.innerHTML = '';
                files.forEach(function (file, idx) {
                    const url  = URL.createObjectURL(file);
                    const wrap = document.createElement('div');
                    wrap.style.cssText = 'position:relative;width:110px;';

                    const img = document.createElement('img');
                    img.src   = url;
                    img.style.cssText =
                        'width:110px;height:80px;object-fit:cover;' +
                        'border-radius:4px;border:1px solid #dee2e6;display:block;';

                    const btn = document.createElement('button');
                    btn.type  = 'button';
                    btn.className = 'btn btn-sm btn-danger w-100 mt-1';
                    btn.innerHTML = '<i class="bi bi-x-lg"></i> Remove';
                    btn.addEventListener('click', function () {
                        URL.revokeObjectURL(url);
                        files.splice(idx, 1);
                        renderQueue();
                    });

                    const name = document.createElement('div');
                    name.className   = 'text-truncate';
                    name.style.cssText = 'font-size:.7rem;color:#6c757d;margin-top:2px;';
                    name.textContent = file.name;

                    wrap.appendChild(img);
                    wrap.appendChild(name);
                    wrap.appendChild(btn);
                    queue.appendChild(wrap);
                });

                // Sync files array → real hidden input via DataTransfer
                const dt = new DataTransfer();
                files.forEach(function (f) { dt.items.add(f); });
                realInput.files = dt.files;
            }
        })();
        </script>

        <div class="d-flex gap-2 mb-3">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-check-lg"></i> <?= $is_edit ? 'Save Changes' : 'Add Firearm' ?>
            </button>
            <a href="index.php?tab=firearms" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

    <?php if ($is_edit && !empty($existing_pics)): ?>
    <!-- Existing pictures — separate standalone forms, intentionally outside the main form -->
    <div class="card mb-5">
        <div class="card-header fw-semibold">Current Pictures</div>
        <div class="card-body d-flex flex-wrap gap-3">
            <?php foreach ($existing_pics as $pic): ?>
            <div style="width:110px;">
                <img src="../<?= htmlspecialchars($pic['img_path']) ?>"
                     style="width:110px;height:80px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;"
                     onerror="this.style.opacity='.3'">
                <form method="post" action="action.php" class="mt-1"
                      onsubmit="return confirm('Remove this picture?')">
                    <input type="hidden" name="entity"     value="firearm_picture">
                    <input type="hidden" name="action"     value="delete">
                    <input type="hidden" name="id"         value="<?= $pic['fp_id'] ?>">
                    <input type="hidden" name="firearm_id" value="<?= $id ?>">
                    <input type="hidden" name="tab"        value="firearms">
                    <button class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
