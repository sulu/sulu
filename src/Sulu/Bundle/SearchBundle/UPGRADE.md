Upgrade
=======

0.1 > 0.2
---------

Changes to template configuration:

The `<indexField/>` tags have been replaced with tags. You should replace each with a
corresponding tag with an appropriate *role*:

e.g. for a title field:

````
<property name="title" type="text_line" mandatory="true">
<tag name="sulu.search.field" role="title" />
</property>
````

The role should be entered for fields which should be shown in the search results, e.g.

- **title**: For the field which should appear as the title
- **description**: For the field containing the body of the result.
- **media**: For the field containing the image URL (optional)

If you just want a field to be indexed and not assigned a role then you can ommit the `role`
attribute.
