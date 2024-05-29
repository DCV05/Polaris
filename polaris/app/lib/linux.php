<?php

function pl_linux_exec( $cmd )
{
    // Inicializamos las variables a retornar
    $output      = null;
    $return_code = null;

    // Ejecutamos el comando
    exec( $cmd, $output, $return_code );

    // Si no ha dado errores, retornamos el output
    if( $return_code === 0 )
        return $output;
    else
        return $return_code;
}

?>