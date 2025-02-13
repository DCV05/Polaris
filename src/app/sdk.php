<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Inicializa la sesión y configura variables de entorno para Polaris.
 *
 * La función verifica si una sesión ya está iniciada, y en caso contrario, la inicia.
 * Luego, captura y almacena en la sesión información relevante del servidor y la URL.
 *
 * @return void
 */
function pl_start(): void
{
  // Inicializamos la sesión
  if( !session_id() )
    session_start();

  /*
    Array | $_SERVER
      [POLARIS_SERVER] => ********
      [POLARIS_USER] => ********
      [POLARIS_PASSWORD] => *******
      [POLARIS_DB] => ******
      [HTTP_HOST] => ********
      [HTTP_USER_AGENT] => Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0
      [HTTP_ACCEPT] => text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*\/*;q=0.8
      [HTTP_ACCEPT_LANGUAGE] => es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3
      [HTTP_ACCEPT_ENCODING] => gzip, deflate
      [HTTP_CONNECTION] => keep-alive
      [HTTP_UPGRADE_INSECURE_REQUESTS] => 1
      [HTTP_PRAGMA] => no-cache
      [HTTP_CACHE_CONTROL] => no-cache
      [PATH] => /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
      [SERVER_SIGNATURE] => <address>Apache/2.4.57 (Debian) Server at ******** Port 80</address>

      [SERVER_SOFTWARE] => Apache/2.4.57 (Debian)
      [SERVER_NAME] => ********
      [SERVER_ADDR] => ********
      [SERVER_PORT] => 80
      [REMOTE_ADDR] => ********
      [DOCUMENT_ROOT] => /var/www/html
      [REQUEST_SCHEME] => http
      [CONTEXT_PREFIX] => 
      [CONTEXT_DOCUMENT_ROOT] => /var/www/html
      [SERVER_ADMIN] => ********
      [SCRIPT_FILENAME] => /var/www/html/polaris/debug.php
      [REMOTE_PORT] => *****
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

  // Escapamos los caracteres especiales de la URI
  $url_relative = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );
  $url_base     = str_replace( '?' . $_SERVER['QUERY_STRING'], '', $url_relative );

  // Protocolo HTTP o HTTPS
  $protocol = !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off'
    ? 'https://'
    : 'http://'
  ;

  $complex_domain = $protocol . $_SERVER['HTTP_HOST'];

  // Capturamos los valores de la página
  $_SESSION['polaris'] = [
      'domain' 		    => $_SERVER['HTTP_HOST']
    , 'protocol'      => $protocol
    , 'complex_domain'=> $complex_domain
    ,	'url_abs' 		  => $complex_domain . $url_relative
    , 'assets'        => $complex_domain . '/assets'
    ,	'url_relative' 	=> $url_relative
    ,	'url_base' 	    => $url_base
    ,	'url_get' 		  => $_GET
    ,	'document_root'	=> $_SERVER['DOCUMENT_ROOT']

    // ACTUAL_DIR es definido en Router.php
    // , 'actual_dir' => ''
  ];
}

//-------------------------------------------------------------------------------
// Funciones de depuración
//-------------------------------------------------------------------------------

/**
 * Función para imprimir un array de forma formateada
 * @param array|object $arr
 * @param boolean $return
 */
function pl_dump( array|object $arr_obj, bool $return = false )
{
  // Si definimos que no debe haber un return, mostramos los datos
  if( !$return )
  {
    // Generamos la cabecera de respuesta
    header( "Expires: Sun, 19 Nov 1978 05:00:00 GMT"               );
    header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"  );
    header( "Cache-Control: no-store, no-cache, must-revalidate"   );
    header( "Cache-Control: post-check=0, pre-check=0", false      );
    header( "Pragma: no-cache"                                     );
    header( "Content-type: application/json; charset: utf-8", true );	  
    header( "HTTP/1.0 400"                                         );
  }

  // El Backtrace proporciona información de las funciones usadas en un script php
  $backtrace = debug_backtrace();

  // El array shift es usado para eliminar el primer elemento de un array origen y almacenarlo
  $caller = array_shift( $backtrace );

  // Capturamos el archivo, la línea y la función origen
  $file     = $caller['file'];
  $line     = $caller['line'];
  $function = $caller['function'];

  // Inicializamos las variables de formato
  $chr  = '';
  $i    = 0;

  // Líneas de separación
  while( $i < 75 )
  {
    $chr .= html_entity_decode( '&#x2212;', ENT_NOQUOTES, 'UTF-8' );
    $i++;
  }

  // Return
  if( $return )
    return $arr_obj;
  else
  {
    echo "\n{$chr} \n{$file} | {$line} | {$function} \n{$chr} \n\n";
    print_r( $arr_obj );
  }
}

/**
 * Función para capturar parámetros del GET
 * @param string $param_name
 * @param mixed $default_value
 * @return mixed
 */
function pl_get( string $param_name, mixed $default_value = null ): mixed
{
  return $_GET[$param_name] ?? $default_value;
}

/**
 * Función para capturar el label mandado como argumento
 * @param string $label_name
 * @return mixed
 */
function pl_label( string $label_name ): mixed
{
  return $_SESSION['labels'][$label_name][DEF_LANG] ?? '!' . $label_name;
}

/**
 * Función para redirigir
 * @param string $url
 */
function pl_redirect( string $url ): void
{
  header( 'Location: ' . $url );
}

/**
 * Función para generar un hash aleatorio de 32 caracteres
 * @return string $hash
 */
function pl_random(): string
{
  return strtoupper( bin2hex( random_bytes( 16 ) ) ); // 16 bytes → 32 caracteres en hex
}

/**
 * Función para rellenar con ceros un número
 * @param string $number
 * @return string $value
 */
function pl_number_id( string $number, int $zeros = 4 ): string
{
	$value = sprintf( '%0' . $zeros . 'd', $number );
	return $value;
}

/**
 * Devuelve el idioma del navegador.
 * 
 * @param  array   $available   Lista de idiomas disponibles para el sitio.
 * @param  string  $default     Idioma predeterminado del sitio.
 * @return string               Código del idioma detectado.
 */
function pl_get_browser_language( array $available = [], string $default = 'en' ): string
{
  // Valor por defecto
  $value = $default;

  do
  {
    // Si hay cabecera de lenguaje
    if( !isset( $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ] ) )
      break;

    // Dividimos los idiomas disponibles
		$langs = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );

    // Si no hay idiomas disponibles definidos, capturamos el primer idioma detectado
		if( empty( $available ) && !empty( $langs ) )
    {
      $value = substr( $langs[0], 0, 2 );
      break;
    }

    // Verificar cada idioma detectado
		foreach( $langs as $lang )
    {
      // Extraemos el código del idioma
			$lang = substr( $lang, 0, 2 );

      // Verificar si coincide con la lista de idiomas disponibles
			if( in_array( $lang, $available ) )
			{
        $value = $lang;
        break 2;
      }
		}
    
  } while( false );

  return $value;
}

