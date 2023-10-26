<?php 

use App\Email;

require_once __DIR__ . '/../vendor/autoload.php';

$message = ''; // Set a default message empty string

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {    
    // Get the form data    
    $email = $_POST['email'];        

    // Create a new Email object
    try {
        $email = Email::fromString($email);
        $message = '<p style="color: green;">Email address is valid.</p>';
    }  
    catch (InvalidArgumentException $e) {
        $message = '<p style="color: red;">' . $e->getMessage() . '</p>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Validator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<body class="d-flex justify-content-center pt-5">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">The Great Email Validator</h5>
            <p class="card-subtitle mb-2 text-body-secondary">Enter a email address to see if it is valid.</p>
            <?= $message ?>
            <form method="post">
                <label for="email" class="form-label">Email:</label>
                <input type="text" name="email" id="email" class="form-control" required><br>

                <button class="btn btn-primary" type="submit">Validate</button>
            </form>
        </div>
    </div>
</body>
</html>
