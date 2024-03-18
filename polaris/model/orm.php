<?php

// ------------------------------------------------------------------------------------------------------------------------------------
// Polaris | PHP Framework
// ------------------------------------------------------------------------------------------------------------------------------------

// Clase abstracta que contiene las fuciones necesarias para hacer una consulta
abstract class Query {
  abstract public function create_sql();
  abstract public function exec_sql();
}

// Clase delete
class Delete extends Query
{
  public function __construct( $database_name )
  {
    $this->database_name = $database_name;
  }

  // Inicializamos las variables utilizadas en la consulta
  protected $table_name;
  protected $where = [];

  // Función delete que asignará el nombre de la tabla a la consulta
  public function delete( $table_name )
  {
    $this->delete = $table_name;
    return $this;
  }

  // Si existe el campo where, lo aplicamos
  public function where( $wheres )
  {
    $combined_wheres = [];

    // Iteramos los wheres
    // Separamos los índices de la condición
    foreach( $wheres as $index => $where )
    {
      // Calculamos las condiciones y los tipos de condiciones
      $condition = $where[0];

      // En el caso de que exista un tipo de condición, lo aplicamos
      // En otro caso, insertamos un 'and'
      $condition_type = isset( $where[1] ) ? $where[1] : ( $index > 0 ? 'and' : '' );

      // Si es la primera posición insertamos el tipo de condición y la condición
      // En otro caso, insertamos únicamente la condición
      if( $index > 0 )
        $combined_wheres[] = "{$condition_type} {$condition}";
      else
        $combined_wheres[] = $condition;
    }

    // Transformamos el array a string
    $this->where = implode( ' ', $combined_wheres );

    return $this;
  }

  // Método para crear la consulta SQL
  public function create_sql()
  {
    // Iniciamos la consulta con los parámetros por defecto
    $sql = "delete from {$this->database_name}.{$this->delete}";

    // Si existe un where, lo añadimos a la consulta
    if( !empty( $this->where ) )
    $sql .= ' where ' . $this->where;
    
    return $sql;
  }

  // Función para ejecutar el SQL
  public function exec_sql()
  {
    $db = new pl_model( $this->database_name );

    // Creamos la consulta
    $sql = $this->create_sql();

    // Ejecutamos la consulta
    $query = $db->pl_query( $sql );

    $db->pl_close();

    return $query;
  }
}

// Clase Update
class Update extends Query
{
  public function __construct( $database_name )
  {
    $this->database_name = $database_name;
  }

  // Definimos los parámetro del objeto Update a insertar en la consulta
  protected $table_name;
  protected $set   = [];
  protected $where = [];

  public function update( $table_name )
  {
    $this->update = $table_name;
    return $this;
  }

  // Si existe el campo set, lo añadimos
  public function set( $sets )
  {
    // Inicializamos el array de sets a incluir
    $combined_sets = [];

    // Por cada entrada del array
    foreach ( $sets as $key => $value )
    {
      // Si el campo es un string, le añadimos unas comillas dobles
      if( is_string( $value ) )
        $value = '"' . $value . '"';

      // Insertamos una entrada con los sets
      $combined_sets[] = "{$key} = {$value}";
    }
    
    // Separamos los sets con comas
    $set_query = implode( ', ', $combined_sets );

    // Retornamos los sets
    $this->set = $set_query;
    return $this;
  }

  // Si existe el campo where, lo aplicamos
  public function where( $wheres )
  {
    $combined_wheres = [];

    // Iteramos los wheres
    // Separamos los índices de la condición
    foreach( $wheres as $index => $where )
    {
      // Calculamos las condiciones y los tipos de condiciones
      $condition = $where[0];

      // En el caso de que exista un tipo de condición, lo aplicamos
      // En otro caso, insertamos un 'and'
      $condition_type = isset( $where[1] ) ? $where[1] : ( $index > 0 ? 'and' : '' );

      // Si es la primera posición insertamos el tipo de condición y la condición
      // En otro caso, insertamos únicamente la condición
      if( $index > 0 )
        $combined_wheres[] = "{$condition_type} {$condition}";
      else
        $combined_wheres[] = $condition;
    }

    // Transformamos el array a string
    $this->where = implode( ' ', $combined_wheres );

    return $this;
  }

  
  // Método para crear la consulta SQL
  public function create_sql()
  {
    // Iniciamos la consulta con los parámetros por defecto
    $sql = "update {$this->database_name}.{$this->update}";

    // Si existe un where, lo añadimos a la consulta
    if( !empty( $this->set ) )
      $sql .= ' set ' . $this->set;

    // Si existe un where, lo añadimos a la consulta
    if( !empty( $this->where ) )
    $sql .= ' where ' . $this->where;
    
    return $sql;
  }

  // Función para ejecutar el SQL
  public function exec_sql()
  {
    $db = new pl_model( $this->database_name );

    // Creamos la consulta
    $sql = $this->create_sql();

    // Ejecutamos la consulta
    $query = $db->pl_query( $sql );

    $db->pl_close();

    return $query;
  }

}

class Select extends Query
{
  // Asignamos el nombre de la base de datos
  public function __construct( $database_name )
  {
    $this->database_name = $database_name;
  }

  // Definimos los parámetros del objeto Select a insertar en la consulta
  protected $from;
  protected $joins = [];
  protected $where = [];
  protected $limit;
  protected $order_by;

  // Métodos para insertar datos en la consulta SQL
  // Cada parámetro insertado, se transformará en una propiedad del objeto, y por ende, de la consulta

