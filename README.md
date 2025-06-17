Repository template for our packages

# Usage
When creating a new repository for a package or a plugin, select this repository as the template. It will initialize the new repository with all the structure & files contained in the template.

# Get started
- Have a mysql DB ready and a user.
- Have `svn` installed.
- Run `composer install`
- Run `npm install`
- Run `npm run build`
- Run `bash bin/install-wp-tests.sh wordpress_test mysql_user mysql_password localhost latest`
- Run `composer run-tests`
- Run `composer phpcs`
- Run `npm run lint:js`
- Run `npm run format`
- You can install the plugin on your website.

# Generate Plugin Zip
- Run `npm run plugin-zip` to get a production enhanced plugin zip file

# Content
* `bin/install-wp-tests.sh`: installer for WordPress tests suite
* `.editorconfig`: config file for your IDE to follow our coding standards
* `.gitattributes`: list of directories & files excluded from export
* `.gitignore`: list of directories & files excluded from versioning
* `.travis.yml`: Travis-CI configuration file
* `composer.json`: Base composer file to customize for the project
* `LICENSE`: License file using GPLv3
* `phpcs.xml`: Base PHP Code Sniffer configuration file to customize for the project
* `README.md`: The readme displayed on Github, to customize for the project
