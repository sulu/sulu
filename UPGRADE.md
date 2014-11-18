# Upgrade

## dev-develop
* Permissions have to be correct now, because they are applied
  * otherwise add a permission value of 120 for `sulu.security.roles`,
    `sulu.security.groups` and `sulu.security.users` to one user to change
    the settings in the UI
* Snippet content type defaults to all snippet types available instead of the
  default one
  * Explicitly define a snippet type in the parameters if this is not desired

