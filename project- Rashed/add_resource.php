<?php
// add_resource.php
require 'db.php';

// ONLY accept POST from the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: teacher_dashboard.php');  // change name if needed
    exit;
}

$title         = trim($_POST['title'] ?? '');
$resource_type = trim($_POST['resource_type'] ?? '');
$description   = trim($_POST['description'] ?? '');

if ($title === '' || $description === '' ||
    !in_array($resource_type, ['document','video','link'], true)) {

    // simple fallback – you can make a nicer error page if you want
    die('Invalid input.');
}

// Insert using PDO
$stmt = $pdo->prepare("
    INSERT INTO resources (title, resource_type, description)
    VALUES (:title, :resource_type, :description)
");

$stmt->execute([
    ':title'         => $title,
    ':resource_type' => $resource_type,
    ':description'   => $description,
]);

// After saving, go back to the dashboard
header('Location: teacher_dashboard.php'); // change to your file name
exit;
