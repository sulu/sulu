#SuluAdminBundle

##Installation for development

1. Install SuluAdminBundle with composer into a symfony project
1. Install all the node modules with `npm install` (only require for grunt)

##Development

###Live Development
There is a grunt task available to make editing and immediate testing of javascript files possible.
Assuming that you have grunt installed you can let run the task with `grunt watch --force` in the root directory.
All files will be copied to the correct location after you have edited them.

###Building a production version
With the command `grunt build` you can build a new production version. All files will be optimized, and saved in a new location.
That is `Resources/public/dist` for javascript and css, and a new template, which uses the optimized files in `Resources/views/Admin/index.html.dist.twig`.
