<?php

// Incluímos Polaris y todos los controladores
include( '/var/www/html/andromeda/polaris/polaris/polaris.php' );
include( '/var/www/html/andromeda/polaris/polaris/app/controllers/Home_Controller.php' );

// Iniciamos la sesión en Polaris
pl_session();

// Instanciamos un enrutador
$route = new Route();

// Por cada controlador encontrado, lo procesamos
foreach( $route->routes as $route_controller )
{
    // Capturamos la clave valor
    $route_name = key( $route_controller );
    $route_method = $route_controller[ $route_name ];

    // Capturamos el nombre y el método del controlador
    [ $controller_name, $method_name ] = explode( '@', $route_method );

    // Calculamos la ruta del controlador final
    $controller_path = '/var/www/html/andromeda/polaris/polaris/app/controllers/' . $controller_name . '.php';

    // Validación por si el controlador no existe
    if ( !file_exists( $controller_path ) )
    {
        print '404 - Controllador no existente';
        continue;
    }

    // Validación por si la clase no existe
    if ( !class_exists( $controller_name ) )
    {
        print '404 - Clase de controlador no existente';
        continue;
    }

    // Instanciamos un nuevo controlador
    $controller = new $controller_name();

    // Validación por si el método no existe
    if ( !method_exists( $controller, $method_name ) )
    {
        print "404 - Método del controlador {$method_name} no existente";
        continue;
    }

    // Ejecutamos el método del controlador calculado
    call_user_func( [ $controller, $method_name ] );
}

?>