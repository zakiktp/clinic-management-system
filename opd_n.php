<?php
require_once __DIR__ . '/core/init.php';
requireRole(['admin','reception']);

// Fetch unique names
$nameResult = $conn->query("SELECT DISTINCT name FROM opd_n ORDER BY name");

// Fetch unique addresses
$addressResult = $conn->query("SELECT DISTINCT address FROM opd_n ORDER BY address");

// Handle form submit
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    // Convert name and address to uppercase
    $name = strtoupper($_POST['name']);
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $address = strtoupper($_POST['address']);
    $fee = $_POST['fee'];

    $stmt = $conn->prepare("INSERT INTO opd_n (name, age, sex, address, fee) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sissd", $name, $age, $sex, $address, $fee);

    if($stmt->execute()){
        header("Location: opd_n.php?msg=added");
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Fetch all OPD records for the list
$opdList = $conn->query("SELECT * FROM opd_n ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>OPD Entry Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f4f8;
            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 500px;
            background: #d6e7da;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            margin: 50px auto;
        }

        .form-container h3 {
            color: #0b6fa4;
            text-align: center;
            margin-bottom: 25px;
        }

        .btn-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        input.text-uppercase {
            text-transform: uppercase;
        }

        label {
            font-weight: 500;
        }

        .form-control {
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #0b6fa4;
            border: none;
        }

        .btn-primary:hover {
            background: #095a87;
        }

        .datalist-option {
            text-transform: uppercase;
        }

    </style>
</head>

<body>

<div class="form-container">

    <!-- Buttons Left and Right -->
    <div class="btn-row">
        <a href="index.php" class="btn btn-secondary btn-sm">Dashboard</a>
        <a href="opd_list.php" class="btn btn-secondary btn-sm">OPD List</a>
    </div>

    <h3 style="font-size: 1.2rem; color: #0e8a3e;">OPD Entry Form</h3>

    <form method="POST">

        <!-- Name -->
        <div class="mb-3">
            <label>Name</label>
            <input list="nameList" name="name" class="form-control text-uppercase form-control-sm" required>
            <datalist id="nameList">
                <?php while($row = $nameResult->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['name']) ?>">
                <?php endwhile; ?>
            </datalist>
        </div>

        <!-- Age -->
        <div class="mb-3">
            <label>Age</label>
            <input type="number" name="age" class="form-control form-control-sm" required>
        </div>

        <!-- Sex -->
        <div class="mb-3">
            <label>Sex</label>
            <select name="sex" class="form-select form-select-sm" required>
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>

        <!-- Address -->
        <div class="mb-3">
            <label>Address</label>
            <input list="addressList" name="address" class="form-control text-uppercase form-control-sm" required>
            <datalist id="addressList">
                <?php while($row = $addressResult->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['address']) ?>">
                <?php endwhile; ?>
            </datalist>
        </div>

        <!-- Fee -->
        <div class="mb-3">
            <label>Fee</label>
            <input type="number" step="0.01" name="fee" class="form-control form-control-sm" required>
        </div>

        <!-- Visit Date -->
        <div class="mb-3">
            <label>Date & Time</label>
            <input type="text" class="form-control form-control-sm" value="<?= date('Y-m-d H:i:s') ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-sm">Save</button>
    </form>
</div>

</body>
</html>