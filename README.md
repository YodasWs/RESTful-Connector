RESTful-Connector
=================

Helping you connect to third-party RESTful APIs using HTTP and OAuth 2.0

<<<<<<< HEAD
## Project Status
* 10 Jan 2014

    Development is ongoing in the oauth branch. Please check there for the most up-to-date code.
=======
## Setup
* Rename 'example.keys.php' to 'keys.php' and add your apps' secrets and client IDs.

  If you're using this code as part of an open source project, do one of the following:
  * Save keys.php in a different folder and update either `require_once('keys.php')` or your PHP include_path
  * Add `keys.php` to your .gitignore

    I cannot be held responsible if you allow your app's secret to become public

## Supported APIs
* [ ] Facebook (in development)
* [ ] Twitter
* [ ] Google+
* [ ] Windows Live
* [ ] Foursquare
* [ ] Easy-to-set Icon Sets
>>>>>>> Start Setup Instructions

* httpWorker (http.class.php) is useful when you want to send an HTTP request that is not HEAD or GET.
 * For HEAD, use PHP `get_headers($url, 1);`
 * For GET, use PHP `file_get_contents($url, […]);`

## TODO
- [ ] OAuth2.0 Authorization (Using Facebook as first connection)
