<?php

/**
 * @author Daniel Correa Villa <daniel.correa@kodalogic.com>
 * 18/03/2024
 * 
 * Modelo de MySQL para realizar su conexión
 * 
 */
class Model extends mysqli
{
  /**
   * @var string Nombre de la base de datos
  */
  public string $db_name;

  /**
   * @var bool|mysqli_result Resultado de la consulta o false si falla
   * */
  public bool|mysqli_result $result;

  /**
   * @var array|bool|null Fila actual de la consulta, false si no hay más filas, o null si no se ha inicializado
   * */
  public array|bool|null $current_row;

  /**
   * Creamos un constructor para poder crear instancias de la conexión
   * @param string $db_name
   */
  public function __construct( string $db_name = DB_SYS )
  {
    $this->db_name      = $db_name;
    $this->current_row  = null;

    // Configuramos mysqli para que lanze excepciones
    mysqli_report( MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR );

    // Intentamos crear una instancia de MySQL
    try
    {
      // Creamos una instancia de MySQL con UTF-8
      parent::__construct( DB_SERVER, DB_USER, DB_PASSWORD, $this->db_name );      
      $this->set_charset( 'utf8' );

      // Manejo de errores
      if( $this->connect_error )
        die( "Connection error {$this->connect_error}" );
    }
    
    // Captura de excepciones en la conexión con la base de datos
    catch( mysqli_sql_exception $mse ) // Capturamos excepciones de mysqli
    {
      throw new Exception( "Error during the connection with MySQLI {$mse->getMessage()}" );
    }
    catch( Exception $e )
    {
      throw new Exception( "Error {$e->getMessage()}" );
    }
    catch( Error $e ) // Captura de errores fatales de versiones mayores a PHP 7.0
    {
      throw new Exception( "Fatal error {$e->getMessage()}" );
    }
  }

  /**
   * Realizar consulta SQL a MySQL
   * @param string $sql
   * @param bool $return_all
   * */ 
  public function pl_query( string $sql, bool $return_all = false ): array|bool
  {
    $value = [];

    try
    {
      // Si hay un resultado anterior, lo liberamos antes de ejecutar una nueva consulta
      if( !empty( $this->result ) && is_object( $this->result ) )
        $this->result->free();

      // Ejecutamos la consulta
      if( $this->real_query( $sql ) )
      {
        $this->result = $this->use_result();

        // Si la consulta es SELECT y `return_all` está activado, devolvemos todos los datos
        if( $return_all && $this->result instanceof mysqli_result )
          $value = $this->result->fetch_all( MYSQLI_ASSOC );

        // Si la consulta es DELETE, UPDATE o INSERT, devolvemos si afectó filas
        elseif( $this->affected_rows >= 0 )
          return true;
      }
    }
    catch( mysqli_sql_exception | Exception $e )
    {
      print "\n\nError: {$e->getMessage()}\n\n";
    }
    finally
    {
      return $value;
    }
  }

