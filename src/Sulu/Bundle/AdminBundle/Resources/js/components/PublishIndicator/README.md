The `PublishIndicator` is a simple component, which can be used to show the draft/publish state of an entity.

It doesn't show anything if only `published` is set to `true`, because this is considered the default, to have less
visual clutter in the UI.

```
<PublishIndicator published={true} />
```

The `draft` state is represented by a yellow circle.

```
<PublishIndicator draft={true} />
```

If something was already published and another draft was saved afterwards, then a yellow and green circle is shown.

```
<PublishIndicator draft={true} published={true} />
```
