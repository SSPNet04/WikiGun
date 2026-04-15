<?php
require 'db.php';
require 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// ── Core firearm row ─────────────────────────────────────────────
$s = $conn->prepare("
    SELECT f.*, ft.type AS firearm_type
    FROM firearm f
    JOIN firearm_type ft ON f.firearm_type_id = ft.id
    WHERE f.id = ?
");
$s->bind_param('i', $id);
$s->execute();
$res = $s->get_result();
$fw  = $res->fetch_assoc();
$res->free();
$s->close();

if (!$fw) {
    echo '<div class="alert alert-danger">Firearm not found.</div>';
    require 'includes/footer.php';
    exit;
}

// ── Pictures ─────────────────────────────────────────────────────
$s = $conn->prepare("
    SELECT p.img_path FROM picture p
    JOIN firearm_picture fp ON p.id = fp.picture_id
    WHERE fp.firearm_id = ?
    ORDER BY fp.id
");
$s->bind_param('i', $id);
$s->execute();
$res      = $s->get_result();
$pic_rows = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$s->close();

// ── Fire modes ───────────────────────────────────────────────────
$s = $conn->prepare("
    SELECT fm.mode FROM fire_mode fm
    JOIN firearm_fire_mode ffm ON fm.id = ffm.fire_mode_id
    WHERE ffm.firearm_id = ?
");
$s->bind_param('i', $id);
$s->execute();
$res   = $s->get_result();
$modes = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$s->close();

// ── Compatible ammo ──────────────────────────────────────────────
$s = $conn->prepare("
    SELECT a.id, a.calibre, a.type AS ammo_type,
           GROUP_CONCAT(b.brand ORDER BY b.brand SEPARATOR ', ') AS brands
    FROM ammo a
    JOIN firearm_ammo fa ON a.id = fa.ammo_id
    LEFT JOIN ammo_brand ab ON a.id = ab.ammo_id
    LEFT JOIN brand b ON ab.brand_id = b.id
    WHERE fa.firearm_id = ?
    GROUP BY a.id
");
$s->bind_param('i', $id);
$s->execute();
$res   = $s->get_result();
$ammos = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$s->close();

// ── Manufacturers ────────────────────────────────────────────────
$s = $conn->prepare("
    SELECT m.name, m.img_path FROM manufacturer m
    JOIN firearm_manufacturer fm ON m.id = fm.manufacturer_id
    WHERE fm.firearm_id = ?
");
$s->bind_param('i', $id);
$s->execute();
$res  = $s->get_result();
$mfrs = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$s->close();

// ── Attachments ──────────────────────────────────────────────────
$s = $conn->prepare("
    SELECT a.name, a.type, a.img_path FROM attachment a
    JOIN firearm_attachment fa ON a.id = fa.attachment_id
    WHERE fa.firearm_id = ?
    ORDER BY a.type, a.name
");
$s->bind_param('i', $id);
$s->execute();
$res  = $s->get_result();
$atts = $res->fetch_all(MYSQLI_ASSOC);
$res->free();
$s->close();
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Firearms</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($fw['name']) ?></li>
    </ol>
</nav>

<div class="row g-4">

    <!-- ── LEFT: gallery ── -->
    <div class="col-12 col-md-5">
        <?php if (!empty($pic_rows)): ?>
            <img src="<?= htmlspecialchars($pic_rows[0]['img_path']) ?>"
                 class="img-fluid rounded mb-2 w-100"
                 style="height:280px;object-fit:cover;"
                 id="mainImg"
                 alt="<?= htmlspecialchars($fw['name']) ?>"
                 onerror="this.style.display='none'">
            <?php if (count($pic_rows) > 1): ?>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($pic_rows as $p): ?>
                <img src="<?= htmlspecialchars($p['img_path']) ?>"
                     class="gallery-img"
                     style="width:80px;height:60px;object-fit:cover;cursor:pointer;border-radius:4px;"
                     onclick="document.getElementById('mainImg').src=this.src"
                     onerror="this.style.display='none'">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-secondary bg-opacity-10 rounded d-flex align-items-center
                        justify-content-center" style="height:280px;">
                <i class="bi bi-image text-secondary" style="font-size:3rem;"></i>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── RIGHT: info ── -->
    <div class="col-12 col-md-7">
        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($fw['firearm_type']) ?></span>
        <h1 class="mb-3"><?= htmlspecialchars($fw['name']) ?></h1>

        <!-- Fire modes -->
        <div class="mb-3">
            <?php foreach ($modes as $m): ?>
                <span class="badge bg-danger badge-mode"><?= htmlspecialchars($m['mode']) ?></span>
            <?php endforeach; ?>
        </div>

        <!-- Specs table -->
        <table class="table table-bordered spec-table">
            <tbody>
                <tr>
                    <th>Rate of Fire</th>
                    <td><?= $fw['rate_of_fire'] ? $fw['rate_of_fire'] . ' rpm' : '—' ?></td>
                </tr>
                <tr>
                    <th>Magazine Capacity</th>
                    <td><?= $fw['capacity'] ? $fw['capacity'] . ' rounds' : '—' ?></td>
                </tr>
                <tr>
                    <th>Effective Range</th>
                    <td><?= $fw['effective_range'] ? $fw['effective_range'] . ' m' : '—' ?></td>
                </tr>
                <tr>
                    <th>Barrel Length</th>
                    <td><?= $fw['barrel_length'] ? $fw['barrel_length'] . ' in' : '—' ?></td>
                </tr>
                <tr>
                    <th>Weight</th>
                    <td><?= $fw['weight'] ? $fw['weight'] . ' kg' : '—' ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Manufacturers ── -->
<?php if (!empty($mfrs)): ?>
<h4 class="page-title mt-5"><i class="bi bi-building"></i> Manufacturers</h4>
<div class="row g-3">
    <?php foreach ($mfrs as $mfr): ?>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3 h-100">
            <?= img_or_icon($mfr['img_path'], 'building', $mfr['name'], 'height:60px;object-fit:contain;') ?>
            <div class="fw-semibold mt-2"><?= htmlspecialchars($mfr['name']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Compatible Ammo ── -->
<?php if (!empty($ammos)): ?>
<h4 class="page-title mt-5"><i class="bi bi-circle"></i> Compatible Ammo</h4>
<table class="table table-hover">
    <thead class="table-dark">
        <tr>
            <th>Calibre</th>
            <th>Type</th>
            <th>Brands</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($ammos as $a): ?>
        <tr>
            <td><strong><?= htmlspecialchars($a['calibre']) ?></strong></td>
            <td><?= htmlspecialchars($a['ammo_type']) ?></td>
            <td><?= htmlspecialchars($a['brands'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ── Attachments ── -->
<?php if (!empty($atts)): ?>
<h4 class="page-title mt-5"><i class="bi bi-tools"></i> Compatible Attachments</h4>
<div class="row row-cols-2 row-cols-md-4 g-3">
    <?php foreach ($atts as $att): ?>
    <div class="col">
        <div class="card text-center p-3 h-100">
            <?= img_or_icon($att['img_path'], 'tools', $att['name'], 'height:60px;object-fit:contain;') ?>
            <span class="badge bg-light text-dark mt-2 mb-1"><?= htmlspecialchars($att['type']) ?></span>
            <div class="small fw-semibold"><?= htmlspecialchars($att['name']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
