<?php

class Payments
{
  private Model $db;

  public function __construct()
  {
    $this->db = new Model();
  }

  /**
   * Obtiene todas las filas de la tabla `payments`.
   * 
   * @return array Lista de pagos o un array vacío si no hay resultados.
   */
  public function GetRows(): array
  {
    $sql = 'select * from ' . DB_PROJECT . '.payments';
    return $this->db->pl_query_prepared( $sql, [], true );
  }

  /**
   * Obtiene una fila específica de la tabla `payments` según `payment_id2`.
   * 
   * @param string $payment_id2 Identificador del pago.
   * @return array Datos del pago si existe, o un array vacío si no hay resultados.
   */
  public function GetRow( string $payment_id2 ): array
  {
    $sql = '
      select
        * 
      from ' . DB_PROJECT . '.payments
      where
        payment_id2 = ?
    ';
    $params = [$payment_id2];
  
    return $this->db->pl_query_prepared( $sql, $params, true );
  }
}