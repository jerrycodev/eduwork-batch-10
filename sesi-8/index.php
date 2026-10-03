<?php
require __DIR__ . '/koneksi.php';

$sql = 'SELECT * FROM users';
$stmt = $pdo->prepare($sql);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Sesi 8 - Jerryco</title>
</head>
<body>
    <h1>Users</h1>
    <ul>
        <?php foreach ($result as $row): ?>
            <li>Name: <?= htmlspecialchars($row['name']) ?><br/>Email: <?= htmlspecialchars($row['email']) ?><br/>Address: <?= htmlspecialchars($row['address']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
