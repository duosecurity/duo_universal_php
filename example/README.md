# Duo Universal PHP SDK Demo

A simple PHP web application that serves a logon page integrated with Duo 2FA.

## Setup
Change to the "example" directory
```
cd example
```

Install the demo requirements:
```
composer update
```

Then, create a `Web SDK` application in the Duo Admin Panel. See https://duo.com/docs/protecting-applications for more details.

## Using the App

1. Copy the Client ID, Client Secret, and API Hostname values for your `Web SDK` application into the `duo.conf` file.
1. Start the app.
    ```
    php -S localhost:8080
    ```
1. Navigate to http://localhost:8080.
1. Log in with the user you would like to enroll in Duo or with an already enrolled user (any password will work).
