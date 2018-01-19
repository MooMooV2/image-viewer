# image-viewer
A simple image viewer / gallery web application

## REQUIREMENTS
Back end: Web server, PHP (version >5.6, though older versions might work as well), and PHP GD-library.
Front end: Any up to date web browser with javascript.

## SETUP
Drop "app" and "index.php" in the directory where your images are located, and give write permissions to the PHP user in that directory. Thumbnails will be automatically created when you open the page first time in a browser (which might take a while). And that's it.

Same settings can be used for multiple pages. Just copy index.php to another directory and open it in any text editor and change $app_dir to point to a correct location (to app directory where your settings are).

## FEATURES
 * Easy to use
 * Automatic thumbnail creation
 * Dynamic thumbnail loading as page is scrolled
 * User interface to view images on desktop and mobile

## KNOWN BUGS
 * It is possible to browse beyond the last picture for the first time
 * Sometimes a thumbnail jumps to a next row on mobile browsers

## BRIEF TECHNICAL OVERVIEW
 * index.php             Functions as an access point
 * app/libgallery.php    Contains a thumbnail generator and front end HTML code
 * app/libgalleryxhr.php Contains a backend for dynamic thumbnail loading feature
 * app/script.js         Contains front end code: fetch new images, tile images nicely, and view full size images.
 * app/settings.php      General settings like page title, thumbnail size, and so on.
 * app/style.css         Page stylesheet.

