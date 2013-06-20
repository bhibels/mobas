Mobas
=====

Mobile activities assignment submission plugin for Moodle


###Overview
The Mobas (mobile assignment) activity is comprised of two parts, a moodle assignment submission plugin, and the mobile application, which can be either a web app (current), or a native mobile app to be available from the Apple and Google Play stores in the near future.

###Usage
Mobas adds a few settings to the Moodle 2 assignment activity.

 * Mobas enabled: This allows the mobas app to be used to submit material for an assignment.
 * Mobas type: This is a choice of 4 types of assignment, which determine the template used on the mobile device. Further types may be added in the future.
 * Mobas content: This is for the Demonstration Checklist, a list of tasks can be put in, one on each line; these will populate the template in the mobile application. 
 * Mobas submit code: This is a password to be used for an instructor to "sign" a submission before a student sends it through. It's only used for the Demonstration Checklist.


###Moodle Installation
Mobas has been tested on Moodle 2.4.1

Mobas is a assignment submission plugin, so the mobas folder should be copied to the moodle folder under mod/assign/submission. The administrator should then go to the Notifications page to complete the installation.

Mobas uses Moodle web services, which need to be enabled.
See Moodle's documentation on [Using Web Services](http://docs.moodle.org/24/en/Using_web_services). Web services must be enabled with the REST protocol. 

Users of the app need the moodle/create:token capability. This can be given to all authenticated users.

Mobas creates a specific web service containing the functions it requires. Currently there is one component of this which much be done manually in the moodle database.
After mobas is installed, there will be a row in Moodle's external_services table as follows:

The "shortname" field must be altered to contain 'mobas' so that the mobile application can talk to moodle. This must be done directly in the database for now, see MDL-29807



##Mobile Application
###Web App
The web app can be installed anywhere on a web server; it's just a set of html, javascript, css files and images.

The web app can be installed on an Android or iOS device by visiting the site in the devices browser, and saving the site to the Home Screen.

###Native Apps
We are submitting native versions of the app to the both the Apple and the Google Play stores, using phonegap to wrap up the web app.

See the mobasapp repository for the codebase.

An android build will also be made available that can be installed from any website, if the Android user has enabled unsigned applications in their devices security settings.

###Technical Notes
The moodle plugin adds one table to the moodle database, called {prefix}_assignsubmission_mobas

It used the onlinetext plugin as a boilerplate.

