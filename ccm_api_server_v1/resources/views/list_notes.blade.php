<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Export Notes List PDF - Tutsmake.com</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <style>
        .container {
            padding: 5%;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-12">
            <a href="{{ url('pdf') }}" class="btn btn-success mb-2">Export PDF</a>
            <table class="table table-bordered" id="laravel_crud">
                <thead>
                <tr>
                    <th>IMAGE</th>
                    <th>PATIENT NAME</th>
                    <th>PATIENT UNIQUE ID</th>
                    <th>GENDER</th>
                    <th>AGE</th>
                    <th>SUMMARY</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{{ $store[0]->ProfilePicturePath }}</td>
                    <td>{{ $store[0]->FirstName . $store[0]->LastName}}</td>
                    <td>{{ $store[0]->PatientUniqueId }}</td>
                    <td>{{ $store[0]->Gender }}</td>
                    <td>{{ $store[0]->Age }}</td>
                    <td>{{ $store[0]->ProfileSummary }}</td>
                </tr>
                <tr></tr>
                </tbody>
            </table>
            <table class="table table-bordered" id="laravel_crud">
                <thead>
                <tr>
                    <th>Component</th>
                    <th>Goal</th>
                    <th>Intervention</th>
                    <th>Review</th>
                </tr>
                </thead>
                <tbody>

                <?php
                $goal = explode(",", $store[0]->Goal);
                print_r($goal);
                ?>
                <tr>
                    <td>1</td>
                    <td>Sample</td>
                    <td>Sample Description</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Sample</td>
                    <td>Sample Description</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Sample</td>
                    <td>Sample Description</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>