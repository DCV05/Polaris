<?php

$db = new Model( 'mysql' );

try
{
  // Verificamos si la base de datos ya existe
  $sql = 'show databases like "polaris"';
  $db->pl_query_prepared( $sql );

  if( $db->get_num_rows() > 0 )
    throw new Exception( 'Database already exists.' );

  // Definimos la ruta del archivo SQL de inicialización
  $sql_file = __DIR__ . '/init.sql';

  // Comprobamos si el archivo SQL existe
  if( !file_exists($sql_file ) )
    throw new Exception( 'SQL file does not exist: ' . $sql_file );

  // Leemos el contenido del archivo SQL
  $sql = file_get_contents( $sql_file );

  // Verificamos que el archivo no esté vacío o sea ilegible
  if( $sql === false || empty( $sql ) )
    throw new Exception( 'SQL file is empty or cannot be read.' );

  // Ejecutamos el script SQL
  if( !$db->multi_query( $sql ) )
    throw new Exception( 'Error executing SQL script.' );
}
catch( Exception $e ) {}
finally
{
  // Cerramos la conexión con la base de datos
  $db->close();
}

?>