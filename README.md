# Multi-Platform Sync

A WordPress plugin that provides seamless integration between Gravity Forms, Zapier, Campaign Monitor, and Quickbase.

## Description

Multi-Platform Sync is a modular and extensible WordPress plugin that allows you to sync data from Gravity Forms submissions with multiple platforms through Zapier. The plugin handles the data flow from Gravity Forms to Zapier, and then from Zapier to Campaign Monitor and Quickbase.

## Features

- **Gravity Forms Integration**: Automatically sync form submissions with external platforms.
- **Zapier Integration**: Use Zapier as a middleware to connect with various services.
- **Campaign Monitor Integration**: Automatically add form submitters to your Campaign Monitor email lists.
- **Quickbase Integration**: Automatically create records in Quickbase from form submissions.
- **Manual Sync**: Manually trigger syncing of form entries.
- **Detailed Logging**: Keep track of all sync activities with a comprehensive logging system.
- **User-Friendly Interface**: Easy-to-use admin dashboard and settings pages.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Gravity Forms 2.4 or higher

## Installation

1. Upload the `multi-platform-sync` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the 'Multi-Platform Sync' menu in your WordPress admin dashboard to configure the plugin.

## Configuration

### Zapier Setup

1. Create a new Zap in Zapier.
2. Choose "Webhook by Zapier" as the trigger app and select "Catch Hook" as the trigger.
3. Copy the provided webhook URL.
4. In your WordPress admin, go to Multi-Platform Sync > Settings > Zapier and paste the webhook URL.
5. Configure the Zap actions to send data to Campaign Monitor and Quickbase.

### Gravity Forms Setup

1. Go to Multi-Platform Sync > Settings > Gravity Forms.
2. Select which forms you want to sync with external platforms.

### Campaign Monitor Setup

1. Go to Multi-Platform Sync > Settings > Campaign Monitor.
2. Enter your Campaign Monitor API key and List ID.

### Quickbase Setup

1. Go to Multi-Platform Sync > Settings > Quickbase.
2. Enter your Quickbase realm hostname, user token, app ID, and table ID.

## Usage

Once configured, the plugin will automatically sync new Gravity Forms submissions with your connected platforms. You can also manually sync entries from the Multi-Platform Sync dashboard.

## Support

If you encounter any issues or have questions, please create an issue on the GitHub repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

This plugin was developed by Eric Mutema. 