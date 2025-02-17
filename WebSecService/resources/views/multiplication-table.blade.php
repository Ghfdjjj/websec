<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiplication Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Multiplication Table (1-10)</h2>
        
        @for ($i = 1; $i <= 10; $i++)
            <h3>Table of {{ $i }}</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Multiplication</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($j = 1; $j <= 10; $j++)
                        <tr>
                            <td>{{ $i }} x {{ $j }}</td>
                            <td>{{ $table[$i][$j] }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        @endfor
    </div>
</body>
</html>
