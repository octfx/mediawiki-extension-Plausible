// Edit Button Tracking
( function () {
  if ( typeof window.plausible === 'undefined' ) {
	return;
  }
  
  var registerEvent = function() {
    var eventName = 'EditButtonClick';
    window.plausible( eventName, {
        props: {
          path: document.location.pathname
        }
    } );
  };
  
  var btns = {
    edit: document.querySelector( '#ca-edit a' ),
    veEdit: document.querySelector( '#ca-ve-edit a' ),
    // This is not great but there is no good selector to get the regular edit button
    sectionEdit: document.querySelector( '.mw-editsection a:last-of-type' ),
    sectionVeEdit: document.querySelector( '.mw-editsection-visualeditor a' )
  };

  for( var btn in btns ) {
    if( btns[ btn ] !== null ) {
      btns[ btn ].addEventListener( 'click', registerEvent );
    }
  }
}() );
