<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Sesi 7 - Jerryco</title>
</head>

<body>
    <?php
    $age = 27;
    $isAdult = ($age >= 18 && $age <= 65) ? 'Ya' : 'Tidak';

    $isSenior = ($age > 65) ? 'Ya' : ($age < 18 ? 'Tidak' : 'Ya');
    echo 'Is Senior: ' . $isSenior . '<br>';

    $name = 'Jerryco';
    echo "Name is an integer: " . (is_int($name) ? 'Yes' : 'No') . '<br>';
    echo "Name is a float: " . (is_float($name) ? 'Yes' : 'No') . '<br>';
    echo "Name is a string: " . (is_string($name) ? 'Yes' : 'No') . '<br>';
    echo "Name is an array: " . (is_array($name) ? 'Yes' : 'No') . '<br>';
    echo "Name is an object: " . (is_object($name) ? 'Yes' : 'No') . '<br>';
    echo "Name is a resource: " . (is_resource($name) ? 'Yes' : 'No') . '<br>';

    echo "Name: $name<br>";
    echo 'Name: $name<br>';
    echo 'Name: ' . $name . '<br>';
    echo 'Is Adult: ' . $isAdult . '<br>';
    ?>
</body>

</html>