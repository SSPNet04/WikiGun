<?php
require __DIR__ . '/../db.php';

function render_simple_tab(
    mysqli $conn, string $tab_key, string $table,
    string $field, string $label, mysqli_result $list,
    string $edit_entity, int $edit_id, array $edit
): void {
    // $table is the entity key used by action.php and $map; $tab_key is only the URL tab param
    $is_editing = ($edit_entity === $table && !empty($edit));
    echo "<h4 class='mb-3'>{$label}</h4>";

    if ($is_editing) {
        echo '<div class="edit-banner mb-3"><i class="bi bi-pencil-square"></i> Editing: <strong>'
            . htmlspecialchars($edit[$field]) . '</strong></div>';
        ?>
        <form method="post" action="action.php" class="bg-white p-3 rounded shadow-sm mb-4">
            <input type="hidden" name="entity" value="<?= $table ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id"     value="<?= $edit_id ?>">
            <input type="hidden" name="tab"    value="<?= $tab_key ?>">
            <div class="row g-2 align-items-end">
                <div class="col-sm-6">
                    <input type="text" name="value" class="form-control" required
                           value="<?= htmlspecialchars($edit[$field]) ?>">
                </div>
                <div class="col-sm-auto d-flex gap-2">
                    <button class="btn btn-primary">Save</button>
                    <a href="?tab=<?= $tab_key ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
        <?php
    }

    echo '<div class="table-responsive">';
    echo '<table class="table table-hover bg-white rounded shadow-sm">';
    echo '<thead class="table-dark"><tr><th>'.ucfirst($field).'</th><th style="width:130px;"></th></tr></thead><tbody>';
    while ($row = $list->fetch_assoc()) {
        $safe     = htmlspecialchars($row[$field]);
        $safe_del = htmlspecialchars(addslashes($row[$field]));
        echo "<tr><td>{$safe}</td><td>";
        echo "<a href='?tab={$tab_key}&edit_entity={$table}&edit_id={$row['id']}' class='btn btn-sm btn-outline-primary'><i class='bi bi-pencil'></i></a> ";
        echo "<form method='post' action='action.php' class='d-inline' onsubmit=\"return confirm('Delete {$safe_del}?')\">";
        echo "<input type='hidden' name='entity' value='{$table}'>";
        echo "<input type='hidden' name='action' value='delete'>";
        echo "<input type='hidden' name='id'     value='{$row['id']}'>";
        echo "<input type='hidden' name='tab'    value='{$tab_key}'>";
        echo "<button class='btn btn-sm btn-outline-danger'><i class='bi bi-trash'></i></button></form>";
        echo "</td></tr>";
    }
    echo '</tbody></table></div>';

    echo '<div class="bg-white p-3 rounded shadow-sm mt-3"><h6 class="mb-3">Add</h6>';
    echo "<form method='post' action='action.php'>";
    echo "<input type='hidden' name='entity' value='{$table}'>";
    echo "<input type='hidden' name='action' value='add'>";
    echo "<input type='hidden' name='tab'    value='{$tab_key}'>";
    echo '<div class="row g-2 align-items-end">';
    echo '<div class="col-sm-6"><input type="text" name="value" class="form-control" required placeholder="'.strip_tags($label).'..."></div>';
    echo '<div class="col-sm-auto"><button class="btn btn-danger"><i class="bi bi-plus-lg"></i> Add</button></div>';
    echo '</div></form></div>';
}

$tab         = $_GET['tab']         ?? 'firearms';
$edit_entity = $_GET['edit_entity'] ?? '';
$edit_id     = (int)($_GET['edit_id'] ?? 0);

// ── Fetch edit record for pre-filling forms ───────────────────────
$edit = [];
if ($edit_id > 0 && $edit_entity !== '') {
    $map = [
        'firearm_type' => ['table'=>'firearm_type', 'fields'=>['id','type']],
        'fire_mode'    => ['table'=>'fire_mode',    'fields'=>['id','mode']],
        'brand'        => ['table'=>'brand',        'fields'=>['id','brand']],
        'ammo'         => ['table'=>'ammo',         'fields'=>['id','calibre','type']],
        'manufacturer' => ['table'=>'manufacturer', 'fields'=>['id','name','img_path']],
        'attachment'   => ['table'=>'attachment',   'fields'=>['id','name','type','img_path']],
    ];
    if (isset($map[$edit_entity])) {
        $tbl = $map[$edit_entity]['table'];
        $s = $conn->prepare("SELECT * FROM `{$tbl}` WHERE id=?");
        $s->bind_param('i', $edit_id);
        $s->execute();
        $res  = $s->get_result();
        $edit = $res->fetch_assoc() ?? [];
        $res->free();
        $s->close();
    }
}

