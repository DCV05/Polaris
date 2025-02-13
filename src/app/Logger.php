<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Clase AppLogger
 *
 * Esta clase proporciona un logger único para toda la aplicación.
 */
class AppLogger
{
  /** 
   * @var Logger|null $logger Instancia del logger de Monolog 
   */
  public ?Logger $logger = null;

  /**
   * Obtiene la instancia del logger. Si no existe, la crea.
   *
   * @return Logger Instancia del logger.
   */

  public function getLogger(): Logger
  {
    if( is_null( $this->logger ) )
    {
      // Instanciamos un Logger general para la aplicación
      $this->logger = new Logger( 'LoggerSocket' );

      // Determinamos el archivo donde se guardarán los log
      $this->logger->pushHandler( new StreamHandler( BASE_PATH . '/logs/app.log', Logger::DEBUG ) );
    }

    return $this->logger;
  }
}

?>