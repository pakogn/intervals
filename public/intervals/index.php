<?php

require __DIR__.'/../../bootstrap/app.php';

use App\Models\Interval;

$intervals = Interval::all();

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
                    <form>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="date_start">Date Start</label>
                                <input type="date" class="form-control" id="date_start" required>
                            </div>
                            <div class="col-md-4">
                                <label for="date_end">Date End</label>
                                <input type="date" class="form-control" id="date_end" required>
                            </div>
                            <div class="col-md-2">
                                <label for="price">Price</label>
                                <input type="text" class="form-control" id="price" required>
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
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead>
                                <th>Start</th>
                                <th>End</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2018-04-01</td>
                                    <td>2018-04-05</td>
                                    <td class="text-right">10.50</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></button>
                                        <form class="d-inline">
                                            <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
        <script src="/assets/jspopper.min.js"></script>
        <script src="/assets/js/bootstrap.min.js"></script>
        <script src="/assets/js/app.js"></script>
    </body>
</html>
