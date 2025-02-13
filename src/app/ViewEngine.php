<?php

/**
 * @author Daniel Correa Villa <daniel.correa@kodalogic.com>
 * 18/03/2024
 * 
 * Compilador de máscaras Polaris
 * 
 */
class ViewEngine
{
	/**
	 * @var string Ruta absoluta del archivo de plantilla.
	 */
	protected string $template_path;

	/**
	 * @var object Controlador asociado que contiene la lógica para la vista.
	 */
	protected object $controller;

	/**
	 * @var string Nombre del controlador utilizado
	 */
	protected string $controller_name;

	/**
	 * En este caso, el constructor almacenará la plantilla
	 * @param string $template_path
	 *  */ 
	public function __construct( string $template_path, object $controller, string $controller_name )
	{
		$this->template_path 		= $template_path;
		$this->controller				= $controller;
		$this->controller_name	= $controller_name;
	}

	/**
	 * Función de compilación
	 * @param string $template
	 * @return string $template
	 *  */ 
	public function compile( $template_html ): string
	{
		// Establecemos una expresión regular para capturar los tags
		$tag_pattern = "/\[\[\s*([^\|\]\s]+(?:\.[^\|\]\s]+)?)\s*(?:\|\s*([^\]]+))?\s*\]\]/";
		preg_match_all( $tag_pattern, $template_html, $matches );

		/*
			Array
			(
				[0] => Array
					(
						[0] => [[ polaris.page.title_seo ]]
						[1] => [[ func | &headers ]]
						[2] => [[ label | test ]]
						[3] => [[ polaris.page.page_title ]]
						[4] => [[ polaris.page.page_title ]]
					)

				[1] => Array
					(
						[0] => polaris.page.title_seo
						[1] => func
						[2] => label
						[3] => polaris.page.page_title
						[4] => polaris.page.page_title
					)

				[2] => Array
					(
						[0] => 
						[1] => &headers 
						[2] => test 
						[3] => 
						[4] => 
					)
			)
		*/
		
		// Separar en arrays
		$labels 		= [];
		$tags 			= [];
		$functions 	= [];
		foreach( $matches[1] as $key => $match )
		{
			// Funciones
			if( $match == 'func' && !empty( $matches[2][$key] ) )
				$functions[] = $match . " | " . $matches[2][$key];
			
			// Labels
			elseif( $match == 'label' && !empty( $matches[2][$key] ) )
				$labels[] = trim( $match . " | " . $matches[2][$key] );

			// Tags
			else
				$tags[] = $match;
		}

		// Intentamos ejecutar las funciones de la máscara
		foreach( $functions as $func_name )
		{
			$func_name = trim( $func_name );

			try
			{
				// Llamamos al método del controlador
				$func_parts 				= array_map( 'trim', explode( '|', $func_name ) );
				$callable_func_name = $func_parts[1];

				// Controlamos que vuelvan a llamar a la función index
				if( $callable_func_name == 'index' )
					throw new Exception( 'INDEX method cannot be executed again' );

				// Detecta las funciones globales
				$is_global = $callable_func_name[0] === '&';

				// Calculamos el nombre de la función
				$callable_func_name = $is_global
					? 'app_' . substr( $callable_func_name, 1 )
					: $callable_func_name
				;

				// Si tiene un parámetro, ejecutamos la función o método con el parámetro
				if( isset( $func_parts[2] ) ) // Funciones y métodos con parámetros
				{
					// Ejecutamos la función
					$func_result = $is_global
						? $callable_func_name( $func_parts[2] )
						: $this->controller->$callable_func_name( $func_parts[2] );
				}
				else // Funciones y métodos sin parámetros
				{
					$func_result = $is_global
						? $callable_func_name()
						: $this->controller->$callable_func_name();
				}

				// Reemplazamos el HTMl en la máscara
				$template_html 	= str_replace( "[[ {$func_name} ]]", $func_result, $template_html );
			}
			catch( Exception $e )
			{
				print $e->getMessage();
				continue;
			}
		}

		// Compilamos los labels
		foreach( $labels as $label )
		{
			// Extraemos el label y buscamos su significado
			$label_name = trim( explode( ' | ', $label )[1] );

			// Buscamos su valor en la sesión
			if( !empty( $_SESSION['labels'][$label_name] ) )
				$label_value = $_SESSION['labels'][$label_name][DEF_LANG];
			else
				$label_value = '!' . $label_name;

			// Reemplazamos el HTML
			$template_html = str_replace( '[[ ' . $label . ' ]]', $label_value, $template_html );
		}
		
		// Intentamos buscar las constantes
		foreach( $tags as $tag_name )
		{
			try
			{
				// Capturamos el nombre de la constante y las llaves por las que navegar
				$tag_parts  = explode( '.', $tag_name );
				$const_name = array_shift( $tag_parts );

				// ------------------------------------------------------------------------------------------
				// Manejo de constantes
				// ------------------------------------------------------------------------------------------
				if( defined( $const_name ) )
				{
					$value 	= constant( $const_name );
					$result = $this->navigate_array( $value, $tag_parts, $tag_name );
				}

				// ------------------------------------------------------------------------------------------
				// Manejo de sesiones
				// ------------------------------------------------------------------------------------------
				elseif( isset( $_SESSION[$const_name] ) )
				{
					$value 	= $_SESSION[$const_name];
					$result = $this->navigate_array( $value, $tag_parts, $tag_name );
				}

				// ------------------------------------------------------------------------------------------
				// Manejo de propiedades en controladores
				// ------------------------------------------------------------------------------------------
				elseif( isset( $this->controller->$const_name ) )
				{
					$value 	= $this->controller->$const_name;
					$result = $this->navigate_array( $value, $tag_parts, $tag_name );
				} 

				// Si no es válido
				else
				{
					$result = '!' . $tag_name;
				}

				// Reemplazamos el HTML
				$template_html = str_replace( '[[ ' . $tag_name . ' ]]', $result, $template_html );
			}
			catch( Exception $e )
			{
				print $e->getMessage();
				exit;
			}
		}

		// Retornamos la plantilla formateada
		return $template_html;
	}

