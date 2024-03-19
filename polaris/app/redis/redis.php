<?php

class pl_redis
{
	public function __construct( $key, $sql )
	{
		$this->key = $key;
		$this->sql = $sql;

			// Iniciamos la sesión con Redis
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$this->redis = $redis;
	}

	public function check_redis_key( $database_name )
	{
		// Si existe la clave redis, sacamos su valor
		if( $this->redis->exists( $this->key ) )
		{
			$value = unserialize( $this->redis->get( $this->key ) );
		}
		else
		{
			$db = new pl_model( $database_name );

			// Realizamos la consulta
			$value = $db->pl_query( $this->sql, false );

			// Insertamos la clave redis y su valor en Redis con un tiempo de expiración de una hora
			$this->redis->set( $this->key, serialize( $value ) );
			$this->redis->expire( $this->key, 3600 );
		}

		return $value;
	}
}

?>