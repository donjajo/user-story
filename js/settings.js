'use strict';

import domReady from '@wordpress/dom-ready';
import { useEffect, useState, render } from '@wordpress/element';
import {
	Panel,
	PanelBody,
	PanelRow,
	TextControl,
	CustomSelectControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import moment from 'moment';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const SettingsPage = () => {
	const [ filter, setFilter ] = useState( {
		start_date: moment().format( 'YYYY-MM-DD' ),
		end_date: moment().format( 'YYYY-MM-DD' ),
		screen: {
			key: '',
			name: __( 'All screens', 'user-story' ),
		},
		host: {
			key: '',
			name: __( 'All Domains', 'user-story' ),
		},
	} );
	const [ isFetching, setIsFetching ] = useState( false );
	const [ data, setData ] = useState( [] );
	const [ filterData, setFilterData ] = useState( {
		screens: [],
		hosts: [],
	} );

	const updateFilter = ( key, value ) => {
		setFilter( {
			...filter,
			[ key ]: value,
		} );
	};

	/**
	 * Fetches filter data from the server.
	 *
	 * This function sends a request to the endpoint '/user-story/links/filter-data'
	 * to retrieve filter-related data. Once the data is successfully fetched,
	 * it updates the filter data state with the received response.
	 *
	 * @function
	 * @return {Promise<void>} A promise that resolves when the filter data has been fetched and the state is updated.
	 */
	const fetchFilterData = () => {
		return apiFetch( {
			path: '/user-story/links/filter-data',
		} ).then( ( response ) => {
			setFilterData( response );
		} );
	};

	/**
	 * Fetches data based on the provided filters and updates the state with the response.
	 *
	 * This function performs the following operations:
	 * 1. Sets the fetching state to true.
	 * 2. Initializes the data state as an empty array.
	 * 3. Builds request arguments by extracting relevant keys from the filter object.
	 * 4. Makes an API call to retrieve filtered user story links using the constructed arguments.
	 * 5. Updates the data state with the API response.
	 * 6. Resets the fetching state regardless of success or failure of the API call.
	 *
	 * The `filter` object is expected to include `screen` and `host` properties, each with a `key` property, which are used to build the request parameters.
	 */
	const fetchData = () => {
		setIsFetching( true );
		setData( [] );

		const args = Object.assign( {}, filter );
		args.screen = args.screen.key;
		args.host = args.host.key;

		apiFetch( {
			path: addQueryArgs( '/user-story/links', { filter: args } ),
		} )
			.then( ( response ) => {
				setData( response );
			} )
			.finally( () => {
				setIsFetching( false );
			} );
	};

	useEffect( () => {
		fetchFilterData().then( fetchData );
		// eslint-disable-next-line
	}, [] );

	const screenSelectValues = [
		{ height: 0, width: 0 },
		...filterData.screens,
	];
	const hostSelectValues = [ '', ...filterData.hosts ];
	return (
		<div>
			<Panel>
				<PanelBody>
					<PanelRow>
						<div
							style={ {
								display: 'flex',
								justifyContent: 'space-between',
							} }
						>
							<div className={ 'components-item' }>
								<TextControl
									type={ 'date' }
									value={ filter.start_date }
									onChange={ ( value ) =>
										updateFilter( 'start_date', value )
									}
									label={ __( 'Start Date', 'user-story' ) }
								/>
							</div>
							<div className={ 'components-item' }>
								<TextControl
									type={ 'date' }
									value={ filter.end_date }
									onChange={ ( value ) =>
										updateFilter( 'end_date', value )
									}
									label={ __( 'End Date', 'user-story' ) }
								/>
							</div>
							<div className={ 'components-item' }>
								<CustomSelectControl
									defaultValue={ __(
										'Select Screen',
										'user-story'
									) }
									label={ __( 'Screen', 'user-story' ) }
									options={ screenSelectValues.map(
										( screen ) => ( {
											key:
												screen.height === 0 &&
												screen.width === 0
													? ''
													: screen.height +
													  'x' +
													  screen.width,
											name:
												screen.height === 0 &&
												screen.width === 0
													? __(
															'All screens',
															'user-story'
													  )
													: screen.height +
													  ' x ' +
													  screen.width,
										} )
									) }
									onChange={ ( value ) =>
										updateFilter(
											'screen',
											value.selectedItem
										)
									}
									value={ filter.screen }
								></CustomSelectControl>
							</div>
							<div className={ 'components-item' }>
								<CustomSelectControl
									label={ __( 'Domain', 'user-story' ) }
									options={ hostSelectValues.map(
										( host ) => ( {
											key: host,
											name:
												host.length === 0
													? __(
															'All domains',
															'user-story'
													  )
													: host,
										} )
									) }
									onChange={ ( value ) =>
										updateFilter(
											'host',
											value.selectedItem
										)
									}
									value={ filter.host }
								></CustomSelectControl>
							</div>
							<div className={ 'components-item' }>
								<div style={ { marginTop: '20px' } }></div>
								<Button
									variant={ 'primary' }
									disabled={ isFetching }
									onClick={ fetchData }
								>
									{ __( 'Search', 'user-story' ) }
								</Button>
							</div>
						</div>
					</PanelRow>
					<PanelRow>
						<table
							className={
								'wp-list-table widefat fixed striped posts'
							}
						>
							<thead>
								<tr>
									<th>{ __( 'Link', 'user-story' ) }</th>
									<th>{ __( 'NAME', 'user-story' ) }</th>
									<th>{ __( 'SCREEN', 'user-story' ) }</th>
									<th>{ __( 'DATE', 'user-story' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ data.map( ( datum, i ) => (
									<tr key={ i }>
										<td>{ datum.link }</td>
										<td>{ datum.name }</td>
										<td>
											{ datum.height }x{ datum.width }
										</td>
										<td>{ datum.date }</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</PanelRow>
					<PanelRow></PanelRow>
				</PanelBody>
			</Panel>
		</div>
	);
};

domReady( () => {
	const root = document.getElementById( 'user-story-settings' );

	if ( typeof root !== 'undefined' && root !== null ) {
		render( <SettingsPage />, root );
	}

	// const root = createRoot(
	// 	document.getElementById( 'user-story-settings' )
	// );
	//
	// root.render( <SettingsPage /> );
} );
