<?php

/**
 * @author Daniel Correa Villa <daniel.correa@kodalogic.com>
 * 24/03/2024
 * 
 * Enrutador de Polaris
 * 
 */
class Router
{
  /**
   * @var array Array que contiene las rutas registradas en la DB.
   */
  public array $routes;

  /**
   * @var string URI calculada a partir de la URL del cliente.
   */
  public string $uri;

  /**
   * @var bool Indica si la petición es una solicitud AJAX.
   */
  public bool $ajax;

  /**
   * Constructor de la clase Router.
   *
   * Inicializa las propiedades, calcula la URI de la solicitud actual
   * y configura el controlador correspondiente basado en la base de datos.
   *
   * @throws Exception Si no se encuentra una página válida en la base de datos.
   */
  public function __construct()
  {   
    $db = new Model();

    try
    {      
      // ------------------------------------------------------------------------------
      // Búsqueda de la URI
      // ------------------------------------------------------------------------------

      // Inicializamos el array de rutas y si es una ruta ajax
      $this->routes = [];
      $this->ajax   = false;

      // Calculamos la URI a partir de la URL del cliente
      if( $_SESSION['polaris']['url_base'] !== '/' )
      {
        // Dividimos la URL por '/'
        $url_parts = explode( '/', $_SESSION['polaris']['url_base'] ); 

        // Eliminamos los espacios en blanco con el array_filter y reindexamos los índices del array
        $url_parts = array_values(
          array_filter( $url_parts )
        );
      }
      else
        $url_parts = ['/'];

      // Si se trata de una llamada AJAX
      if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) )
      {
        // Calculamos el destinatario de la llamada AJAX
        $this->uri  = '/' . pl_get( 'cn' );
        $this->ajax = true;
      }
      else
      {
        // Capturamos la URI del cliente
        $this->uri = !empty( $url_parts[0] )
          ? $_SESSION['polaris']['url_base']
          : '/'; // URI por defecto
      }

      // Elinimanos el último / 
      if( strlen( $this->uri ) > 1 )
        $this->uri = rtrim( $this->uri, '/' );

      // Comprobamos que no sea un archivo CSS, JS o HTML
      $allowed_extensions = ['.css', '.js', '.html', '.png', '.webp', '.jpeg', '.jpg'];
      foreach( $allowed_extensions as $extension )
      {
        if( str_ends_with( $this->uri, $extension ) && file_exists( 'src/' . $this->uri ) )
        {
          // Cabeceras
          $content_type = match( $extension )
          {
              '.css'  => 'text/css'
            , '.js'   => 'text/javascript'
            , default => mime_content_type( 'src/' . $this->uri )
          };

          // Enviar el archivo al cliente con la cabecera adecuada
          header( 'Content-Type: ' . $content_type );
          readfile( 'src/' . $this->uri );
          exit;
        }
      }

      // Buscamos la página en la DB
      $sql    = 'select * from ' . DB_SYS . '.polaris_pages where url = ? limit 1';
      $params = [$db->esc( $this->uri )];

      // Ejecutamos la consulta
      $db->pl_query_prepared( $sql, $params );
      if( $db->next_row() )
        $row = $db->get_row();


      // Si no hay resultados, redireccionamos al home
      if( empty( $row ) || $this->uri !== $row['url'] )
      {
        // Si existe el fichero 404, lo mostramos
        $file_404 = BASE_PATH . '/src/apache/errors/404.html';
        if( file_exists( $file_404 ) )
        {
          print file_get_contents( $file_404 );
          exit;
        }
      }

      /*
        Array
          [0] => Array
            [page_id] => 1
            [url] => Index
            [redirect] => 
            [page_title] => Index
            [file] => Index/Index
            [title_seo] => Index
      */

      // ------------------------------------------------------------------------------
      // Redirecciones
      // ------------------------------------------------------------------------------

      // Si la página tiene vinculada alguna redirección, la realizamos
      if( !empty( $row['redirect'] ) )
        pl_redirect( $row['redirect'] );

      // ------------------------------------------------------------------------------
      // Creación del nombre del controlador
      // ------------------------------------------------------------------------------

      if( !empty( $row['file'] ) )
      {
        // Array reduce es una función iteradora en la que defines un buffer al inicio del proceso
        // Este buffer es el '' definido después de la función anónima
        // En este caso, la función anónima devolverá un string y este se añadirá al buffer

        // Calculamos el nombre de la clase
        $class_name = array_reduce(

          // Dividimos la url y cambiamos los "/char" por "Char"
          explode( '/', $this->uri ),
          function( $buffer, $part ): string {
            return $buffer . ucfirst( $part );
          },
          '' // Valor inicial del buffer
        );

        // Añadimos un controlador al array final de la ruta
        $controller_name = $class_name . 'Controller@index';
        $this->routes[]  = [
            $this->uri => $controller_name
          , 'file'     => $row['file']
        ];

        // Definimos el nombre del controlador en la sesión
        $row['controller_name']      = ucfirst( $this->uri ) . 'Controller';
        $_SESSION['polaris']['page'] = $row;

        // Directorio de la página
        $_SESSION['polaris']['actual_dir'] = $_SESSION['polaris']['complex_domain'] . '/pages' . $row['url'];
      }
    }
    catch( Exception $e )
    {
      print $e->getMessage();
    }
    finally
    {
      $db->close();
    }
  }
}

?>