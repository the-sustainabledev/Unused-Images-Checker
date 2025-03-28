# README #

This README gives information on the Leap Starter Theme and how to use it.

## What is this repository for? ##

* This repository is for the Leap Starter Theme. A kickstarter for WordPress-based website projects.
* Version 0.1

## How do I get set up? ##

To get started, Clone the repository into a new local WordPress install - within the *themes* folder inside *wp-content*.

### Dependencies ###

There are no code dependencies to install for this theme other than a compiler for creating CSS files. You can use Composer if you wish, but we prefer using the more visual tool PrePros. More on that in the following section.

The theme uses vanilla JavaScript. However, it will require the [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/) plugin to work fully within WordPress.

### PrePros ###

We use the [PrePros](https://prepros.io/) app to manage the compiling of CSS and JS within the theme.

This creates a prepros config and package files. Prepros handles the installation of any Node scripts that are necessary, but for this theme, there are none that are necessary.

### Compiling and minifying CSS ###

The theme uses SCSS to create and manage styles. These are broken down into individual .scss files to make it easier to find code when editing.

PrePros handles the compiling of SCSS into the `style.css` and `style.min.css` file used in production.

This minified file is loading to the frontend via an enqeue at the end of the `functions.php` file at the root of the theme.

### Compiling and minifying JavaScript ###

Prepros also handles the minification of JavaScript. By default, the theme has only one JavaScript file called `main.js`, held within the /js folder. This file is minified into the production ready `main.min.js` via PrePros.

The minified file is loading to the frontend via an enqeue at the end of the `functions.php` file at the root of the theme.

## The .gitignore file ##

This repository comes along with a .gitignore file that is set up for ignoring any folders and files that would be unnecessary for production, such as the PrePros config and package files.

## Contribution guidelines ##

To suggest a change or an addition to the code, please create a Pull Request so the code can be reviewed before it is merged into the main branch.
