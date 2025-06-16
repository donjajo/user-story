'use strict';

import { sha1 } from 'js-sha1';
import apiFetch from '@wordpress/api-fetch';

/**
 * Sanitizes and validates a given URL string.
 *
 * The function attempts to construct a URL object from the provided href.
 * If the input is a valid URL, it returns the sanitized URL string.
 * If the input is invalid or cannot be parsed as a URL, it returns null.
 *
 * @param {string} href - The input URL string to be sanitized and validated.
 * @return {string|null} The sanitized URL string if valid, or null if invalid.
 */
const sanizeHref = ( href ) => {
	try {
		const url = new URL( href );

		return url.href;
	} catch ( TypeError ) {
		return null;
	}
};

/**
 * Generates an object representing a link with details extracted from the given DOM target.
 *
 * @param    {HTMLElement} target - The DOM element representing the target link.
 * @return {Object} An object containing the sanitized URL, link name, and the x and y coordinates of the link's bounding rectangle.
 * @property {string}      url    - The sanitized href of the target link.
 * @property {string}      name   - The text content of the target link.
 * @property {number}      x      - The x-coordinate of the target link's bounding rectangle.
 * @property {number}      y      - The y-coordinate of the target link's bounding rectangle.
 */
const userStoryMkLink = ( target ) => {
	return {
		url: sanizeHref( target ),
		name: target.textContent,
		x: target.getBoundingClientRect().x,
		y: target.getBoundingClientRect().y,
	};
};

/**
 * Retrieves the value of 'userStoryHash' from the browser's session storage.
 *
 * This function accesses the session storage and fetches the item associated
 * with the key 'userStoryHash'. If no value is stored under this key, it
 * will return null.
 *
 * @function
 * @return {string|null} The value of 'userStoryHash' from session storage, or null if not present.
 */
const userStoryGetHash = () => {
	return window.sessionStorage.getItem( 'userStoryHash' );
};

/**
 * Updates the session storage with the provided user story hash.
 *
 * This function takes a hash string as an argument and saves it to the browser's
 * session storage under the key 'userStoryHash'. The stored value persists for the duration
 * of the browser session and is cleared once the session ends.
 *
 * @param {string} hash - The hash value to store in session storage. It represents
 *                      the unique identifier or state for a user story.
 */
const userStorySetHash = ( hash ) => {
	window.sessionStorage.setItem( 'userStoryHash', hash );
};

/**
 * Updates the device identifier for a user story in local storage.
 *
 * This function takes a device ID as its parameter and stores it in the
 * browser's local storage with the key 'userStoryDeviceId'. This allows
 * the application to persist the device ID across sessions.
 *
 * @param {string} id - The device identifier to be stored in local storage.
 */
const userStoryUpdateDevice = ( id ) => {
	window.localStorage.setItem( 'userStoryDeviceId', id );
};

/**
 * Deletes the user story device ID from the browser's local storage.
 *
 * This function removes the stored value associated with the key 'userStoryDeviceId'
 * from the localStorage, effectively deleting the reference to a user story device ID.
 * It does not return any value.
 */
const userStoryDeleteDevice = () => {
	window.localStorage.removeItem( 'userStoryDeviceId' );
};

/**
 * Retrieves the user story device identifier from the browser's local storage.
 *
 * @function userStoryGetDevice
 * @return {string|null} The stored user story device ID, or null if it does not exist.
 */
const userStoryGetDevice = () => {
	return window.localStorage.getItem( 'userStoryDeviceId' );
};

/**
 * Updates the user story with the provided links by sending a POST request to the server.
 *
 * @async
 * @function userStoryUpdate
 * @param {Array} links - An array of link objects to be sent to the server.
 * @return {Promise<boolean>} - Resolves to `true` if the update is successful, otherwise resolves to `false`.
 */
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
		.catch( ( error ) => {
			// If status is 403, it is likely invalid device ID. Maybe site cleared their devices?
			if ( error?.status === 403 ) {
				// Clear and resend
				userStoryDeleteDevice();
				userStoryInit();
			}
			return false;
		} );
};

/**
 * Callback function to handle observed elements within the user story interface.
 * This function processes intersection entries, identifies visible links, updates
 * the hash for integrity, and triggers updates when necessary.
 *
 * @function userStoryObserverCallback
 * @param {IntersectionObserverEntry[]} entries - An array of intersection observer entries representing elements being monitored for visibility changes.
 */
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

/**
 * Initializes user story observation by selecting all anchor elements
 * with href attributes on the document and attaching them to the
 * userStoryObserver instance.
 *
 * This function is used to track or handle interactions with links
 * that have defined href attributes within the document.
 */
const userStoryInit = () => {
	document.querySelectorAll( 'a[href]' ).forEach( ( link ) => {
		userStoryObserver.observe( link );
	} );
};

window.addEventListener( 'load', userStoryInit );
