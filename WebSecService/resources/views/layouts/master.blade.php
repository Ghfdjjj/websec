<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Basic Website - @yield('title')</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Add CSRF token to all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            // Get the CSRF token from the meta tag
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Add the token to all forms
            document.querySelectorAll('form').forEach(form => {
                // Check if the form already has a CSRF token
                if (!form.querySelector('input[name="_token"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_token';
                    input.value = token;
                    form.appendChild(input);
                }
            });
        });

        // Function to submit the buy form
        function submitBuyForm(productId) {
            if (confirm('Are you sure you want to buy this product?')) {
                const form = document.getElementById('buy-form-' + productId);
                if (form) {
                    form.submit();
                }
            }
        }
    </script>
</head>
<body>
    @include('layouts.menu')
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