  // Si existe el campo select_from, lo aplicamos
  public function select( $selects )
  {
    // Inicializamos el array de todos los campos combinados
    $combined_fields = [];

    // Iteramos los campos del select
    foreach ( $selects as $select )
    {
      // Capturamos la tabla origen y sus campos relacionados
      foreach ( $select as $table_name => $select_fields )
      {

        // Si no tiene campos dentro, seleccionamos todos los campos por defecto
        if ( empty( $select_fields ) || $select_fields == '*' )
        {
          $combined_fields[] = "{$table_name}.*";
          break;
        }

        // Por cada array de campos insertados, le vinculamos su tabla de origen
        // Para ello, usaremos una función anónima que le aplicará el nombre de la tabla a cada elemento del array
        // Como array_map sólo acepta un argumento, usaremos use ( $table_name ) para capturar el nombre de forma externa a la función
        foreach ( $select as $field )
        {
          $fields = array_map( function( $field ) use ( $table_name )
          {
            return "{$table_name}.{$field}";
          }, $field );
        }

        // Unimos todas las consultas
        $combined_fields = array_merge( $combined_fields, $fields );

      }

    }

    // Si hay varios parámetros de búsqueda, lo convertimos en un string separado por comas
    $this->select_from = is_array( $select ) ? implode( ', ', $combined_fields ) : $select_fields;
    return $this;
  }

  // Si existe el campo from, lo aplicamos
  public function from( $table_name )
  {
    $this->from = $table_name;
    return $this;
  }
  
  // Si existe el campo join, lo aplicamos
  public function join( $table_name, $union, $type = 'left join' )
  {
    $this->joins[] = "{$type} {$this->database_name}.{$table_name} on {$union}";
    return $this;
  }

  // Si existe el campo where, lo aplicamos
  public function where( $wheres )
  {
    $combined_wheres = [];

    // Iteramos los wheres
    // Separamos los índices de la condición
    foreach( $wheres as $index => $where )
    {

      // Calculamos las condiciones y los tipos de condiciones
      $condition = $where[0];

      // En el caso de que exista un tipo de condición, lo aplicamos
      // En otro caso, insertamos un 'and'
      $condition_type = isset( $where[1] ) ? $where[1] : ( $index > 0 ? 'and' : '' );

      // Si es la primera posición insertamos el tipo de condición y la condición
      // En otro caso, insertamos únicamente la condición
      if( $index > 0 )
       $combined_wheres[] = "{$condition_type} {$condition}";
      else
        $combined_wheres[] = $condition;
    }

    // Transformamos el array a string
    $this->where = implode( ' ', $combined_wheres );

    return $this;
  }

  // Si existe el campo order_by, lo aplicamos
  public function order_by( $order )
  {
    $this->order_by = $order;
    return $this;
  }

  // Si existe el campo limit, lo aplicamos
  public function limit( $limit_field )
  {
    $this->limit = $limit_field;
    return $this;
  }

  // Método para crear la consulta SQL
  public function create_sql()
  {
    // Iniciamos la consulta con los parámetros por defecto
    $sql = "select {$this->select_from} from {$this->database_name}.{$this->from}";

    // Si el array de joins no está vacío, unimos los elementos en un string y lo añadimos a la consulta
    if( !empty( $this->joins ) )
      $sql .= ' ' . implode( ' ', $this->joins );

    // Si existe un where, lo añadimos a la consulta
    if( !empty( $this->where ) )
      $sql .= ' where ' . $this->where;

    // Si existe un order_by, lo añadimos 
    if( !empty( $this->order_by ) )
      $sql .= " order by {$this->order_by} ";

    // Si existe un limit, lo añadimos
    if( !empty( $this->limit ) )
      $sql .= " limit {$this->limit}";

    return $sql;
  }

  // Función para ejecutar el SQL
  public function exec_sql()
  {
    $db = new pl_model( $this->database_name );

    // Creamos la consulta
    $sql = $this->create_sql();

    // Ejecutamos la consulta
    $query = $db->pl_query( $sql );

    $db->pl_close();

    return $query;
  }
}

// Clase Insert
class Insert extends Query
{
  // Asignamos el nombre de la base de datos
  public function __construct( $database_name )
  {
    $this->database_name = $database_name;
  }

  // Inicializamos las variables de la consulta
  protected $table_name;
  protected $columns = [];
  protected $values  = [];

  // Aplicamos la tabla al objeto
  public function insert( $table_name )
  {
    $this->insert = $table_name;
    return $this;
  }

  // Si hay columnas, las insertamos en la consulta
  public function columns( $columns )
  {
    // Inicializamos el array final de columnas
    $combined_columns = [];

    // Por cada columna, la añadimos al array final en forma de string
    foreach ( $columns as $column )
      $combined_columns[] = $column;

    $combined_columns = implode( ', ', $combined_columns );

    $this->columns = $combined_columns;
    return $this;
  }

  // Si hay valores, las insertamos en la consulta
  public function values( $values )
  {
    // Inicializamos el array final de valores
    $combined_values = [];

    foreach ( $values as $value )
    {
      // Si el campo es un string, le añadimos unas comillas dobles
      if( is_string( $value ) )
      $value = '"' . $value . '"';

      $combined_values[] = $value;
    }

    $combined_values = implode( ', ', $combined_values );

    $this->values = $combined_values;
    return $this;
  }

  // Método para crear la consulta SQL
  public function create_sql()
  {
    // Iniciamos la consulta con los parámetros por defecto
    $sql = "insert into {$this->database_name}.{$this->insert} ({$this->columns}) values ({$this->values})";
    
    return $sql;
  }

  // Función para ejecutar el SQL
  public function exec_sql()
  {
    $db = new pl_model( $this->database_name );

    // Creamos la consulta
    $sql = $this->create_sql();

    // Ejecutamos la consulta
    $query = $db->pl_query( $sql );

    $db->pl_close();

    return $query;
  }
}

?>