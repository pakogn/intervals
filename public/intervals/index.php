<?php

require __DIR__.'/../../bootstrap/app.php';

use App\Service\Intervals\IntervalsManager;
use Valitron\Validator;

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    $validator = new Validator($data);
    $validator->rule('required', ['date_start', 'date_end', 'price']);
    $validator->rule('numeric', 'price');
    $validator->rule('date', ['date_start', 'date_end']);
    if (isset($data['date_start'])) {
        $validator->rule('dateAfter', 'date_end', $data['date_start']);
    }

    if ($validator->validate()) {
        if (!isset($data['_method'])) {
            IntervalsManager::create($data);
            $_SESSION['status'] = 'Interval created successfuly.';
        } else if ($data['_method'] === 'PATCH') {
            unset($data['_method']);
            $_SESSION['status'] = 'Interval updated successfuly.';
        } else if ($data['_method'] === 'DELETE') {
            unset($data['_method']);
            $_SESSION['status'] = 'Interval deleted successfuly.';
        }

        header('Location: /intervals');
        die;
    } else {
        $errors = $validator->errors();
    }
}

if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];
    unset($_SESSION['status']);
}

$intervals = IntervalsManager::all();

?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Intervals manager</title>

        <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
        <link rel="stylesheet" href="/assets/css/app.css">
    </head>
    <body class="bg-light">
        <div class="container">
            <div class="py-5 text-center">
                <h2>Intervals Manager</h2>
                <p class="lead">A simple solution for managing intervals.</p>
            </div>
            <?php if ($intervals->isNotEmpty()): ?>
                <div class="row">
                    <div class="col-md-8 offset-md-2 mb-5">
                        <form>
                            <br>
                            <button class="btn btn-danger" type="submit"><i class="fa fa-sync"></i> Flush Database</button>
                        </form>
                    </div>
                </div>
            <?php endif?>
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <?php if (isset($status)): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <strong>Success!</strong> <?php echo $status ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif?>
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="date_start">Date Start</label>
                                <input type="date" class="form-control <?php echo isset($errors['date_start']) ? 'is-invalid' : null ?>" value="<?php echo $data['date_start'] ?? null ?>" name="date_start" id="date_start" required>
                                <?php if (isset($errors['date_start'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['date_start'][0] ?>
                                    </div>
                                <?php endif?>
                            </div>
                            <div class="col-md-4">
                                <label for="date_end">Date End</label>
                                <input type="date" class="form-control <?php echo isset($errors['date_end']) ? 'is-invalid' : null ?>" value="<?php echo $data['date_end'] ?? null ?>" name="date_end" id="date_end" required>
                                <?php if (isset($errors['date_end'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['date_end'][0] ?>
                                    </div>
                                <?php endif?>
                            </div>
                            <div class="col-md-2">
                                <label for="price">Price</label>
                                <input type="text" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : null ?>" value="<?php echo $data['price'] ?? null ?>" name="price" id="price" required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['price'][0] ?>
                                    </div>
                                <?php endif?>
                            </div>
                            <div class="col-md-2">
                                <br>
                                <button class="btn btn-primary mt-2" type="submit">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 offset-md-2 mt-5">
                    <?php if ($intervals->isEmpty()): ?>
                        There are no intervals in the system.
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </thead>
                                <tbody>
                                    <?php foreach ($intervals as $interval): ?>
                                        <tr>
                                            <td><?php echo $interval->date_start ?></td>
                                            <td><?php echo $interval->date_end ?></td>
                                            <td class="text-right"><?php echo $interval->price ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></button>
                                                <form class="d-inline">
                                                    <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif?>
                </div>
            </div>
            <footer class="my-5 pt-5 text-muted text-center text-small">
                <p class="mb-1">&copy; <?php echo Date('Y') ?> <a href="https://daniel.garcianoriega.com" target="_blank" rel="noopener">Francisco Daniel</a></p>
                <ul class="list-inline">
                    <li class="list-inline-item"><a href="https://github.com/pakogn" target="_blank" rel="noopener">GitHub</a></li>
                    <li class="list-inline-item"><a href="https://www.linkedin.com/in/franciscodaniel" target="_blank" rel="noopener">LinkedIn</a></li>
                    <li class="list-inline-item"><a href="https://github.com/pakogn/intervals" target="_blank" rel="noopener">Repository</a></li>
                </ul>
            </footer>
        </div>

        <script src="/assets/js/jquery-3.2.1.slim.min.js"></script>
        <script src="/assets/js/popper.min.js"></script>
        <script src="/assets/js/bootstrap.min.js"></script>
        <script src="/assets/js/app.js"></script>
    </body>
</html>
