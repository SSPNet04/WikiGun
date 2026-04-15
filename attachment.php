<?php
require 'db.php';
require 'includes/header.php';

// ── Filter ───────────────────────────────────────────────────────
$f_type = trim($_GET['type'] ?? '');

// Dropdown: distinct attachment types
$types = $conn->query("SELECT DISTINCT type FROM attachment ORDER BY type");

// ── Query ─────────────────────────────────────────────────────────
$sql = "
    SELECT a.id, a.name, a.type, a.img_path,
           GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') AS firearms
    FROM attachment a
    LEFT JOIN firearm_attachment fa ON a.id = fa.attachment_id
    LEFT JOIN firearm f             ON fa.firearm_id = f.id
    WHERE (? = '' OR a.type = ?)
    GROUP BY a.id
    ORDER BY a.type, a.name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $f_type, $f_type);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1 class="page-title"><i class="bi bi-tools"></i> Attachments</h1>

<!-- Filter bar -->
<form method="get" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label mb-1 fw-semibold">Type</label>
            <select name="type" class="form-select filter-select">
                <option value="">All Types</option>
                <?php while ($r = $types->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($r['type']) ?>"
                        <?= $f_type === $r['type'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['type']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-danger">Filter</button>
            <a href="attachment.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<p class="text-muted mb-3"><?= $result->num_rows ?> attachment<?= $result->num_rows !== 1 ? 's' : '' ?></p>

<?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <i class="bi bi-tools"></i>
        No attachments match your filter.
    </div>
<?php else: ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php while ($att = $result->fetch_assoc()): ?>
    <div class="col">
        <div class="card h-100 text-center p-3 firearm-card">
            <?= img_or_icon($att['img_path'], 'tools', $att['name'], 'height:70px;object-fit:contain;') ?>
            <span class="badge bg-secondary mb-1"><?= htmlspecialchars($att['type']) ?></span>
            <h6 class="mb-1"><?= htmlspecialchars($att['name']) ?></h6>
            <?php if ($att['firearms']): ?>
                <p class="text-muted" style="font-size:.72rem;">
                    Compatible: <?= htmlspecialchars($att['firearms']) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
