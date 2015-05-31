/**
 * Main.js
 */
// Object basic
var rahe;
if (!rahe) {
    rahe = {};
} else {
    if (typeof rahe !== "object") {
        throw new Error('rahe already exists and not an object');
    }
}

if (!rahe.sis) {
    rahe.sis = {};
} else {
    if (typeof rahe.sis !== "object") {
        throw new Error('rahe.sis already exists and not an object');
    }
}

rahe.sis = {
    views: {},
    models: {},
    collections: {}
};