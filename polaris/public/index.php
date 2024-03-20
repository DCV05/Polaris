<?php

include( '/var/www/html/polaris/polaris/polaris.php' );
include( '/var/www/html/polaris/polaris/app/controllers/Home_Controller.php' );

pl_session();

$route = new Route();


foreach( $route->routes as $route_controller )
{
    $route_name = key( $route_controller );
    $route_method = $route_controller[ $route_name ];

    [ $controller_name, $method_name ] = explode( '@', $route_method );

    $controller_path = '/var/www/html/polaris/polaris/app/controllers/' . $controller_name . '.php';

    if ( !file_exists( $controller_path ) )
    {
        print '404 - Controller file not found';
        continue;
    }

    if ( !class_exists( $controller_name ) )
    {
        print '404 - Controller class not found';
        continue;
    }

    $controller = new $controller_name();

    if ( !method_exists( $controller, $method_name  ))
    {
        print '404 - Method not found';
        continue;
    }

    call_user_func( [ $controller, $method_name ] );
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>ok</h1>
</body>
</html>