<?php

/**
 * --------------------------------------------------------------------------------------------------------------------
 *     ____        __           _     
 *    / __ \____  / /___ ______(_)____
 *   / /_/ / __ \/ / __ `/ ___/ / ___/
 *  / ____/ /_/ / / /_/ / /  / (__  ) 
 * /_/    \____/_/\__,_/_/  /_/____/
 *
 * POLARIS KERNEL | KODALOGIC
 * 
 * @author Daniel Correa Villa <daniel.correa@kodalogic.com>
 * @link   https://kodalogic.com
 * --------------------------------------------------------------------------------------------------------------------
 *  
 * Núcleo del framework Polaris.
 * Este script es el punto de entrada principal y el corazón del sistema, encargado de:
 *  
 * - Configuración de CORS y gestión de cabeceras HTTP.
 * - Inicialización del framework y carga de archivos esenciales.
 * - Manejo del enrutamiento y ejecución de controladores.
 * - Gestión de AJAX y procesamiento de peticiones asíncronas.
 * - Renderización de vistas y aplicación de máscaras.
 */

// Cabeceras de CORS
// Ponemos estas peticiones para que las llamadas AJAX no se detengan
header( 'Access-Control-Allow-Origin: *' );
header( 'Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method' );
header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE' );
header( 'Allow: GET, POST, OPTIONS, PUT, DELETE' );

// Incluímos el framework
require_once __DIR__ . '/polaris.php';

// Si ya existe la DB, no se ejecutará nada
require_once __DIR__ . '/src/init/polaris/init.php';
require_once __DIR__ . '/src/init/project/init.php';

// -------------------------------------------------------------------------------------
// Enturador y compilador de máscaras
// -------------------------------------------------------------------------------------

// Instanciamos un enrutador
$router = new Router();

/*
  Array | $router->routes
    [0] => Array
      [/debug] => DebugController@index
      [file] => Debug/Debug
*/

// Extraemos la primera ruta
// Por cada controlador encontrado, lo procesamos
foreach( $router->routes as $route_controller )
{
  // Capturamos la clave valor
  $route_name   = key( $route_controller );
  $route_method = $route_controller[$route_name];
  $route_file   = $route_controller['file'];

  // Capturamos el nombre y el método del controlador
  [$controller_name, $method_name] = explode( '@', $route_method );

  // Calculamos la ruta del controlador final
  $controller_path = sprintf( '%s/pages/%s.php', MAIN_PATH, $route_file );
  $mask_path       = sprintf( '%s/pages/%s.html', MAIN_PATH, $route_file );

  // -------------------------------------------------------------------------------------
  // Ejecución del controlador
  // -------------------------------------------------------------------------------------

  // Añadimos el Controlador
  try
  {
    require_once $controller_path;
  }
  catch( Exception $e )
  {
    print $e->getMessage() . '<br>';
    print '404 - Controller class not found';
    continue;
  }

  // Validación por si el controlador no existe
  if( !file_exists( $controller_path ) )
  {
    print '404 - Controller not found';
    continue;
  }

  // Capturamos la instancia de la sesión
  if( !empty( $_SESSION['controllers'][$controller_name] ) )
    $controller = unserialize( $_SESSION['controllers'][$controller_name] );
  else // Instanciamos el nuevo controlador
    $controller = new $controller_name();

  // Validación por si el método no existe
  if( !method_exists( $controller, $method_name ) )
  {
    print '404 - Controller Method {$method_name} not found';
    continue;
  }

  // Ejecutamos el método del controlador calculado
  if( !$router->ajax )
    call_user_func( [$controller, $method_name] );

  // -------------------------------------------------------------------------------------
  // AJAX
  // -------------------------------------------------------------------------------------

  // Controlamos las peticiones POST de AJAX
  elseif( isset( $_GET['cm'] ) )
  {
    // Determinamos qué datos enviar a la función
    if( !empty( $_FILES ) && !empty( $_POST ) )
      $data = array_merge( $_POST, $_FILES ); // Si hay archivos y datos POST, combinamos ambos
    elseif( !empty( $_FILES ) ) 
      $data = $_FILES;  // Si solo hay archivos
    elseif( !empty( $_POST ) ) 
      $data = $_POST;   // Si solo hay datos POST
    else
      $data = null;

    if( $data != null )
    {
      // Ejecutamos la función AJAX correspondiente
      $ajax_response = call_user_func(
          [$controller, 'ajax_' . pl_get( 'cm' )]
        , $data
      );
    }
    else
    {
      // Ejecutamos la función AJAX correspondiente
      $ajax_response = call_user_func(
        [$controller, 'ajax_' . pl_get( 'cm' )]
      );
    }

    // Cabeceras del SUCCESS
    header( 'Content-Type: application/json' );
    http_response_code( 200 );

    // Devolvemos la respuesta
    echo json_encode( $ajax_response );
    break;
  }

  // -------------------------------------------------------------------------------------
  // Ejecución de la máscara
  // -------------------------------------------------------------------------------------

  // Añadimos los parámetros y renderizamos la máscara
  $view_engine = new ViewEngine( $mask_path, $controller, $controller_name );
  $view_engine->render_template();
}

?>
