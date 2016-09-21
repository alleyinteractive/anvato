/**
 * Respond to events emitted by Anvato player instances.
 *
 * Data to use for individual player instances should live under a property of
 * the instance ID: "anvatoPlayerData['p0'][...]".
 *
 * @see http://docs.anvato.com/universal-player-api/api-events/.
 */
if ( typeof anvp.setListener === 'function' ) {
	anvp.setListener( function( e ) {
		if ( undefined === typeof( anvatoPlayerData ) ) {
			return;
		}

		if ( 'PLAYING_START' == e.name ) {
			/**
			 * If this video should start playing N seconds in, seekTo() N.
			 *
			 * N is in milliseconds. For example, to start playing the "p0" instance
			 * 60 seconds into the video, use "anvatoPlayerData.p0.seekTo = 60000".
			 *
			 * @see http://docs.anvato.com/universal-player-api/api-functions/playback-actions/#fn-seekto.
			 */
			if ( anvatoPlayerData[ e.sender ].hasOwnProperty( 'seekTo' ) ) {
				anvp[ e.sender ].seekTo( anvatoPlayerData[ e.sender ]['seekTo'] );
			}
		}
	} );
}
