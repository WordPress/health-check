jQuery( document ).ready(function( $ ) {
    function HealthCheckSiteStatusClassification() {
        $( 'tr', '#health-check-site-status-test-wrapper' ).each(function() {
            var $span = $( 'span', $( this ) ),
                $noEntries;

            if ( $( this ).hasClass( 'health-check-site-status-test' ) || $span.hasClass( 'spinner' ) ) {
                return true;
            }

            $noEntries = $( '.no-entries', '#health-check-accordion-block-' + $span.attr( 'class' ) );

            if ( $noEntries.length > 0 ) {
                $noEntries.remove();
            }

            $( 'tbody', '#health-check-accordion-block-' + $span.attr( 'class' ) ).append( '<tr>' + $( this ).html() + '</tr>' );
            $( this ).remove();
        } );
    }

    $( document ).on( 'health-check:site-status-classification', function() {
        HealthCheckSiteStatusClassification();
    } );

    HealthCheckSiteStatusClassification();
});