	/**
	 * Navega por un array o un objeto basado en un conjunto de claves.
	 * @param mixed $value
	 * @param array $keys
	 * @param string $tag_name
	 * @return mixed
	 */
	private function navigate_array( mixed $value, array $keys, string $tag_name ): mixed
	{
		// Si no hay más claves que procesar, devolvemos el valor final
		if( empty( $keys ) )
		{
			return is_string( $value ) || is_numeric( $value )
				? $value 						// Si es un string o número, lo devolvemos
				: '!' . $tag_name 	// Si no, devolvemos un error
			;
		}

		// Capturamos la siguiente clave del array
		$key = array_shift( $keys );

		// Si es un array y la clave existe, seguimos recorriendo
		if( is_array( $value ) && isset( $value[$key] ) )
			return $this->navigate_array( $value[$key], $keys, $tag_name );

		// Si es un objeto y la clave existe, seguimos recorriendo
		if( is_object( $value ) && isset( $value->$key ) )
			return $this->navigate_array( $value->$key, $keys, $tag_name );

		// Si no encontramos la clave, devolvemos un error
		return '!' . $tag_name;
	}

	/**
	 * Método para renderizar la plantilla
	 * */ 
	public function render_template(): void
	{
		// Capturamos la ruta absoluta de la plantilla
		if( !file_exists( $this->template_path ) ) // Si la plantilla no existe, lo mostramos
			throw new Exception( "Error: Template not found" );

		// Capturamos el contenido de la plantilla y la compilamos
		$html = file_get_contents( $this->template_path );
		$compiled_html = $this->compile( $html );

		// Guardamos el controlador en la sesión
		if( empty( $_SESSION['controllers'][$this->controller_name] ) )
			$_SESSION['controllers'][$this->controller_name] = serialize( $this->controller );

		echo $compiled_html;
	}
}

?>