<?php

class SchedulesMonitors
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `schedules_monitors`.
   * 
   * @return array Lista de horarios o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.schedule_monitors';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `schedules_monitors` según `schedule_id2`.
   * 
   * @param string $schedule_id2 Identificador del horario.
   * @return array Datos del horario si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( string $schedule_id2 ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.schedule_monitors
      where
        schedule_id2 = ?
    ';
    $params = [$schedule_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
  

  /**
   * Obtiene los eventos de un monitor en formato JSON para un calendario.
   * 
   * @param string $monitor_id2 ID del monitor.
   * @return array Array de eventos.
   */
  public function GetEvents( string $monitor_id2 ): array
  {
    $sql = '
      select
        s.*
      from ' . DB_PROJECT . '.schedule_monitors s
      left join ' . DB_PROJECT . '.users u on s.monitor_id = u.user_id
      where
        u.user_id2 = ?
    ';
    $params = [$monitor_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}