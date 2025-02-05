<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('font-awesome/css/font-awesome.min.css') }}">
    <title>@yield('title', 'Admin Panel')</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Custom styles -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>

<body>

    <main>
        <div class="main-container">
            <div class="d-flex h-100">
                @include('admin.includes.sidebar-menu')

                <div class="w-100">
                    <div class="content-area">
                        @include('admin.includes.header')
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </main>
      

    @include('admin.includes.footer')

  
