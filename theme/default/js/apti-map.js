(function() {
'use strict';

var INTERVAL = 4;

L.mapbox.accessToken = 'pk.eyJ1IjoibWdheCIsImEiOiJZM2VMVm5BIn0.gLOGe-GhinkiHWeQYv941A';
var map = L.mapbox.map('homepage-map', 'examples.map-i86nkdio', {
  dragging: false,
  touchZoom: false,
  scrollWheelZoom: false,
  doubleClickZoom: false,
  boxZoom: false,
  tap: false,
  keyboard: false,
  zoomControl: false
});
map.setView([52, 14], 4);

var centroids = {};
$.getJSON('/theme/default/js/country_centroids.geojson', function(geojson) {
  $.get('/recently_published.json', function(data) {
    geojson.features.forEach(function(f) {
      var coords = f.geometry.coordinates
      centroids[f.properties.iso_a2] = [coords[1], coords[0]];
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
