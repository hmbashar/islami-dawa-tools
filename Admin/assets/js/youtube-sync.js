/**
 * youtube-sync.js
 *
 * SweetAlert2-powered interactions for the YouTube Sync admin page.
 * Handles confirm dialogs for sync buttons and displays result toasts.
 *
 * @package IslamiDawaTools\Admin
 * @since   1.0.0
 */

/* global idtYtSync, Swal */
( function () {
	'use strict';

	// Guard: SweetAlert2 must be loaded.
	if ( typeof Swal === 'undefined' ) {
		return;
	}

	// Shared SweetAlert2 theme matching the plugin colours.
	const idtTheme = {
		confirmButtonColor: '#c0392b',
		cancelButtonColor:  '#7f8c8d',
		customClass: {
			popup:         'idt-swal-popup',
			confirmButton: 'idt-swal-confirm',
			cancelButton:  'idt-swal-cancel',
		},
	};

	// -----------------------------------------------------------------------
	// Spinner overlay helpers
	// -----------------------------------------------------------------------
	const overlay = document.querySelector( '.idt-spinner-overlay' );

	function showSpinner( label ) {
		if ( ! overlay ) return;
		const lbl = overlay.querySelector( '.idt-spinner-label' );
		if ( lbl ) lbl.textContent = label || idtYtSync.i18n.processing;
		overlay.classList.add( 'is-active' );
	}

	function hideSpinner() {
		if ( overlay ) overlay.classList.remove( 'is-active' );
	}

	// -----------------------------------------------------------------------
	// Show success/error result toasts from redirect query params
	// -----------------------------------------------------------------------
	function handleResultNotice() {
		if ( ! idtYtSync.syncStatus ) return;

		if ( idtYtSync.syncStatus === 'success' ) {
			Swal.fire( Object.assign( {}, idtTheme, {
				icon:              'success',
				title:             idtYtSync.i18n.syncComplete,
				html:
					'<div class="idt-swal-stats">' +
					'<div class="idt-swal-stat">' +
						'<strong>' + idtYtSync.syncFound + '</strong>' +
						'<span>' + idtYtSync.i18n.found + '</span>' +
					'</div>' +
					'<div class="idt-swal-stat imported">' +
						'<strong>' + idtYtSync.syncImported + '</strong>' +
						'<span>' + idtYtSync.i18n.imported + '</span>' +
					'</div>' +
					'<div class="idt-swal-stat skipped">' +
						'<strong>' + idtYtSync.syncSkipped + '</strong>' +
						'<span>' + idtYtSync.i18n.skipped + '</span>' +
					'</div>' +
					'<div class="idt-swal-stat failed">' +
						'<strong>' + idtYtSync.syncFailed + '</strong>' +
						'<span>' + idtYtSync.i18n.failed + '</span>' +
					'</div>' +
					'</div>',
				confirmButtonText: idtYtSync.i18n.ok,
				showClass: { popup: 'animate__animated animate__fadeInDown' },
			} ) );
		}

		if ( idtYtSync.syncStatus === 'error' ) {
			Swal.fire( Object.assign( {}, idtTheme, {
				icon:              'error',
				title:             idtYtSync.i18n.syncFailed,
				text:              idtYtSync.syncMessage,
				confirmButtonText: idtYtSync.i18n.ok,
			} ) );
		}
	}

	// -----------------------------------------------------------------------
	// "Sync All Videos" confirmation dialog
	// -----------------------------------------------------------------------
	const syncAllForm = document.getElementById( 'idt-sync-all-form' );

	if ( syncAllForm ) {
		syncAllForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			Swal.fire( Object.assign( {}, idtTheme, {
				icon:              'warning',
				title:             idtYtSync.i18n.syncAllTitle,
				text:              idtYtSync.i18n.syncAllText,
				showCancelButton:  true,
				confirmButtonText: idtYtSync.i18n.syncAllConfirm,
				cancelButtonText:  idtYtSync.i18n.cancel,
			} ) ).then( function ( result ) {
				if ( result.isConfirmed ) {
					showSpinner( idtYtSync.i18n.syncingAll );
					syncAllForm.submit();
				}
			} );
		} );
	}

	// -----------------------------------------------------------------------
	// "Run Latest Sync Now" confirmation dialog
	// -----------------------------------------------------------------------
	const syncLatestForm = document.getElementById( 'idt-sync-latest-form' );

	if ( syncLatestForm ) {
		syncLatestForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			Swal.fire( Object.assign( {}, idtTheme, {
				icon:              'question',
				title:             idtYtSync.i18n.syncLatestTitle,
				text:              idtYtSync.i18n.syncLatestText,
				showCancelButton:  true,
				confirmButtonText: idtYtSync.i18n.syncLatestConfirm,
				cancelButtonText:  idtYtSync.i18n.cancel,
			} ) ).then( function ( result ) {
				if ( result.isConfirmed ) {
					showSpinner( idtYtSync.i18n.syncingLatest );
					syncLatestForm.submit();
				}
			} );
		} );
	}

	// -----------------------------------------------------------------------
	// Boot
	// -----------------------------------------------------------------------
	document.addEventListener( 'DOMContentLoaded', function () {
		handleResultNotice();
		hideSpinner();
	} );

	// Hide spinner on page show (handles browser back/forward cache).
	window.addEventListener( 'pageshow', hideSpinner );

} )();
