var sx, sy;
var tcnt=0;
var last=-1;
var piclist=new Array();

/**
 * external variables:
 *
var tout=$tng_time  // timeout in seconds
piclist[$picnum]=   // list of pictures (paths)
var current=$curpic	// index of the current picture to start slideshow
var last=current	// index of the current picture to start slideshow
var lastpic=		// the highest picture index
**/

function blink( col ) {
	bg.style.backgroundColor=col;
	setTimeout(function(){bg.style.backgroundColor='#fff';}, 25);
}

function toggleSlideshow() {
	blink('#844');
	if( last == -1 ) {
		last=current
		tcnt=0;
		setTimeout( "nextpic()", 1000 );
	} else {
		last=-1
		tcnt=tout;
	}
}

function setPicDim(){ 
	// Dimensions of the display area
	var sheight=window.innerHeight-10; // -25;
	var swidth=window.innerWidth-6;
	// Size of the picture
	var pheight=mypic.naturalHeight;
	var pwidth=mypic.naturalWidth;
	// size difference and aspect ratio
	var hdiff=( sheight > pheight ) ? 0 : pheight - sheight;
	var wdiff=(  swidth > pwidth  ) ? 0 : pwidth - swidth;
	var prat=pwidth/pheight;

	// Fir image in display area
	if( ( hdiff != 0 ) || ( wdiff != 0 ) ) {
		if( wdiff > ( hdiff * prat ) ) {
			mypic.width=swidth
			mypic.height=-1
		} else {
			mypic.height=sheight
			mypic.width=-1
		}
	} else {
		mypic.width=-1;
		mypic.height=-1;
	}

	document.title=piclist[current];
}

window.addEventListener('orientationchange', setPicDim );

/**
 * load a new picture and update the status line
 */
function loadPic( pic ) {
	if( last != -1 ) {
		tcnt=0;
		var buff='*';
		for( i=tout; i>0; i--) buff=buff+'*';
		document.title=buff;
	} else {
		document.title="loading";
		blink('#666');
	}
	mypic.src=piclist[pic];
}

/**
 * touchscreen control
 */
document.addEventListener('touchmove', function(event) {
	event.defaultPrevented; // preventDefault();
}, false);

document.addEventListener('touchstart', function(event) {
	var touch = event.changedTouches[0]
	sx = touch.pageX
	sy = touch.pageY
	event.defaultPrevented; // preventDefault();
}, false);

document.addEventListener('touchend', function(event){
	var touch = event.changedTouches[0]
	var dirx = 1
	var diry = 1
	var orient, distx, disty
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

	if ( distx > 200 ) {
		if( ( current > 0 ) && ( dirx > 0 ) ) current=current-1
		if( ( current < lastpic ) && ( dirx < 0 ) ) current=current+1
		loadPic( current );
	} else 	if ( disty > 200 ) {
		if( diry < 0 ) history.back();
		else {
			toggleSlideshow();
		}
	}

	event.defaultPrevented; // preventDefault()
}, false)

/**
 * mouse control
 */
document.onclick = function(e) {
	var wwidth=window.innerWidth
	var wheight=window.innerHeight
	var newpic=current;
	if( e.pageY < 100 ) 
		history.back();
	else if( e.pageY > ( wheight - 100 ) ) {
		toggleSlideshow();
	} else {
		if( ( current > 0 ) && ( e.pageX < 100 ) ) newpic=current-1;
		if( ( current < lastpic ) && ( e.pageX > ( wwidth - 100 ) ) ) newpic=current+1;
		if( current != newpic ) {
			current = newpic;
			loadPic(current);
		}
	}
}

/**
 * Keyboard control
 */
document.onkeydown=function(e) {
	switch(e.keyCode) {
	case 37: // left
		if( current > 0 ) current=current-1;
		else current=lastpic;
		loadPic(current);
	break;
	case 38: // up
		history.back();
	break;
 	case 39: // right
		if( current < lastpic ) current=current+1;
		else current=0;
		loadPic(current)
	break;
	case 40: // down
		toggleSlideshow();
	break;
	}
}

function nextpic() {
	var buff='*';
	if( tcnt < tout ) {
		tcnt=tcnt+1;
		for( i=tout-tcnt; i>0; i--) buff=buff+'*';
		document.title=buff;
		setTimeout( "nextpic()", 1000 );
	} else {
		if( last != -1 ) {
			if( current < lastpic ) { 
				current=current+1
				last=current;
				loadPic( current );
				setTimeout( "nextpic()", 1000 );
			} else {
				history.back();
			}
		} else {
			document.title=piclist[current];
		}
	}
}

