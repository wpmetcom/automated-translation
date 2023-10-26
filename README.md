# automated-translation
It uses Google Translator API to translate WP pot files via \"om/potrans\" in batch.

## Script installation
* Create a directory and put Google Translate's credentials.json there. Then open CLI from that directory.

* User composer to install the package as follows:

`composer require wpmet/automated-translation`

## Running the translations

* Use this command to run the script from CLI, specify multiple language codes at the end of the command.

`vendor/bin/fire translate ko ar`

* Fill up the guided form and it will generate all po,mo file in the plugin's language directory.