<?php
require 'db.php';
require 'includes/header.php';

// ── Filters ─────────────────────────────────────────────────────
$f_calibre = trim($_GET['calibre'] ?? '');
$f_type    = trim($_GET['type']    ?? '');
$f_brand   = (int)($_GET['brand']  ?? 0);

// Dropdown options
$calibres  = $conn->query("SELECT DISTINCT calibre FROM ammo ORDER BY calibre");
$types_res = $conn->query("SELECT DISTINCT type FROM ammo ORDER BY type");
$brands    = $conn->query("SELECT id, brand FROM brand ORDER BY brand");

// ── Query ────────────────────────────────────────────────────────
$sql = "
    SELECT a.id, a.calibre, a.type AS ammo_type,
           GROUP_CONCAT(DISTINCT b.brand ORDER BY b.brand SEPARATOR ', ') AS brands,
           GROUP_CONCAT(DISTINCT f.name  ORDER BY f.name  SEPARATOR ', ') AS firearms
    FROM ammo a
    LEFT JOIN ammo_brand ab ON a.id = ab.ammo_id
    LEFT JOIN brand b       ON ab.brand_id = b.id
    LEFT JOIN firearm_ammo fa ON a.id = fa.ammo_id
    LEFT JOIN firearm f       ON fa.firearm_id = f.id
    WHERE (? = '' OR a.calibre = ?)
      AND (? = '' OR a.type = ?)
      AND (? = 0  OR ab.brand_id = ?)
    GROUP BY a.id
    ORDER BY a.calibre, a.type
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssii',
    $f_calibre, $f_calibre,
    $f_type,    $f_type,
    $f_brand,   $f_brand
);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1 class="page-title"><i class="bi bi-circle"></i> Ammunition</h1>

<!-- Filter bar -->
<form method="get" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label mb-1 fw-semibold">Calibre</label>
            <select name="calibre" class="form-select filter-select">
                <option value="">All Calibres</option>
                <?php while ($r = $calibres->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($r['calibre']) ?>"
                        <?= $f_calibre === $r['calibre'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['calibre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1 fw-semibold">Type</label>
            <select name="type" class="form-select filter-select">
                <option value="">All Types</option>
                <?php while ($r = $types_res->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($r['type']) ?>"
                        <?= $f_type === $r['type'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['type']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1 fw-semibold">Brand</label>
            <select name="brand" class="form-select filter-select">
                <option value="0">All Brands</option>
                <?php while ($r = $brands->fetch_assoc()): ?>
                    <option value="<?= $r['id'] ?>"
                        <?= $f_brand == $r['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['brand']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-danger">Filter</button>
            <a href="ammo.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<p class="text-muted mb-3"><?= $result->num_rows ?> result<?= $result->num_rows !== 1 ? 's' : '' ?></p>

<?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        No ammo matches your filters.
    </div>
<?php else: ?>
<table class="table table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th>Calibre</th>
            <th>Type</th>
            <th>Brands</th>
            <th>Used By</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><strong><?= htmlspecialchars($row['calibre']) ?></strong></td>
            <td><?= htmlspecialchars($row['ammo_type']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($row['brands'] ?? '—') ?></td>
            <td class="text-muted small"><?= htmlspecialchars($row['firearms'] ?? '—') ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