// Ammo edit: also fetch selected brands
$edit_ammo_brands = [];
if ($edit_entity === 'ammo' && $edit_id > 0) {
    $s = $conn->prepare("SELECT brand_id FROM ammo_brand WHERE ammo_id=?");
    $s->bind_param('i', $edit_id);
    $s->execute();
    $res = $s->get_result();
    while ($r = $res->fetch_row()) $edit_ammo_brands[] = $r[0];
    $res->free();
    $s->close();
}

// ── Data for each tab ─────────────────────────────────────────────
$firearms     = $conn->query("
    SELECT f.id, f.name, ft.type AS ftype, f.capacity, f.weight
    FROM firearm f JOIN firearm_type ft ON f.firearm_type_id=ft.id ORDER BY f.name");
$ft_list      = $conn->query("SELECT id, type  FROM firearm_type  ORDER BY type");
$fm_list      = $conn->query("SELECT id, mode  FROM fire_mode     ORDER BY mode");
$brand_list   = $conn->query("SELECT id, brand FROM brand         ORDER BY brand");
$ammo_list    = $conn->query("
    SELECT a.id, a.calibre, a.type,
           GROUP_CONCAT(b.brand ORDER BY b.brand SEPARATOR ', ') AS brands
    FROM ammo a
    LEFT JOIN ammo_brand ab ON a.id=ab.ammo_id
    LEFT JOIN brand b ON ab.brand_id=b.id
    GROUP BY a.id ORDER BY a.calibre");
$mfr_list     = $conn->query("SELECT id, name, img_path FROM manufacturer ORDER BY name");
$att_list     = $conn->query("SELECT id, name, type, img_path FROM attachment ORDER BY type, name");
$all_brands   = $conn->query("SELECT id, brand FROM brand ORDER BY brand");

$tabs = [
    'firearms'     => ['icon'=>'bi-gun',        'label'=>'Firearms'],
    'ammo'         => ['icon'=>'bi-circle',      'label'=>'Ammo'],
    'firearm_types'=> ['icon'=>'bi-tag',         'label'=>'Firearm Types'],
    'fire_modes'   => ['icon'=>'bi-toggles',     'label'=>'Fire Modes'],
    'brands'       => ['icon'=>'bi-bookmark',    'label'=>'Brands'],
    'manufacturers'=> ['icon'=>'bi-building',    'label'=>'Manufacturers'],
    'attachments'  => ['icon'=>'bi-tools',       'label'=>'Attachments'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — WikiGun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f5f5f5; }
        .admin-nav { background:#1a1a2e; }
        .tab-btn { white-space:nowrap; }
        .table td, .table th { vertical-align:middle; }
        .edit-banner { background:#fff3cd; border-left:4px solid #ffc107; padding:.6rem 1rem; border-radius:4px; margin-bottom:1rem; }
    </style>
</head>
<body>

<!-- Top bar -->
<nav class="navbar admin-nav navbar-dark px-3 mb-0">
    <span class="navbar-brand fw-bold"><i class="bi bi-gear-fill"></i> WikiGun Admin</span>
    <a href="../index.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-box-arrow-up-left"></i> View Site
    </a>
</nav>

<!-- Tab strip -->
<div class="bg-dark px-3 pb-0 pt-1 d-flex gap-1 flex-wrap border-bottom border-secondary">
    <?php foreach ($tabs as $key => $t): ?>
    <a href="?tab=<?= $key ?>"
       class="btn btn-sm tab-btn mb-1 <?= $tab === $key ? 'btn-danger' : 'btn-outline-secondary text-white' ?>">
        <i class="bi <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="container-fluid px-4 py-4" style="max-width:1100px;">

<?php /* ═══════════════════════ FIREARMS TAB ═══════════════════════ */ ?>
<?php if ($tab === 'firearms'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-gun"></i> Firearms</h4>
    <a href="firearm_form.php" class="btn btn-danger btn-sm">
        <i class="bi bi-plus-lg"></i> Add Firearm
    </a>
</div>
<div class="table-responsive">
<table class="table table-hover bg-white rounded shadow-sm">
    <thead class="table-dark">
        <tr><th>Name</th><th>Type</th><th>Capacity</th><th>Weight</th><th style="width:130px;"></th></tr>
    </thead>
    <tbody>
    <?php while ($fw = $firearms->fetch_assoc()): ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($fw['name']) ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($fw['ftype']) ?></span></td>
        <td><?= $fw['capacity'] ?> rds</td>
        <td><?= $fw['weight'] ?> kg</td>
        <td>
            <a href="firearm_form.php?id=<?= $fw['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="post" action="action.php" class="d-inline"
                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($fw['name'])) ?>?')">
                <input type="hidden" name="entity" value="firearm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $fw['id'] ?>">
                <input type="hidden" name="tab"    value="firearms">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<?php /* ═══════════════════════ AMMO TAB ═══════════════════════ */ ?>
<?php elseif ($tab === 'ammo'): ?>
<h4 class="mb-3"><i class="bi bi-circle"></i> Ammo</h4>

<?php if ($edit_entity === 'ammo' && !empty($edit)): ?>
<div class="edit-banner mb-3"><i class="bi bi-pencil-square"></i> Editing: <strong><?= htmlspecialchars($edit['calibre'].' '.$edit['type']) ?></strong></div>
<form method="post" action="action.php" class="bg-white p-3 rounded shadow-sm mb-4">
    <input type="hidden" name="entity" value="ammo">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id"     value="<?= $edit_id ?>">
    <input type="hidden" name="tab"    value="ammo">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3"><label class="form-label">Calibre</label>
            <input type="text" name="calibre" class="form-control" required value="<?= htmlspecialchars($edit['calibre']) ?>"></div>
        <div class="col-sm-3"><label class="form-label">Type</label>
            <input type="text" name="type" class="form-control" required value="<?= htmlspecialchars($edit['type']) ?>"></div>
        <div class="col-sm-4">
            <label class="form-label">Brands</label>
            <div class="d-flex flex-wrap gap-2">
            <?php $all_brands->data_seek(0); while ($b = $all_brands->fetch_assoc()): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="brands[]"
                           value="<?= $b['id'] ?>" id="eb_<?= $b['id'] ?>"
                           <?= in_array($b['id'], $edit_ammo_brands) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="eb_<?= $b['id'] ?>"><?= htmlspecialchars($b['brand']) ?></label>
                </div>
            <?php endwhile; ?>
            </div>
        </div>
        <div class="col-sm-auto d-flex gap-2">
            <button class="btn btn-primary">Save</button>
            <a href="?tab=ammo" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover bg-white rounded shadow-sm">
    <thead class="table-dark"><tr><th>Calibre</th><th>Type</th><th>Brands</th><th style="width:130px;"></th></tr></thead>
    <tbody>
    <?php while ($a = $ammo_list->fetch_assoc()): ?>
    <tr>
        <td><strong><?= htmlspecialchars($a['calibre']) ?></strong></td>
        <td><?= htmlspecialchars($a['type']) ?></td>
        <td class="text-muted small"><?= htmlspecialchars($a['brands'] ?? '—') ?></td>
        <td>
            <a href="?tab=ammo&edit_entity=ammo&edit_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="post" action="action.php" class="d-inline"
                  onsubmit="return confirm('Delete this ammo?')">
                <input type="hidden" name="entity" value="ammo">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $a['id'] ?>">
                <input type="hidden" name="tab"    value="ammo">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<div class="bg-white p-3 rounded shadow-sm mt-3">
    <h6 class="mb-3">Add Ammo</h6>
    <form method="post" action="action.php">
        <input type="hidden" name="entity" value="ammo">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="tab"    value="ammo">
        <div class="row g-2 align-items-end">
            <div class="col-sm-3"><label class="form-label">Calibre</label>
                <input type="text" name="calibre" class="form-control" required></div>
            <div class="col-sm-3"><label class="form-label">Type</label>
                <input type="text" name="type" class="form-control" required></div>
            <div class="col-sm-4">
                <label class="form-label">Brands</label>
                <div class="d-flex flex-wrap gap-2">
                <?php $all_brands->data_seek(0); while ($b = $all_brands->fetch_assoc()): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brands[]"
                               value="<?= $b['id'] ?>" id="ab_<?= $b['id'] ?>">
                        <label class="form-check-label" for="ab_<?= $b['id'] ?>"><?= htmlspecialchars($b['brand']) ?></label>
                    </div>
                <?php endwhile; ?>
                </div>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-danger"><i class="bi bi-plus-lg"></i> Add</button>
            </div>
        </div>
    </form>
</div>

<?php /* ═══════════════ SIMPLE LOOKUP TABS ═══════════════ */ ?>

<?php elseif ($tab === 'firearm_types'): ?>
<?php render_simple_tab($conn,'firearm_types','firearm_type','type','<i class="bi bi-tag"></i> Firearm Types',$ft_list,$edit_entity,$edit_id,$edit); ?>

<?php elseif ($tab === 'fire_modes'): ?>
<?php render_simple_tab($conn,'fire_modes','fire_mode','mode','<i class="bi bi-toggles"></i> Fire Modes',$fm_list,$edit_entity,$edit_id,$edit); ?>

<?php elseif ($tab === 'brands'): ?>
<?php render_simple_tab($conn,'brands','brand','brand','<i class="bi bi-bookmark"></i> Brands',$brand_list,$edit_entity,$edit_id,$edit); ?>

<?php /* ═══════════════════════ MANUFACTURERS TAB ═══════════════════════ */ ?>
<?php elseif ($tab === 'manufacturers'): ?>
<h4 class="mb-3"><i class="bi bi-building"></i> Manufacturers</h4>

<?php if ($edit_entity === 'manufacturer' && !empty($edit)): ?>
<div class="edit-banner mb-3"><i class="bi bi-pencil-square"></i> Editing: <strong><?= htmlspecialchars($edit['name']) ?></strong></div>
<form method="post" action="action.php" enctype="multipart/form-data" class="bg-white p-3 rounded shadow-sm mb-4">
    <input type="hidden" name="entity"       value="manufacturer">
    <input type="hidden" name="action"       value="edit">
    <input type="hidden" name="id"           value="<?= $edit_id ?>">
    <input type="hidden" name="tab"          value="manufacturers">
    <input type="hidden" name="existing_img" value="<?= htmlspecialchars($edit['img_path'] ?? '') ?>">
    <div class="row g-3 align-items-start">
        <div class="col-sm-4"><label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit['name']) ?>"></div>
        <div class="col-sm-5">
            <label class="form-label">Image</label>
            <?php if (!empty($edit['img_path'])): ?>
            <div class="mb-1">
                <img src="../<?= htmlspecialchars($edit['img_path']) ?>"
                     style="height:50px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;padding:2px;"
                     onerror="this.style.display='none'">
                <small class="text-muted ms-2"><?= htmlspecialchars(basename($edit['img_path'])) ?></small>
            </div>
            <?php endif; ?>
            <input type="file" name="img_file" class="form-control" accept="image/*">
            <div class="form-text">Leave blank to keep current image.</div>
        </div>
        <div class="col-sm-auto d-flex gap-2 align-items-end" style="padding-top:1.85rem;">
            <button class="btn btn-primary">Save</button>
            <a href="?tab=manufacturers" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover bg-white rounded shadow-sm">
    <thead class="table-dark"><tr><th>Name</th><th>Image</th><th style="width:130px;"></th></tr></thead>
    <tbody>
    <?php while ($m = $mfr_list->fetch_assoc()): ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($m['name']) ?></td>
        <td>
            <?php if (!empty($m['img_path'])): ?>
            <img src="../<?= htmlspecialchars($m['img_path']) ?>"
                 style="height:36px;object-fit:contain;border-radius:3px;"
                 onerror="this.style.display='none'">
            <?php else: ?>
            <span class="text-muted small">—</span>
            <?php endif; ?>
        </td>
        <td>
            <a href="?tab=manufacturers&edit_entity=manufacturer&edit_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="post" action="action.php" class="d-inline"
                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($m['name'])) ?>?')">
                <input type="hidden" name="entity" value="manufacturer">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $m['id'] ?>">
                <input type="hidden" name="tab"    value="manufacturers">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<div class="bg-white p-3 rounded shadow-sm mt-3">
    <h6 class="mb-3">Add Manufacturer</h6>
    <form method="post" action="action.php" enctype="multipart/form-data">
        <input type="hidden" name="entity" value="manufacturer">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="tab"    value="manufacturers">
        <div class="row g-2 align-items-end">
            <div class="col-sm-4"><label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required></div>
            <div class="col-sm-5"><label class="form-label">Image</label>
                <input type="file" name="img_file" class="form-control" accept="image/*"></div>
            <div class="col-sm-auto">
                <button class="btn btn-danger"><i class="bi bi-plus-lg"></i> Add</button>
            </div>
        </div>
    </form>
</div>

<?php /* ═══════════════════════ ATTACHMENTS TAB ═══════════════════════ */ ?>
<?php elseif ($tab === 'attachments'): ?>
<h4 class="mb-3"><i class="bi bi-tools"></i> Attachments</h4>

<?php if ($edit_entity === 'attachment' && !empty($edit)): ?>
<div class="edit-banner mb-3"><i class="bi bi-pencil-square"></i> Editing: <strong><?= htmlspecialchars($edit['name']) ?></strong></div>
<form method="post" action="action.php" enctype="multipart/form-data" class="bg-white p-3 rounded shadow-sm mb-4">
    <input type="hidden" name="entity"       value="attachment">
    <input type="hidden" name="action"       value="edit">
    <input type="hidden" name="id"           value="<?= $edit_id ?>">
    <input type="hidden" name="tab"          value="attachments">
    <input type="hidden" name="existing_img" value="<?= htmlspecialchars($edit['img_path'] ?? '') ?>">
    <div class="row g-3 align-items-start">
        <div class="col-sm-3"><label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit['name']) ?>"></div>
        <div class="col-sm-2"><label class="form-label">Type</label>
            <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($edit['type'] ?? '') ?>"></div>
        <div class="col-sm-4">
            <label class="form-label">Image</label>
            <?php if (!empty($edit['img_path'])): ?>
            <div class="mb-1">
                <img src="../<?= htmlspecialchars($edit['img_path']) ?>"
                     style="height:50px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;padding:2px;"
                     onerror="this.style.display='none'">
                <small class="text-muted ms-2"><?= htmlspecialchars(basename($edit['img_path'])) ?></small>
            </div>
            <?php endif; ?>
            <input type="file" name="img_file" class="form-control" accept="image/*">
            <div class="form-text">Leave blank to keep current image.</div>
        </div>
        <div class="col-sm-auto d-flex gap-2 align-items-end" style="padding-top:1.85rem;">
            <button class="btn btn-primary">Save</button>
            <a href="?tab=attachments" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-hover bg-white rounded shadow-sm">
    <thead class="table-dark"><tr><th>Name</th><th>Type</th><th>Image</th><th style="width:130px;"></th></tr></thead>
    <tbody>
    <?php while ($a = $att_list->fetch_assoc()): ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($a['name']) ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['type'] ?? '') ?></span></td>
        <td>
            <?php if (!empty($a['img_path'])): ?>
            <img src="../<?= htmlspecialchars($a['img_path']) ?>"
                 style="height:36px;object-fit:contain;border-radius:3px;"
                 onerror="this.style.display='none'">
            <?php else: ?>
            <span class="text-muted small">—</span>
            <?php endif; ?>
        </td>
        <td>
            <a href="?tab=attachments&edit_entity=attachment&edit_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="post" action="action.php" class="d-inline"
                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($a['name'])) ?>?')">
                <input type="hidden" name="entity" value="attachment">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $a['id'] ?>">
                <input type="hidden" name="tab"    value="attachments">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<div class="bg-white p-3 rounded shadow-sm mt-3">
    <h6 class="mb-3">Add Attachment</h6>
    <form method="post" action="action.php" enctype="multipart/form-data">
        <input type="hidden" name="entity" value="attachment">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="tab"    value="attachments">
        <div class="row g-2 align-items-end">
            <div class="col-sm-3"><label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required></div>
            <div class="col-sm-2"><label class="form-label">Type</label>
                <input type="text" name="type" class="form-control" placeholder="Scope, Grip..."></div>
            <div class="col-sm-4"><label class="form-label">Image</label>
                <input type="file" name="img_file" class="form-control" accept="image/*"></div>
            <div class="col-sm-auto">
                <button class="btn btn-danger"><i class="bi bi-plus-lg"></i> Add</button>
            </div>
        </div>
    </form>
</div>

<?php endif; ?>
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
