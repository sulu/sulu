# Sulu Location Bundle Documentation

The SuluLocationBundle provides services related to maps and geolocation.

## Configuration

````yaml
# Default configuration for extension with alias: "sulu_location"
sulu_location:
    geolocator:                   ~ # One of "nominatim"; "google"
    geolocators:
        nominatim:
            api_key:              ''
            endpoint:             'http://open.mapquestapi.com/nominatim/v1/search.php'
        google:
            api_key:              ''
````

## Services

### Geolocators

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

Both of the above services are free-to-use, but the mapquest endpoint seems faster.

Configuration:

- **api_key**: Create the api-key in the [Developer portal of Mapquest](https://developer.mapquest.com/user/me/apps).
- **endpoint**: The endpoint to use

#### Goolge Maps

[Google](https://developers.google.com/maps/documentation/geocoding) geocoding API.

Configuration:

- **api_key**: Create the api-key in the [Google Cloud Console](http://g.co/dev/maps-no-account).
