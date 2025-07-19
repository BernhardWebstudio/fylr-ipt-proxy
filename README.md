# fylr-ipt-proxy

A small web-app with a darwin-core database, storing data from easydb5 and/or fylr to export via IPT to GBIF.

Easydb Webhooks should call the endpoints of this app, which will then query the modified entities from their and store or update them in the app's database.
An IPT instance can then be used to export the database to GBIF.
