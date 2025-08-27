# Leaflet Local Assets

Place the Leaflet distribution files in this folder for offline/local usage.

Expected filenames (as referenced by views/live-tracking.php):
- leaflet.css
- leaflet.js

Recommended version: 1.9.x (or the version you prefer). You can download from:
https://unpkg.com/leaflet@1.9.4/dist/

Instructions:
1) Download `leaflet.css` and `leaflet.js`.
2) Put them in this directory: `public/vendor/leaflet/`
3) Hard refresh the app.

Note: If these files are missing or invalid, the page includes a safe runtime fallback to the Leaflet CDN so the map still works.
