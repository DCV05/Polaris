<?php

class pl_template_engine
{
	// Variables necesarias para compilar la máscara
	protected $template_path;
	protected $vars = [];

	// Variables de caché
	protected $cache_enabled  = true;
	protected $cache_path     = '/var/www/html/andromeda/polaris/polaris/storage/cache';
	protected $cache_lifetime = 300;

	/**
	 * En este caso, el constructor almacenará la plantilla, el directorio de la caché, y el tiempo de vida de la caché en segundos
	 * @param string $template_path
	 * @param string $cache_path
	 * @param int $cache_lifetime
	 *  */ 
	public function __construct(
		$template_path,
		$cache_enabled  = true,
		$cache_lifetime = 10,
		$cache_path     = '/var/www/html/andromeda/polaris/polaris/storage/cache'
	)
	{
		$this->template_path    = $template_path;
		$this->cache_path       = $cache_path;
		$this->cache_lifetime   = $cache_lifetime;
	}

	// Le asignamos a la plantilla unas variables
	public function vars( $vars )
	{
		$this->vars = $vars;
	}

	/**
	 * Función de compilación
	 * @param string $template
	 * @return string $template
	 *  */ 
	public function compile( $template )
	{
		// Establecemos una expresión regular para capturar los tags "{{ }}" y así, remplazar los valores con los de la plantilla
		$tag_patron = "/\{\{\s*([^}]+)\s*\}\}/";

		// Guardamos las similitudes en un array
		preg_match_all( $tag_patron, $template, $tags );

		// Comprobamos si existe algún valor por el que sustituir la etiqueta
		foreach ( $tags[1] as $tag )
		{
			// Formateamos el tag para evitar errores de compilación
			$tag = trim($tag);

			// Si la llave ( etiqueta ) existe en el array de valores insertado, la sustituimos por el valor vinculado
			if( array_key_exists( trim( $tag ), $this->vars ) )
				$template = str_replace( "{{ {$tag} }}", $this->vars[$tag], $template );
		}

		// Retornamos la plantilla formateada
		return $template;
	}

	/**
	 * Devuelve la ruta relativa del archivo caché que se va a crear
	 * @param string $template
	 *  */ 
	public function cache_file( $template )
	{
		return "{$this->cache_path}/" . md5( $template ) . '.cache';
	}

	/**
	 * Método para renderizar la plantilla
	 * @param string $template
	 *  */ 
	public function render_template( $template )
	{
		// Capturamos la ruta absoluta de la plantilla
		$path = "{$this->template_path}/{$template}";
		if( !file_exists( $path ) ) // Si la plantilla no existe, lo mostramos
			throw new Exception( "Error: plantilla no existente " );

		// Generamos la ruta relativa del archivo de caché
		$cache_file = $this->cache_file( $template );

		// Capturamos la última hora de modificación del nuevo archivo caché
		$filemtime = @filemtime( $cache_file );
		
		// Si permitimos la caché, existe una ruta relativa, y no ha pasado el tiempo de vida de la caché, leemos el archivo de la caché
		if( $this->cache_enabled && $filemtime && ( time() - $filemtime < $this->cache_lifetime ) )
		{
			readfile( $cache_file );
			exit;
		}
		else // En otro caso
		{
			// Capturamos el contenido del script sin mandarlo al naandromedador
			ob_start();

			// Capturamos el contenido de la plantilla y la compilamos
			$html = file_get_contents( $path );
			$compiled_html = $this->compile( $html );
			echo $compiled_html;

			if( $this->cache_enabled )
			{
				// Se verifica si el directorio de caché existe
				// Si no existe, lo creamos con permisos 755
				if( !is_dir( $this->cache_path ) )
				{
					mkdir( $this->cache_path, 0755, true );
				}

				// Capturamos el resultado en la ruta cache_file
				// Enviamos el contenido del buffer al naandromedador ( ob_get_flush )
				// La función de LOCK_EX es evitar que, mientras se está escribiendo el archivo, no haya otro proceso que pueda escirbir dentro de él
				file_put_contents( $cache_file, ob_get_flush(), LOCK_EX );
			}
			else
			{
				// En el caso de que la caché no esté habilitada, limpia el buffer de salida ( el del naandromedador), y no envía nada al cliente.
				ob_end_clean();
			}

		}
	}
}

?>