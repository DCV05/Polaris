<?php

class ActivitiesParticipants
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `activities_participants`.
   * 
   * @return array Lista de relaciones entre actividades y participantes o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.activities_participants';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `activities_participants` según `activity_id` y `participant_id`.
   * 
   * @param int $activity_id ID de la actividad.
   * @param int $participant_id ID del participante.
   * @return array Datos de la relación si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( int $activity_id, int $participant_id = null ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.activities_participants
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

  /**
   * Obtiene la lista de participantes de una actividad junto con sus detalles de usuario.
   * 
   * @param int $activity_id ID de la actividad.
   * @return array Lista de participantes con detalles de usuario.
   */
  public function GetActivityDetails( int $activity_id, int $participant_id = null ): array
  {
    $sql = '
      select 
          ap.*
        , p.participant_id2
        , p.participant_name
        , p.participant_allergies
        , p.participant_birth_date
        , p.participant_special_needs
      from ' . DB_PROJECT . '.activities_participants ap
      join ' . DB_PROJECT . '.participants p on ap.participant_id = p.participant_id
      where
        ap.activity_id = ?
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
  
  /**
  * Obtiene la lista de participantes de una actividad junto con sus detalles de usuario.
  * 
  * @param int $activity_id ID de la actividad.
  * @return array Lista de participantes con detalles de usuario.
  */
  public function GetAttendanceDetails( int $activity_id, int $participant_id = null ): array
  {
    $mod_participants             = new Participants();
    $mod_activities_participants  = new Attendance();

    // Obtenemos la lista de registros de asistencia
    $attendance_list = $mod_activities_participants->GetRow( $activity_id, $participant_id );
    if( empty( $attendance_list ) )
      return [];

    $detailed_list = [];

    // Recorremos cada registro de asistencia y obtenemos los datos del participante
    foreach( $attendance_list as $attendance )
    {
      $participant_details = $mod_participants->GetRow( $attendance['participant_id'] );
      $detailed_list[] = [
          'attendance'  => $attendance
        , 'participant' => $participant_details
      ];
    }

    return $detailed_list;
  }
}