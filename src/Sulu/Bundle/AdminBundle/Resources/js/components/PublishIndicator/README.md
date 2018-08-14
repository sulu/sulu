The `PublishIndicator` is a simple component, which can be used to show the draft/publish state of an entity.

The `published` state is represented by a green circle.

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
