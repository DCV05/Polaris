<?php

/**
 * Clase MySQL
 */
class pl_model extends mysqli
{
  public $database_name;

  /**
   * Creamos un constructor para poder crear instancias de la conexión
   * @param string $database_name
   */
  public function __construct( $database_name )
  {
    $this->database_name = $database_name;

    // Configuramos mysqli para que lanze excepciones
    mysqli_report( MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR );

    // Intentamos crear una instancia de MySQL
    try
    {
      // Creamos una instancia de MySQL
      parent::__construct( DB_SERVER, DB_USER, DB_PASSWORD, $this->database_name );
      
      // Configuramos la instancia para que acepte caractéres especiales
      $this->set_charset("utf8");

      // Manejo de errores
      if( $this->connect_error )
        die( "Error de conexión ({$this->connection_errno}) {$this->connect_error}" );
    }
    
    // Captura de excepciones en la conexión con la base de datos
    catch ( mysqli_sql_exception $mse ) // Capturamos excepciones de mysqli
    {
      throw new Exception( "Error al intentar conectarse a MySQL {$mse->getMessage()}" );
    }
    catch ( Exception $e )
    {
      throw new Exception( "Error {$e->getMessage()}" );
    }
    catch ( Error $e ) // Captura de errores fatales de versiones mayores a PHP 7.0
    {
      throw new Exception("Error fatal {$e->getMessage()}" );
    }
  }

  /**
   * Realizar consulta SQL a MySQL
   * @param string $sql
   * @return array $value
   * */ 
  public function pl_query( $sql, $redis = true )
  {
    // Inicializamos el array a devolver
    $value = [];

    // Creamos la clave redis
    $key_sql = 'sql:' . md5( $sql );

    // Creamos la instancia de Redis | Sistema de Caché SQL
    $redis = new pl_redis( $key_sql, $sql );
    if( $redis == false )
      $value = $redis->check_redis_key( $this->database_name );

    // Intentamos hacer la consulta
    try
    {
      // Ejecutamos la consulta
      if( $this->real_query( $sql ) )
      {
        // Usamos use_result para optimizar la eficiencia de memoria en el cliente
        if( $data = $this->use_result() )
        {
          // Guardamos el resultado en el array a devolver
          while( $row = $data->fetch_assoc() )
            $value[] = $row;

          // Liberamos recursos
          $data->free();
        }
      }
      else
      {
        // Si real_query retorna falso, es posible que nunca se asigne $data, por lo que no debemos intentar liberarlo aquí
        throw new Exception( "Error al realizar la consulta: {$this->error}" );
        $data->free();
      }

    }
    catch ( mysqli_sql_exception | Exception $e ) // Capturamos la excepción
    {
      print "\n\nSe ha producido un error: {$e->getMessage()}\n\n";
      $value = null;
    }

    return $value;
  }


  /**
   * Método para cerrar la sesión de MySQL
   */ 
  public function pl_close()
  {
    $this->close();
  }

  /**
   * Función para imprimir información de los campos de una tabla
   * @param string $table_name
   * @return string $error_fields
   */ 
  public function pl_describe( $table_name )
  {
    // Inicializamos los arrays temporaales y el string final
    $table_fields = [];
    $error_fields = [];
    $error_str    = '';

    // Consulta SQL de la tabla
    $sql = "describe {$this->database_name}.{$table_name}";
    $result = $this->pl_query( $sql );

    // Iteramos cada resultado
    foreach ( $result as $key => $value )
      $error_fields[] = $value['Field'];

    // Unimos los campos en un String
    $error_str = implode( ', ', $error_fields );
    return $error_fields;

    // Imprimimos los resultados
    // print "\n\n" . 'Error: campos requeridos: (' . $error_str . ')' . "\n\n";
  }

  /**
   * Función para añadir una columna en una tabla
   * Se debe de especificar la tabla, el nombre de la columna, el tipo de dato y su capacidad (opcional)
   * @param string $table_name
   * @param string $col_name
   * @param string $col_type
   * @param string $col_capacity
   */
  public function pl_migration_add_column( $table_name, $col_name, $col_type, $col_capacity = null )
  {
    $in_table = false;

    // Comprobamos si existe ua columna con ese nombre
    $table_fields = $this->pl_describe( $table_name );

    // Si el campo aparece en la tabla, lo mostramos
    foreach ( $table_fields as $key => $value )
    {
      if( $col_name == $value )
        $in_table = true;
    }

    // En el caso de que no esté en la tabla, añadimos la columna
    if( $in_table == false )
    {
      $str_capacity = '';

      // Si tiene capacidad, la añadimos en el SQL
      if( $col_capacity != null )
        $str_capacity = "({$col_capacity})";
  
      // Consulta SQL
      $sql = "alter table {$this->database_name}.{$table_name} add {$col_name} {$col_type}{$str_capacity}";
       
      // Ejecutamos la consulta
      $result = $this->pl_query( $sql );

      return 1;
    }
    else
    {
      print "\n\nEl campo <b>{$col_name}</b> ya existe en <b>{$table_name}</b>";

      return 0;
    }
  }

