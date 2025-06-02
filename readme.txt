== Frequently Asked Questions ==

= Does this plugin work with Gravity Forms? =

Yes, Multi-Platform Sync integrates with Gravity Forms to send form submission data to Zapier, which can then be sent to Campaign Monitor and Quickbase.

= Can I use this plugin alongside the official Gravity Forms Zapier add-on? =

Yes! Multi-Platform Sync now automatically detects if the official Gravity Forms Zapier add-on is active. When detected:

1. Multi-Platform Sync will let the official add-on handle sending Gravity Forms data to Zapier
2. Multi-Platform Sync will continue to process data from Zapier to Campaign Monitor and Quickbase
3. You'll need to set up a Webhook step in your Zap to send data back to WordPress for Campaign Monitor and Quickbase processing

This prevents duplicate data being sent to Zapier and ensures compatibility between both plugins.

= How do I set up the integration when using the Gravity Forms Zapier add-on? =

1. Install and activate the Gravity Forms Zapier add-on
2. Configure your Zap in Zapier to receive form submissions via the Gravity Forms Zapier add-on
3. Add a Webhook step to your Zap to send data back to WordPress (the webhook URL will be shown in the Multi-Platform Sync settings)
4. Configure the Campaign Monitor and Quickbase settings in Multi-Platform Sync as usual

The plugin will automatically detect the Gravity Forms Zapier add-on and adjust its behavior accordingly.

= Can I use this plugin without Gravity Forms? =

Yes! The plugin can process data coming from any external source through Zapier. You can set up a Zap that:

1. Starts with any trigger source (not necessarily Gravity Forms)
2. Sends data to the Multi-Platform Sync webhook endpoint (shown in the plugin settings)
3. The plugin will then process that data and send it to Campaign Monitor and Quickbase

This is useful when you want to sync data from external systems that are outside the WordPress ecosystem.

= What data format should I send from Zapier to the webhook? =

When sending data from Zapier to the Multi-Platform Sync webhook, include at minimum these fields:

- For Campaign Monitor: Include a field containing a valid email address. Fields with names containing "email" will be automatically detected.
- For Quickbase: Include any key-value pairs that match your Quickbase table fields.

The plugin will automatically process the data regardless of its origin. 