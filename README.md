RESTful-Connector
=================

Helping you connect to third-party RESTful APIs using HTTP and OAuth 2.0

## Project Status
* 10 Jan 2014

    Development is ongoing in the oauth branch. Please check there for the most up-to-date code.

* httpWorker (http.class.php) is useful when you want to send an HTTP request that is not HEAD or GET.
 * For HEAD, use PHP `get_headers($url, 1);`
 * For GET, use PHP `file_get_contents($url, […]);`

## TODO
- [ ] OAuth2.0 Authorization (Using Facebook as first connection)