  /**
   * Función para eliminar una columna de una tabla
   * @param string $table_name
   * @param string $col_name
   *  */ 
  public function pl_migration_remove_column( $table_name, $col_name )
  {
    $in_table = false;

    // Capturamos los campos de la tabla
    $table_fields = $this->pl_describe( $table_name );

    // Iteramos los campos y comprobamos si estamos intentando eliminar una columna no existente
    foreach ( $table_fields as $key => $value )
    {
      if( $value == $col_name )
        $in_table = true;
    }

    // Si la columna existe en la tabla
    if( $in_table )
    {
      // Consulta SQL
      $sql = "alter table {$this->database_name}.{$table_name} drop column {$col_name}";
    
      // Ejecutamos la consulta
      $result = $this->pl_query( $sql );

      return 1;
    }
    else
    {
      print "\n\nEl campo <b>{$col_name}</b> no existe en <b>{$table_name}</b>";

      return 0;
    }
  }

  /**
   * Función para crear una tabla
   * @param string $table_name
   * @param array $columns
   */
  public function pl_migration_create_table( $table_name, $columns )
  {
    // Inicializamos el array de campos
    $combined_fields = [];

    // Iteramos los campos del array insertado en las opciones
    foreach ( $columns as $column )
    {
      // Insertamos los campos de las opciones dentro del array de campos
      foreach ( $column as $column_name => $options )
        $combined_fields[] = $column_name . ' ' . $options;
    }

    // Pasamos de array a string
    $combined_fields = implode( ', ', $combined_fields );

    // Conulta SQL
    $sql = "create table {$table_name} ({$combined_fields})";

    print $sql; exit;

    // Ejecutamos la consulta
    $query = $this->pl_query( $sql );
  }

  /**
   * Función para borrar una tabla
   * @param string $table_name
   */
  public function pl_migration_drop_table( $table_name )
  {
    // Consulta SQL
    $sql = "drop table if exists {$this->database_name}.{$table_name}";

    // Ejecutamos la consulta
    $query = $this->pl_query( $sql );
  }

  /**
   * Función para crear una base de datos
   * @param string $db_name
   */
  public function pl_migration_create_database( $db_name )
  {
    // Consulta SQL
    $sql = "create database `{$db_name}`";

    // Ejecutamos la consulta
    $query = $this->pl_query( $sql );
  }

  /**
   * Función para borrar una bas de datos
   * @param string $db_name
   */
  public function pl_migration_drop_database( $db_name )
  {
    $sql = "drop database {$db_name}";

    $query = $this->pl_query( $sql );
  }

  /**
   * Función para crear un Backup de una base de datos
   * @param string $host
   * @param string $user
   * @param string $password
   * @param string $db_name
   * @param string $path_backup
   * @return string $backu_file_name
   *  */ 
  public function pl_migration_backup_database( $host, $user, $password, $db_name, $path_backup, $ip = '127.0.0.1' )
  {
    // Calculamos el nombre del backup usando la fecha actual
    $date = floor( microtime( true ) * 1000 );
    $hash = hash( 'ripemd160', $date );

    $backup_file_name = escapeshellarg("{$db_name}_{$hash}.sql");

    // Calculamos el comando a ejecutar en Linux
    $cmd = "mysqldump -u {$user} --password='{$password}' {$db_name} > {$path_backup}{$backup_file_name}";

    // Ejecutamos el comando
    $result = pl_linux_exec( $cmd );

    // Detectamos errores al crear el Backup
    if( $result <= 0 )
      print "Error al intentar crear un Backup de {$db_name}";

    $query = new Insert( 'polaris_vc' );
    $result = $query
      ->insert( 'vc_main' )
      ->columns( [ 'version_name', 'version_hash', 'version_host' ] )
      ->values( [ "{$db_name}_{$hash}_{$date}", $hash, $ip ] )
      ->exec_sql();

    // Retornamos el nombre del fichero SQL que almacena el Backup
    return $backup_file_name;
  }

  /**
   * Función para aplicar un Backup a una DB específica
   * @param string $user
   * @param string $password
   * @param string $db_name
   * @param string $path_backup
   */
  public function pl_migration_restore_database( $user, $password, $db_name, $path_backup )
  {
    // Creamos una base de datos con el nombre de Backup
    $cmd = "mysql -u {$user} -p'{$password}' {$db_name} < {$path_backup}";

    // Restauramos la base de datos
    $result = pl_linux_exec( $cmd );

    if( $result <= 0 )
        print "Error al intentar restaurar {$db_name}";
  }

  /**
   * Función para escapar caracteres
   * @param string $str
   * @return string $escaped_str
   *  */ 
  public function pl_esc( $str )
  {
    // Escapamos el string
    $str = htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
    $escaped_str = $this->real_escape_string( $str );

    return $escaped_str;
  }
}

?>