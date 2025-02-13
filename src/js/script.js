// --------------------------------------------------------------------------------
// Librería de funciones de JS
// --------------------------------------------------------------------------------

function ajax_get( url, data = {} ) {

  // Petición AJAX
  $.ajax( {
    method: 'GET',
    url: url,
    data: data,
    success: function( response ) {
      return response;
    },
    error: function( error ) {
      console.error( error );
      return false;
    }
  } );
}

function ajax_post( url, data ) {

  // Petición AJAX
  $.ajax( {
    method: 'POST',
    url: url,
    data: data,
    success: function( response ) {
      return response;
    },
    error: function( error ) {
      console.error( error );
      return false;
    }
  } );
}

function pl_ajax_post( url, data ) {

  // Calculamos la URL de la petición
  let protocol   = window.location.protocol;
  let pathname   = window.location.pathname;
  pathname = pathname.substring( 1, pathname.length );
  
  let action_url = protocol + window.location.host + '?cn=' + encodeURIComponent( pathname ) + '&cm=' + encodeURIComponent( url );
  
  // Petición AJAX
  return $.ajax( {
    method: 'POST',
    url: action_url,
    dataType: 'json',
    data: data
  } );
}

function pl_ajax_post_files( url, data ) {
  // Calculamos la URL de la petición
  let protocol   = window.location.protocol;
  let pathname   = window.location.pathname.substring( 1 ); // Elimina el primer '/'
  let action_url = protocol + '//' + window.location.host + '?cn=' + encodeURIComponent( pathname ) + '&cm=' + encodeURIComponent( url );


  // Crear un objeto FormData
  let formdata = new FormData();

  // Agregar archivos al FormData
  for( let key in data ) {
    if( data[key] instanceof File )
      formdata.append( key, data[key] );
    else
      formdata.append( key, data[key] );
  }

  // Realizar la petición AJAX para subir archivos y datos
  return $.ajax( {
    method: 'POST',
    url: action_url,
    data: formdata,
    processData: false, // Evita que jQuery convierta el FormData en un string
    contentType: false, // Permite que el navegador configure automáticamente el encabezado Content-Type
    dataType: 'json',
  } );
}

function pl_dom( elements ) {

  elements.forEach( ( element ) => {

    // Dependiendo del método, ejecutamos una función u otra
    let node      = $( element.selector );
    let is_string = ( element.value > '' && element.value !== undefined && typeof element.value === 'string' );

    switch( element.method_name ) {
      case 'update':
        if( is_string )
          node.replaceWith( element.value );
        break;

      case 'remove':
        $( node ).remove();
        break;

      case 'prepend':
        if( is_string )
          node.prepend( element.value );
        break;

      case 'insertBefore':
        if( is_string )
          $( element.value ).insertBefore( node );
        break;

      case 'append':
        if( is_string )
          node.append( element.value );
        break;

      case 'hide':
        node.hide();
        break;

      case 'show':
        node.show();
        break;

      case 'css':
        if( element.css && is_string )
          node.css( element.css, element.value );
        break;

      case 'addClass':
        if( element.class_name )
          node.addClass( element.class_name );
        break;

      case 'removeClass':
        if( element.class_name )
          node.removeClass( element.class_name );
        break;

      case 'toggleClass':
        if( element.class_name )
          node.toggleClass( element.class_name );
        break;

      case 'execute':
        if( element.func_name && element.kwargs )
          window[element.func_name]( element.kwargs );
        break;

      default:
        console.error( `pl_dom: Undefined method '${element.method_name}' at`, element );
        break;
    }
  } );

}