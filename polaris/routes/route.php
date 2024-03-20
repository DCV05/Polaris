<?php

class Route
{
  public function __construct()
  {
    $this->routes = [];

    $url_parts = explode( '/', $_SESSION['polaris']['url_relative'] );
    $uri = end( $url_parts );

    $this->uri = $uri;

    $query = new Select( 'polaris' );
    $result = $query
        ->select( [
            [ 'polaris_domains' => [ 'title' ] ]
        ] )
        ->from( 'polaris_domains' )
        ->exec_sql();

    $titles = array_column($result, 'title');
    if( in_array( $uri, $titles ) )
    {
      $controller_name = ucfirst( $uri ) . '_Controller@index';

      $this->routes[] = [
        $uri => $controller_name
      ];
    }
  }

}



?>