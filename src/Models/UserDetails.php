<?php

class UserDetails
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `user_details` asociadas a un usuario.
   * 
   * @param int $user_id ID del usuario.
   * @return array Lista de detalles del usuario o un array vacío si no hay resultados.
   */
  public function GetRows( int $user_id ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.user_details 
      where
        user_id = ?
    ';
    $params = [$user_id];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }

  /**
   * Obtiene una fila específica de la tabla `user_details` según `detail_id2`.
   * 
   * @param string $detail_id2 Identificador del detalle del usuario.
   * @return array Datos del usuario si existe, o un array vacío si no hay resultados.
   */

  public function GetRow( string $detail_id2 ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.user_details 
      where
        detail_id2 = ?
    ';
    $params = [$detail_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}