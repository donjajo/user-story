'use strict';

const sanizeHref = ( href ) => {
	const url = new URL( href );

	// console.log( url );
};
const userStoryObserverCallback = ( entries, observer ) => {
	const visibleLinks = []
	entries.forEach( ( entry ) => {
		if ( entry.isIntersecting ) {
			visibleLinks.push( sanizeHref( entry.target ) );
			console.log( entry.isIntersecting, entry.target );
		}

		// We don't need to observe anymore after page load
		userStoryObserver.unobserve( entry.target );
	} );
};

const userStoryObserver = new IntersectionObserver( userStoryObserverCallback, {
	rootMargin: '0px',
	threshold: 0.5,
} );
const userStoryInit = () => {
	sessionStorage.setItem( 'visibleLinks', [] );

	document.querySelectorAll( 'a[href]' ).forEach( ( link ) => {
		userStoryObserver.observe( link );
	} );
};

window.addEventListener( 'load', userStoryInit );
