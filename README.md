# Sharpspring integration plugin for Craft CMS 3.x

A SharpSpring integration plugin.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Add the repository to Composer to load this plugin from git:

        "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/the-refinery/sharpspring-craft-3-plugin"
        }
        ],


3. Then tell Composer to load the plugin:

        composer require therefinery/sharpspring-integration

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for salsifyhelper.

## sharpspring-integration Overview

-Insert text here-

## Configuring sharpspring-integration

1. Add environment variables CRAFTENV_SHARPSPRING_ACCOUNTID and CRAFTENV_SHARPSPRING_SECRETKEY to your system

2. Create sharpringintegration.php in the config folder of your Craft application (see sharpspringintegration.php.example for format)

## Using sharpspring-integration

-Insert text here-

## sharpspring-integration Roadmap

Some things to do, and ideas for potential features:

* Integrate craft entry updates (not updated yet from Craft 2)

Brought to you by [The Refinery](https://the-refinery.io)