  /**
   * Realiza una consulta SQL preparada con placeholders (?).
   *
   * @param string $sql         Consulta SQL con placeholders (p.ej: "SELECT * FROM usuarios WHERE id = ?").
   * @param array  $parameters  Arreglo con valores en el mismo orden que los placeholders.
   * @param bool   $return_all  Si es true y la consulta es SELECT, retornará todas las filas en un array.
   *
   * @return array|bool  Array de resultados si se trata de una consulta SELECT
   */
  public function pl_query_prepared( string $sql, array $parameters = [], bool $return_all = false ): array|bool
  {
    $db     = new Model();
    $value  = [];

    try
    {
      // Liberamos el resultado previo si existe
      if( !empty( $this->result ) && is_object( $this->result ) )
        $this->result->free();

      // Preparamos la sentencia
      $stmt = $this->prepare( $sql );
      if( !$stmt )
        throw new Exception( 'Error during the query: ' . $this->error );

      // Si hay parámetros, reemplazamos los parámetros dentro de la consutla
      if( count( $parameters ) > 0 )
      {
        $types  = '';
        $values = [];

        // Detectamos tipos y asignamos
        foreach( $parameters as $parameter )
        {
          // Si es un string, escapamos el parámetro
          if( is_string( $parameter ) )
            $parameter = $db->esc( $parameter );

          // Dependiendo del tipo de parámetro añadimos un tipo específico
          [$parameter_type, $parameter_value] = match( gettype( $parameter ) )
          {
              'bool'    => ['i', intval( $parameter )]
            , 'integer' => ['i', $parameter]
            , 'float'   => ['d', $parameter]
            , 'null'    => ['s', null]
            , default   => ['s', $parameter]
          };

          // Añadimos el parámetro y el tipo
          $types    .= $parameter_type;
          $values[] = $parameter_value;
        }

        // Preparamos el array de parámetros para bind_param
        $bind_params = [$types];
        foreach( $values as &$value )
          $bind_params[] = &$value; // MYSQLI pide pasarlo por referencia obligatoriamente

        /*
          Array | bind_params
            [0] => ss // Tipos
            [1] => /  // Parámetros
            [2] => test
        */

        // Reemplazamos los parámetros
        call_user_func_array( [$stmt, 'bind_param'], $bind_params );
      }

      // Ejecutamos
      if( $stmt->execute() )
      {
        // Guardamos el resultado
        $this->result = $stmt->get_result();

        // Select | fetch_all está disponible si $this->result es instancia de mysqli_result
        if( $return_all && $this->result instanceof mysqli_result )
          $value = $this->result->fetch_all( MYSQLI_ASSOC );

        // Insert, Delete, Update
        elseif( $this->affected_rows >= 0 )
          $value = true;
      }
    }
    catch( Exception $e )
    {
      print 'Error: ' . $e->getMessage();
      $value = false;
    }
    finally
    {
      return $value;
    }
  }

  /**
   * Avanza al siguiente resultado en la consulta.
   *
   * @return bool Devuelve true si hay más filas, false en caso contrario.
   */
  public function next_row(): bool
  {
    // Verificamos si existe un siguiente resultado
    // Si no hay un siguiente resultado, devuelve false
    $this->current_row = isset( $this->result ) && is_object( $this->result )
      ? $this->result->fetch_assoc()
      : false
    ;

    return $this->current_row !== null;
  }

  /**
   * Obtiene la fila actual de la consulta.
   *
   * @return array|false Devuelve la fila actual como un array asociativo o false si no hay más filas.
   */
  public function get_row(): array|bool
  {
    return $this->current_row;
  }

  /**
   * Obtiene el número total de filas en el resultado.
   *
   * @return int Número de filas en el resultado.
   */
  public function get_num_rows(): int|string
  {
    // Devuelve 0 si no hay resultado
    return $this->result->num_rows ?? 0;
  }

  /**
   * Escapa caracteres especiales en un string o convierte números a enteros.
   *
   * Si el valor es numérico, lo convierte a entero.  
   * Si es un string, lo escapa para evitar ataques XSS y SQL Injection.
   *
   * @param string|int $str El valor a escapar.
   * @return string|int Valor escapado.
   */
  public function esc( string|int $str ): string|int
  {
    // Si es un número, realizamos un invtal
    if( is_numeric( $str ) )
      $escaped_str = intval( $str );
    else
    {
      // Escapamos el string
      $str = htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
      $escaped_str = $this->real_escape_string( $str );
    }

    return $escaped_str;
  }

   /**
   * Función para capturar el último ID insertado
   * @return mixed $value
   *  */ 
  public function get_last_id(): mixed
  {
    // Buscamos el nuevo ID
    $sql = 'select last_insert_id() as last_id';
    $query = $this->query( $sql );
    if( $query && $row = $query->fetch_assoc() )
    {
      $value = $row['last_id'];
      $query->close();
    }
    else
      $value = 0;
    
    return $value;
  }

