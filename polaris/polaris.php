<?php

//------------------------------------------------------------------------------------------------------------------------------------
// POLARIS | PHP FRAMEWORK
//------------------------------------------------------------------------------------------------------------------------------------

// Mostramos los errores
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// Definimos las constantes
define( 'DB_SERVER'   , getenv( 'POLARIS_SERVER'   ) );
define( 'DB_USER'     , getenv( 'POLARIS_USER'     ) );
define( 'DB_PASSWORD' , getenv( 'POLARIS_PASSWORD' ) );

// Incluímos las librerías de Polaris
include( 'model/orm.php'            ); // ORM
include( 'model/model.php'          ); // MySQL y MongoDB
include( 'model/redis.php'          ); // Redis
include( 'view/template_engine.php' ); // Motor de plantillas

include( 'sdk.php'   );
include( 'linux.php' );

function pl_session()
{
  // Iniciamos la sesión
  if( session_id() == '' )
      session_start();

  // pl_dump( $_SERVER );

  /*
  Array | $_SERVER
      [POLARIS_SERVER] => 10.8.0.1
      [POLARIS_USER] => root
      [POLARIS_PASSWORD] => *******
      [POLARIS_DB] => ******
      [HTTP_HOST] => 10.8.0.1
      [HTTP_USER_AGENT] => Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0
      [HTTP_ACCEPT] => text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*\/*;q=0.8
      [HTTP_ACCEPT_LANGUAGE] => es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3
      [HTTP_ACCEPT_ENCODING] => gzip, deflate
      [HTTP_CONNECTION] => keep-alive
      [HTTP_UPGRADE_INSECURE_REQUESTS] => 1
      [HTTP_PRAGMA] => no-cache
      [HTTP_CACHE_CONTROL] => no-cache
      [PATH] => /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
      [SERVER_SIGNATURE] => <address>Apache/2.4.57 (Debian) Server at 10.8.0.1 Port 80</address>

      [SERVER_SOFTWARE] => Apache/2.4.57 (Debian)
      [SERVER_NAME] => 10.8.0.1
      [SERVER_ADDR] => 10.8.0.1
      [SERVER_PORT] => 80
      [REMOTE_ADDR] => 10.8.0.2
      [DOCUMENT_ROOT] => /var/www/html
      [REQUEST_SCHEME] => http
      [CONTEXT_PREFIX] => 
      [CONTEXT_DOCUMENT_ROOT] => /var/www/html
      [SERVER_ADMIN] => webmaster@localhost
      [SCRIPT_FILENAME] => /var/www/html/polaris/debug.php
      [REMOTE_PORT] => 49833
      [GATEWAY_INTERFACE] => CGI/1.1
      [SERVER_PROTOCOL] => HTTP/1.1
      [REQUEST_METHOD] => GET
      [QUERY_STRING] => 
      [REQUEST_URI] => /polaris/debug.php
      [SCRIPT_NAME] => /polaris/debug.php
      [PHP_SELF] => /polaris/debug.php
      [REQUEST_TIME_FLOAT] => 1710448972.0606
      [REQUEST_TIME] => 1710448972
  */

  $url_relative = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );

  // Capturamos las variables de la SESSION
  $_SESSION['polaris'] = [
      'domain' 		=> $_SERVER['HTTP_HOST']
  ,	'url_abs' 		=> $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $url_relative
  ,	'url_relative' 	=> $url_relative
  ,	'url_get' 		=> $_GET
  ,	'document_root'	=> $_SERVER['DOCUMENT_ROOT'] . '/polaris'
  ];

  // Saneamos las variables del GET aplicando un filtro de URLs
  $_SESSION['polaris']['url_get'] = filter_var_array( $_SESSION['polaris']['url_get'], FILTER_SANITIZE_URL );

  pl_router();
}

function pl_router()
{
  if( $_SESSION['polaris']['url_relative'] > '' )
  {
    $arr_url = explode( '/', $_SESSION['polaris']['url_relative'] );

    $query = new Select( 'polaris' );

    $result = $query
      ->select( [
          [ 'polaris_domains' => [] ]
      ] )
      ->from( 'polaris_domains' )
      ->where( [ 
          [ "polaris_domains.title = '{$arr_url[1]}'" ]
      ] )
      ->exec_sql();

    if( count( $result ) == 0 )
        print 'ko'; exit;
      //pl_redirect( 'view/templates/404.html' );
  }
}

?>