    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Campus Navigator</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
        @routes
         <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
     <meta name="csrf-token" content="{{ csrf_token() }}">

    </head>
    <body>
        @inertia
        <div id="app">
            {{-- Your Vue components will render here --}}
        </div>
    </body>
    </html>
