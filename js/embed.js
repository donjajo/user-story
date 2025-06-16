'use strict';

import { sha1 } from 'js-sha1';
import apiFetch from '@wordpress/api-fetch';

const sanizeHref = ( href ) => {
	try {
		const url = new URL( href );

		return url.href;
	} catch ( TypeError ) {
		return null;
	}
};

const userStoryMkLink = ( target ) => {
	return {
		url: sanizeHref( target ),
		name: target.textContent,
		x: target.getBoundingClientRect().x,
		y: target.getBoundingClientRect().y,
	};
};

const userStoryGetHash = () => {
	return window.sessionStorage.getItem( 'userStoryHash' );
};

const userStorySetHash = ( hash ) => {
	window.sessionStorage.setItem( 'userStoryHash', hash );
};

const userStoryUpdateDevice = ( id ) => {
	window.localStorage.setItem( 'userStoryDeviceId', id );
};

const userStoryDeleteDevice = () => {
	window.localStorage.removeItem( 'userStoryDeviceId' );
};

const userStoryGetDevice = () => {
	return window.localStorage.getItem( 'userStoryDeviceId' );
};

const userStoryUpdate = async ( links ) => {
	return apiFetch( {
		path: '/user-story/links',
		method: 'POST',
		parse: false,
		headers: {
			'X-Device': userStoryGetDevice() || '',
		},
		data: {
			links,
			height: window.screen.height,
			width: window.screen.width,
			nonce: window.userStoryVars.nonce,
		},
	} )
		.then( ( response ) => {
			userStoryUpdateDevice( response.headers.get( 'X-Device' ) );
			return true;
		} )
		.catch( (error) => {
			// If status is 403, it is likely invalid device ID. Maybe site cleared their devices?
			if ( error?.status === 403) {
				// Clear and resend
				userStoryDeleteDevice()
				userStoryInit()
			}
			return false;
		} );
};

const userStoryObserverCallback = ( entries ) => {
	const visibleLinks = [];
	let hashValue = '';

	entries.forEach( ( entry ) => {
		if ( entry.isIntersecting ) {
			if ( sanizeHref( entry.target ) !== null ) {
				const link = userStoryMkLink( entry.target );

				hashValue += `${ link.url }${ link.name }${ link.x }${ link.y }`;
				visibleLinks.push( link );
			}
		}

		// We don't need to observe anymore after page load
		userStoryObserver.unobserve( entry.target );
	} );

	const hash = sha1( hashValue );
	if ( hash !== userStoryGetHash() ) {
		userStoryUpdate( visibleLinks ).then( ( re ) => {
			if ( re ) {
				userStorySetHash( hash );
			}
		} );
	}
};

const userStoryObserver = new window.IntersectionObserver(
	userStoryObserverCallback,
	{
		rootMargin: '0px',
		threshold: 0.5,
	}
);
const userStoryInit = () => {
	document.querySelectorAll( 'a[href]' ).forEach( ( link ) => {
		userStoryObserver.observe( link );
	} );
};

window.addEventListener( 'load', userStoryInit );
