<?php

class Users
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `users`.
   * 
   * @return array Lista de usuarios o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.users';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `users` según `user_id2`.
   * 
   * @param string $user_id2 Identificador del usuario.
   * @return array Datos del usuario si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( string $user_id2 ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.users
      where
        user_id2 = ?
    ';
    $params = [$user_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}