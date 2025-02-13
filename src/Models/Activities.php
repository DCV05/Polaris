<?php

class Activities
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `activities`.
   * 
   * @return array Lista de actividades o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.activities';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `activities` según `activity_id2`.
   * 
   * @param string $activity_id2 Identificador de la actividad.
   * @return array Datos de la actividad si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( string $activity_id2 ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.activities
      where
        activity_id2 = ?
    ';
    $params = [$this->db->esc( $activity_id2 )];

    return $this->db->pl_query_prepared( $sql, $params, true );
  }

  /**
   * Obtiene la actividad asociada a un participante específico.
   * 
   * @param int $participant_id ID del participante.
   * @return array Datos de la actividad si existe, o un array vacío si no hay resultados.
   */
  public function GetParticipantLinkedRows( int $participant_id ): array
  {
    $sql = '
      select
        a.*
      from ' . DB_PROJECT . '.activities a
      left join ' . DB_PROJECT . '.activities_participants ap on a.activity_id = ap.activity_id
      where
        ap.participant_id = ?';
    $params = [$participant_id];

    return $this->db->pl_query_prepared( $sql, $params, true );
  }

  /**
   * Obtiene la actividad asociada a un participante específico.
   * 
   * @param int $monitor_id ID del participante.
   * @return array Datos de la actividad si existe, o un array vacío si no hay resultados.
   */
  public function GetMonitorLinkedRows( int $monitor_id ): array
  {
    $sql = '
      select
        a.*
      from ' . DB_PROJECT . '.activities a
      left join ' . DB_PROJECT . '.activities_monitors am on a.activity_id = am.activity_id
      where
        am.monitor_id = ?';
    $params = [$monitor_id];
    
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}