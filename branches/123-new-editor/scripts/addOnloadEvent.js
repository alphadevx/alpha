// $Id$

// adds a funtion to the body/window onload event

// sample usages:
//addOnloadEvent(myFunctionName);

// Or to pass arguments

//addOnloadEvent(function(){ myFunctionName('myArgument') });

function addOnloadEvent(fnc){
  if ( typeof window.addEventListener != "undefined" )
    window.addEventListener( "load", fnc, false );
  else if ( typeof window.attachEvent != "undefined" ) {
    window.attachEvent( "onload", fnc );
  }
  else {
    if ( window.onload != null ) {
      var oldOnload = window.onload;
      window.onload = function ( e ) {
        oldOnload( e );
        window[fnc]();
      };
    }
    else 
      window.onload = fnc;
  }
}
