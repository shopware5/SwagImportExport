# SwagImportExport
## Description
Importing and exporting product and customer data, categories as well as product images is a standard task of any shop owner.
Keeping that in mind, Shopware wants to make it as easy as possible for you to quickly edit inventory, prices or even perform a customized import. 
 
Shopware’s advanced feature "Import/Export" is a valuable extension to the default Import/Export module: it allows you to define CSV and XML formats and export your data as such.
Furthermore, you can import existing formats (i.e. from your manufacturer) following a one-time configuration.
 
This advanced feature also comes with a massive performance boost in comparison to the default module and supports large amounts of data.
 
It is also possible to import files via shell command or cron job using this extension. 
 
A well-organized overview allows you to review, download or repeat all past imports and exports. 

### Overview of all features:
- Create own XML / CSV formats
- Convert data during import / export
- Log of any operation
- History of all imports / export and the possibility to download / re-run those imports / exports
- Limit / filter product and order exports
- Automatic imports via cron job
- Shell command-line tool

## Images
![Import](https://github.com/shopwareLabs/SwagImportExport/raw/master/import.jpg "Import")
![Profiles](https://github.com/shopwareLabs/SwagImportExport/raw/master/profiles.jpg "Profiles")

## License
The MIT License (MIT). Please see [License File](https://github.com/shopwareLabs/SwagImportExport/blob/master/LICENSE "License File") for more information.

## Run tests in a different environment

We are using [psh](https://github.com/shopwareLabs/psh) to run unit tests it in its own environment.

Run `./psh.phar`:

```
- cleanup: Removes all files which was created for the tests
- init: Initilizaes the test environment, i.e. creating database, moving config file
- reinstall: Reinstalls the plugin in the test environment
- unit: Runs all scripts together, you don't need to worry about removing fixtures etc
```

Example execution:
`$ ./psh.phar -unit`

Further all tests which are executed with the `phpunit` cli command will be executed in the environment you configured.

## sw-zip-blacklist
Exclude files and/or directories in `.sw-zip-blacklist`, which should not be in the release package of the plugin.
List them separated by a new line.
