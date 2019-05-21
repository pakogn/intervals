<?php

require __DIR__.'/../../bootstrap/app.php';

use App\Service\Intervals\IntervalsManager;
use Carbon\Carbon;
use Valitron\Validator;

session_start();

$intervals = IntervalsManager::all();

// We need to know if the given ID belongs to a resource that exists.
if (isset($_GET['id'])) {
    $submitButtonText = 'Update';
    $intervalToEdit = $intervals->where('id', $_GET['id'])->first();

    if (is_null($intervalToEdit)) {
        echo 'Resource Not Found';
        die;
    }
} else {
    $submitButtonText = 'Save';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    if (isset($data['_method']) && ($data['_method'] === 'DELETE')) {
        if (!isset($_GET['id'])) {
            echo 'Resource identifier required.';
            die;
        }

        IntervalsManager::delete($_GET['id']);
        $_SESSION['status'] = 'Interval deleted successfuly.';

        header('Location: /intervals');
        die;
    }

    // We need to validate the given data to have the necessary data to manage an interval.
    $validator = new Validator($data);
    $validator->rule('required', ['date_start', 'date_end', 'price']);
    $validator->rule('numeric', 'price');
    $validator->rule('date', ['date_start', 'date_end']);
    if (isset($data['date_start'])) {
        $validator->rule('dateAfter', 'date_end', Carbon::parse($data['date_start'])->subDay()->toDateString());
    }
    // If we are going to edit, We need to be sure that the user choosed a valid date.
    if (isset($_GET['id'])) {
        $validator->rule('dateAfter', 'date_start', $intervalToEdit->date_start->copy()->subDay());
        $validator->rule('dateBefore', 'date_end', $intervalToEdit->date_end->copy()->addDay());
    }

    // If the validator passes We need to handle the request, if not We share the errors.
    if ($validator->validate()) {
        if (!isset($data['_method'])) {
            IntervalsManager::create($data);
            $_SESSION['status'] = 'Interval created successfuly.';
        } else if ($data['_method'] === 'PATCH') {
            if (!isset($_GET['id'])) {
                echo 'Resource identifier required.';
                die;
            }

            unset($data['_method']);
            IntervalsManager::update($_GET['id'], $data);
            $_SESSION['status'] = 'Interval updated successfuly.';
        }

        header('Location: /intervals');
        die;
    } else {
        $errors = $validator->errors();
    }
}

// If We have flashed status to the session We need to manage it.
if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];
    unset($_SESSION['status']);
}
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="A simple solution for managing intervals.">
        <meta name="author" content="Francisco Daniel">

        <title>Intervals Manager</title>

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
                        <form action="/intervals/flush.php" method="POST" onsubmit="return confirm('Are you sure you want to flush the intervals?');">
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
                    <?php if (isset($intervalToEdit)): ?>
                        <form action="/intervals/index.php?id=<?php echo $intervalToEdit->id ?>" method="POST" autocomplete="off">
                        <input type="hidden" name="_method" value="PATCH">
                    <?php else: ?>
                        <form action="" method="POST" autocomplete="off">
                    <?php endif?>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="date_start">Date Start</label>
                                <input min="<?php echo isset($intervalToEdit) ? $intervalToEdit->date_start->toDateString() : null ?>"
                                       max="<?php echo isset($intervalToEdit) ? $intervalToEdit->date_end->toDateString() : null ?>"
                                       type="date"
                                       class="form-control <?php echo isset($errors['date_start']) ? 'is-invalid' : null ?>"
                                       value="<?php echo $data['date_start'] ?? (isset($intervalToEdit) ? $intervalToEdit->date_start->toDateString() : null) ?>"
                                       name="date_start"
                                       id="date_start"
                                       required>
                                <?php if (isset($errors['date_start'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['date_start'][0] ?>
                                    </div>
                                <?php endif?>
                            </div>
                            <div class="col-md-4">
                                <label for="date_end">Date End</label>
                                <input min="<?php echo isset($intervalToEdit) ? $intervalToEdit->date_start->toDateString() : null ?>"
                                       max="<?php echo isset($intervalToEdit) ? $intervalToEdit->date_end->toDateString() : null ?>"
                                       type="date"
                                       class="form-control <?php echo isset($errors['date_end']) ? 'is-invalid' : null ?>"
                                       value="<?php echo $data['date_end'] ?? (isset($intervalToEdit) ? $intervalToEdit->date_end->toDateString() : null) ?>"
                                       name="date_end"
                                       id="date_end"
                                       required>
                                <?php if (isset($errors['date_end'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['date_end'][0] ?>
                                    </div>
                                <?php endif?>
                            </div>
                            <div class="col-md-2">
                                <label for="price">Price</label>
                                <input type="text"
                                       class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : null ?>"
                                       value="<?php echo $data['price'] ?? ($intervalToEdit->price ?? null) ?>"
                                       name="price"
                                       id="price"
                                       required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['price'][0] ?>
                                    </div>
                                <?php endif?>
                            </div>
                            <div class="col-md-2">
                                <br>
                                <button class="btn btn-primary mt-2" type="submit"><?php echo $submitButtonText ?></button>
                                <a href="/intervals/index.php">Cancel</a>
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
                                    <th>Date Start</th>
                                    <th>Date End</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </thead>
                                <tbody>
                                    <?php foreach ($intervals as $interval): ?>
                                        <tr>
                                            <td><?php echo $interval->date_start->toDateString() ?></td>
                                            <td><?php echo $interval->date_end->toDateString() ?></td>
                                            <td class="text-right"><?php echo $interval->price ?></td>
                                            <td>
                                                <a href="/intervals/index.php?id=<?php echo $interval->id ?>" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                                <form action="/intervals/index.php?id=<?php echo $interval->id ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure to delete this interval?');">
                                                    <input type="hidden" name="_method" value="DELETE">
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
        <script type="text/javascript">
            Intervals.initialize();
        </script>
    </body>
</html>
