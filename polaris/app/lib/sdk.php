<?php

//-------------------------------------------------------------------------------
// Funciones de depuración
//-------------------------------------------------------------------------------

/**
 * Función para imprimir un array de forma formateada
 * @param array $arr
 * @param boolean $return
 */
function pl_dump( $arr, $return = false )
{
  if( !$return )
  {
    // Generamos la cabecera de respuesta
    header ( "Expires: Sun, 19 Nov 1978 05:00:00 GMT"               );
    header ( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"  );
    header ( "Cache-Control: no-store, no-cache, must-revalidate"   );
    header ( "Cache-Control: post-check=0, pre-check=0", false      );
    header ( "Pragma: no-cache"                                     );
    header ( "Content-type: application/json; charset: utf-8", true );	  
    header ( "HTTP/1.0 400"                                         );
  }

  // El Backtrace proporciona información de las funciones usadas en un script php
  $backtrace = debug_backtrace();

  // El array shift es usado para eliminar el primer elemento de un array origen y almacenarlo
  $caller = array_shift($backtrace);

  // Capturamos el archivo, la línea y la función origen
  $file     = $caller['file'];
  $line     = $caller['line'];
  $function = $caller['function'];

  // Inicializamos las variables de formato
  $chr = '';
  $i = 0;

  while( $i < 75 )
  {
    $chr .= html_entity_decode('&#x2212;', ENT_NOQUOTES, 'UTF-8');
    $i++;
  }

  if( $return )
  {
    $result[] = $arr;

    return $result;
  }
  else
  {
    echo "\n{$chr} \n{$file} | {$line} | {$function} \n{$chr} \n\n";
    print_r( $arr );
  }

}

/**
 * Función para depuración medinte archivos txt
 * @param array $arr
 * @param boolean $replace
 */
function pl_log( $arr, $replace = false )
{
  // Si no existe el directorio de los logs, lo creamos
  if( !is_dir( "{$_SESSION['document_root']}/storage/app/log/" ) )
    mkdir( "{$_SESSION['document_root']}/storage/app/log/", 0755, true );

  // Dependiendo del parámetro $replace, remplazamos el contenido del log o no
  $mode = $replace ? 'w+' : 'a+';

  // El Backtrace proporciona información de las funciones usadas en un script php
  $backtrace = debug_backtrace();

  // El array shift es usado para eliminar el primer elemento de un array origen y almacenarlo
  $caller = array_shift($backtrace);

  // Capturamos el archivo, la línea y la función origen
  $file     = $caller['file'];
  $line     = $caller['line'];
  $function = $caller['function'];
  date_default_timezone_get();
  $date = date('Y-m-d H:i:s');

  // Inicializamos las variables de formato
  $chr = '';
  $i = 0;

  while( $i < 75 )
  {
    $chr .= html_entity_decode('&#x2212;', ENT_NOQUOTES, 'UTF-8');
    $i++;
  }

  // Formateamos el array a texto
  $log_text = pl_dump( $arr, true );

  // Abrimos el archivo log con permisos de lectura y escritura
  $log_file = fopen( $_SESSION['document_root'] . '/storage/app/log/pl_log.txt', $mode );

  // Escribimos el contenido en el txt
  fwrite( $log_file, "\n{$chr} \n{$file} | {$line} | {$function} | {$date}\n{$chr} \n\n" );
  fwrite( $log_file, print_r( $log_text, 1 ) );

  // Movemos el puntero de nuevo al inicio del txt y capturamos el contenido
  rewind( $log_file );
  $final_log_content = fread( $log_file, filesize( $_SESSION['document_root'] . '/storage/app/log/pl_log.txt' ) );

  // Cerramos el archivo
  fclose( $log_file );
}

//-------------------------------------------------------------------------------
// Control de versiones
//-------------------------------------------------------------------------------

/**
 * Función para imprimir los Backups de Polaris en pantalla
 */
function pl_vc ()
{
  $arr = scandir( "{$_SESSION['polaris']['document_root']}/polaris/storage/app/backup/" );

  $num_files = count( $arr ) - 2;

  // Iteramos los ficheros
  foreach ( $arr as $key => $backup )
  {
    // Si son directorios de sistema, nos los saltamos
    if( in_array( $backup, [ '.', '..' ] ) )
      continue;

    // Capturamos las variables de formato
    $db_name = explode( '_', $backup )[0];
    $filemtime = date( "d/m/y H:i:s", @filemtime( "{$_SESSION['polaris']['document_root']}/polaris/storage/app/backup/" . $backup) );

    // Calculamos el nombre del hash a mostrar
    $backup = str_replace( array( $db_name, '.sql', '_' ), '', $backup );
    $backup = substr( $backup, 0, 6 );

    $key_buffer = ( $key - 2 );

    // Formato ASCII
    if( ( $key - 2 ) == 0 )
      $chr = "\u{250C} {$key_buffer} - ";
    elseif( ( $key - 2 ) === $num_files - 1 )
      $chr = "\u{2514} {$key_buffer}*- ";
    else
      $chr = "\u{251C} {$key_buffer} - ";

    // Imprimimos los directorios
    echo "{$chr} {$backup} - {$filemtime} - {$db_name}<br>";
  }
  
}

//-------------------------------------------------------------------------------
// Funciones HTTP
//-------------------------------------------------------------------------------

/**
 * @param string $url
 * Función para redirigir a otra página
 */
function pl_redirect( $url = '' )
{
  // Si no se ha especificado una URL, refrescamos la página
  if( $url == '' )
    $url = $_SERVER['REQUEST_URI'];

  // Redirección
  header( 'HTTP/1.1 301 Moved Permanently', true, 301 );
  header( 'location: ' . $url );

  exit;
}

/**
 * Función para capturar variables de la URL
 * @param string $var
 * @return string $value
 */
function pl_get( $var )
{
  // Si existe la variable en la sesión, devolvemos su valor
  if( $_SESSION['polaris']['url_get'][ $var ] )
    $value = $_SESSION['polaris'][ $var ];
  else
    $value = null;

  return $value;
}

function pl_curl( $url, $data = [], $username = null, $password = null )
{
  // Iniciamos el cURL
  $ch = curl_init();

  // Insertamos la URL del endpoint
  curl_setopt( $ch, CURLOPT_URL, $url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

  if( $username != null && $password != null )
  {
    curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
    curl_setopt( $ch, CURLOPT_USERPWD, "$username:$password" );
  }

  // Si hay campos post, los añadimos
  if( count ( $data ) > 0 )
  {
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
  }

  // Ejecutamos la consulta
  $result = curl_exec( $ch );

  // Capturamos el código http
  $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
  
  // Cerramos el canal
  curl_close( $ch );

  return [ $result, $http_code ];
}

// Devuelve el último ID generado (autonumérico) en una tabla
function pl_db_last_id( $db )
{
	$sql = "select last_insert_id() as last_id";

	$query = $db->query( $sql );
	if( $query && $row = $query->fetch_assoc() )
	{
		$value = $row['last_id'];

	  $query->close();
	}
	else
	{
		$value = 0;
	}
	
	return $value;
}

?>