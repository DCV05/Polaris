<?php

class SchedulesParticipants
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `schedules_participants`.
   * 
   * @return array Lista de horarios o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.schedule_participants';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `schedules_participants` según `schedule_id2`.
   * 
   * @param string $schedule_id2 Identificador del horario.
   * @return array Datos del horario si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( string $schedule_id2 ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.schedule_participants
      where
        schedule_id2 = ?
    ';
    $params = [$schedule_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }

  /**
   * Obtiene los eventos de un participante en formato JSON para un calendario.
   * 
   * @param string $participant_id ID del participante.
   * @return array Array de eventos.
   */
  public function GetEvents( string $participant_id2 ): array
  {
    $sql = '
      select
        s.*
      from ' . DB_PROJECT . '.schedule_participants s
      left join ' . DB_PROJECT . '.participants p on s.participant_id = p.participant_id
      where
        p.participant_id2 = ?
    ';
    $params = [$participant_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}