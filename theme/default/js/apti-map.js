(function() {
'use strict';

var INTERVAL = 4;

var map = L.map('homepage-map', {
  dragging: false,
  touchZoom: false,
  scrollWheelZoom: false,
  doubleClickZoom: false,
  boxZoom: false,
  tap: false,
  keyboard: false,
  zoomControl: false
});
map.setView([52, 14], 5);
L.tileLayer(
  'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png',
  {
    maxZoom: 11,
    attribution: 'CartoDB base map, data from <a href="http://openstreetmap.org">OpenStreetMap</a>'
  }
).addTo(map);

var centroids = {};
$.getJSON('/theme/default/js/country_centroids.geojson', function(geojson) {
  $.get('/recently_published.json', function(data) {
    geojson.features.forEach(function(f) {
      var coords = f.geometry.coordinates
      centroids[f.properties.code] = [coords[1], coords[0]];
    });
    itemList = [];
    data.forEach(function(item) {
      if(centroids[item.country]) {
        itemList.push(item);
      }
    });
    showNextItem();
    setInterval(showNextItem, 1000 * INTERVAL);
  });
});

function showItem(item) {
  var latlng = centroids[item.country];
  if(! latlng) { return; }
  var html = '<p>' +
    '<a href="' + item.link + '">' + item.title + '</a><br>' +
    item.country_name + ', ' + item.date +
    '</p>';
  var popup = L.popup({closeButton: false, closeOnClick: false})
    .setLatLng(latlng)
    .setContent(html);
  map.openPopup(popup);
}

var nextItem = 0;
var itemList = [];
function showNextItem() {
  if(! itemList.length) { return; }
  showItem(itemList[nextItem]);
  nextItem = (nextItem + 1) % itemList.length;
}


})();
