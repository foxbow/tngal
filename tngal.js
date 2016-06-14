/**
 * buffer variables to recognize drag events
 */
var sx, sy;
/**
 * countdown variable starts at 0 and goes up to tout
 * is set to -1 when a new image is being loaded and 
 * set to 0 when the image has been resized properly
 */
var tcnt=0;
/**
 * The last picture that was displayed
 * used to check state of the slideshow
 * When -1 no slideshow is running
 */
var last=-1;
/**
 * the list of pictures in the current directory
 * this needs to be filled by the page (see below)
 */
var piclist=new Array();
/**
 * buffer to hold the current cursor before changing so it can be reset properly
 */
var lastcur="default";
/**
 * the current picture that is being displayed
 */
var current=-1;
/**
 * id of the current timeout timer for the heartbeat
 */
var nptimer=-1;

/**
 * timeout default
 */
var tout=5;

/**
 * The <img> element holding the actual picture
 * assigned in initPicViewer()
 */
var mypic;
/**
 * The <div> element holding the <img> elemont
 * assigned in initPicViewer()
 */
var bg;

/**
 * external variables - to be set in the page
 *
piclist[..]=<path>	// list of pictures (paths)
current=<int>		// index of the current picture to start slideshow
**/

function genThumb( dir, file ) {
	var xmlhttp;
	xmlhttp=new XMLHttpRequest();

	xmlhttp.onreadystatechange=function() {
  		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById(file).src=xmlhttp.responseText;
  		}
	}

	xmlhttp.open('GET', '?tng_cmd=genThumb&file='+file+'&dir='+dir, true);
	xmlhttp.send();
}

/**
 * lets the background (div) flash with the given colour
 */
function blink( col ) {
	bg.style.backgroundColor=col;
	setTimeout(function(){bg.style.backgroundColor='#000';}, 25);
}

/**
 * switch slideshow mode on and off
 */
function toggleSlideshow() {
	blink('#844');
	if( last == -1 ) {
		last=current
		tcnt=0;
		if( nptimer == -1 ) heartbeat();
	} else {
		last=-1
		tcnt=tout;
		document.title=piclist[current];
	}
}

/**
 * resize and position the current image
 * also called on orientation changes of small devices
 * resets title and cursor to show finished loading the image
 */
function setPicDim(){ 
	// Dimensions of the display area
	var sheight=window.innerHeight-4; // Avoid right scrollbar
	var swidth=window.innerWidth;
	// Size of the picture
	var pheight=mypic.naturalHeight;
	var pwidth=mypic.naturalWidth;

	// size difference and aspect ratio
	var hdiff=( sheight > pheight ) ? 0 : pheight - sheight;
	var wdiff=(  swidth > pwidth  ) ? 0 : pwidth - swidth;
	var prat=pwidth/pheight;

	// Fit image in display area
	if( ( hdiff != 0 ) || ( wdiff != 0 ) ) {
		if( wdiff > ( hdiff * prat ) ) {
			mypic.width=swidth
			mypic.height=swidth/prat;
		} else {
			mypic.width=sheight*prat;
			mypic.height=sheight
		}
	} else {
		mypic.width=-1;
		mypic.height=-1;
	}

	// Center image vertically
	if( ( sheight-mypic.height ) > 1 ){ 
		mypic.style.paddingTop=((sheight-mypic.height)/2)+'px';
		mypic.style.paddingBottom=mypic.style.paddingTop;
	} else {
		mypic.style.paddingTop='0px'
		mypic.style.paddingBottom='0px'
	}
	
	bg.style.cursor=lastcur;
	if( last != -1 ) {  						// Slideshow active?
		if ( tcnt == -1 ) {						// Next picture? 
			tcnt=0;
		} else if( current != last ) {			// manual change?
			last = current;
			tcnt=0;
		}
	} else {
		document.title=piclist[current];
	}
}

/**
 * initialize all callbacks after the basic page is set up.
 * especially firefox does not like forward declarations
 * on callbacks, i.e.: document.body when there is no <BODY>
 * tag yet.
 */
