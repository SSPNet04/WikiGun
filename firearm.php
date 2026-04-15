<?php
require 'db.php';
require 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// ── Core firearm row ────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT f.*, ft.type AS firearm_type
    FROM firearm f
    JOIN firearm_type ft ON f.firearm_type_id = ft.id
    WHERE f.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$fw = $stmt->get_result()->fetch_assoc();

if (!$fw) {
    echo '<div class="alert alert-danger">Firearm not found.</div>';
    require 'includes/footer.php';
    exit;
}

// ── Pictures ────────────────────────────────────────────────────
$pics = $conn->prepare("
    SELECT p.img_path FROM picture p
    JOIN firearm_picture fp ON p.id = fp.picture_id
    WHERE fp.firearm_id = ?
");
$pics->bind_param('i', $id);
$pics->execute();
$pics = $pics->get_result();

// ── Fire modes ──────────────────────────────────────────────────
$modes = $conn->prepare("
    SELECT fm.mode FROM fire_mode fm
    JOIN firearm_fire_mode ffm ON fm.id = ffm.fire_mode_id
    WHERE ffm.firearm_id = ?
");
$modes->bind_param('i', $id);
$modes->execute();
$modes = $modes->get_result();

// ── Compatible ammo ─────────────────────────────────────────────
$ammos = $conn->prepare("
    SELECT a.id, a.calibre, a.type AS ammo_type,
           GROUP_CONCAT(b.brand ORDER BY b.brand SEPARATOR ', ') AS brands
    FROM ammo a
    JOIN firearm_ammo fa ON a.id = fa.ammo_id
    LEFT JOIN ammo_brand ab ON a.id = ab.ammo_id
    LEFT JOIN brand b ON ab.brand_id = b.id
    WHERE fa.firearm_id = ?
    GROUP BY a.id
");
$ammos->bind_param('i', $id);
$ammos->execute();
$ammos = $ammos->get_result();

// ── Manufacturers ───────────────────────────────────────────────
$mfrs = $conn->prepare("
    SELECT m.name, m.img_path FROM manufacturer m
    JOIN firearm_manufacturer fm ON m.id = fm.manufacturer_id
    WHERE fm.firearm_id = ?
");
$mfrs->bind_param('i', $id);
$mfrs->execute();
$mfrs = $mfrs->get_result();

// ── Attachments ─────────────────────────────────────────────────
$atts = $conn->prepare("
    SELECT a.name, a.type, a.img_path FROM attachment a
    JOIN firearm_attachment fa ON a.id = fa.attachment_id
    WHERE fa.firearm_id = ?
    ORDER BY a.type, a.name
");
$atts->bind_param('i', $id);
$atts->execute();
$atts = $atts->get_result();
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
        <?php
        $pic_rows = [];
        while ($p = $pics->fetch_assoc()) $pic_rows[] = $p;
        ?>
        <?php if (!empty($pic_rows)): ?>
            <img src="<?= htmlspecialchars($pic_rows[0]['img_path']) ?>"
                 class="img-fluid rounded mb-2 w-100"
                 style="height:280px;object-fit:cover;"
                 id="mainImg"
                 alt="<?= htmlspecialchars($fw['name']) ?>"
                 onerror="this.src='assets/images/placeholder.png'">
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
            <?php while ($m = $modes->fetch_assoc()): ?>
                <span class="badge bg-danger badge-mode"><?= htmlspecialchars($m['mode']) ?></span>
            <?php endwhile; ?>
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
<h4 class="page-title mt-5"><i class="bi bi-building"></i> Manufacturers</h4>
<div class="row g-3">
    <?php while ($mfr = $mfrs->fetch_assoc()): ?>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3 h-100">
            <?php if ($mfr['img_path'] && file_exists($mfr['img_path'])): ?>
                <img src="<?= htmlspecialchars($mfr['img_path']) ?>"
                     class="mx-auto mb-2" style="height:60px;object-fit:contain;"
                     alt="<?= htmlspecialchars($mfr['name']) ?>">
            <?php else: ?>
                <i class="bi bi-building fs-2 text-secondary mb-2"></i>
            <?php endif; ?>
            <div class="fw-semibold"><?= htmlspecialchars($mfr['name']) ?></div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- ── Compatible Ammo ── -->
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
        <?php while ($a = $ammos->fetch_assoc()): ?>
        <tr>
            <td><strong><?= htmlspecialchars($a['calibre']) ?></strong></td>
            <td><?= htmlspecialchars($a['ammo_type']) ?></td>
            <td><?= htmlspecialchars($a['brands'] ?? '—') ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- ── Attachments ── -->
<h4 class="page-title mt-5"><i class="bi bi-tools"></i> Compatible Attachments</h4>
<div class="row row-cols-2 row-cols-md-4 g-3">
    <?php while ($att = $atts->fetch_assoc()): ?>
    <div class="col">
        <div class="card text-center p-3 h-100">
            <?php if ($att['img_path'] && file_exists($att['img_path'])): ?>
                <img src="<?= htmlspecialchars($att['img_path']) ?>"
                     class="mx-auto mb-2" style="height:60px;object-fit:contain;"
                     alt="<?= htmlspecialchars($att['name']) ?>">
            <?php else: ?>
                <i class="bi bi-tools fs-2 text-secondary mb-2"></i>
            <?php endif; ?>
            <span class="badge bg-light text-dark mb-1"><?= htmlspecialchars($att['type']) ?></span>
            <div class="small fw-semibold"><?= htmlspecialchars($att['name']) ?></div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php require 'includes/footer.php'; ?>
