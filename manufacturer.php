<?php
require 'db.php';
require 'includes/header.php';

// ── Query: each manufacturer + count of firearms ─────────────────
$result = $conn->query("
    SELECT m.id, m.name, m.img_path,
           COUNT(fm.firearm_id) AS firearm_count
    FROM manufacturer m
    LEFT JOIN firearm_manufacturer fm ON m.id = fm.manufacturer_id
    GROUP BY m.id
    ORDER BY m.name
");
?>

<h1 class="page-title"><i class="bi bi-building"></i> Manufacturers</h1>

<?php if ($result->num_rows === 0): ?>
    <div class="empty-state">
        <i class="bi bi-building"></i>
        No manufacturers found.
    </div>
<?php else: ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php while ($m = $result->fetch_assoc()): ?>
    <div class="col">
        <div class="card h-100 text-center p-3 firearm-card">
            <?php if ($m['img_path'] && file_exists($m['img_path'])): ?>
                <img src="<?= htmlspecialchars($m['img_path']) ?>"
                     class="mx-auto mb-3" style="height:80px;object-fit:contain;"
                     alt="<?= htmlspecialchars($m['name']) ?>">
            <?php else: ?>
                <i class="bi bi-building fs-1 text-secondary mb-3"></i>
            <?php endif; ?>
            <h5 class="card-title mb-1"><?= htmlspecialchars($m['name']) ?></h5>
            <p class="text-muted small mb-3"><?= $m['firearm_count'] ?> firearm<?= $m['firearm_count'] !== 1 ? 's' : '' ?></p>
            <a href="index.php?mfr=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger">
                View Firearms
            </a>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
