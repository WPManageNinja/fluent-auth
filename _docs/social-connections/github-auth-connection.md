---
title: Configure Login with GitHub
slug: github-auth-connection
tagline: Add Login or Register with GitHub in minutes
sidebar: false
prev: true
next: true
editLink: true
pageClass: docs-github
menu_order: 90
---

![Configure GitHub](https://fluentauth.com/wp-content/uploads/2022/12/configure-github.png)

Configure "Login with GitHub" is super easy. First you have to create an application in GitHub portal.

## Create Application in GitHub

Go to your GitHub Settings -> Developer settings -> [OAuth Apps](https://github.com/settings/developers) 

Then Click "New OAuth App" then fill the Form.

![GitHub App Create](https://fluentauth.com/wp-content/uploads/2022/12/github-app.png)

The **Authorization callback URL** will be what you are seeing in your GitHub settings in FluentAuth Settings. Make sure that URL is correct.

Once you are done with the settings Click "Register application".

In the next screen, you will get **Client ID** and you can generate **Client Secret**

Get these 2 values and then provide that in the FluentAuth's GitHub connection Settings.

You can directly add the credential or use the wp-config instruction to save the credentials in wp-config.php file.

Once you set the credential to FluentAuth, Click save button in FluentAuth. 

Now, your users can Signup or Login with GitHub profile.
