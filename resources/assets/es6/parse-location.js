//parse-location.js

//text = `POINT(lon lat)`
export default function parseLocation(text) {
    let coords = /POINT\((.*)\)/.exec(text);
    let parsedLongitude = coords[1].split(' ')[0];
    let parsedLatitude = coords[1].split(' ')[1];

    return {'latitude': parsedLatitude, 'longitude': parsedLongitude};
}