/**
 * Normaliza una cadena convirtiéndola a minúsculas, eliminando caracteres
 * acentuados o especiales y reemplazándolos por un separador. Elimina
 * el separador sobrante al inicio o final.
 *
 * @param string $string Cadena de texto a normalizar.
 * @param string $sep    Caracter separador (por defecto '-').
 *
 * @return string Devuelve la cadena normalizada, en minúsculas y sin caracteres no alfanuméricos.
 */
function pl_normalize( $string, $sep = '-' ): string
{
  // Convertir a minúsculas en UTF-8
  $string = mb_strtolower( $string, 'UTF-8' );

  // Transformamos caracteres acentuados a sin tilde
  $string = iconv( 'UTF-8', 'ASCII//TRANSLIT', $string );

  // Reemplazar cualquier secuencia de caracteres que no sean [a-z0-9] por el separador
  $string = preg_replace( '/[^a-z0-9]+/', $sep, $string );

  return trim( $string, $sep );
}

/**
 * Envía un correo electrónico utilizando PHPMailer con configuración básica.
 *
 * El correo se envía en formato HTML con un título y contenido especificados.
 *
 * @param string $email_address Dirección de correo final.
 * @param string $title El título del correo.
 * @param string $html El contenido del correo en formato HTML.
 *
 * @return bool
 *
 * @throws Exception Si ocurre un error al enviar el correo.
 */
function pl_send_email( string $email_address, string $title, string $html ): bool
{
  $value = false;

  try
  {
    // Inicializamos el email y designamos la configuración
    $mail = new PHPMailer( true );

    $mail->isSMTP();
    $mail->Host 		= 'localhost';
    $mail->Port 		= 25;
    $mail->CharSet 	= 'UTF-8';
    $mail->Encoding = 'base64';

    // Configuración del correo
    $mail->setFrom( 'server@campamento.com', 'Campamento' );
    $mail->addAddress( $email_address );

    $mail->isHTML( true );
    $mail->Subject 	= $title;
    $mail->Body 		= $html;

    // Enviamos el email
    $mail->send();
    $value = true;
  }
  catch( Exception $e )
  {
    print $e->getMessage();
  }
  finally
  {
    return $value;
  }
}

?>