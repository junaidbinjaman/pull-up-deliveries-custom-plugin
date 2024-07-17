(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     */

    $(document).ready(function () {
        pd_load_maps_data(jQuery);
        $('#billing_address_1').prop('disabled', true);
    });
})(jQuery);

function pd_load_maps_data($) {
    $.ajax({
        url: ajax_object.url,
        type: 'POST',
        data: {
            action: 'pd_load_maps_data',
            nonce: ajax_object.nonce,
        },
        success: function (response) {
            localStorage.setItem('storLat', response.data[0].lat);
            localStorage.setItem('storLng', response.data[0].lng);
            localStorage.setItem('storeMsg', response.data[0].msg);

            localStorage.setItem(
                'customerMarkerUrl',
                response.data[1].marker_url
            );
            localStorage.setItem(
                'customerMarkerSize',
                response.data[1].marker_size
            );
            localStorage.setItem('customerMarkerMsg', response.data[1].msg);

            pd_handle_location_input_visibility($);

            pd_visual_map(
                Number(response.data[0].lat),
                Number(response.data[0].lng),
                response.data[0].marker_icon,
                response.data[0].marker_icon_size,
                response.data[0].msg
            );
        },
        error: function (xhr, error, status) {
            console.log(error);
        },
    });
}

function pd_handle_location_input_visibility($) {
    $('.pd-manual-location-detector').on('click', function () {
        $('.pd-manual-location-detector-wrapper').show();
        pd_address_auto_complete();
    });

    $('.pd-auto-location-detector').on('click', function () {
        $('.pd-manual-location-detector-wrapper').hide();
    });
}

// Note: This example requires that you consent to location sharing when
// prompted by your browser. If you see the error "The Geolocation service
// failed.", it means you probably did not give permission for the browser to
// locate you.
let map, infoWindow;

function initMap() {
    infoWindow = new google.maps.InfoWindow();

    const locationButton = document.querySelector('.pd-auto-location-detector');

    locationButton.addEventListener('click', () => {
        document.querySelector(
            '.pd-auto-location-detector-loader'
        ).style.display = 'block';

        // Try HTML5 geolocation.
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    let customerMarkerUrl =
                        localStorage.getItem('customerMarkerUrl');
                    let customerMarkerSize =
                        localStorage.getItem('customerMarkerSize');
                    let customerMarkerMsg =
                        localStorage.getItem('customerMarkerMsg');

                    pd_visual_map(
                        position.coords.latitude,
                        position.coords.longitude,
                        customerMarkerUrl,
                        customerMarkerSize,
                        customerMarkerMsg
                    );
                    pd_calculate_distance_and_time(
                        position.coords.latitude,
                        position.coords.longitude
                    );
                    document.querySelector(
                        '.pd-auto-location-detector-loader'
                    ).style.display = 'none';
                },
                () => {
                    handleLocationError(true, infoWindow, map.getCenter());
                }
            );
        } else {
            // Browser doesn't support Geolocation
            handleLocationError(false, infoWindow, map.getCenter());
        }
    });
}

function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(
        browserHasGeolocation
            ? 'Error: The Geolocation service failed.'
            : "Error: Your browser doesn't support geolocation."
    );
    infoWindow.open(map);
}

window.initMap = initMap;

// Search auto complete
function pd_address_auto_complete() {
    let storeLat = Number(localStorage.getItem('storLat'));
    let storeLng = Number(localStorage.getItem('storLng'));

    const center = {lat: storeLat, lng: storeLng};
    // Create a bounding box with sides ~10km away from the center point
    const defaultBounds = {
        north: center.lat + 0.1,
        south: center.lat - 0.1,
        east: center.lng + 0.1,
        west: center.lng - 0.1,
    };

    const input = document.getElementById('pac-input');
    const options = {
        bounds: defaultBounds,
        componentRestrictions: {country: 'bd'},
        fields: ['address_components', 'geometry', 'icon', 'name', 'place_id'], // Note: 'place_id' instead of 'placeId'
        strictBounds: false,
    };

    const autocomplete = new google.maps.places.Autocomplete(input, options);

    autocomplete.addListener('place_changed', function () {
        const place = autocomplete.getPlace();

        if (!place.place_id) {
            console.log('Place ID not returned.');
        }

        let pos = {
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng(),
        };

        let customerMarkerUrl = localStorage.getItem('customerMarkerUrl');
        let customerMarkerSize = localStorage.getItem('customerMarkerSize');
        let customerMarkerMsg = localStorage.getItem('customerMarkerMsg');

        pd_visual_map(
            pos.lat,
            pos.lng,
            customerMarkerUrl,
            customerMarkerSize,
            customerMarkerMsg
        );
        pd_calculate_distance_and_time(pos.lat, pos.lng);
    });
}

function pd_visual_map(lat, lng, iconUrl, iconSize, msg) {
    let pos = {
        lat: Number(lat),
        lng: Number(lng),
    };
    iconSize = Number(iconSize);

    const customIcon = {
        url: iconUrl,
        size: new google.maps.Size(50, 50),
        scaledSize: new google.maps.Size(iconSize, iconSize),
        anchor: new google.maps.Point(15, 15),
    };

    const map = new google.maps.Map(document.getElementById('map'), {
        center: pos,
        zoom: 18,
    });

    const marker = new google.maps.Marker({
        position: pos,
        map: map,
        title: msg,
        icon: customIcon,
    });
}

function pd_calculate_distance_and_time(lat, long) {
    let storeLat = localStorage.getItem('storLat');
    let storeLng = localStorage.getItem('storLng');

    var storeLocation = new google.maps.LatLng(storeLat, storeLng);
    var destination = new google.maps.LatLng(lat, long);

    var service = new google.maps.DistanceMatrixService();
    service.getDistanceMatrix(
        {
            origins: [storeLocation],
            destinations: [destination],
            travelMode: 'DRIVING',
            avoidHighways: false,
            avoidTolls: false,
        },
        callback
    );

    function callback(response, status) {
        console.log(response);
        pd_update_shipping_cost(
            jQuery,
            response.rows[0].elements[0].duration.value
        );

        pd_populate_address(response.destinationAddresses[0], lat, long);
    }
}

function pd_populate_address(address, lat, lng) {
    jQuery('#billing_address_1').val(address);
    jQuery('#billing_address_1').prop('disabled', true);
    jQuery('#pd-customer-destination-lat-lng').val('Lat: ' + lat + ' Lng: ' + lng);
}

function pd_update_shipping_cost($, time) {
    let min = time / 60;
    let cost = 0.5 * min;

    $.ajax({
        url: ajax_object.url,
        type: 'POST',
        data: {
            action: 'update_shipping_cost',
            nonce: ajax_object.nonce,
            shipping_cost: cost,
        },
        success: function (response) {
            $('body').trigger('update_checkout');
            $('body').trigger('wc_update_cart');
        },
        error: function (xhs, error, status) {
            console.log(error);
        },
    });
}
