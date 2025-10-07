    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laravel Vue App</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
        @routes
    </head>
    <body>
        @inertia
        <div id="app">
            {{-- Your Vue components will render here --}}
        </div>
    </body>
    </html>
