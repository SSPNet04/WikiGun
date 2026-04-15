<?php
require 'db.php';
require 'includes/header.php';

// ── Read filter inputs ──────────────────────────────────────────
$search  = trim($_GET['q']    ?? '');
$type_id = (int)($_GET['type'] ?? 0);
$mode_id = (int)($_GET['mode'] ?? 0);
$mfr_id  = (int)($_GET['mfr']  ?? 0);
$ammo_id = (int)($_GET['ammo'] ?? 0);

// ── Sorting ─────────────────────────────────────────────────────
// Whitelist: maps GET param value → actual SQL column (never interpolate raw input)
$allowed_sort = [
    'name'           => 'f.name',
    'type'           => 'ft.type',
    'rate_of_fire'   => 'f.rate_of_fire',
    'capacity'       => 'f.capacity',
    'effective_range'=> 'f.effective_range',
    'weight'         => 'f.weight',
];
$sort_col = $_GET['sort'] ?? 'name';
$sort_dir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

if (!array_key_exists($sort_col, $allowed_sort)) {
    $sort_col = 'name';
}
$order_sql = $allowed_sort[$sort_col] . ' ' . $sort_dir;

// Helper: build a sort URL keeping all current filters intact
function sort_url(string $col): string {
    global $sort_col, $sort_dir, $search, $type_id, $mode_id, $mfr_id, $ammo_id;
    $new_dir = ($sort_col === $col && $sort_dir === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query([
        'q'    => $search,
        'type' => $type_id,
        'mode' => $mode_id,
        'mfr'  => $mfr_id,
        'ammo' => $ammo_id,
        'sort' => $col,
        'dir'  => $new_dir,
    ]);
}

// Helper: render the sort arrow icon in a column header
function sort_icon(string $col): string {
    global $sort_col, $sort_dir;
    if ($sort_col !== $col) {
        return '<i class="bi bi-arrow-down-up text-secondary ms-1" style="font-size:.75rem;"></i>';
    }
    return $sort_dir === 'ASC'
        ? '<i class="bi bi-sort-up text-danger ms-1"></i>'
        : '<i class="bi bi-sort-down text-danger ms-1"></i>';
}

// ── Populate filter dropdowns ───────────────────────────────────
$types         = $conn->query("SELECT id, type FROM firearm_type ORDER BY type");
$modes         = $conn->query("SELECT id, mode FROM fire_mode ORDER BY mode");
$manufacturers = $conn->query("SELECT id, name FROM manufacturer ORDER BY name");
$ammos         = $conn->query("SELECT id, CONCAT(calibre, ' ', type) AS label FROM ammo ORDER BY calibre");

// ── Build main query ─────────────────────────────────────────────
$sql = "
    SELECT DISTINCT
        f.id, f.name, f.rate_of_fire, f.capacity,
        f.effective_range, f.weight,
        ft.type AS firearm_type,
        (SELECT p.img_path
         FROM firearm_picture fp
         JOIN picture p ON fp.picture_id = p.id
         WHERE fp.firearm_id = f.id
         ORDER BY fp.id
         LIMIT 1) AS img_path
    FROM firearm f
    JOIN  firearm_type ft          ON f.firearm_type_id = ft.id
    LEFT JOIN firearm_fire_mode ffm   ON f.id = ffm.firearm_id
    LEFT JOIN firearm_manufacturer fm ON f.id = fm.firearm_id
    LEFT JOIN firearm_ammo fa         ON f.id = fa.firearm_id
    WHERE f.name LIKE ?
      AND (? = 0 OR ft.id = ?)
      AND (? = 0 OR ffm.fire_mode_id = ?)
      AND (? = 0 OR fm.manufacturer_id = ?)
      AND (? = 0 OR fa.ammo_id = ?)
    ORDER BY {$order_sql}
";

$stmt = $conn->prepare($sql);
$like = '%' . $search . '%';
$stmt->bind_param(
    'siiiiiiii',
    $like,
    $type_id, $type_id,
    $mode_id, $mode_id,
    $mfr_id,  $mfr_id,
    $ammo_id, $ammo_id
);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1 class="page-title"><i class="bi bi-gun"></i> Firearms</h1>

<!-- ── Filter bar ── -->
<form method="get" class="filter-bar">
    <!-- Preserve current sort in the filter form so it survives a Search click -->
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_col) ?>">
    <input type="hidden" name="dir"  value="<?= htmlspecialchars($sort_dir) ?>">

    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label mb-1 fw-semibold">Search</label>
            <input type="text" name="q" class="form-control"
                   placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1 fw-semibold">Type</label>
            <select name="type" class="form-select filter-select">
                <option value="0">All Types</option>
                <?php while ($row = $types->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"
                        <?= $type_id == $row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['type']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1 fw-semibold">Fire Mode</label>
            <select name="mode" class="form-select filter-select">
                <option value="0">All Modes</option>
                <?php while ($row = $modes->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"
                        <?= $mode_id == $row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['mode']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1 fw-semibold">Manufacturer</label>
            <select name="mfr" class="form-select filter-select">
                <option value="0">All</option>
                <?php while ($row = $manufacturers->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"
                        <?= $mfr_id == $row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1 fw-semibold">Ammo</label>
            <select name="ammo" class="form-select filter-select">
                <option value="0">All Ammo</option>
                <?php while ($row = $ammos->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"
                        <?= $ammo_id == $row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['label']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-danger">Search</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<!-- ── Results count ── -->
<p class="text-muted mb-3">
    <?= $result->num_rows ?> firearm<?= $result->num_rows !== 1 ? 's' : '' ?> found
</p>

<!-- ── Results table ── -->
<?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <i class="bi bi-search"></i>
        No firearms match your filters.
    </div>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th style="width:60px;"></th>
            <th>
                <a href="<?= sort_url('name') ?>" class="text-white text-decoration-none">
                    Name <?= sort_icon('name') ?>
                </a>
            </th>
            <th>
                <a href="<?= sort_url('type') ?>" class="text-white text-decoration-none">
                    Type <?= sort_icon('type') ?>
                </a>
            </th>
            <th>
                <a href="<?= sort_url('rate_of_fire') ?>" class="text-white text-decoration-none">
                    Rate of Fire <?= sort_icon('rate_of_fire') ?>
                </a>
            </th>
            <th>
                <a href="<?= sort_url('capacity') ?>" class="text-white text-decoration-none">
                    Capacity <?= sort_icon('capacity') ?>
                </a>
            </th>
            <th>
                <a href="<?= sort_url('effective_range') ?>" class="text-white text-decoration-none">
                    Eff. Range <?= sort_icon('effective_range') ?>
                </a>
            </th>
            <th>
                <a href="<?= sort_url('weight') ?>" class="text-white text-decoration-none">
                    Weight <?= sort_icon('weight') ?>
                </a>
            </th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php while ($fw = $result->fetch_assoc()): ?>
        <tr>
            <td>
                <?php if ($fw['img_path'] && file_exists($fw['img_path'])): ?>
                    <img src="<?= htmlspecialchars($fw['img_path']) ?>"
                         style="width:50px;height:40px;object-fit:cover;border-radius:4px;"
                         alt="<?= htmlspecialchars($fw['name']) ?>">
                <?php else: ?>
                    <div class="bg-secondary bg-opacity-10 d-flex align-items-center
                                justify-content-center rounded"
                         style="width:50px;height:40px;">
                        <i class="bi bi-image text-secondary"></i>
                    </div>
                <?php endif; ?>
            </td>
            <td class="fw-semibold"><?= htmlspecialchars($fw['name']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($fw['firearm_type']) ?></span></td>
            <td class="text-muted">
                <?= $fw['rate_of_fire'] ? $fw['rate_of_fire'] . ' rpm' : '—' ?>
            </td>
            <td class="text-muted"><?= $fw['capacity'] ?> rds</td>
            <td class="text-muted"><?= $fw['effective_range'] ?> m</td>
            <td class="text-muted"><?= $fw['weight'] ?> kg</td>
            <td>
                <a href="firearm.php?id=<?= $fw['id'] ?>"
                   class="btn btn-sm btn-outline-danger">Details</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
