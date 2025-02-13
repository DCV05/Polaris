<?php

/**
 * --------------------------------------------------------------------------------------------------------------------
 *     ____        __           _     
 *    / __ \____  / /___ ______(_)____
 *   / /_/ / __ \/ / __ `/ ___/ / ___/
 *  / ____/ /_/ / / /_/ / /  / (__  ) 
 * /_/    \____/_/\__,_/_/  /_/____/
 *
 * MADE BY KODALOGIC
 * 
 * @author  Daniel Correa Villa <daniel.correa@kodalogic.com>
 * @link    https://kodalogic.com
 * 
 * --------------------------------------------------------------------------------------------------------------------
 * 
 * Este script inicializa el framework Polaris y configura el entorno de la aplicación.
 * 
 * Funcionalidades principales:
 * - Configuración de errores y debugging.
 * - Definición de rutas y constantes del sistema.
 * - Carga del SDK y librerías esenciales.
 * - Configuración del idioma por defecto.
 * - Configuración de la base de datos.
 * - Inicialización del sistema de logging con Monolog.
 * - Carga de etiquetas de la aplicación.
 * 
 * --------------------------------------------------------------------------------------------------------------------
 * DEPENDENCIAS
 * --------------------------------------------------------------------------------------------------------------------
 * 
 * Este script requiere las siguientes dependencias, definidas en el archivo `composer.json`:
 * 
 * - monolog/monolog (^3.8)       → Sistema de logging basado en PSR-3.
 * - erguncaner/table (dev-master) → Generador de tablas en HTML con PHP.
 * 
 * Para instalar las dependencias, ejecutar:
 * ```
 * composer install
 * ```
 */

// ---------------------------------------------------------------------------------------------------------------------
// CONFIGURACIÓN DEL ENTORNO
// ---------------------------------------------------------------------------------------------------------------------

// Configuración de errores y debugging.
// - Muestra errores en pantalla para debugging en entorno de desarrollo.
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

// ---------------------------------------------------------------------------------------------------------------------
// DEFINICIÓN DE RUTAS Y CONSTANTES
// ---------------------------------------------------------------------------------------------------------------------

// Define las rutas principales del sistema.
define( 'BASE_PATH'  , __DIR__ );
define( 'MAIN_PATH'  , __DIR__ . '/src' );
define( 'APP_PATH'   , __DIR__ . '/src/app' );
define( 'ASSETS_PATH', __DIR__ . '/src/assets' );

// ---------------------------------------------------------------------------------------------------------------------
// CARGA DEL SDK Y ARRANQUE DEL FRAMEWORK
// ---------------------------------------------------------------------------------------------------------------------

// Carga el SDK del framework y ejecuta su inicialización.
require_once( APP_PATH . '/sdk.php' );
pl_start();

// ---------------------------------------------------------------------------------------------------------------------
// CONFIGURACIÓN DEL IDIOMA
// ---------------------------------------------------------------------------------------------------------------------

// Define el idioma por defecto del sistema. 
// - Si está definido en la sesión, lo usa.
// - Si no está definido, intenta detectar el idioma del navegador.
if( !defined( 'DEF_LANG' ) && !empty( $_SESSION['polaris']['def_lang'] ) )
  define( 'DEF_LANG', $_SESSION['polaris']['def_lang'] );
elseif( !defined( 'DEF_LANG' ) && empty( $_SESSION['polaris']['def_lang'] ) )
  define( 'DEF_LANG', pl_get_browser_language( ['es', 'en'], 'es' ) );

// ---------------------------------------------------------------------------------------------------------------------
// CARGA DE LIBRERÍAS Y DEPENDENCIAS
// ---------------------------------------------------------------------------------------------------------------------

// Inicializa Composer y carga las dependencias del proyecto.
require_once( __DIR__ . '/vendor/autoload.php' );

// Incluye las librerías esenciales del framework.
require_once( APP_PATH . '/Model.php'      );
require_once( APP_PATH . '/Router.php'     );
require_once( APP_PATH . '/ViewEngine.php' );
require_once( APP_PATH . '/app.php'        );
require_once( APP_PATH . '/Logger.php'     );

// ---------------------------------------------------------------------------------------------------------------------
// CONFIGURACIÓN DE LA BASE DE DATOS
// ---------------------------------------------------------------------------------------------------------------------

// Carga la configuración de la base de datos desde el archivo `config.ini`
// y define las constantes de conexión.
$config = parse_ini_file( __DIR__ . '/config.ini', true  );

define( 'DB_SERVER'   , $config['mysql']['db_server']   );
define( 'DB_USER'     , $config['mysql']['db_user']     );
define( 'DB_PASSWORD' , $config['mysql']['db_password'] );
define( 'DB_SYS'      , $config['mysql']['db_sys']      );
define( 'DB_PROJECT'  , $config['mysql']['db_project']  );

// ---------------------------------------------------------------------------------------------------------------------
// CARGADO DE MODELOS
// ---------------------------------------------------------------------------------------------------------------------

// Capturamos los modelos definidos en el directorio src/Models
$models = glob( __DIR__ . '/src/Models/*.php' );
foreach( $models as $model )
{
  // Si es una clase, la incluímos
  try
  {
    require_once( $model );
  }
  catch( Exception $e )
  {
    continue;
  }
}

// ---------------------------------------------------------------------------------------------------------------------
// CONFIGURACIÓN DEL LOGGER (MONOLOG)
// ---------------------------------------------------------------------------------------------------------------------

// Inicializa el sistema de logging global utilizando Monolog.
// Crea una variable global `$logger` accesible en toda la aplicación.
global $logger;
$logger = ( new AppLogger() )->getLogger();

// ---------------------------------------------------------------------------------------------------------------------
// CARGA DE ETIQUETAS DE IDIOMA
// ---------------------------------------------------------------------------------------------------------------------

// Carga las etiquetas de idioma desde un archivo JSON y las almacena en la sesión.
$labels_json        = file_get_contents( APP_PATH . '/labels.json' );
$_SESSION['labels'] = json_decode( $labels_json, true );

?>