<?php

// Clase enrutador
class Route
{
  // Constructor
  public function __construct()
  {
    // Inicializamos el array de rutas
    $this->routes = [];

    // Calculamos la URI a partir de la URL del cliente
    $url_parts = explode( '/', $_SESSION['polaris']['url_relative'] );
    $uri = end( $url_parts );
    $this->uri = $uri;

    // Solicitamos si la URI es existente en Polaris
    $query = new Select( 'polaris' );
    $result = $query
        ->select( [
            [ 'polaris_domains' => [ 'title' ] ]
        ] )
        ->from( 'polaris_domains' )
        ->exec_sql();

    // Capturamos los valores de los títulos del array
    $titles = array_column($result, 'title');
    
    // Si la URI existe en Polaris, creamos una ruta de controlador
    if( in_array( $uri, $titles ) )
    {
      // Añadimos un controlador al array final de rutas
      $controller_name = ucfirst( $uri ) . '_Controller@index';
      $this->routes[] = [
        $uri => $controller_name
      ];
    }
  }

}



?>