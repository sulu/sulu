# Sulu Location Bundle Documentation

The SuluLocationBundle provides services related to maps and geolocation.

## Configuration

````yaml
# Default configuration for extension with alias: "sulu_location"
sulu_location:
    types:
        location:
            template:             'SuluLocationBundle:Template:content-types/location.html.twig'
    enabled_providers:

        # Defaults:
        - leaflet
        - google
    default_provider:     ~ # One of "leaflet"; "google"
    geolocator:           ~ # One of "nominatim"; "google"
    providers:
        leaflet:
            title:                'Leaflet (OSM)'
        google:
            title:                'Google Maps'
            api_key:              null
    geolocators:
        nominatim:
            endpoint:             'http://open.mapquestapi.com/nominatim/v1/search.php'
        google:
            api_key:              ''
````

## Services

### Map Providers

Map providers are javascript modules which render a map using a specific third-party service (e.g. google
maps or Leaflet).

The map providers can be found in: `Resources/public/js/map`.

#### Leaflet

[LeafletJS](http://leafletjs.com) is an "Open-Source Library for Mobile-Friendly Interactive Maps".

Leaflet requires no configuration.

#### Google Maps

[Google Maps](https://developers.google.com/maps/) is uses the Google Maps API V3 to render the map.

Configuration:

- **api_key**: Optional, but recommended if making many requests.

### Geoloctors

The Geolocator services in the map bundle provide a common API to geocoding services, in particular
for obtaining address information from user searches.

Geolocators are PHP services and can be found in the `Geolocator/Service` namespace.

#### Nominatim

[Nominatim](http://wiki.openstreetmap.org/wiki/Nominatim):

 > Nominatim (from the Latin, 'by name') is a tool to search OSM data by name and 
 > address and to generate synthetic addresses of OSM points (reverse geocoding)

You can choose between at least two different endpoints:

- OSM: http://nominatim.openstreetmap.org/search: [Example](http://nominatim.openstreetmap.org/search?format=json&q=466+dorchester+road+weymouth)
- MapQuest: http://open.mapquestapi.com/nominatim/v1/search.php: [Example](http://open.mapquestapi.com/nominatim/v1/search.php?format=json&q=466+dorchester+road+weymouth)

Both of the aboce services are free-to-use, but the mapquest endpoint seems faster.

Configuration:

- **endpoint**: The endpoint to use

#### Goolge Maps

[Google](https://developers.google.com/maps/documentation/geocoding) geocoding API.

Configuration:

- **api_key**: Optional, but recommended if making many requests.

## Development

When developing use grunt `build|watch` to test changes to the assets.

````
$ grunt watch
````

### Developing the Location content type

The location content type can be developed in isolation at the following URL
(after a grunt build)

http://sulu.lo/bundles/sululocation/js/examples/index.html