function initPicViewer( numpic, timeout ) {
	tout=timeout;
	mypic=document.getElementById('image');
	bg=document.getElementById('bgd');

	window.addEventListener('orientationchange', setPicDim );
	
	/**
     * set the cursor on active positions
     */
	document.onmousemove=function( e ) {
		var wwidth=window.innerWidth/3
		var wheight=window.innerHeight/3

		if( ( current > 0 ) && ( e.pageX < wwidth ) ) {
			bg.style.cursor="w-resize";
		} else if( ( current < piclist.length-1 ) && ( e.pageX > ( 2*wwidth ) ) ) {
			bg.style.cursor="e-resize";
		} else if( e.pageY < wheight ) {
			bg.style.cursor="n-resize";
		} else if( e.pageY > ( 2*wheight ) ) {
			bg.style.cursor="col-resize";
		} else {
			bg.style.cursor="default";
		}
	};

	/**
	 * touchscreen control
	 */
	bg.addEventListener('touchmove', function(event) {
		event.defaultPrevented; // preventDefault();
	}, false);

	bg.addEventListener('touchstart', function(event) {
		var touch = event.changedTouches[0]
		sx = touch.pageX
		sy = touch.pageY
		event.defaultPrevented; // preventDefault();
	}, false);

	bg.addEventListener('touchend', function(event){
		var touch = event.changedTouches[0]
		var dirx = 1
		var diry = 1
		var orient, distx, disty
		var wwidth=window.innerWidth/3
		var wheight=window.innerHeight/3
		distx = touch.pageX - sx
		disty = touch.pageY - sy

		if ( distx < 0 ) {
			dirx = -1
			distx = -distx
		}
		if ( disty < 0 ) {
			diry = -1
			disty = -disty
		}

		if ( distx > wwidth ) {
			if( dirx > 0 ) {
				nextpic(-1);
			} else {
				nextpic( +1 );
			}
		} else 	if ( disty > wheight ) {
			if( diry < 0 ) {
				history.back();
			} else {
				toggleSlideshow();
			}
		}

		event.defaultPrevented; // preventDefault()
	}, false)

	/**
	 * mouse control
	 */
	document.onclick = function(e) {
		var wwidth=window.innerWidth/3
		var wheight=window.innerHeight/3
		
		if( e.pageX < wwidth ) {
			nextpic(-1);
		} else if( e.pageX > ( 2*wwidth ) ) {
			nextpic(+1);
		} else if( e.pageY < wheight ) {
			history.back();
		} else if( e.pageY > ( 2*wheight ) ) {
			toggleSlideshow();
		} else {
			window.open(piclist[current]);
		}
	}

	/**
	 * Keyboard control
	 */
	document.onkeydown=function(e) {
		switch(e.keyCode) {
		case 32: // space
			window.open(piclist[current]);
		break;
		case 37: // left
			nextpic(-1);
		break;
		case 38: // up
			history.back();
		break;
	 	case 39: // right
			nextpic(1);
		break;
		case 40: // down
			toggleSlideshow();
		break;
		}
	}

	loadPic( numpic );
	heartbeat();
}

/**
 * load a new picture and update the status line
 */
function loadPic( pic ) {
	if( pic != current ) {
		if( last != -1 ) {
			tcnt=-1;
		} else {
			blink('#888');
		}
		document.title="loading";
		lastcur=bg.style.cursor;
		bg.style.cursor="wait";
		current=pic;
		mypic.src=piclist[pic];
	} else {
		if( last != -1 ) {
			tcnt=0;
		}
	}
}

/**
 * loads the prev/next picture in the row
 * wraps ends to have an infinite slideshow
 */
function nextpic( dir ) {
	if( dir < 0 ) {
		if( current > 0 ) loadPic( current-1 );
		else loadPic( piclist.length-1 );
	} else {
		if( current < piclist.length-1 ) loadPic( current+1 );
		else loadPic( 0 );
	}
}

/**
 * internal timer to control slideshows
 */
function heartbeat() {
	var buff='*';
	// Slideshow active?
	if( last != -1 ) {
		// loading?
		if ( tcnt != -1 ) {
			if( tcnt < tout ) {
				tcnt=tcnt+1;
				for( i=tout-tcnt; i>0; i--) buff=buff+'*';
				document.title=buff;
			} else {
				if( current < piclist.length-1 ) { 
					last=current+1;
					loadPic( last );
				} else {
					last=0;
					loadPic( last );
				}
			}
		}
	}
	nptimer=setTimeout( "heartbeat()", 1000 );
}

