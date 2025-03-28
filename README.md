# Unused Images Checker plugin for WordPress #

This plugin for WordPress checks for images within the WordPress Media Library that are not being used within Post or Page content, featured images or within fields created by the [Advanced Custom Fields](https://www.advancedcustomfields.com/) plugin. Such as Repeater or Gallery fields.

It lists the unused images in a dedicated admin page, for easy viewing and deletion.

> [!WARNING]
> This plugin is in active development, as a first draft. Please proceed with caution when using it.

## Installing the plugin ##

* To get started, download the *unused-images-checker* folder from inside the *public* folder.
* Upload this folder into your WordPress plugins folder on the server.
* If you don't have access to the server, compress (zip) the downloaded folder and upload via the Plugins > Upload area of your WordPress dashboard.
* Activate the plugin.

## Using the plugin ##

Within your WordPress dashboard, navigate to Media, then to Unused Images within the sub navigation.
If there are unused images within the media library, they will appear on this page.
* You can view each image within the library by clicking the *View in library* button.
* You can delete a single image by clicking the *Delete* button for that image.
* You can bulk delete by using the built in Bulk Actions by checking the box next to the images, then choosing *Delete* from the *Bulk Actions* dropdown, then clicking *Apply*.

### Dependencies ###

There are no code dependencies to install for this plugin.

## Contributions ##

To suggest a change or an addition to the code, please create a Pull Request so the code can be reviewed before it is merged into the main branch.
