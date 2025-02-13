<?php

class ActivitiesMonitors
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `activities_monitors`.
   * 
   * @return array Lista de relaciones entre actividades y monitores o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.activities_monitors';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `activities_monitors` según `activity_id` y `monitor_id`.
   * 
   * @param int $activity_id ID de la actividad.
   * @param int $monitor_id ID del monitor.
   * @return array Datos de la relación si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( int $activity_id, int $monitor_id ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.activities_monitors
      where
        activity_id = ? and
        monitor_id = ?
    ';
    $params = [$activity_id, $monitor_id];

    return $this->db->pl_query_prepared( $sql, $params, true );
  }
  

  /**
   * Obtiene una fila específica de la tabla `activities_monitors` según `monitor_id`.
   * 
   * @param int|string $monitor_id ID del monitor.
   * @return array Datos de la relación si existe, o un array vacío si no hay resultados.
   */
  public function GetMonitorRows( int|string $monitor_id ): array
  {
    $field = is_numeric( $monitor_id ) ? 'monitor_id' : 'monitor_id2';
    $sql = '
      select
          am.* 
        , a.*
      from ' . DB_PROJECT . '.activities_monitors am
      left join ' . DB_PROJECT . '.activities a on am.activity_id = a.activity_id
      where
        am.' . $field . ' = ?
    ';
    $params = [$monitor_id];

    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}