  /**
   * Función para imprimir información de los campos de una tabla
   * @param string $table_name
   * @return string $error_fields
   */ 
  public function pl_describe( string $table_name ): array
  {
    // Inicializamos el array de errores a devolver
    $error_fields = [];

    // Consulta SQL de la tabla
    $sql = "describe " . $this->db_name . ".{$table_name}";
    $this->pl_query( $sql );

    // Iteramos cada resultado
    foreach( $this->result as $none => $value )
      $error_fields[] = $value['field'];

    return $error_fields;
  }

  /**
   * Función para añadir una columna en una tabla
   * Se debe de especificar la tabla, el nombre de la columna, el tipo de dato y su capacidad (opcional)
   * @param string $table_name
   * @param string $col_name
   * @param string $col_type
   * @param string $col_capacity
   */
  public function pl_migration_add_column( string $table_name, string $col_name, string $col_type, string $col_capacity = null ): bool
  {
    $value = false;

    // Comprobamos si existe una columna con ese nombre
    $table_fields = $this->pl_describe( $table_name );

    do
    {
      // Si el campo aparece en la tabla, no procesamos la consulta
      if( array_key_exists( $col_name, $table_fields ) )
      {
        print "\n\nThe field <b>{$col_name}</b> already exists in <b>{$table_name}</b>";
        break;
      }

      // En el caso de que no esté en la tabla, añadimos la columna
      $str_capacity = '';

      // Si tiene capacidad, la añadimos en el SQL
      if( $col_capacity != null )
        $str_capacity = "({$col_capacity})";
  
      $sql = "alter table " . $this->db_name . ".{$table_name} add {$col_name} {$col_type}{$str_capacity}"; 
      $this->pl_query( $sql );

      // Si llegamos hasta aquí, está todo ok
      $value = true;
      break;

    } while( false );

    return $value;
  }

  /**
   * Función para eliminar una columna de una tabla
   * @param string $table_name
   * @param string $col_name
   *  */ 
  public function pl_migration_remove_column( string $table_name, string $col_name ): int
  {
    $value = false;

    // Comprobamos si existe una columna con ese nombre
    $table_fields = $this->pl_describe( $table_name );

    do
    {
      // Si el campo aparece en la tabla, no procesamos la consulta
      if( !array_key_exists( $col_name, $table_fields ) )
      {
        print "\n\nThe field <b>{$col_name}</b> does not exist in <b>{$table_name}</b>";
        break;
      }
  
      $sql = "alter table " . $this->db_name . ".{$table_name} drop column {$col_name}";
      $this->pl_query( $sql );

      // Si llegamos hasta aquí, está todo ok
      $value = true;
      break;

    } while( false );

    return $value;
  }

  /**
   * Función para crear una tabla
   * @param string $table_name
   * @param array<string,array> $columns
   */
  public function pl_migration_create_table( string $table_name, array $columns ): void
  {
    // Inicializamos el array de campos
    $combined_fields = [];

    // Iteramos los campos del array insertado en las opciones
    foreach( $columns as $column )
    {
      // Insertamos los campos de las opciones dentro del array de campos
      foreach( $column as $column_name => $options )
        $combined_fields[] = $column_name . ' ' . $options;
    }

    // Pasamos de array a string
    $combined_fields = implode( ', ', $combined_fields );

    $sql = "create table {$table_name} ({$combined_fields})";
    $this->pl_query( $sql );
  }

  /**
   * Función para borrar una tabla
   * @param string $table_name
   */
  public function pl_migration_drop_table( $table_name ): void
  {
    $sql = "drop table if exists " . $this->db_name . ".{$table_name}";
    $this->pl_query( $sql );
  }

  /**
   * Función para crear una base de datos
   * @param string $db_name
   */
  public function pl_migration_create_database( $db_name ): void
  {
    $sql = "create database if not exists `{$db_name}`";
    $this->pl_query( $sql );
  }

  /**
   * Función para borrar una base de datos
   * @param string $db_name
   */
  public function pl_migration_drop_database( string $db_name ): void
  {
    $sql = "drop database if not exists {$db_name}";
    $this->pl_query( $sql );
  }
}

?>