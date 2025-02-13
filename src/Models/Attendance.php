<?php

class Attendance
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `attendance`.
   * 
   * @return array Lista de registros de asistencia o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.attendance';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `attendance` según `activity_id`.
   * 
   * @param string $activity_id Identificador de la asistencia.
   * @return array Datos de la asistencia si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( int $activity_id, int $participant_id = null ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.attendance
      where
        activity_id = ?
    ';
    $params = [$activity_id];
    
    // Añadimos el parámetro del participante
    if( !is_null( $participant_id ) )
    {
      $sql      .= 'and participant_id = ?';
      $params[] = $participant_id;
    }
    
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}